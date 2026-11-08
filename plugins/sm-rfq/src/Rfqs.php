<?php
/**
 * Requests for quotation: storage and queries.
 *
 * Status: pending (moderation) → open → awarded (an offer accepted) | closed
 * (by the buyer) | expired (deadline passed) | rejected (moderation).
 *
 * @package SM\Rfq
 */

namespace SM\Rfq;

use SM\Core\Audit\AuditLog;
use SM\Core\Content\Media;
use SM\Core\Content\Translations;

defined( 'ABSPATH' ) || exit;

final class Rfqs {

	const STATUSES = array( 'pending', 'open', 'awarded', 'closed', 'expired', 'rejected' );
	const UNITS    = array( 'pieces', 'kg', 't', 'm', 'm2', 'lot' );

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sm_rfqs';
	}

	public static function get( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) ); // phpcs:ignore WordPress.DB
		return $row ? self::hydrate( $row ) : null;
	}

	private static function hydrate( $r ) {
		foreach ( array( 'id', 'company_id', 'user_id', 'offers_count', 'awarded_offer', 'views' ) as $k ) {
			$r->$k = (int) $r->$k;
		}
		foreach ( array( 'quantity', 'weight_kg' ) as $k ) {
			$r->$k = (float) $r->$k;
		}
		$r->lines = json_decode( (string) $r->inventory, true ) ?: array();
		return $r;
	}

	/** Create or update from a checked Spec result. */
	public static function save( $id, $company_id, $user_id, array $spec, $status, $files = null ) {
		$is_new = ! $id;
		global $wpdb;
		$now = current_time( 'mysql', true );
		$d   = $spec['data'];
		$row = array_intersect_key( $d, array_flip( array( 'market', 'category', 'designation', 'dimensions', 'quantity', 'unit', 'weight_kg', 'weight_source', 'engine_version', 'wilaya', 'place', 'needed_by', 'deadline' ) ) );
		$row['inventory']  = wp_json_encode( $d['lines'] ?? array() );
		$row['status']     = $status;
		$row['updated_at'] = $now;
		if ( 'open' === $status ) {
			$row['published_at'] = $now;
		}
		if ( $id ) {
			$wpdb->update( self::table(), $row, array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		} else {
			$row += array( 'company_id' => (int) $company_id, 'user_id' => (int) $user_id, 'created_at' => $now );
			$wpdb->insert( self::table(), $row ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$id = (int) $wpdb->insert_id;
		}
		Translations::set( 'rfq', $id, array( 'title' => $spec['title'], 'description' => self::by_lang( $spec['description'] ), 'notes' => self::by_lang( $spec['notes'] ) ) );
		if ( null !== $files ) {
			Media::set( 'rfq', $id, 'file', $files );
		}
		AuditLog::add( 'rfq.saved', 'rfq', $id, array( 'status' => $status, 'new' => $is_new ), $user_id, $company_id );
		return (int) $id;
	}

	/** A text is stored under the language it was written in (Arabic letters or not). */
	public static function by_lang( $text ) {
		if ( '' === $text ) {
			return array( 'fr' => '', 'ar' => '' );
		}
		$ar = preg_match_all( '/\p{Arabic}/u', $text );
		$la = preg_match_all( '/\p{Latin}/u', $text );
		return $ar > $la ? array( 'ar' => $text, 'fr' => '' ) : array( 'fr' => $text, 'ar' => '' );
	}

	public static function set_status( $id, $status, array $extra = array() ) {
		global $wpdb;
		if ( ! in_array( $status, self::STATUSES, true ) ) {
			return false;
		}
		$now = current_time( 'mysql', true );
		$row = array_merge( array( 'status' => $status, 'updated_at' => $now ), $extra );
		if ( 'open' === $status && empty( $extra['published_at'] ) ) {
			$row['published_at'] = $now;
		}
		if ( in_array( $status, array( 'awarded', 'closed', 'expired' ), true ) ) {
			$row['closed_at'] = $now;
		}
		$ok = (bool) $wpdb->update( self::table(), $row, array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		AuditLog::add( 'rfq.status', 'rfq', $id, array( 'status' => $status ) );
		sm_event( 'rfq.status', array( 'rfq_id' => (int) $id, 'status' => $status ) );
		return $ok;
	}

	public static function delete( $id ) {
		global $wpdb;
		foreach ( Offers::for_rfq( $id ) as $o ) {
			Offers::delete( $o->id );
		}
		$wpdb->delete( self::table(), array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		Translations::delete( 'rfq', $id );
		Media::set( 'rfq', $id, 'file', array() );
	}

	public static function refresh_count( $id ) {
		global $wpdb;
		$n = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_rfq_offers WHERE rfq_id = %d AND status <> 'withdrawn'", $id ) ); // phpcs:ignore WordPress.DB
		$wpdb->update( self::table(), array( 'offers_count' => $n ), array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $n;
	}

	public static function add_view( $id ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . self::table() . ' SET views = views + 1 WHERE id = %d', $id ) ); // phpcs:ignore WordPress.DB
	}

	/** Open for offers: status open and deadline not passed. */
	public static function is_open( $r ) {
		return $r && 'open' === $r->status && ( ! $r->deadline || $r->deadline >= current_time( 'Y-m-d', true ) );
	}

	/**
	 * @param array $a status (string|array|'any'), company, market, category, wilaya, q, sort (recent|deadline|updated), per_page, page.
	 * @return array{items: object[], total: int, pages: int}
	 */
	public static function query( array $a ) {
		global $wpdb;
		$a     = wp_parse_args( $a, array( 'status' => 'open', 'per_page' => 12, 'page' => 1, 'sort' => 'recent' ) );
		$where = array( '1=1' );
		$args  = array();
		if ( 'any' !== $a['status'] ) {
			$st      = (array) $a['status'];
			$where[] = 'status IN (' . implode( ',', array_fill( 0, count( $st ), '%s' ) ) . ')';
			$args    = array_merge( $args, $st );
		}
		foreach ( array( 'company' => 'company_id = %d', 'market' => 'market = %s', 'category' => 'category = %s', 'wilaya' => 'wilaya = %s' ) as $k => $sql ) {
			if ( ! empty( $a[ $k ] ) ) {
				$where[] = $sql;
				$args[]  = $a[ $k ];
			}
		}
		if ( ! empty( $a['q'] ) ) {
			$like    = '%' . $wpdb->esc_like( preg_replace( '/\s+/', '', (string) $a['q'] ) ) . '%';
			$tlike   = '%' . $wpdb->esc_like( (string) $a['q'] ) . '%';
			$where[] = "(REPLACE(designation, ' ', '') LIKE %s OR REPLACE(inventory, ' ', '') LIKE %s OR id IN (SELECT entity_id FROM {$wpdb->prefix}sm_translations WHERE entity_type = 'rfq' AND value LIKE %s))";
			array_push( $args, $like, $like, $tlike );
		}
		$order = array(
			'recent'   => 'COALESCE(published_at, created_at) DESC, id DESC',
			'deadline' => 'deadline IS NULL, deadline ASC, id DESC',
			'updated'  => 'updated_at DESC, id DESC',
		)[ $a['sort'] ] ?? 'COALESCE(published_at, created_at) DESC, id DESC';
		$per   = max( 1, min( 60, (int) $a['per_page'] ) );
		$page  = max( 1, (int) $a['page'] );
		$w     = implode( ' AND ', $where );
		$t     = self::table();
		$total = (int) ( $args ? $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE {$w}", $args ) ) : $wpdb->get_var( "SELECT COUNT(*) FROM {$t} WHERE {$w}" ) ); // phpcs:ignore WordPress.DB
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE {$w} ORDER BY {$order} LIMIT %d OFFSET %d", array_merge( $args, array( $per, ( $page - 1 ) * $per ) ) ) ); // phpcs:ignore WordPress.DB
		return array(
			'items' => array_map( array( __CLASS__, 'hydrate' ), $rows ),
			'total' => $total,
			'pages' => (int) ceil( $total / $per ),
		);
	}

	public static function count_by_status( $company_id ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT status, COUNT(*) AS n FROM ' . self::table() . ' WHERE company_id = %d GROUP BY status', $company_id ), OBJECT_K ); // phpcs:ignore WordPress.DB
		return array_map( static fn( $r ) => (int) $r->n, $rows );
	}
}

<?php
/**
 * Service listings: storage and queries.
 * Status: published | pending (moderation) | paused (by the provider) | rejected.
 *
 * @package SM\Services
 */

namespace SM\Services;

use SM\Core\Audit\AuditLog;
use SM\Core\Content\Media;
use SM\Core\Content\Translations;

defined( 'ABSPATH' ) || exit;

final class Services {

	const STATUSES = array( 'published', 'pending', 'paused', 'rejected' );

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sm_services';
	}

	private static function hydrate( $r ) {
		foreach ( array( 'id', 'company_id', 'user_id', 'lead_days', 'views' ) as $k ) {
			$r->$k = (int) $r->$k;
		}
		$r->price_from = (float) $r->price_from;
		$r->categories = json_decode( (string) $r->categories, true ) ?: array();
		$r->wilayas    = json_decode( (string) $r->wilayas, true ) ?: array();
		return $r;
	}

	public static function get( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) ); // phpcs:ignore WordPress.DB
		return $row ? self::hydrate( $row ) : null;
	}

	public static function save( $id, $company_id, $user_id, array $spec, $status, $photos = null ) {
		$is_new = ! $id;
		global $wpdb;
		$now = current_time( 'mysql', true );
		$d   = $spec['data'];
		$row = array(
			'kind'       => $d['kind'],
			'categories' => wp_json_encode( $d['categories'] ),
			'wilayas'    => wp_json_encode( $d['wilayas'] ),
			'lead_days'  => $d['lead_days'],
			'price_mode' => $d['price_mode'],
			'price_from' => $d['price_from'],
			'price_unit' => $d['price_unit'],
			'status'     => $status,
			'updated_at' => $now,
		);
		if ( 'published' === $status ) {
			$row['published_at']  = $now;
			$row['reject_reason'] = '';
		}
		if ( $id ) {
			$wpdb->update( self::table(), $row, array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		} else {
			$wpdb->insert( self::table(), $row + array( 'company_id' => (int) $company_id, 'user_id' => (int) $user_id, 'created_at' => $now ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$id = (int) $wpdb->insert_id;
		}
		Translations::set( 'service', $id, $spec['texts'] );
		if ( null !== $photos ) {
			Media::set( 'service', $id, 'photo', $photos );
		}
		AuditLog::add( 'service.saved', 'service', $id, array( 'status' => $status, 'new' => $is_new ), $user_id, $company_id );
		return (int) $id;
	}

	public static function set_status( $id, $status, $reason = '' ) {
		global $wpdb;
		if ( ! in_array( $status, self::STATUSES, true ) ) {
			return false;
		}
		$row = array( 'status' => $status, 'updated_at' => current_time( 'mysql', true ) );
		if ( 'published' === $status ) {
			$row['published_at'] = current_time( 'mysql', true );
		}
		if ( 'rejected' === $status ) {
			$row['reject_reason'] = $reason;
		}
		$wpdb->update( self::table(), $row, array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		AuditLog::add( 'service.status', 'service', $id, array( 'status' => $status ) );
		return true;
	}

	public static function delete( $id ) {
		global $wpdb;
		$wpdb->delete( self::table(), array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		Translations::delete( 'service', $id );
		Media::set( 'service', $id, 'photo', array() );
	}

	public static function add_view( $id ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . self::table() . ' SET views = views + 1 WHERE id = %d', $id ) ); // phpcs:ignore WordPress.DB
	}

	public static function count_for_company( $company_id ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE company_id = %d', $company_id ) ); // phpcs:ignore WordPress.DB
	}

	/**
	 * @param array $a kind, status, company, category, wilaya (served), q, per_page, page.
	 * @return array{items: object[], total: int, pages: int}
	 */
	public static function query( array $a ) {
		global $wpdb;
		$a     = wp_parse_args( $a, array( 'status' => 'published', 'per_page' => 24, 'page' => 1 ) );
		$where = array( '1=1' );
		$args  = array();
		if ( 'any' !== $a['status'] ) {
			$st      = (array) $a['status'];
			$where[] = 'status IN (' . implode( ',', array_fill( 0, count( $st ), '%s' ) ) . ')';
			$args    = array_merge( $args, $st );
		}
		if ( ! empty( $a['kind'] ) ) {
			$where[] = 'kind = %s';
			$args[]  = $a['kind'];
		}
		if ( ! empty( $a['company'] ) ) {
			$where[] = 'company_id = %d';
			$args[]  = (int) $a['company'];
		}
		if ( ! empty( $a['category'] ) ) {
			$where[] = 'categories LIKE %s';
			$args[]  = '%"' . $wpdb->esc_like( $a['category'] ) . '"%';
		}
		if ( ! empty( $a['wilaya'] ) ) {
			// Served there: the wilaya is listed, or the service covers all Algeria (empty list).
			$where[] = "(wilayas = '[]' OR wilayas LIKE %s OR company_id IN (SELECT id FROM {$wpdb->prefix}sm_companies WHERE wilaya = %s))";
			$args[]  = '%"' . $wpdb->esc_like( $a['wilaya'] ) . '"%';
			$args[]  = $a['wilaya'];
		}
		if ( ! empty( $a['q'] ) ) {
			$where[] = "id IN (SELECT entity_id FROM {$wpdb->prefix}sm_translations WHERE entity_type = 'service' AND value LIKE %s)";
			$args[]  = '%' . $wpdb->esc_like( (string) $a['q'] ) . '%';
		}
		$per   = max( 1, min( 60, (int) $a['per_page'] ) );
		$page  = max( 1, (int) $a['page'] );
		$w     = implode( ' AND ', $where );
		$t     = self::table();
		$order = 'any' === $a['status'] ? 'updated_at DESC, id DESC' : 'COALESCE(published_at, created_at) DESC, id DESC';
		$total = (int) ( $args ? $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE {$w}", $args ) ) : $wpdb->get_var( "SELECT COUNT(*) FROM {$t} WHERE {$w}" ) ); // phpcs:ignore WordPress.DB
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE {$w} ORDER BY {$order} LIMIT %d OFFSET %d", array_merge( $args, array( $per, ( $page - 1 ) * $per ) ) ) ); // phpcs:ignore WordPress.DB
		return array( 'items' => array_map( array( __CLASS__, 'hydrate' ), $rows ), 'total' => $total, 'pages' => (int) ceil( $total / $per ) );
	}
}

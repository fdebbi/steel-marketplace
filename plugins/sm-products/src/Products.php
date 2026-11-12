<?php
/**
 * Product records: storage, status changes, queries.
 *
 * Status: draft -> pending (waiting for moderation) -> published | rejected;
 * published -> sold | expired; any -> draft when the seller withdraws it.
 * Every product has a row in sm-core's listing index; its ID is the public
 * listing number (/annonce/{id}/).
 *
 * @package SM\Products
 */

namespace SM\Products;

use SM\Core\Audit\AuditLog;
use SM\Core\Content\Media;
use SM\Core\Content\Translations;
use SM\Core\Listings\Index;

defined( 'ABSPATH' ) || exit;

final class Products {

	const MODULE   = 'products'; // Default market; each product stores its own market.
	const STATUSES = array( 'draft', 'pending', 'published', 'rejected', 'sold', 'expired', 'paused' );

	private static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sm_products';
	}

	public static function get( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) ); // phpcs:ignore WordPress.DB
		return $row ? self::hydrate( $row ) : null;
	}

	/** Product behind a public listing number. */
	public static function by_listing( $listing_id ) {
		$idx = Index::get( $listing_id );
		return $idx && Markets::exists( $idx->module ) ? self::get( $idx->entity_id ) : null;
	}

	private static function hydrate( $r ) {
		foreach ( array( 'id', 'listing_id', 'company_id', 'user_id', 'section_id', 'length_mm', 'width_mm', 'views' ) as $k ) {
			$r->$k = (int) $r->$k;
		}
		foreach ( array( 'pieces', 'quantity', 'unit_weight', 'total_weight', 'price', 'total_price', 'price_per_kg', 'lot_price' ) as $k ) {
			$r->$k = (float) $r->$k;
		}
		$r->dims   = json_decode( (string) $r->dims, true ) ?: array();
		$r->specs  = json_decode( (string) $r->specs, true ) ?: array();
		$r->attrs  = json_decode( (string) ( $r->attrs ?? '' ), true ) ?: array();
		$r->lines  = json_decode( (string) ( $r->inventory ?? '' ), true ) ?: array();
		$r->market  = $r->market ? $r->market : self::MODULE;
		$r->variant = (string) ( $r->variant ?? '' );
		return $r;
	}

	/**
	 * Create or update a product from a checked Spec result.
	 *
	 * @param array $spec   Spec::build() result without errors.
	 * @param array $photos Attachment IDs in order (null = unchanged).
	 * @param array $sheets Attachment IDs of technical sheets (null = unchanged).
	 * @return int Product ID.
	 */
	public static function save( $id, $company_id, $user_id, array $spec, $status, $photos = null, $sheets = null ) {
		PriceTrend::flush(); // The home price curve follows every change.
		$is_new = ! $id;
		global $wpdb;
		$now  = current_time( 'mysql', true );
		$was  = $id ? self::get( $id ) : null;
		$cols = array( 'market', 'item_condition', 'variant', 'category', 'family', 'section_id', 'designation', 'grade', 'qty_mode', 'length_mm', 'width_mm', 'pieces', 'quantity', 'unit', 'unit_weight', 'total_weight', 'weight_source', 'engine_version', 'ref_version', 'price', 'price_unit', 'total_price', 'price_per_kg', 'wilaya', 'sale_mode', 'lot_price', 'drawing' );
		$row  = array();
		foreach ( $cols as $c ) {
			if ( array_key_exists( $c, $spec['data'] ) ) {
				$row[ $c ] = $spec['data'][ $c ];
			}
		}
		$row['dims']       = wp_json_encode( $spec['data']['dims'] ?? array() );
		$row['specs']      = wp_json_encode( $spec['data']['specs'] ?? array() );
		$row['attrs']      = wp_json_encode( $spec['data']['attrs'] ?? array() );
		$row['inventory']  = wp_json_encode( $spec['data']['lines'] ?? array() ); // "lines" is a reserved word in MySQL.
		$row['status']     = $status;
		$row['updated_at'] = $now;
		if ( 'pending' === $status ) {
			$row['submitted_at']  = $now;
			$row['reject_reason'] = '';
		}
		if ( 'published' === $status && ! ( $was && 'published' === $was->status ) ) {
			$row += self::publish_dates(); // A listing already online keeps its dates when it is edited.
		}
		if ( $id ) {
			$wpdb->update( self::table(), $row, array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		} else {
			$row += array( 'company_id' => (int) $company_id, 'user_id' => (int) $user_id, 'created_at' => $now );
			$wpdb->insert( self::table(), $row ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$id = (int) $wpdb->insert_id;
		}
		Translations::set( 'product', $id, array( 'title' => $spec['title'], 'description' => self::by_lang( $spec['description'] ) ) );
		if ( null !== $photos ) {
			Media::set( 'product', $id, 'photo', $photos );
		}
		if ( null !== $sheets ) {
			Media::set( 'product', $id, 'sheet', $sheets );
		}
		self::index( $id );
		AuditLog::add( 'product.saved', 'product', $id, array( 'status' => $status, 'new' => $is_new ), $user_id, $company_id );
		return (int) $id;
	}

	/** The description is stored under the language it was written in (detected: Arabic letters or not). */
	public static function split_lang( $text ) {
		return self::by_lang( $text );
	}

	private static function by_lang( $text ) {
		if ( '' === $text ) {
			return array( 'fr' => '', 'ar' => '' );
		}
		$ar = preg_match_all( '/\p{Arabic}/u', $text );
		$la = preg_match_all( '/\p{Latin}/u', $text );
		return $ar > $la ? array( 'ar' => $text, 'fr' => '' ) : array( 'fr' => $text, 'ar' => '' );
	}

	private static function publish_dates() {
		$days = (int) Settings::get( 'expiry_days' );
		return array(
			'published_at' => current_time( 'mysql', true ),
			'expires_at'   => $days ? gmdate( 'Y-m-d H:i:s', time() + $days * DAY_IN_SECONDS ) : null,
		);
	}

	/** Change the status (moderation, sold, withdrawn, expired). */
	public static function set_status( $id, $status, $reason = '' ) {
		global $wpdb;
		PriceTrend::flush(); // The home price curve follows every change.
		$p = self::get( $id );
		if ( ! $p || ! in_array( $status, self::STATUSES, true ) ) {
			return false;
		}
		$row = array( 'status' => $status, 'updated_at' => current_time( 'mysql', true ) );
		if ( 'published' === $status ) {
			$row += self::publish_dates();
		}
		if ( 'rejected' === $status ) {
			$row['reject_reason'] = $reason;
		}
		$wpdb->update( self::table(), $row, array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		self::index( $id );
		AuditLog::add( 'product.' . $status, 'product', $id, $reason ? array( 'reason' => $reason ) : array(), null, $p->company_id );
		return true;
	}

	/* ---- Changes waiting for review (0.5.0) ------------------------------ */

	/** Fields whose change is checked by the platform before it is shown. */
	const REVIEWED = array( 'category', 'family', 'section_id', 'designation', 'grade', 'dims', 'specs', 'attrs', 'item_condition', 'variant', 'wilaya', 'unit', 'length_mm', 'width_mm' );

	/**
	 * Does this edit of a published listing need a review? Price, sale mode,
	 * quantities and weights apply at once; texts, photos, files and technical
	 * data wait for the platform.
	 */
	public static function needs_review( $p, array $spec, array $photos, array $sheets ) {
		$d = $spec['data'];
		foreach ( self::REVIEWED as $k ) {
			if ( ! array_key_exists( $k, $d ) ) {
				continue;
			}
			$old = $p->$k ?? '';
			$new = $d[ $k ];
			if ( is_array( $old ) || is_array( $new ) ) {
				if ( wp_json_encode( (array) $old ) !== wp_json_encode( (array) $new ) ) {
					return true;
				}
			} elseif ( (string) $old !== (string) $new && ! ( is_numeric( $old ) && is_numeric( $new ) && (float) $old === (float) $new ) ) {
				return true;
			}
		}
		$strip = static fn( $lines ) => array_map( static fn( $l ) => array_diff_key( (array) $l, array_flip( array( 'qty', 'total' ) ) ), (array) $lines );
		if ( wp_json_encode( $strip( $p->lines ) ) !== wp_json_encode( $strip( $d['lines'] ?? array() ) ) ) {
			return true;
		}
		$t = Translations::get( 'product', $p->id );
		$old_title = array( 'fr' => (string) ( $t['title']['fr'] ?? '' ), 'ar' => (string) ( $t['title']['ar'] ?? '' ) );
		if ( $old_title !== array( 'fr' => (string) ( $spec['title']['fr'] ?? '' ), 'ar' => (string) ( $spec['title']['ar'] ?? '' ) ) ) {
			return true;
		}
		if ( trim( (string) ( $t['description']['fr'] ?? '' ) . (string) ( $t['description']['ar'] ?? '' ) ) !== trim( (string) $spec['description'] ) ) {
			return true;
		}
		return array_map( 'intval', Media::get( 'product', $p->id, 'photo' ) ) !== array_map( 'intval', $photos )
			|| array_map( 'intval', Media::get( 'product', $p->id, 'sheet' ) ) !== array_map( 'intval', $sheets );
	}

	/** Keep a change for review: the published version stays online meanwhile. */
	public static function save_revision( $id, $user_id, array $spec, array $photos, array $sheets ) {
		global $wpdb;
		$rev = array(
			'data'        => $spec['data'],
			'title'       => $spec['title'],
			'description' => $spec['description'],
			'photos'      => array_values( array_map( 'intval', $photos ) ),
			'sheets'      => array_values( array_map( 'intval', $sheets ) ),
			'user_id'     => (int) $user_id,
		);
		$now = current_time( 'mysql', true );
		$wpdb->update( self::table(), array( 'revision' => wp_json_encode( $rev ), 'revision_at' => $now, 'updated_at' => $now ), array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$p = self::get( $id );
		AuditLog::add( 'product.revision_submitted', 'product', $id, array(), $user_id, $p ? $p->company_id : 0 );
	}

	/** The change waiting for review, or null. */
	public static function revision( $p ) {
		if ( ! $p || empty( $p->revision ) ) {
			return null;
		}
		$r = json_decode( (string) $p->revision, true );
		return is_array( $r ) && isset( $r['data'] ) ? $r : null;
	}

	/** Apply the change waiting for review (approved by the platform). */
	public static function approve_revision( $id ) {
		$p   = self::get( $id );
		$rev = self::revision( $p );
		if ( ! $rev ) {
			return false;
		}
		self::save( $id, $p->company_id, (int) $rev['user_id'], array( 'data' => $rev['data'], 'title' => $rev['title'], 'description' => $rev['description'] ), 'published', $rev['photos'], $rev['sheets'] );
		self::clear_revision( $id );
		AuditLog::add( 'product.revision_approved', 'product', $id, array(), null, $p->company_id );
		return true;
	}

	public static function reject_revision( $id, $reason ) {
		$p = self::get( $id );
		if ( ! self::revision( $p ) ) {
			return false;
		}
		self::clear_revision( $id );
		AuditLog::add( 'product.revision_rejected', 'product', $id, array( 'reason' => $reason ), null, $p->company_id );
		return true;
	}

	public static function clear_revision( $id ) {
		global $wpdb;
		$wpdb->update( self::table(), array( 'revision' => null, 'revision_at' => null ), array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/** Number of changes waiting for review. */
	public static function count_revisions() {
		global $wpdb;
		return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE revision_at IS NOT NULL' ); // phpcs:ignore WordPress.DB
	}

	public static function delete( $id ) {
		global $wpdb;
		$p = self::get( $id );
		if ( ! $p ) {
			return false;
		}
		$wpdb->delete( self::table(), array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		Index::remove( $p->market, $id );
		Translations::delete( 'product', $id );
		Media::set( 'product', $id, 'photo', array() );
		Media::set( 'product', $id, 'sheet', array() );
		AuditLog::add( 'product.deleted', 'product', $id, array(), null, $p->company_id );
		return true;
	}

	/** Keep the cross-market index in step. */
	public static function index( $id ) {
		global $wpdb;
		$p = self::get( $id );
		if ( ! $p ) {
			return 0;
		}
		$listing_id = Index::sync(
			$p->market,
			$p->id,
			array(
				'company_id'     => $p->company_id,
				'item_condition' => $p->item_condition,
				'category'     => $p->category,
				'designation'  => mb_substr( $p->designation, 0, 60 ),
				'wilaya'       => $p->wilaya,
				'price_per_kg' => $p->price_per_kg,
				'price'        => $p->price,
				'price_unit'   => $p->price_unit,
				'total_price'  => $p->total_price,
				'total_weight' => $p->total_weight,
				'status'       => $p->status,
				'published_at' => $p->published_at,
			)
		);
		if ( $listing_id !== $p->listing_id ) {
			$wpdb->update( self::table(), array( 'listing_id' => $listing_id ), array( 'id' => $p->id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}
		return $listing_id;
	}

	/**
	 * Query products.
	 *
	 * @param array $a status, market (string|string[]), condition, length_min (m), qty_min, company, category, family, wilaya, q, min_price, max_price, sort (recent|price_asc|price_desc|weight_desc), per_page, page.
	 * @return array{items: object[], total: int, pages: int}
	 */
	public static function query( array $a ) {
		global $wpdb;
		$a     = wp_parse_args( $a, array( 'status' => 'published', 'per_page' => 12, 'page' => 1, 'sort' => 'recent' ) );
		$where = array( '1=1' );
		$args  = array();
		if ( 'any' !== $a['status'] ) {
			$st      = (array) $a['status'];
			$where[] = 'status IN (' . implode( ',', array_fill( 0, count( $st ), '%s' ) ) . ')';
			$args    = array_merge( $args, $st );
		}
		if ( ! empty( $a['revision'] ) ) {
			$where[] = 'revision_at IS NOT NULL';
		}
		if ( ! empty( $a['market'] ) ) {
			$m       = array_values( (array) $a['market'] );
			$where[] = 'market IN (' . implode( ',', array_fill( 0, count( $m ), '%s' ) ) . ')';
			$args    = array_merge( $args, $m );
		}
		if ( ! empty( $a['length_min'] ) ) {
			$where[] = 'length_mm >= %d';
			$args[]  = (int) round( (float) $a['length_min'] * 1000 );
		}
		if ( ! empty( $a['qty_min'] ) ) {
			$where[] = 'pieces >= %f';
			$args[]  = (float) $a['qty_min'];
		}
		foreach ( array( 'mode' => 'variant = %s', 'company' => 'company_id = %d', 'category' => 'category = %s', 'family' => 'family = %s', 'wilaya' => 'wilaya = %s', 'condition' => 'item_condition = %s' ) as $k => $sql ) {
			if ( ! empty( $a[ $k ] ) ) {
				$where[] = $sql;
				$args[]  = $a[ $k ];
			}
		}
		if ( ! empty( $a['q'] ) ) {
			$like    = '%' . $wpdb->esc_like( preg_replace( '/\s+/', '', (string) $a['q'] ) ) . '%';
			$tlike   = '%' . $wpdb->esc_like( (string) $a['q'] ) . '%';
			$where[] = "(REPLACE(designation, ' ', '') LIKE %s OR REPLACE(inventory, ' ', '') LIKE %s OR id IN (SELECT entity_id FROM {$wpdb->prefix}sm_translations WHERE entity_type = 'product' AND value LIKE %s))";
			$args[]  = $like;
			$args[]  = $like;
			$args[]  = $tlike;
		}
		if ( ! empty( $a['min_price'] ) ) {
			$where[] = 'price_per_kg >= %f';
			$args[]  = (float) $a['min_price'];
		}
		if ( ! empty( $a['max_price'] ) ) {
			$where[] = 'price_per_kg <= %f AND price_per_kg > 0';
			$args[]  = (float) $a['max_price'];
		}
		$order = array(
			'recent'      => 'COALESCE(bumped_at, published_at, updated_at) DESC, id DESC',
			'price_asc'   => 'CASE WHEN price_per_kg > 0 THEN 0 ELSE 1 END, price_per_kg ASC, id DESC',
			'price_desc'  => 'price_per_kg DESC, id DESC',
			'weight_desc' => 'total_weight DESC, id DESC',
			'updated'     => 'updated_at DESC, id DESC',
		)[ $a['sort'] ] ?? 'COALESCE(bumped_at, published_at, updated_at) DESC, id DESC';
		// Listings featured by an option (0.8.0) come first wherever they are listed.
		$order = $wpdb->prepare( 'CASE WHEN featured_until > %s THEN 0 ELSE 1 END, ', current_time( 'mysql', true ) ) . $order;
		$per   = max( 1, min( 60, (int) $a['per_page'] ) );
		$page  = max( 1, (int) $a['page'] );
		$w     = implode( ' AND ', $where );
		$t     = self::table();
		$total = (int) ( $args ? $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE {$w}", $args ) ) : $wpdb->get_var( "SELECT COUNT(*) FROM {$t} WHERE {$w}" ) ); // phpcs:ignore WordPress.DB
		$sql   = "SELECT * FROM {$t} WHERE {$w} ORDER BY {$order} LIMIT %d OFFSET %d";
		$rows  = $wpdb->get_results( $wpdb->prepare( $sql, array_merge( $args, array( $per, ( $page - 1 ) * $per ) ) ) ); // phpcs:ignore WordPress.DB
		return array(
			'items' => array_map( array( __CLASS__, 'hydrate' ), $rows ),
			'total' => $total,
			'pages' => (int) ceil( $total / $per ),
		);
	}

	/**
	 * What a company has online (0.8.0): listings online or waiting for review,
	 * their total weight (products and reuse) and the buildings, counted apart.
	 * $except: a product id left out (the one being edited).
	 */
	public static function online_totals( $company_id, $except = 0 ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT SUM(CASE WHEN market <> 'buildings' THEN 1 ELSE 0 END) AS listings, SUM(CASE WHEN market <> 'buildings' THEN total_weight ELSE 0 END) AS weight, SUM(CASE WHEN market = 'buildings' THEN 1 ELSE 0 END) AS buildings FROM " . self::table() . " WHERE company_id = %d AND status IN ('published','pending') AND id <> %d", $company_id, (int) $except ) ); // phpcs:ignore WordPress.DB
		return array(
			'listings'  => (int) ( $row->listings ?? 0 ),
			'weight'    => (float) ( $row->weight ?? 0 ),
			'buildings' => (int) ( $row->buildings ?? 0 ),
		);
	}

	/**
	 * Paid option on a listing (0.8.0): "bump" puts it back at the top of the
	 * lists; "featured" and "urgent" last $days (added to a running option).
	 */
	public static function boost( $id, $type, $days = 7 ) {
		global $wpdb;
		$now = current_time( 'mysql', true );
		if ( 'bump' === $type ) {
			return false !== $wpdb->update( self::table(), array( 'bumped_at' => $now ), array( 'id' => (int) $id ) );
		}
		$col = array( 'featured' => 'featured_until', 'urgent' => 'urgent_until' )[ $type ] ?? '';
		if ( ! $col ) {
			return false;
		}
		$p    = self::get( $id );
		$from = $p && $p->$col && $p->$col > $now ? strtotime( $p->$col . ' UTC' ) : time();
		return false !== $wpdb->update( self::table(), array( $col => gmdate( 'Y-m-d H:i:s', $from + (int) $days * DAY_IN_SECONDS ) ), array( 'id' => (int) $id ) );
	}

	/** Listings of a company online, oldest first (for pausing over the plan limits). */
	public static function online_oldest_first( $company_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( 'SELECT id, market, total_weight FROM ' . self::table() . " WHERE company_id = %d AND status = 'published' ORDER BY COALESCE(published_at, updated_at) ASC, id ASC", $company_id ) ); // phpcs:ignore WordPress.DB
	}

	public static function count_by_status( $company_id ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT status, COUNT(*) AS n FROM ' . self::table() . ' WHERE company_id = %d GROUP BY status', $company_id ), OBJECT_K ); // phpcs:ignore WordPress.DB
		return array_map( static fn( $r ) => (int) $r->n, $rows );
	}

	public static function add_view( $id ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . self::table() . ' SET views = views + 1 WHERE id = %d', $id ) ); // phpcs:ignore WordPress.DB
	}
}

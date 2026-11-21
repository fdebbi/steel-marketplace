<?php
/**
 * Cross-market listing index.
 *
 * Every listing of every market (products, reuse, buildings) has one row
 * here. Its ID is the public listing number (/annonce/{id}/), so the URLs
 * never collide between markets, and searches across markets read one table.
 * The market plugins keep their own detailed tables and call sync() whenever
 * a listing changes.
 *
 * Readable address (0.9.0): /annonce/ipe-200-s275jr-msila-552/. The name is made
 * once, from the « slug_source » given by the market at the first publication (or later
 * through the filter sm_listing_slug_source for the older listings), and never
 * changes after: the address stays stable for the search engines. The number at
 * the end finds the listing, so an old or wrong name never breaks a link.
 *
 * @package SM\Core
 */

namespace SM\Core\Listings;

defined( 'ABSPATH' ) || exit;

final class Index {

	const FIELDS = array( 'company_id', 'category', 'designation', 'item_condition', 'wilaya', 'price_per_kg', 'price', 'price_unit', 'total_price', 'total_weight', 'status', 'featured', 'published_at' );

	public static function init() {
		add_filter( 'sm_listing_owner', array( __CLASS__, 'owner' ), 5, 3 );
	}

	/**
	 * Create or update the index row of a listing.
	 *
	 * @return int Public listing ID.
	 */
	public static function sync( $module, $entity_id, array $fields ) {
		global $wpdb;
		$table = sm_table( 'listing_index' );
		$row   = array_intersect_key( $fields, array_flip( self::FIELDS ) );
		$row['updated_at'] = current_time( 'mysql', true );
		$slug  = self::make_slug( (string) ( $fields['slug_source'] ?? '' ) );
		$old   = $wpdb->get_row( $wpdb->prepare( "SELECT id, slug FROM {$table} WHERE module = %s AND entity_id = %d", $module, $entity_id ) ); // phpcs:ignore WordPress.DB
		$id    = $old ? (int) $old->id : 0;
		if ( '' !== $slug && ( ! $old || '' === (string) $old->slug ) ) {
			$row['slug'] = $slug; // Made once, never changed after.
		}
		if ( $id ) {
			$wpdb->update( $table, $row, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		} else {
			$wpdb->insert( $table, $row + array( 'module' => $module, 'entity_id' => (int) $entity_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$id = (int) $wpdb->insert_id;
		}
		wp_cache_delete( 'listing_' . $id, 'sm' );
		return $id;
	}

	public static function remove( $module, $entity_id ) {
		global $wpdb;
		$row = self::find( $module, $entity_id );
		if ( $row ) {
			$wpdb->delete( sm_table( 'listing_index' ), array( 'id' => $row->id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			wp_cache_delete( 'listing_' . $row->id, 'sm' );
		}
	}

	public static function get( $id ) {
		global $wpdb;
		$row = wp_cache_get( 'listing_' . (int) $id, 'sm' );
		if ( false === $row ) {
			$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . sm_table( 'listing_index' ) . ' WHERE id = %d', $id ) ); // phpcs:ignore WordPress.DB
			wp_cache_set( 'listing_' . (int) $id, $row ? $row : 0, 'sm', HOUR_IN_SECONDS );
		}
		return $row ? $row : null;
	}

	/**
	 * Name of a listing in its address ('' when none can be made). Older listings
	 * get theirs the first time their address is asked (filter sm_listing_slug_source).
	 */
	public static function slug( $id ) {
		$row = self::get( $id );
		if ( ! $row ) {
			return '';
		}
		$slug = (string) ( $row->slug ?? '' );
		// Not yet public (draft, waiting, in the store): no name yet, the address is /annonce/{id}/.
		if ( '' === $slug && ! in_array( (string) $row->status, array( 'draft', 'pending', 'stock', 'rejected' ), true ) ) {
			$slug = self::make_slug( (string) apply_filters( 'sm_listing_slug_source', '', $row ) );
			if ( '' !== $slug ) {
				global $wpdb;
				$wpdb->update( sm_table( 'listing_index' ), array( 'slug' => $slug ), array( 'id' => (int) $row->id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				wp_cache_delete( 'listing_' . (int) $row->id, 'sm' );
			}
		}
		return $slug;
	}

	/** « IPE 200 S275JR M'Sila » → « ipe-200-s275jr-msila » (latin letters, 80 characters at most). */
	public static function make_slug( $text ) {
		$text = trim( (string) $text );
		if ( '' === $text ) {
			return '';
		}
		$text = str_replace( array( '’', "'", '×' ), array( '', '', 'x' ), $text );
		$slug = sanitize_title( remove_accents( $text ) );
		$slug = preg_replace( '/[^a-z0-9-]+/', '', strtolower( rawurldecode( $slug ) ) );
		$slug = trim( preg_replace( '/-+/', '-', $slug ), '-' );
		if ( strlen( $slug ) > 80 ) {
			$slug = substr( $slug, 0, 80 );
			$cut  = strrpos( $slug, '-' );
			$slug = $cut > 40 ? substr( $slug, 0, $cut ) : $slug;
		}
		return trim( $slug, '-' );
	}

	public static function find( $module, $entity_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . sm_table( 'listing_index' ) . ' WHERE module = %s AND entity_id = %d', $module, $entity_id ) ); // phpcs:ignore WordPress.DB
	}

	/** Company that owns a listing, for enquiries. */
	public static function owner( $company_id, $module, $listing_id ) {
		if ( $company_id ) {
			return $company_id;
		}
		$row = self::get( $listing_id );
		return $row && ( '' === $module || $row->module === $module ) ? (int) $row->company_id : 0;
	}

	public static function count_published( $company_id ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . sm_table( 'listing_index' ) . " WHERE company_id = %d AND status = 'published'", $company_id ) ); // phpcs:ignore WordPress.DB
	}
}

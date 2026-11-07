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
		$id    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE module = %s AND entity_id = %d", $module, $entity_id ) ); // phpcs:ignore WordPress.DB
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

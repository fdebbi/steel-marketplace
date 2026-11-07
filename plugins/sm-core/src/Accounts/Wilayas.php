<?php
/**
 * Wilaya reference list (sm_wilayas).
 *
 * @package SM\Core
 */

namespace SM\Core\Accounts;

defined( 'ABSPATH' ) || exit;

final class Wilayas {

	/** @var array|null */
	private static $all;

	/** code => ['fr' => name, 'ar' => name] (active only unless $all). */
	public static function all( $include_inactive = false ) {
		global $wpdb;
		if ( null === self::$all ) {
			$rows = wp_cache_get( 'sm_wilayas', 'sm' );
			if ( false === $rows ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$rows = $wpdb->get_results( 'SELECT code, name_fr, name_ar, lat, lng, active FROM ' . sm_table( 'wilayas' ) . ' ORDER BY code' );
				wp_cache_set( 'sm_wilayas', $rows, 'sm', HOUR_IN_SECONDS );
			}
			self::$all = (array) $rows;
		}
		$out = array();
		foreach ( self::$all as $r ) {
			if ( $include_inactive || (int) $r->active ) {
				$out[ $r->code ] = array( 'fr' => $r->name_fr, 'ar' => $r->name_ar );
			}
		}
		return $out;
	}

	/**
	 * Map centre of each wilaya: code => [lat, lng] (floats). Approximate; used
	 * only to open the location map on the right area.
	 */
	public static function centres() {
		self::all( true );
		$out = array();
		foreach ( self::$all as $r ) {
			if ( null !== $r->lat && null !== $r->lng && '' !== $r->lat ) {
				$out[ $r->code ] = array( round( (float) $r->lat, 4 ), round( (float) $r->lng, 4 ) );
			}
		}
		return $out;
	}

	public static function exists( $code ) {
		return isset( self::all()[ (string) $code ] );
	}

	public static function name( $code, $lang = null ) {
		$all = self::all( true );
		if ( ! isset( $all[ $code ] ) ) {
			return '';
		}
		$lang = $lang ?: sm_current_language();
		return $all[ $code ][ $lang ] ?? $all[ $code ]['fr'];
	}

	public static function update( $code, $name_fr, $name_ar, $active ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update( sm_table( 'wilayas' ), array( 'name_fr' => sanitize_text_field( $name_fr ), 'name_ar' => sanitize_text_field( $name_ar ), 'active' => $active ? 1 : 0 ), array( 'code' => $code ) );
		wp_cache_delete( 'sm_wilayas', 'sm' );
		self::$all = null;
	}
}

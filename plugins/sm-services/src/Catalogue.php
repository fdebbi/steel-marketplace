<?php
/**
 * The two service catalogues (fabrication & site work, engineering).
 *
 * @package SM\Services
 */

namespace SM\Services;

defined( 'ABSPATH' ) || exit;

final class Catalogue {

	const PRICE_UNITS = array( 'project', 'kg', 't', 'm2', 'ml', 'piece', 'hour', 'day' );

	public static function all() {
		static $all = null;
		if ( null === $all ) {
			$all = apply_filters( 'sm_services_catalogue', require SM_SERVICES_DIR . '/data/catalogue.php' );
		}
		return $all;
	}

	public static function kind( $kind ) {
		return self::all()[ (string) $kind ] ?? null;
	}

	public static function kinds() {
		return array_keys( self::all() );
	}

	/** Kinds the company may publish (by permission; engineering never gets fabrication). */
	public static function allowed_kinds( $company_id = null ) {
		$out = array();
		foreach ( self::all() as $k => $def ) {
			if ( ! is_wp_error( sm_can_explain( $def['cap'], $company_id ) ) ) {
				$out[] = $k;
			}
		}
		return $out;
	}
}

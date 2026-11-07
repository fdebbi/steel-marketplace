<?php
/**
 * Limits of the building form, editable in wp-admin (SM → Buildings).
 *
 * @package SM\Buildings
 */

namespace SM\Buildings;

defined( 'ABSPATH' ) || exit;

final class Settings {

	const OPTION = 'sm_buildings_settings';

	public static function defaults() {
		return array(
			'max_length_m' => 300,  // Length and width.
			'max_height_m' => 40,   // Eaves height.
			'max_portals'  => 150,
			'max_lines'    => 60,   // Rows of the element inventory.
		);
	}

	public static function all() {
		return wp_parse_args( (array) get_option( self::OPTION, array() ), self::defaults() );
	}

	public static function get( $key ) {
		return self::all()[ $key ] ?? null;
	}

	public static function save( array $in ) {
		$out = array();
		foreach ( self::defaults() as $k => $v ) {
			$n         = isset( $in[ $k ] ) ? (float) $in[ $k ] : $v;
			$out[ $k ] = $n > 0 ? $n : $v;
		}
		$out['max_portals'] = (int) $out['max_portals'];
		$out['max_lines']   = (int) min( 200, $out['max_lines'] );
		update_option( self::OPTION, $out, false );
		return $out;
	}
}

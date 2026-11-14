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
			'max_height_m' => 40,   // Eaves height (the ridge may be up to 1.5 times higher).
			'max_floors'   => 10,
			'max_portals'  => 150,
			'max_lines'    => 60,   // Rows of the element inventory.
			'max_crane_t'  => 500,  // Capacity of an overhead crane (t).
			// Elements proposed from the dimensions (0.3.0, see Proposal).
			'prop_col_family'    => 'IPE',
			'prop_col_factor'    => 20,   // Column height (mm) = width (m) × factor.
			'prop_crane_family'  => 'HEA',
			'prop_crane_factor'  => 18,   // Same, with an overhead crane.
			'prop_rafter_family' => 'IPE',
			'prop_rafter_factor' => 20,
			'prop_purlin'        => 'IPE 120',
			'prop_purlin_step'   => 1.2,  // Spacing of the purlin rows (m).
			'prop_rail'          => 'UPN 120',
			'prop_rail_step'     => 1.2,
			'prop_brace'         => 'L 80x80x8',
			'prop_brace_qty'     => 32,
			'prop_brace_len'     => 6,
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
			if ( is_string( $v ) ) {
				$t         = isset( $in[ $k ] ) ? trim( sanitize_text_field( (string) $in[ $k ] ) ) : '';
				$out[ $k ] = '' !== $t ? mb_substr( $t, 0, 40 ) : $v;
				continue;
			}
			$n         = isset( $in[ $k ] ) ? (float) str_replace( ',', '.', (string) $in[ $k ] ) : $v;
			$out[ $k ] = $n > 0 ? $n : $v;
		}
		$out['prop_brace_qty'] = (int) $out['prop_brace_qty'];
		$out['max_portals'] = (int) $out['max_portals'];
		$out['max_floors']  = (int) $out['max_floors'];
		$out['max_lines']   = (int) min( 200, $out['max_lines'] );
		update_option( self::OPTION, $out, false );
		return $out;
	}
}

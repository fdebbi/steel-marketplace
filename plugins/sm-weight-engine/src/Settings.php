<?php
/**
 * Settings of the weight engine (wp-admin -> Steel Marketplace -> Steel catalogue -> Settings).
 *
 * Nothing is hard-coded in the calculations: density, bar lengths and input
 * limits come from here. Defaults: density 7850 kg/m3 (the value EN 10365 and
 * EN 10219-2 use for their tabulated masses), commercial lengths 12 m and 6 m.
 *
 * @package SM\WeightEngine
 */

namespace SM\WeightEngine;

defined( 'ABSPATH' ) || exit;

final class Settings {

	const OPTION = 'sm_weight_settings';

	public static function defaults() {
		return array(
			'density'        => 7850,
			'bar_lengths'    => array( 12000, 6000 ),
			'default_length' => 12000,
			'max_length'     => 24000,
			'max_bars'       => 100000,
			'max_total_kg'   => 10000000,
			'max_dimension'  => 1000,
			'max_thickness'  => 200,
			'max_plate_area' => 100,
		);
	}

	public static function all() {
		$saved = get_option( self::OPTION, array() );
		return array_merge( self::defaults(), is_array( $saved ) ? $saved : array() );
	}

	public static function get( $key, $default = null ) {
		$all = self::all();
		return $all[ $key ] ?? $default;
	}

	/** Validate and save; returns the saved values. */
	public static function save( array $in ) {
		$d   = self::defaults();
		$out = array(
			'density'        => min( 9000, max( 7000, (float) ( $in['density'] ?? $d['density'] ) ) ),
			'default_length' => max( 100, (int) ( $in['default_length'] ?? $d['default_length'] ) ),
			'max_length'     => max( 1000, (int) ( $in['max_length'] ?? $d['max_length'] ) ),
			'max_bars'       => max( 1, (int) ( $in['max_bars'] ?? $d['max_bars'] ) ),
			'max_total_kg'   => max( 1, (float) ( $in['max_total_kg'] ?? $d['max_total_kg'] ) ),
			'max_dimension'  => max( 10, (float) ( $in['max_dimension'] ?? $d['max_dimension'] ) ),
			'max_thickness'  => max( 1, (float) ( $in['max_thickness'] ?? $d['max_thickness'] ) ),
			'max_plate_area' => max( 1, (float) ( $in['max_plate_area'] ?? $d['max_plate_area'] ) ),
		);
		$lengths = is_array( $in['bar_lengths'] ?? null ) ? $in['bar_lengths'] : preg_split( '/[\s,;]+/', (string) ( $in['bar_lengths'] ?? '' ) );
		$lengths = array_values( array_unique( array_filter( array_map( 'intval', $lengths ), static fn( $l ) => $l >= 100 && $l <= $out['max_length'] ) ) );
		rsort( $lengths );
		$out['bar_lengths'] = $lengths ? $lengths : $d['bar_lengths'];
		if ( ! in_array( $out['default_length'], $out['bar_lengths'], true ) ) {
			$out['default_length'] = $out['bar_lengths'][0];
		}
		update_option( self::OPTION, $out );
		return $out;
	}
}

<?php
/**
 * Settings of the services module (wp-admin → SM → Services).
 *
 * @package SM\Services
 */

namespace SM\Services;

defined( 'ABSPATH' ) || exit;

final class Settings {

	const OPTION = 'sm_services_settings';

	public static function defaults() {
		return array(
			'moderation'      => 0,   // Check each new service before it is shown.
			'max_services'    => 30,  // Services per company.
			'max_photos'      => 8,
			'photo_mb'        => 8,
			'file_mb'         => 10,
			'max_files'       => 3,
			'requests_per_day' => 20, // Service requests a company may send per day.
			'max_portfolio'   => 30,
			'max_price'       => 10000000000,
		);
	}

	public static function all() {
		$s = get_option( self::OPTION, array() );
		return array_merge( self::defaults(), is_array( $s ) ? $s : array() );
	}

	public static function get( $key ) {
		return self::all()[ $key ] ?? null;
	}

	public static function save( array $in ) {
		$d   = self::defaults();
		$out = array( 'moderation' => empty( $in['moderation'] ) ? 0 : 1 );
		foreach ( array( 'max_services' => 200, 'max_photos' => 30, 'photo_mb' => 50, 'file_mb' => 50, 'max_files' => 20, 'requests_per_day' => 500, 'max_portfolio' => 200 ) as $k => $max ) {
			$out[ $k ] = min( $max, max( 1, (int) ( $in[ $k ] ?? $d[ $k ] ) ) );
		}
		$out['max_price'] = max( 1, (float) ( $in['max_price'] ?? $d['max_price'] ) );
		update_option( self::OPTION, $out );
		return $out;
	}
}

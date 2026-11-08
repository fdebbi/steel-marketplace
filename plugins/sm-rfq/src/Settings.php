<?php
/**
 * Settings of the RFQ module (wp-admin → SM → Requests for quotation).
 *
 * @package SM\Rfq
 */

namespace SM\Rfq;

defined( 'ABSPATH' ) || exit;

final class Settings {

	const OPTION = 'sm_rfq_settings';

	public static function defaults() {
		return array(
			'moderation'    => 0,   // Check each request before it is shown.
			'default_days'  => 21,  // Offers deadline proposed to the buyer.
			'max_days'      => 90,  // Latest deadline allowed.
			'max_files'     => 5,
			'file_mb'       => 10,
			'max_per_day'   => 10,  // Requests a company may publish per day.
			'min_price'     => 1,
			'max_price'     => 10000000000,
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
		foreach ( array( 'default_days' => array( 1, 365 ), 'max_days' => array( 1, 365 ), 'max_files' => array( 1, 20 ), 'file_mb' => array( 1, 50 ), 'max_per_day' => array( 1, 200 ) ) as $k => list( $min, $max ) ) {
			$out[ $k ] = min( $max, max( $min, (int) ( $in[ $k ] ?? $d[ $k ] ) ) );
		}
		$out['default_days'] = min( $out['default_days'], $out['max_days'] );
		$out['min_price']    = max( 0, (float) ( $in['min_price'] ?? $d['min_price'] ) );
		$out['max_price']    = max( 1, (float) ( $in['max_price'] ?? $d['max_price'] ) );
		update_option( self::OPTION, $out );
		return $out;
	}
}

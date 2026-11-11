<?php
/**
 * Settings of the products market (wp-admin -> Steel Marketplace -> Products -> Settings).
 *
 * @package SM\Products
 */

namespace SM\Products;

defined( 'ABSPATH' ) || exit;

final class Settings {

	const OPTION = 'sm_products_settings';

	public static function defaults() {
		return array(
			'moderation'   => 1,
			'client_moderation' => 1, // Listings of client accounts (private sellers) are always checked.
			'client_max_active' => 5, // Published or waiting listings of a client account (0 = no limit).
			'expiry_days'  => 90,
			'max_photos'   => 10,
			'photo_mb'     => 8,
			'sheet_mb'     => 10,
			'grades'       => array( 'S235JR', 'S275JR', 'S355JR', 'S355J2', 'S235JRH', 'S275J0H', 'S355J2H' ),
			'min_price'    => 1,
			'max_price'    => 100000000,
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
		$out = array(
			'moderation'  => empty( $in['moderation'] ) ? 0 : 1,
			'client_moderation' => empty( $in['client_moderation'] ) ? 0 : 1,
			'client_max_active' => min( 1000, max( 0, (int) ( $in['client_max_active'] ?? $d['client_max_active'] ) ) ),
			'expiry_days' => max( 0, (int) ( $in['expiry_days'] ?? $d['expiry_days'] ) ),
			'max_photos'  => min( 30, max( 1, (int) ( $in['max_photos'] ?? $d['max_photos'] ) ) ),
			'photo_mb'    => min( 50, max( 1, (int) ( $in['photo_mb'] ?? $d['photo_mb'] ) ) ),
			'sheet_mb'    => min( 50, max( 1, (int) ( $in['sheet_mb'] ?? $d['sheet_mb'] ) ) ),
			'min_price'   => max( 0, (float) ( $in['min_price'] ?? $d['min_price'] ) ),
			'max_price'   => max( 1, (float) ( $in['max_price'] ?? $d['max_price'] ) ),
		);
		$grades        = is_array( $in['grades'] ?? null ) ? $in['grades'] : preg_split( '/[\s,;]+/', (string) ( $in['grades'] ?? '' ) );
		$grades        = array_values( array_unique( array_filter( array_map( static fn( $g ) => strtoupper( preg_replace( '/[^A-Za-z0-9+]/', '', $g ) ), $grades ) ) ) );
		$out['grades'] = $grades ? $grades : $d['grades'];
		update_option( self::OPTION, $out );
		return $out;
	}
}

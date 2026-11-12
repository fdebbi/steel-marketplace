<?php
/**
 * Settings: invoicing identity, payment details, founding members offer,
 * AI credit packs and listing options. Edited in SM → Subscriptions → Settings.
 *
 * @package SM\Subscriptions
 */

namespace SM\Subscriptions;

defined( 'ABSPATH' ) || exit;

final class Settings {

	const OPTION = 'sm_sub_settings';

	public static function defaults() {
		return array(
			// Invoicing identity of the platform (shown on every invoice).
			'legal_name'      => '',
			'rc'              => '',
			'nif'             => '',
			'nis'             => '',
			'art'             => '',
			'address'         => '',
			'tva_rate'        => 19,      // Prices are shown tax included; the invoice splits HT / TVA.
			// Payment outside the site.
			'bank_name'       => '',
			'rib'             => '',
			'ccp'             => '',
			'baridimob'       => '',
			'cheque_to'       => '',
			'instructions'    => '',
			'methods'         => array( 'transfer', 'ccp', 'baridimob', 'cheque' ),
			// Founding members: a free paid plan for the first verified accounts.
			'founders_on'     => 1,
			'founders_slots'  => 100,
			'founders_months' => 6,
			'founders_plan'   => array( 'company' => 'pro', 'professional' => 'artisan_pro' ),
			'founders_level'  => 2,       // Verification level that opens the offer (2 = documents checked).
			// AI credit packs and listing options (prices tax included).
			'packs'           => array(
				array( 'code' => 'ai50', 'credits' => 50, 'price' => 1000 ),
				array( 'code' => 'ai150', 'credits' => 150, 'price' => 2500 ),
			),
			'opt_bump'        => 300,
			'opt_urgent'      => 500,
			'opt_urgent_days' => 7,
			'opt_featured'    => 2000,
			'opt_featured_days' => 7,
			'reminder_days'   => 7,
		);
	}

	public static function all() {
		return array_merge( self::defaults(), (array) get_option( self::OPTION, array() ) );
	}

	public static function get( $key ) {
		return self::all()[ $key ] ?? null;
	}

	/** Payment methods offered: code => label. */
	public static function method_labels() {
		return array(
			'transfer'  => __( 'Bank transfer', 'sm-subscriptions' ),
			'ccp'       => __( 'CCP payment', 'sm-subscriptions' ),
			'baridimob' => __( 'BaridiMob', 'sm-subscriptions' ),
			'cheque'    => __( 'Cheque', 'sm-subscriptions' ),
		);
	}

	public static function methods() {
		return array_intersect_key( self::method_labels(), array_flip( (array) self::get( 'methods' ) ) );
	}

	public static function save( array $in ) {
		$d   = self::defaults();
		$out = array();
		foreach ( array( 'legal_name', 'rc', 'nif', 'nis', 'art', 'bank_name', 'rib', 'ccp', 'baridimob', 'cheque_to' ) as $k ) {
			$out[ $k ] = sanitize_text_field( (string) ( $in[ $k ] ?? '' ) );
		}
		foreach ( array( 'address', 'instructions' ) as $k ) {
			$out[ $k ] = sanitize_textarea_field( (string) ( $in[ $k ] ?? '' ) );
		}
		$out['tva_rate'] = min( 100, max( 0, (float) str_replace( ',', '.', (string) ( $in['tva_rate'] ?? $d['tva_rate'] ) ) ) );
		$out['methods']  = array_values( array_intersect( array_keys( self::method_labels() ), (array) ( $in['methods'] ?? array() ) ) );
		$out['founders_on'] = empty( $in['founders_on'] ) ? 0 : 1;
		foreach ( array( 'founders_slots', 'founders_months', 'founders_level', 'opt_urgent_days', 'opt_featured_days', 'reminder_days' ) as $k ) {
			$out[ $k ] = max( 0, (int) ( $in[ $k ] ?? $d[ $k ] ) );
		}
		foreach ( array( 'opt_bump', 'opt_urgent', 'opt_featured' ) as $k ) {
			$out[ $k ] = max( 0, (float) str_replace( array( ' ', ',' ), array( '', '.' ), (string) ( $in[ $k ] ?? $d[ $k ] ) ) );
		}
		$out['founders_plan'] = array();
		foreach ( Plans::classes() as $class ) {
			$code = sanitize_key( (string) ( $in['founders_plan'][ $class ] ?? '' ) );
			$out['founders_plan'][ $class ] = isset( Plans::paid( $class )[ $code ] ) ? $code : $d['founders_plan'][ $class ];
		}
		$packs = array();
		foreach ( (array) ( $in['packs'] ?? array() ) as $i => $p ) {
			$credits = (int) ( $p['credits'] ?? 0 );
			$price   = (float) str_replace( array( ' ', ',' ), array( '', '.' ), (string) ( $p['price'] ?? 0 ) );
			if ( $credits > 0 && $price > 0 ) {
				$packs[] = array( 'code' => 'ai' . $credits, 'credits' => $credits, 'price' => $price );
			}
		}
		$out['packs'] = $packs;
		update_option( self::OPTION, $out, false );
	}
}

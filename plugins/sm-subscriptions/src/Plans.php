<?php
/**
 * Plans (decided on 12 Nov 2026): three for companies, three for professionals.
 * Clients and partner engineers have no plan; they can buy AI credits.
 *
 * Limits: listings online (products and reuse), total weight online in tonnes
 * (products and reuse), buildings online, service pages, completed projects,
 * first answers to requests for quotation per month, AI credits per month,
 * contacts visible on the public page, featured place. -1 = no limit.
 * Every number and price is a starting value, edited in SM → Subscriptions.
 *
 * @package SM\Subscriptions
 */

namespace SM\Subscriptions;

defined( 'ABSPATH' ) || exit;

final class Plans {

	const OPTION    = 'sm_sub_plans';
	const UNLIMITED = -1;

	/** Limit keys, in display order. */
	public static function limit_keys() {
		return array( 'listings', 'weight_t', 'buildings', 'services', 'portfolio', 'rfq_offers', 'ai_credits', 'contacts', 'featured' );
	}

	/** Default plans: class => code => data. */
	public static function defaults() {
		return array(
			'company'      => array(
				'free'     => array( 'price_month' => 0, 'price_year' => 0, 'listings' => 5, 'weight_t' => 10, 'buildings' => 1, 'services' => 1, 'portfolio' => 3, 'rfq_offers' => 3, 'ai_credits' => 5, 'contacts' => 0, 'featured' => '' ),
				'pro'      => array( 'price_month' => 3500, 'price_year' => 35000, 'listings' => 40, 'weight_t' => 200, 'buildings' => 5, 'services' => 5, 'portfolio' => 20, 'rfq_offers' => -1, 'ai_credits' => 50, 'contacts' => 1, 'featured' => '' ),
				'business' => array( 'price_month' => 9000, 'price_year' => 90000, 'listings' => 150, 'weight_t' => 1000, 'buildings' => 20, 'services' => 20, 'portfolio' => -1, 'rfq_offers' => -1, 'ai_credits' => 200, 'contacts' => 1, 'featured' => 'home' ),
			),
			'professional' => array(
				'free'            => array( 'price_month' => 0, 'price_year' => 0, 'listings' => 3, 'weight_t' => 5, 'buildings' => 1, 'services' => 1, 'portfolio' => 3, 'rfq_offers' => 3, 'ai_credits' => 5, 'contacts' => 0, 'featured' => '' ),
				'artisan_pro'     => array( 'price_month' => 1500, 'price_year' => 15000, 'listings' => 10, 'weight_t' => 30, 'buildings' => 2, 'services' => 3, 'portfolio' => 15, 'rfq_offers' => -1, 'ai_credits' => 30, 'contacts' => 1, 'featured' => '' ),
				'artisan_premium' => array( 'price_month' => 3000, 'price_year' => 30000, 'listings' => 30, 'weight_t' => 100, 'buildings' => 5, 'services' => 8, 'portfolio' => -1, 'rfq_offers' => -1, 'ai_credits' => 80, 'contacts' => 1, 'featured' => 'home' ),
			),
		);
	}

	/** Translated names. */
	public static function names() {
		return array(
			'free'            => __( 'Discovery', 'sm-subscriptions' ),
			'pro'             => __( 'Pro', 'sm-subscriptions' ),
			'business'        => __( 'Business', 'sm-subscriptions' ),
			'artisan_pro'     => __( 'Artisan Pro', 'sm-subscriptions' ),
			'artisan_premium' => __( 'Artisan Premium', 'sm-subscriptions' ),
		);
	}

	public static function name( $code, $class = 'company' ) {
		if ( 'free' === $code && 'professional' === $class ) {
			return __( 'Free', 'sm-subscriptions' );
		}
		return self::names()[ $code ] ?? $code;
	}

	/** Account classes that have plans. */
	public static function classes() {
		return array( 'company', 'professional' );
	}

	/** All plans with the administrator's values. */
	public static function all() {
		$saved = (array) get_option( self::OPTION, array() );
		$out   = self::defaults();
		foreach ( $out as $class => $plans ) {
			foreach ( $plans as $code => $data ) {
				$out[ $class ][ $code ] = array_merge( $data, array_intersect_key( (array) ( $saved[ $class ][ $code ] ?? array() ), $data ) );
			}
		}
		return $out;
	}

	/** Plans of a class (code => data), or an empty array for clients and engineers. */
	public static function for_class( $class ) {
		return self::all()[ $class ] ?? array();
	}

	/** One plan, or null. */
	public static function get( $class, $code ) {
		return self::for_class( $class )[ $code ] ?? null;
	}

	/** Paid plans of a class. */
	public static function paid( $class ) {
		return array_filter( self::for_class( $class ), static fn( $p ) => $p['price_month'] > 0 || $p['price_year'] > 0 );
	}

	/** Save the administrator's values (sanitized). */
	public static function save( array $in ) {
		$out = array();
		foreach ( self::defaults() as $class => $plans ) {
			foreach ( $plans as $code => $def ) {
				$row = (array) ( $in[ $class ][ $code ] ?? array() );
				foreach ( $def as $k => $v ) {
					if ( 'featured' === $k ) {
						$out[ $class ][ $code ][ $k ] = in_array( $row[ $k ] ?? '', array( '', 'home' ), true ) ? (string) ( $row[ $k ] ?? '' ) : '';
					} elseif ( 'contacts' === $k ) {
						$out[ $class ][ $code ][ $k ] = empty( $row[ $k ] ) ? 0 : 1;
					} elseif ( in_array( $k, array( 'price_month', 'price_year' ), true ) ) {
						$out[ $class ][ $code ][ $k ] = max( 0, (float) str_replace( array( ' ', ',' ), array( '', '.' ), (string) ( $row[ $k ] ?? $v ) ) );
					} else {
						$n = trim( (string) ( $row[ $k ] ?? $v ) );
						$out[ $class ][ $code ][ $k ] = ( '' === $n || (int) $n < 0 ) ? self::UNLIMITED : (int) $n;
					}
				}
			}
		}
		update_option( self::OPTION, $out, false );
	}

	/** Labels of the limits. */
	public static function limit_labels() {
		return array(
			'listings'   => __( 'Listings online', 'sm-subscriptions' ),
			'weight_t'   => __( 'Total weight online', 'sm-subscriptions' ),
			'buildings'  => __( 'Buildings online', 'sm-subscriptions' ),
			'services'   => __( 'Service pages', 'sm-subscriptions' ),
			'portfolio'  => __( 'Completed projects', 'sm-subscriptions' ),
			'rfq_offers' => __( 'Answers to requests for quotation per month', 'sm-subscriptions' ),
			'ai_credits' => __( 'AI credits per month', 'sm-subscriptions' ),
			'contacts'   => __( 'Phone, e-mail, WhatsApp and website visible', 'sm-subscriptions' ),
			'featured'   => __( 'Featured on the home page', 'sm-subscriptions' ),
		);
	}
}

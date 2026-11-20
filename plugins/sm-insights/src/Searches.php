<?php
/**
 * Searches of the catalogue (action sm_products_searched, sm-products 0.22.0):
 * one count per profile, market, wilaya and day. Who searched is never kept: a
 * visitor counts once a day per profile (short-lived key from a hash), robots,
 * live counters (ajax) and texts that are not a profile are not counted.
 *
 * @package SM\Insights
 */

namespace SM\Insights;

defined( 'ABSPATH' ) || exit;

final class Searches {

	const KEEP_DAYS = 400;
	const PROFILE   = '/\b(IPE|IPN|HEA|HEB|HEM|UPN|UPE)\s*-?\s*(\d{2,4})\b/i';

	public static function init() {
		add_action( 'sm_products_searched', array( __CLASS__, 'searched' ) );
		add_action( 'sm_core_daily', array( __CLASS__, 'purge' ) );
	}

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sm_search_counts';
	}

	/** Profile asked in a search: [ term, family ] or null. */
	public static function term( $family, $designation, $q ) {
		$family = strtoupper( (string) $family );
		foreach ( array( (string) $designation, (string) $q ) as $text ) {
			if ( '' !== $text && preg_match( self::PROFILE, $text, $m ) ) {
				return array( strtoupper( $m[1] ) . ' ' . (int) $m[2], strtoupper( $m[1] ) );
			}
		}
		if ( '' !== $designation && $family && class_exists( '\SM\WeightEngine\Catalogue' ) ) {
			$s = \SM\WeightEngine\Catalogue::find( $family, $designation );
			if ( $s ) {
				return array( (string) $s->designation, $family );
			}
		}
		if ( $family && preg_match( '/^[A-Z]{1,6}$/', $family ) ) {
			return array( $family, $family );
		}
		$q = strtoupper( trim( (string) $q ) );
		if ( preg_match( '/^(IPE|IPN|HEA|HEB|HEM|UPN|UPE)$/', $q ) ) {
			return array( $q, $q );
		}
		return null;
	}

	public static function searched( $s ) {
		if ( wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || self::robot() ) {
			return;
		}
		$t = self::term( $s['family'] ?? '', $s['designation'] ?? '', $s['q'] ?? '' );
		if ( ! $t ) {
			return;
		}
		$markets = array_values( array_intersect( (array) ( $s['market'] ?? array() ), array( 'products', 'reuse', 'buildings' ) ) );
		$market  = 1 === count( $markets ) ? $markets[0] : '';
		$wilaya  = preg_match( '/^\d{2}$/', (string) ( $s['wilaya'] ?? '' ) ) ? (string) $s['wilaya'] : '';
		$day     = gmdate( 'Y-m-d' );
		$ip      = class_exists( '\SM\Core\Support\Forms' ) ? \SM\Core\Support\Forms::ip() : '';
		$ua      = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$once    = 'sm_ins_s_' . md5( wp_salt( 'nonce' ) . '|' . $ip . '|' . $ua . '|' . $t[0] . '|' . $market . '|' . $wilaya . '|' . $day );
		if ( get_transient( $once ) ) {
			return;
		}
		set_transient( $once, 1, DAY_IN_SECONDS );
		self::add( $t[0], $t[1], $market, $wilaya, $day );
	}

	public static function add( $term, $family, $market = '', $wilaya = '', $day = '', $hits = 1 ) {
		global $wpdb;
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'INSERT INTO ' . self::table() . ' (day, term, family, market, wilaya, hits) VALUES (%s, %s, %s, %s, %s, %d) ON DUPLICATE KEY UPDATE hits = hits + VALUES(hits)',
				$day ? $day : gmdate( 'Y-m-d' ),
				mb_substr( (string) $term, 0, 60 ),
				mb_substr( (string) $family, 0, 12 ),
				(string) $market,
				(string) $wilaya,
				max( 1, (int) $hits )
			)
		);
	}

	private static function robot() {
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( (string) wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		return '' === $ua || (bool) preg_match( '/bot|crawl|spider|slurp|preview|monitor|curl|wget|python|headless/', $ua );
	}

	public static function purge() {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::table() . ' WHERE day < %s', gmdate( 'Y-m-d', time() - self::KEEP_DAYS * DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}
}

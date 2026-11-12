<?php
/**
 * Average asking price per kg of steel sections, week by week (0.6.0).
 *
 * Computed from the platform's own listings: the price per kg of every
 * listing of a hot-rolled section (IPE, HEA, HEB, HEM, IPN, UPN, UPE, angles)
 * that went online in the week, whatever happened to it afterwards (sold,
 * paused, expired). Drafts, listings waiting for review and refused ones are
 * left out. These are prices asked by sellers, not market or transaction prices.
 *
 * A week counts only with at least MIN_LISTINGS listings; the curve is shown
 * only with at least MIN_POINTS such weeks. Below that the result is empty, so
 * the home page shows nothing rather than a curve made of two listings.
 *
 * @package SM\Products
 */

namespace SM\Products;

defined( 'ABSPATH' ) || exit;

final class PriceTrend {

	const WEEKS        = 12;
	const MIN_LISTINGS = 5;
	const MIN_POINTS   = 4;
	const CACHE        = 'smp_price_trend';

	/** Section families followed by the curve (hot-rolled sections). */
	public static function families() {
		return (array) apply_filters( 'smp_price_trend_families', array( 'IPE', 'HEA', 'HEB', 'HEM', 'IPN', 'UPN', 'UPE', 'L', 'LI' ) );
	}

	/** Statuses of listings that went online. */
	private static function statuses() {
		return array( 'published', 'sold', 'paused', 'expired' );
	}

	/**
	 * Weekly averages for a market: list of { start (Y-m-d, Monday), value (DA/kg), count }.
	 * Weeks without enough listings are left out.
	 */
	public static function weekly( $market = 'products', $weeks = self::WEEKS, $now = null ) {
		global $wpdb;
		$now    = $now ? (int) $now : time();
		$monday = strtotime( 'monday this week', $now );
		$from   = $monday - ( $weeks - 1 ) * WEEK_IN_SECONDS;
		$fams   = self::families();
		$stats  = self::statuses();
		$sql    = "SELECT published_at, price_per_kg FROM {$wpdb->prefix}sm_products
			WHERE market = %s AND price_per_kg > 0 AND published_at IS NOT NULL AND published_at >= %s
			AND family IN (" . implode( ',', array_fill( 0, count( $fams ), '%s' ) ) . ')
			AND status IN (' . implode( ',', array_fill( 0, count( $stats ), '%s' ) ) . ')';
		$rows   = $wpdb->get_results( $wpdb->prepare( $sql, array_merge( array( $market, gmdate( 'Y-m-d 00:00:00', $from ) ), $fams, $stats ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- placeholders built above.
		$sums   = array();
		foreach ( (array) $rows as $r ) {
			$t = strtotime( $r->published_at . ' UTC' );
			$w = (int) floor( ( $t - $from ) / WEEK_IN_SECONDS );
			if ( $w < 0 || $w >= $weeks ) {
				continue;
			}
			$sums[ $w ][] = (float) $r->price_per_kg;
		}
		$out = array();
		for ( $w = 0; $w < $weeks; $w++ ) {
			$prices = $sums[ $w ] ?? array();
			if ( count( $prices ) < self::MIN_LISTINGS ) {
				continue;
			}
			$out[] = array(
				'start' => gmdate( 'Y-m-d', $from + $w * WEEK_IN_SECONDS ),
				'value' => round( array_sum( $prices ) / count( $prices ), 2 ),
				'count' => count( $prices ),
			);
		}
		return $out;
	}

	/** Average of the last 30 days for a market, or null with fewer than MIN_LISTINGS listings. */
	public static function recent_average( $market, $days = 30, $now = null ) {
		global $wpdb;
		$now   = $now ? (int) $now : time();
		$fams  = self::families();
		$stats = self::statuses();
		$sql   = "SELECT COUNT(*) AS n, AVG(price_per_kg) AS avg FROM {$wpdb->prefix}sm_products
			WHERE market = %s AND price_per_kg > 0 AND published_at IS NOT NULL AND published_at >= %s
			AND family IN (" . implode( ',', array_fill( 0, count( $fams ), '%s' ) ) . ')
			AND status IN (' . implode( ',', array_fill( 0, count( $stats ), '%s' ) ) . ')';
		$row   = $wpdb->get_row( $wpdb->prepare( $sql, array_merge( array( $market, gmdate( 'Y-m-d H:i:s', $now - $days * DAY_IN_SECONDS ) ), $fams, $stats ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- placeholders built above.
		return ( $row && (int) $row->n >= self::MIN_LISTINGS ) ? round( (float) $row->avg, 2 ) : null;
	}

	/**
	 * Data for the home page: { points, last, change (% over the curve), reuse } or
	 * an empty array when there are not enough weeks. Cached for an hour.
	 */
	public static function summary() {
		$cached = get_transient( self::CACHE );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		$points = self::weekly( 'products' );
		$out    = array();
		if ( count( $points ) >= self::MIN_POINTS ) {
			$first = $points[0]['value'];
			$last  = end( $points )['value'];
			$out   = array(
				'points' => $points,
				'last'   => $last,
				'change' => $first > 0 ? round( ( $last - $first ) / $first * 100, 1 ) : 0.0,
				'reuse'  => self::recent_average( 'reuse' ),
			);
		}
		set_transient( self::CACHE, $out, HOUR_IN_SECONDS );
		return $out;
	}

	/** Forget the cached curve (a listing went online or changed price). */
	public static function flush() {
		delete_transient( self::CACHE );
	}
}

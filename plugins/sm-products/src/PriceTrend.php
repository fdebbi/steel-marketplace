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
 * only with at least MIN_POINTS such weeks. Both thresholds are set in the
 * administration (0.9.0), where the automatic average can also be switched off.
 *
 * Reference prices (0.9.0): the administrator can enter a price per kg with
 * its date and source (a survey of suppliers, for example). They are shown
 * when the automatic average is off, or while it does not have enough
 * listings, and are always labelled as ReuSteel reference prices.
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
	const OPTION       = 'sm_products_price_index';
	const REFS         = 'sm_products_price_refs';
	const MAX_REFS     = 12;

	/** Settings of the index: automatic average on/off and its thresholds. */
	public static function settings() {
		$s = get_option( self::OPTION, array() );
		$s = is_array( $s ) ? $s : array();
		return array(
			'auto'         => isset( $s['auto'] ) ? (int) (bool) $s['auto'] : 1,
			'min_listings' => max( 1, (int) ( $s['min_listings'] ?? self::MIN_LISTINGS ) ),
			'min_weeks'    => max( 1, (int) ( $s['min_weeks'] ?? self::MIN_POINTS ) ),
		);
	}

	public static function save_settings( array $in ) {
		$out = array(
			'auto'         => empty( $in['auto'] ) ? 0 : 1,
			'min_listings' => min( 100, max( 1, (int) ( $in['min_listings'] ?? self::MIN_LISTINGS ) ) ),
			'min_weeks'    => min( self::WEEKS, max( 1, (int) ( $in['min_weeks'] ?? self::MIN_POINTS ) ) ),
		);
		update_option( self::OPTION, $out, false );
		self::flush();
		return $out;
	}

	/** Reference prices entered by the administrator, oldest first: { date, price, source }. */
	public static function refs() {
		$r = get_option( self::REFS, array() );
		$r = is_array( $r ) ? array_values( array_filter( $r, static fn( $x ) => is_array( $x ) && ! empty( $x['date'] ) && (float) ( $x['price'] ?? 0 ) > 0 ) ) : array();
		usort( $r, static fn( $a, $b ) => strcmp( $a['date'], $b['date'] ) );
		return $r;
	}

	/**
	 * Add a reference price; an entry with the same date is replaced (to correct it).
	 * Returns true, or a WP_Error.
	 */
	public static function add_ref( $date, $price, $source = '' ) {
		$d = \DateTime::createFromFormat( '!Y-m-d', (string) $date );
		if ( ! $d || $d->format( 'Y-m-d' ) !== (string) $date ) {
			return new \WP_Error( 'date', __( 'Enter a valid date.', 'sm-products' ) );
		}
		if ( $d->getTimestamp() > time() + DAY_IN_SECONDS ) {
			return new \WP_Error( 'date', __( 'The date cannot be in the future.', 'sm-products' ) );
		}
		$price = (float) str_replace( array( ' ', "\u{00A0}", ',' ), array( '', '', '.' ), (string) $price );
		if ( $price <= 0 || $price > 100000 ) {
			return new \WP_Error( 'price', __( 'Enter a price per kg greater than 0.', 'sm-products' ) );
		}
		$refs = array_filter( self::refs(), static fn( $r ) => $r['date'] !== (string) $date );
		$refs[] = array(
			'date'   => (string) $date,
			'price'  => round( $price, 2 ),
			'source' => mb_substr( sanitize_text_field( (string) $source ), 0, 160 ),
		);
		usort( $refs, static fn( $a, $b ) => strcmp( $a['date'], $b['date'] ) );
		update_option( self::REFS, array_values( $refs ), false );
		self::flush();
		return true;
	}

	public static function delete_ref( $date ) {
		update_option( self::REFS, array_values( array_filter( self::refs(), static fn( $r ) => $r['date'] !== (string) $date ) ), false );
		self::flush();
	}

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
	public static function weekly( $market = 'products', $weeks = self::WEEKS, $now = null, $min = null ) {
		$min    = null === $min ? self::settings()['min_listings'] : max( 1, (int) $min );
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
			if ( count( $prices ) < $min ) {
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
		return ( $row && (int) $row->n >= self::settings()['min_listings'] ) ? round( (float) $row->avg, 2 ) : null;
	}

	/**
	 * Data for the home page and the price index:
	 * {
	 *   mode:      auto (average of listings) | manual (reference prices) | none,
	 *   points:    list of { date (Y-m-d), value (DA/kg) }, oldest first (may be empty),
	 *   last:      latest value or null, last_date,
	 *   change:    % between the first and the last point (0 with one point),
	 *   source:    source of the latest reference price (manual),
	 *   reuse:     average of reused sections over 30 days, or null,
	 * }
	 * Cached for an hour; flushed when a listing, a setting or a reference price changes.
	 */
	public static function summary() {
		$cached = get_transient( self::CACHE );
		if ( is_array( $cached ) && isset( $cached['mode'] ) ) {
			return $cached;
		}
		$s      = self::settings();
		$out    = array( 'mode' => 'none', 'points' => array(), 'last' => null, 'last_date' => '', 'change' => 0.0, 'source' => '', 'reuse' => null );
		$points = array();
		if ( $s['auto'] ) {
			$weekly = self::weekly( 'products', self::WEEKS, null, $s['min_listings'] );
			if ( count( $weekly ) >= $s['min_weeks'] ) {
				$out['mode'] = 'auto';
				$points      = array_map( static fn( $p ) => array( 'date' => $p['start'], 'value' => (float) $p['value'] ), $weekly );
				$out['reuse'] = self::recent_average( 'reuse' );
			}
		}
		if ( 'none' === $out['mode'] ) {
			$refs = array_slice( self::refs(), -self::MAX_REFS );
			if ( $refs ) {
				$out['mode']   = 'manual';
				$points        = array_map( static fn( $r ) => array( 'date' => $r['date'], 'value' => (float) $r['price'] ), $refs );
				$out['source'] = (string) end( $refs )['source'];
			}
		}
		if ( $points ) {
			$first            = $points[0]['value'];
			$lastp            = end( $points );
			$out['points']    = array_values( $points );
			$out['last']      = $lastp['value'];
			$out['last_date'] = $lastp['date'];
			$out['change']    = ( count( $points ) > 1 && $first > 0 ) ? round( ( $lastp['value'] - $first ) / $first * 100, 1 ) : 0.0;
		}
		set_transient( self::CACHE, $out, HOUR_IN_SECONDS );
		return $out;
	}

	/** Forget the cached curve (a listing went online or changed price). */
	public static function flush() {
		delete_transient( self::CACHE );
	}
}

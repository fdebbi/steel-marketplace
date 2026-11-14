<?php
/**
 * Statistics of an account for its overview page (0.4.0): what it sold and
 * bought on the platform, in money, steel weight and number of orders.
 *
 * Only accepted orders and offers count. Payment is settled outside the
 * platform, so these are the amounts agreed, not money received.
 * The weight of an order is stored when it is made (weight_kg); for orders made
 * before 0.4.0 it is estimated once from the listing, when the listing allows it.
 * Orders without a steel weight (roofing per m²…) count in money, not in weight.
 *
 * @package SM\Trade
 */

namespace SM\Trade;

defined( 'ABSPATH' ) || exit;

final class Stats {

	/** Periods of the overview: key => [ length, bucket ]. */
	const PERIODS = array(
		'30d' => array( 30, 'day' ),
		'3m'  => array( 13, 'week' ),
		'12m' => array( 12, 'month' ),
		'all' => array( 0, 'month' ),
	);

	/**
	 * Steel weight of an order, from the listing at the moment of the order.
	 *
	 * @param object $p    Product row.
	 * @param string $mode lot|detail
	 * @param float  $qty  Quantity in $unit.
	 * @param string $unit kg|unit|lot|m2|ml…
	 */
	public static function weight_of( $p, $mode, $qty, $unit ) {
		$qty    = (float) $qty;
		$weight = (float) ( $p->total_weight ?? 0 );
		$pieces = (float) ( $p->pieces ?? 0 );
		if ( 'kg' === $unit ) {
			return round( $qty, 3 );
		}
		if ( 'lot' === $mode || 'lot' === $unit ) {
			return round( $weight, 3 );
		}
		if ( 'unit' === $unit ) {
			if ( $pieces > 0 && $weight > 0 ) {
				return round( $qty * $weight / $pieces, 3 );
			}
			$per_m = (float) ( $p->unit_weight ?? 0 );
			$len   = (float) ( $p->length_mm ?? 0 );
			if ( $per_m > 0 && $len > 0 ) {
				return round( $qty * $per_m * $len / 1000, 3 );
			}
		}
		return 0.0;
	}

	/** Orders made before 0.4.0: weight estimated from their listing, once. */
	public static function estimate_weights() {
		global $wpdb;
		$t    = Deals::table();
		$rows = (array) $wpdb->get_results( "SELECT id, product_id, mode, quantity, unit, items FROM {$t} WHERE weight_kg = 0" ); // phpcs:ignore WordPress.DB
		foreach ( $rows as $r ) {
			$p = \SM\Products\Products::get( (int) $r->product_id );
			$w = $p ? self::weight_of( $p, $r->mode, $r->quantity, $r->unit ) : ( 'kg' === $r->unit ? (float) $r->quantity : 0.0 );
			if ( $w > 0 ) {
				$wpdb->update( $t, array( 'weight_kg' => $w ), array( 'id' => (int) $r->id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}
		}
	}

	/**
	 * Statistics of an account for each period.
	 *
	 * @return array { periods: key => { buckets: [ { label, title, sold, bought, sold_kg, bought_kg, sold_n, bought_n } ],
	 *                 totals: { sold, bought, sold_kg, bought_kg, sold_n, bought_n, no_weight }, prev: { sold, bought }|null } }
	 */
	public static function for_account( $company_id, $user_id ) {
		global $wpdb;
		$t    = Deals::table();
		$rows = (array) $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT seller_company_id, buyer_user_id, buyer_company_id, amount, weight_kg, closed_at FROM {$t}
				WHERE status = 'accepted' AND closed_at IS NOT NULL AND ( seller_company_id = %d OR buyer_user_id = %d OR ( buyer_company_id = %d AND buyer_company_id > 0 ) )
				ORDER BY closed_at ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				(int) $company_id,
				(int) $user_id,
				(int) $company_id
			)
		);
		$tz    = wp_timezone();
		$now   = new \DateTimeImmutable( 'now', $tz );
		$deals = array();
		foreach ( $rows as $r ) {
			$deals[] = array(
				'at'     => ( new \DateTimeImmutable( $r->closed_at, new \DateTimeZone( 'UTC' ) ) )->setTimezone( $tz ),
				'sold'   => (int) $r->seller_company_id === (int) $company_id && (int) $company_id > 0,
				'amount' => (float) $r->amount,
				'kg'     => (float) $r->weight_kg,
			);
		}
		$out = array();
		foreach ( self::PERIODS as $key => $def ) {
			$out[ $key ] = self::period( $deals, $now, $def[0], $def[1], 'all' === $key );
		}
		return array( 'periods' => $out );
	}

	/** Buckets of one period, its totals and the totals of the period before. */
	private static function period( array $deals, \DateTimeImmutable $now, $count, $step, $all ) {
		$today = $now->setTime( 0, 0 );
		if ( 'day' === $step ) {
			$start = $today->modify( '-' . ( $count - 1 ) . ' days' );
		} elseif ( 'week' === $step ) {
			$start = $today->modify( '-' . ( $count * 7 - 1 ) . ' days' );
		} else {
			$first = $today->modify( 'first day of this month' );
			if ( $all ) {
				$oldest = $deals ? $deals[0]['at']->setTime( 0, 0 )->modify( 'first day of this month' ) : $first->modify( '-11 months' );
				$months = ( (int) $first->format( 'Y' ) - (int) $oldest->format( 'Y' ) ) * 12 + (int) $first->format( 'n' ) - (int) $oldest->format( 'n' ) + 1;
				$count  = max( 12, min( 60, $months ) );
			}
			$start = $first->modify( '-' . ( $count - 1 ) . ' months' );
		}
		$edges = array();
		for ( $i = 0; $i <= $count; $i++ ) {
			$edges[] = 'day' === $step ? $start->modify( "+{$i} days" ) : ( 'week' === $step ? $start->modify( '+' . ( $i * 7 ) . ' days' ) : $start->modify( "+{$i} months" ) );
		}
		$buckets = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$buckets[] = array(
				'label'     => self::label( $edges[ $i ], $step, false ),
				'title'     => self::label( $edges[ $i ], $step, true ),
				'sold'      => 0.0,
				'bought'    => 0.0,
				'sold_kg'   => 0.0,
				'bought_kg' => 0.0,
				'sold_n'    => 0,
				'bought_n'  => 0,
			);
		}
		$totals = array( 'sold' => 0.0, 'bought' => 0.0, 'sold_kg' => 0.0, 'bought_kg' => 0.0, 'sold_n' => 0, 'bought_n' => 0, 'no_weight' => 0 );
		$prev   = array( 'sold' => 0.0, 'bought' => 0.0 );
		// The period before, of the same length (no comparison for "all").
		$p_start = 'day' === $step ? $start->modify( "-{$count} days" ) : ( 'week' === $step ? $start->modify( '-' . ( $count * 7 ) . ' days' ) : $start->modify( "-{$count} months" ) );
		foreach ( $deals as $d ) {
			$side = $d['sold'] ? 'sold' : 'bought';
			if ( ! $all && $d['at'] >= $p_start && $d['at'] < $start ) {
				$prev[ $side ] += $d['amount'];
				continue;
			}
			if ( $d['at'] < $start ) {
				continue;
			}
			$i = $count - 1;
			for ( $k = 1; $k <= $count; $k++ ) {
				if ( $d['at'] < $edges[ $k ] ) {
					$i = $k - 1;
					break;
				}
			}
			$buckets[ $i ][ $side ]          += $d['amount'];
			$buckets[ $i ][ $side . '_kg' ]  += $d['kg'];
			++$buckets[ $i ][ $side . '_n' ];
			$totals[ $side ]                 += $d['amount'];
			$totals[ $side . '_kg' ]         += $d['kg'];
			++$totals[ $side . '_n' ];
			if ( $d['kg'] <= 0 ) {
				++$totals['no_weight'];
			}
		}
		foreach ( $buckets as &$b ) {
			foreach ( array( 'sold', 'bought', 'sold_kg', 'bought_kg' ) as $f ) {
				$b[ $f ] = round( $b[ $f ], 2 );
			}
		}
		unset( $b );
		return array(
			'buckets' => $buckets,
			'totals'  => array_map( static fn( $v ) => is_float( $v ) ? round( $v, 2 ) : $v, $totals ),
			'prev'    => $all ? null : array_map( static fn( $v ) => round( $v, 2 ), $prev ),
		);
	}

	/**
	 * Short and long label of a bucket, in the language of the page. Month names
	 * come from this plugin, so they follow the language of the visitor even when
	 * WordPress has no translation of its own installed.
	 */
	private static function label( \DateTimeImmutable $d, $step, $long ) {
		$m     = (int) $d->format( 'n' );
		$short = array( '', __( 'Jan', 'sm-trade' ), __( 'Feb', 'sm-trade' ), __( 'Mar', 'sm-trade' ), __( 'Apr', 'sm-trade' ), __( 'May', 'sm-trade' ), __( 'Jun', 'sm-trade' ), __( 'Jul', 'sm-trade' ), __( 'Aug', 'sm-trade' ), __( 'Sep', 'sm-trade' ), __( 'Oct', 'sm-trade' ), __( 'Nov', 'sm-trade' ), __( 'Dec', 'sm-trade' ) );
		$full  = array( '', __( 'January', 'sm-trade' ), __( 'February', 'sm-trade' ), __( 'March', 'sm-trade' ), __( 'April', 'sm-trade' ), __( 'May (month)', 'sm-trade' ), __( 'June', 'sm-trade' ), __( 'July', 'sm-trade' ), __( 'August', 'sm-trade' ), __( 'September', 'sm-trade' ), __( 'October', 'sm-trade' ), __( 'November', 'sm-trade' ), __( 'December', 'sm-trade' ) );
		$day   = $d->format( 'j' );
		if ( 'month' === $step ) {
			return $long ? $full[ $m ] . ' ' . $d->format( 'Y' ) : $short[ $m ];
		}
		if ( 'week' === $step ) {
			/* translators: %s: date of the first day of the week. */
			return $long ? sprintf( __( 'Week of %s', 'sm-trade' ), $day . ' ' . $full[ $m ] ) : $day . ' ' . $short[ $m ];
		}
		return $long ? $day . ' ' . $full[ $m ] . ' ' . $d->format( 'Y' ) : $day;
	}
}

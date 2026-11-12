<?php
/**
 * Periods of paid plans. A company without an active period is on the free
 * plan of its class. Paying again for the same plan extends the period; another
 * plan starts at once and replaces the current one.
 *
 * @package SM\Subscriptions
 */

namespace SM\Subscriptions;

use SM\Core\Accounts\Companies;

defined( 'ABSPATH' ) || exit;

final class Subscriptions {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sm_subscriptions';
	}

	private static function now() {
		return current_time( 'mysql', true );
	}

	/** Active period of a company, or null. */
	public static function current( $company_id ) {
		global $wpdb;
		$now = self::now();
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . " WHERE company_id = %d AND status = 'active' AND starts_at <= %s AND ends_at > %s ORDER BY ends_at DESC LIMIT 1", $company_id, $now, $now ) ); // phpcs:ignore WordPress.DB
	}

	/** History of a company, newest first. */
	public static function history( $company_id, $limit = 20 ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE company_id = %d ORDER BY id DESC LIMIT %d', $company_id, $limit ) ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Plan in force for a company: { class, code, name, data (limits), sub (row|null), has_plans }.
	 * Clients and partner engineers: has_plans false, code ''.
	 */
	public static function plan_of( $company ) {
		$company = is_object( $company ) ? $company : Companies::get( (int) $company );
		$class   = $company ? (string) $company->account_class : '';
		$plans   = Plans::for_class( $class );
		if ( ! $company || ! $plans ) {
			return array( 'class' => $class, 'code' => '', 'name' => '', 'data' => null, 'sub' => null, 'has_plans' => false );
		}
		$sub  = self::current( (int) $company->id );
		if ( $sub ) {
			$sub->paid_until = self::paid_until( (int) $company->id, $sub->plan );
		}
		$code = $sub && isset( $plans[ $sub->plan ] ) ? $sub->plan : 'free';
		return array( 'class' => $class, 'code' => $code, 'name' => Plans::name( $code, $class ), 'data' => $plans[ $code ], 'sub' => $sub, 'has_plans' => true );
	}

	/** End of the last period paid in advance for the same plan (renewals follow each other). */
	public static function paid_until( $company_id, $plan ) {
		global $wpdb;
		return (string) $wpdb->get_var( $wpdb->prepare( 'SELECT MAX(ends_at) FROM ' . self::table() . " WHERE company_id = %d AND plan = %s AND status = 'active'", $company_id, $plan ) ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Start or extend a plan. $months: length of the period.
	 * $args: period (month|year), price, founder (bool), order_id, note.
	 *
	 * @return int Subscription id.
	 */
	public static function activate( $company_id, $plan, $months, array $args = array() ) {
		global $wpdb;
		$args = wp_parse_args( $args, array( 'period' => 'year', 'price' => 0, 'founder' => 0, 'order_id' => 0, 'note' => '' ) );
		$cur  = self::current( $company_id );
		$from = time();
		if ( $cur && $cur->plan === $plan ) {
			$from = strtotime( $cur->ends_at . ' UTC' ); // Same plan: the new period follows the current one.
		} elseif ( $cur ) {
			$wpdb->update( self::table(), array( 'status' => 'replaced', 'ends_at' => self::now() ), array( 'id' => $cur->id ) );
		}
		$start = gmdate( 'Y-m-d H:i:s', $from );
		$end   = gmdate( 'Y-m-d H:i:s', strtotime( '+' . (int) $months . ' months', $from ) );
		$wpdb->insert(
			self::table(),
			array(
				'company_id' => (int) $company_id,
				'plan'       => $plan,
				'period'     => $args['period'],
				'status'     => 'active',
				'price'      => (float) $args['price'],
				'founder'    => $args['founder'] ? 1 : 0,
				'order_id'   => (int) $args['order_id'],
				'starts_at'  => $start,
				'ends_at'    => $end,
				'note'       => mb_substr( (string) $args['note'], 0, 255 ),
				'created_at' => self::now(),
			)
		);
		$id = (int) $wpdb->insert_id;
		do_action( 'sm_subscription_activated', $id, (int) $company_id, $plan );
		return $id;
	}

	/** Stop the active period now (administrator). */
	public static function cancel( $company_id, $note = '' ) {
		global $wpdb;
		$cur = self::current( $company_id );
		if ( ! $cur ) {
			return false;
		}
		$wpdb->update( self::table(), array( 'status' => 'cancelled', 'ends_at' => self::now(), 'note' => mb_substr( (string) $note, 0, 255 ) ), array( 'id' => $cur->id ) );
		do_action( 'sm_subscription_ended', (int) $company_id, $cur );
		return true;
	}

	/** Close the periods that ended (daily). Returns the companies concerned. */
	public static function expire() {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . " WHERE status = 'active' AND ends_at <= %s", self::now() ) ); // phpcs:ignore WordPress.DB
		$out  = array();
		foreach ( $rows as $r ) {
			$wpdb->update( self::table(), array( 'status' => 'expired' ), array( 'id' => $r->id ) );
			if ( ! self::current( (int) $r->company_id ) ) {
				$out[] = (int) $r->company_id;
				do_action( 'sm_subscription_ended', (int) $r->company_id, $r );
			}
		}
		return $out;
	}

	/** Periods ending within $days that were not reminded yet. */
	public static function ending_soon( $days ) {
		global $wpdb;
		$now = self::now();
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . " s WHERE s.status = 'active' AND s.reminded = 0 AND s.ends_at > %s AND s.ends_at <= %s AND NOT EXISTS (SELECT 1 FROM " . self::table() . " n WHERE n.company_id = s.company_id AND n.status = 'active' AND n.starts_at >= s.ends_at)", $now, gmdate( 'Y-m-d H:i:s', time() + $days * DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB
	}

	public static function mark_reminded( $id ) {
		global $wpdb;
		$wpdb->update( self::table(), array( 'reminded' => 1 ), array( 'id' => (int) $id ) );
	}

	/** Number of founding members (accounts that received the offer). */
	public static function founders_count() {
		global $wpdb;
		return (int) $wpdb->get_var( 'SELECT COUNT(DISTINCT company_id) FROM ' . self::table() . ' WHERE founder = 1' ); // phpcs:ignore WordPress.DB
	}

	public static function is_founder( $company_id ) {
		global $wpdb;
		return (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::table() . ' WHERE company_id = %d AND founder = 1 LIMIT 1', $company_id ) ); // phpcs:ignore WordPress.DB
	}

	/** Active paid periods, for the administration. */
	public static function active_list( $limit = 200 ) {
		global $wpdb;
		$now = self::now();
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . " WHERE status = 'active' AND ends_at > %s ORDER BY ends_at ASC LIMIT %d", $now, $limit ) ); // phpcs:ignore WordPress.DB
	}
}

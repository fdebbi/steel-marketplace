<?php
/**
 * AI credits. Every account gets the credits of its plan each month; since
 * 0.3.0 the free plan and the accounts without plans get none (they can buy
 * packs). Bought credits are kept until used and are only used once the
 * monthly credits are spent. One credit = one use of an AI service; the smart
 * search is free (sm-ai 0.2.0).
 *
 * @package SM\Subscriptions
 */

namespace SM\Subscriptions;

use SM\Core\Accounts\Companies;

defined( 'ABSPATH' ) || exit;

final class Credits {

	/** Monthly credits of accounts without a plan (clients, partner engineers): none since 0.3.0. */
	const NO_PLAN_MONTHLY = 0;

	/** Option of the transition month: the free credits of before stay until this date (0.3.0). */
	const GRACE_OPTION = 'sm_sub_ai_grace';

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sm_sub_credits';
	}

	/** Bought credits left. */
	public static function balance( $company_id ) {
		global $wpdb;
		return max( 0, (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(delta),0) FROM ' . self::table() . ' WHERE company_id = %d', $company_id ) ) ); // phpcs:ignore WordPress.DB
	}

	public static function add( $company_id, $credits, $reason = 'pack', $order_id = 0 ) {
		global $wpdb;
		return (bool) $wpdb->insert( self::table(), array( 'company_id' => (int) $company_id, 'delta' => (int) $credits, 'reason' => $reason, 'order_id' => (int) $order_id, 'created_at' => current_time( 'mysql', true ) ) );
	}

	/** Credits of the plan this month. */
	public static function monthly( $company ) {
		$plan = Subscriptions::plan_of( $company );
		if ( ! $plan['has_plans'] ) {
			return max( (int) apply_filters( 'sm_sub_no_plan_ai_credits', self::NO_PLAN_MONTHLY ), self::grace( 'no_plan' ) );
		}
		$n = (int) $plan['data']['ai_credits'];
		if ( Plans::UNLIMITED === $n ) {
			return PHP_INT_MAX;
		}
		return 'free' === $plan['code'] ? max( $n, self::grace( $plan['class'] ) ) : $n;
	}

	/**
	 * Free credits kept until the end of the month when the free plan lost its
	 * credits (0.3.0): the accounts keep what they were promised this month.
	 */
	public static function grace( $key ) {
		$g = get_option( self::GRACE_OPTION );
		if ( ! is_array( $g ) || empty( $g['until'] ) || time() >= (int) $g['until'] ) {
			return 0;
		}
		return (int) ( $g['credits'][ $key ] ?? 0 );
	}

	/** Credits used since the 1st of the month (all AI services). */
	public static function used_month( $company_id ) {
		return Compat::ready( 'ai' ) ? \SM\Ai\Usage::company_month_total( (int) $company_id ) : 0;
	}

	/** { monthly, used, monthly_left, bought, total_left } */
	public static function status( $company ) {
		$company = is_object( $company ) ? $company : Companies::get( (int) $company );
		if ( ! $company ) {
			return array( 'monthly' => 0, 'used' => 0, 'monthly_left' => 0, 'bought' => 0, 'total_left' => 0 );
		}
		$monthly = self::monthly( $company );
		$used    = self::used_month( (int) $company->id );
		$bought  = self::balance( (int) $company->id );
		$left    = max( 0, $monthly - $used );
		return array( 'monthly' => $monthly, 'used' => $used, 'monthly_left' => $left, 'bought' => $bought, 'total_left' => PHP_INT_MAX === $monthly ? PHP_INT_MAX : $left + $bought );
	}

	/**
	 * After a use: the credits of the month go first, then the bought ones
	 * (0.5.1: a tool can cost several credits; the log already counts this use).
	 */
	public static function on_used( $feature, $company_id, $credits = 1 ) {
		if ( ! $company_id ) {
			return;
		}
		$company = Companies::get( (int) $company_id );
		if ( ! $company ) {
			return;
		}
		$credits = max( 1, (int) $credits );
		$monthly = self::monthly( $company );
		if ( PHP_INT_MAX === $monthly ) {
			return;
		}
		$before = self::used_month( (int) $company_id ) - $credits; // Used before this call.
		$left   = max( 0, $monthly - $before );                      // Credits of the month that were left.
		$take   = min( max( 0, $credits - $left ), self::balance( (int) $company_id ) );
		if ( $take > 0 ) {
			self::add( (int) $company_id, -$take, 'used:' . sanitize_key( $feature ) );
		}
	}

	/** Value of one AI credit in DA (0.5.1): the cheapest pack per credit; 0 without packs. */
	public static function value( $value = 0 ) {
		$best = 0.0;
		foreach ( (array) Settings::all()['packs'] as $p ) {
			if ( (int) $p['credits'] > 0 && (float) $p['price'] > 0 ) {
				$v    = (float) $p['price'] / (int) $p['credits'];
				$best = $best > 0 ? min( $best, $v ) : $v;
			}
		}
		return $best > 0 ? round( $best, 2 ) : $value;
	}
}

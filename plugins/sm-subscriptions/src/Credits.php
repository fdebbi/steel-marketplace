<?php
/**
 * AI credits. Every account gets the credits of its plan each month (free
 * accounts too); bought credits are kept until used and are only used once the
 * monthly credits are spent. One credit = one use of an AI service.
 *
 * @package SM\Subscriptions
 */

namespace SM\Subscriptions;

use SM\Core\Accounts\Companies;

defined( 'ABSPATH' ) || exit;

final class Credits {

	/** Monthly credits of accounts without a plan (clients, partner engineers). */
	const NO_PLAN_MONTHLY = 5;

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
			return (int) apply_filters( 'sm_sub_no_plan_ai_credits', self::NO_PLAN_MONTHLY );
		}
		$n = (int) $plan['data']['ai_credits'];
		return Plans::UNLIMITED === $n ? PHP_INT_MAX : $n;
	}

	/** Credits used since the 1st of the month (all AI services). */
	public static function used_month( $company_id ) {
		return class_exists( '\\SM\\Ai\\Usage' ) ? \SM\Ai\Usage::company_month_total( (int) $company_id ) : 0;
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

	/** After a use: once the monthly credits are spent, one bought credit is taken. */
	public static function on_used( $feature, $company_id ) {
		if ( ! $company_id ) {
			return;
		}
		$company = Companies::get( (int) $company_id );
		if ( $company && self::used_month( (int) $company_id ) > self::monthly( $company ) && self::balance( (int) $company_id ) > 0 ) {
			self::add( (int) $company_id, -1, 'used:' . sanitize_key( $feature ) );
		}
	}
}

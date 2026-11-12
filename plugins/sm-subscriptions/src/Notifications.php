<?php
/**
 * Notifications of plans and orders (on the site and by e-mail, in the
 * recipient's language, through SM Core).
 *
 * @package SM\Subscriptions
 */

namespace SM\Subscriptions;

use SM\Core\Notifications\Notifications as Notify;
use SM\Core\Routing\Routes as CoreRoutes;

defined( 'ABSPATH' ) || exit;

final class Notifications {

	public static function init() {
		add_filter( 'sm_notification_types', array( __CLASS__, 'types' ) );
		add_action( 'sm_sub_order_review', array( __CLASS__, 'on_review' ) );
		add_action( 'sm_sub_order_paid', array( __CLASS__, 'on_paid' ) );
		add_action( 'sm_sub_order_rejected', array( __CLASS__, 'on_rejected' ) );
		add_action( 'sm_sub_ending', array( __CLASS__, 'on_ending' ) );
		add_action( 'sm_subscription_ended', array( __CLASS__, 'on_ended' ), 20, 2 );
		add_action( 'sm_sub_founder', array( __CLASS__, 'on_founder' ), 10, 2 );
		add_action( 'sm_sub_paused', array( __CLASS__, 'on_paused' ), 10, 2 );
	}

	public static function plan_link() {
		return CoreRoutes::url( 'account', array( 'view' => 'plan' ) );
	}

	public static function types( $types ) {
		$order = static fn( $p ) => Orders::get( (int) ( $p['order_id'] ?? 0 ) );
		$label = static fn( $p ) => ( $o = Orders::get( (int) ( $p['order_id'] ?? 0 ) ) ) ? $o->ref . ' — ' . $o->label : '';
		$def   = static fn( $subject, $body, $link, $email = true ) => array( 'email' => $email, 'subject' => $subject, 'body' => $body, 'link' => $link );
		$plan  = static fn( $p ) => self::plan_link();

		$types['sub.review']   = $def(
			static fn( $p ) => __( 'Proof of payment to check', 'sm-subscriptions' ),
			/* translators: %s: order reference and label. */
			static fn( $p ) => sprintf( __( 'A proof of payment was sent for %s. Check it and confirm the payment.', 'sm-subscriptions' ), $label( $p ) ),
			static fn( $p ) => admin_url( 'admin.php?page=sm-subscriptions&order=' . (int) ( $p['order_id'] ?? 0 ) ),
			false
		);
		$types['sub.paid']     = $def(
			static fn( $p ) => __( 'Payment confirmed', 'sm-subscriptions' ),
			/* translators: %s: order reference and label. */
			static fn( $p ) => sprintf( __( 'Your payment for %s is confirmed. Your invoice is in your account.', 'sm-subscriptions' ), $label( $p ) ),
			$plan
		);
		$types['sub.rejected'] = $def(
			static fn( $p ) => __( 'Payment not accepted', 'sm-subscriptions' ),
			/* translators: 1: order reference and label, 2: reason. */
			static fn( $p ) => sprintf( __( 'Your payment for %1$s could not be confirmed. Reason: %2$s', 'sm-subscriptions' ), $label( $p ), ( $o = $order( $p ) ) ? $o->reject_reason : '' ),
			$plan
		);
		$types['sub.ending']   = $def(
			static fn( $p ) => __( 'Your plan ends soon', 'sm-subscriptions' ),
			/* translators: 1: plan name, 2: date. */
			static fn( $p ) => sprintf( __( 'Your plan %1$s ends on %2$s. Renew it to keep your limits; otherwise what goes beyond the free plan will be paused.', 'sm-subscriptions' ), (string) ( $p['plan_name'] ?? '' ), (string) ( $p['ends'] ?? '' ) ),
			$plan
		);
		$types['sub.ended']    = $def(
			static fn( $p ) => __( 'Your plan has ended', 'sm-subscriptions' ),
			/* translators: %s: plan name. */
			static fn( $p ) => sprintf( __( 'Your plan %s has ended. Your account is back on the free plan.', 'sm-subscriptions' ), (string) ( $p['plan_name'] ?? '' ) ),
			$plan
		);
		$types['sub.paused']   = $def(
			static fn( $p ) => __( 'Some listings were paused', 'sm-subscriptions' ),
			/* translators: %d: number of listings or service pages. */
			static fn( $p ) => sprintf( _n( '%d listing or service page went beyond your plan and was paused. Nothing was deleted: choose a plan or publish it again when there is room.', '%d listings or service pages went beyond your plan and were paused. Nothing was deleted: choose a plan or publish them again when there is room.', (int) ( $p['count'] ?? 0 ), 'sm-subscriptions' ), (int) ( $p['count'] ?? 0 ) ),
			$plan
		);
		$types['sub.founder']  = $def(
			static fn( $p ) => __( 'Welcome, founding member', 'sm-subscriptions' ),
			/* translators: 1: plan name, 2: number of months. */
			static fn( $p ) => sprintf( __( 'Thank you for joining early: your account gets the plan %1$s free for %2$d months, and you will keep its price afterwards.', 'sm-subscriptions' ), (string) ( $p['plan_name'] ?? '' ), (int) ( $p['months'] ?? 0 ) ),
			$plan
		);
		return $types;
	}

	public static function on_review( $order ) {
		Notify::notify_moderators( 'sub.review', array( 'order_id' => (int) $order->id ) );
	}

	public static function on_paid( $order ) {
		Notify::notify_company( (int) $order->company_id, 'sub.paid', array( 'order_id' => (int) $order->id ) );
	}

	public static function on_rejected( $order ) {
		Notify::notify_company( (int) $order->company_id, 'sub.rejected', array( 'order_id' => (int) $order->id ) );
	}

	public static function on_ending( $sub ) {
		$c = \SM\Core\Accounts\Companies::get( (int) $sub->company_id );
		Notify::notify_company( (int) $sub->company_id, 'sub.ending', array( 'plan_name' => Plans::name( $sub->plan, $c ? $c->account_class : 'company' ), 'ends' => wp_date( 'd/m/Y', strtotime( $sub->ends_at . ' UTC' ) ) ) );
	}

	public static function on_ended( $company_id, $sub = null ) {
		if ( ! $sub ) {
			return;
		}
		$c = \SM\Core\Accounts\Companies::get( (int) $company_id );
		Notify::notify_company( (int) $company_id, 'sub.ended', array( 'plan_name' => Plans::name( $sub->plan, $c ? $c->account_class : 'company' ) ) );
	}

	public static function on_founder( $company_id, $code ) {
		$c = \SM\Core\Accounts\Companies::get( (int) $company_id );
		Notify::notify_company( (int) $company_id, 'sub.founder', array( 'plan_name' => Plans::name( $code, $c ? $c->account_class : 'company' ), 'months' => (int) Settings::get( 'founders_months' ) ) );
	}

	public static function on_paused( $company_id, $count ) {
		Notify::notify_company( (int) $company_id, 'sub.paused', array( 'count' => (int) $count ) );
	}
}

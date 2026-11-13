<?php
/**
 * Payment of a study by the client (0.3.0), through the orders of SM
 * Subscriptions: the client pays the advance once the quote is accepted, and the
 * balance to see the deliverables. Each payment is an order of kind "mission"
 * (item advance|balance, target = mission id) with the payment details of the
 * platform, the proof of payment, the check by the team and the invoice.
 *
 * Without SM Subscriptions, the platform records the payments by hand on the
 * mission page.
 *
 * @package SM\Engineering
 */

namespace SM\Engineering;

use SM\Core\Routing\Routes as CoreRoutes;

defined( 'ABSPATH' ) || exit;

final class Payments {

	const KIND = 'mission';

	public static function init() {
		add_filter( 'sm_sub_order_quote', array( __CLASS__, 'quote' ), 10, 6 );
		add_filter( 'sm_sub_order_label', array( __CLASS__, 'label' ), 10, 2 );
		add_filter( 'sm_sub_order_back', array( __CLASS__, 'back' ), 10, 2 );
		add_action( 'sm_sub_order_paid', array( __CLASS__, 'paid' ) );
	}

	/** What the client pays now for a mission: { label, amount, tva_rate } or a WP_Error. */
	public static function quote( $result, $company, $kind, $item, $period = '', $target = 0 ) {
		if ( self::KIND !== $kind ) {
			return $result;
		}
		$m = Missions::get( (int) $target );
		if ( ! $m || ! $company || (int) $m->client_id !== (int) $company->id ) {
			return new \WP_Error( 'mission', __( 'This study request is not yours.', 'sm-engineering' ) );
		}
		$t = Missions::totals( $m );
		if ( 'advance' === $item && $t['advance_due'] ) {
			return array( 'label' => self::text( $m, 'advance' ), 'amount' => $t['advance'], 'tva_rate' => $t['tva_rate'] );
		}
		if ( 'balance' === $item && $t['balance_due'] ) {
			return array( 'label' => self::text( $m, 'balance' ), 'amount' => $t['balance'], 'tva_rate' => $t['tva_rate'] );
		}
		return new \WP_Error( 'mission', __( 'Nothing is to pay for this study now.', 'sm-engineering' ) );
	}

	private static function text( $m, $item ) {
		$t = Missions::totals( $m );
		return 'advance' === $item
			/* translators: 1: percentage, 2: mission reference. */
			? sprintf( __( 'Advance %1$s %% — engineering study %2$s', 'sm-engineering' ), rtrim( rtrim( number_format( $t['advance_pct'], 2, '.', '' ), '0' ), '.' ), $m->ref )
			/* translators: %s: mission reference. */
			: sprintf( __( 'Balance — engineering study %s', 'sm-engineering' ), $m->ref );
	}

	public static function label( $label, $order ) {
		if ( self::KIND !== $order->kind ) {
			return $label;
		}
		$m = Missions::get( (int) $order->target_id );
		return $m ? self::text( $m, $order->item ) : $label;
	}

	public static function back( $url, $order ) {
		return self::KIND === $order->kind ? Notifications::link( 'client', (int) $order->target_id ) : $url;
	}

	/** An order of the mission is paid (checked by the platform team). */
	public static function paid( $order ) {
		if ( self::KIND !== $order->kind ) {
			return;
		}
		Missions::payment_received( Missions::get( (int) $order->target_id ), 'advance' === $order->item ? 'advance' : 'balance' );
	}

	/** Orders of a mission: item => latest order (any status). */
	public static function orders( $m ) {
		if ( ! Settings::payments_ready() ) {
			return array();
		}
		$out = array();
		foreach ( \SM\Subscriptions\Orders::query( array( 'company' => (int) $m->client_id, 'limit' => 200 ) ) as $o ) {
			if ( self::KIND === $o->kind && (int) $o->target_id === (int) $m->id && ! isset( $out[ $o->item ] ) ) {
				$out[ $o->item ] = $o; // Newest first.
			}
		}
		return $out;
	}

	/** True when a proof of payment of the advance is waiting for the check. */
	public static function has_proof( $m ) {
		$o = self::orders( $m )['advance'] ?? null;
		return $o && 'review' === $o->status;
	}

	/**
	 * The open order of what is due now (advance or balance), created if needed.
	 *
	 * @return int|\WP_Error Order id.
	 */
	public static function order_for( $m, $item, $user_id ) {
		if ( ! Settings::payments_ready() ) {
			return new \WP_Error( 'payments', __( 'Online payment details are not available yet: the platform contacts you.', 'sm-engineering' ) );
		}
		$o = self::orders( $m )[ $item ] ?? null;
		if ( $o && in_array( $o->status, array( 'awaiting', 'review' ), true ) ) {
			return (int) $o->id;
		}
		$company = \SM\Core\Accounts\Companies::get( (int) $m->client_id );
		return \SM\Subscriptions\Orders::create( $company, $user_id, self::KIND, $item, '', (int) $m->id );
	}

	/** Cancel the orders still waiting for a payment (the quote is cancelled). */
	public static function cancel_open( $m ) {
		foreach ( self::orders( $m ) as $o ) {
			if ( 'awaiting' === $o->status ) {
				\SM\Subscriptions\Orders::cancel( $o );
			}
		}
	}

	/** Address of the order page. */
	public static function order_url( $order_id ) {
		return CoreRoutes::url( 'account', array( 'view' => 'order', 'order' => (int) $order_id ) );
	}

	/** State of the payments for the client page: { item => { amount, paid, status, label, url } }. */
	public static function state( $m ) {
		$t      = Missions::totals( $m );
		$orders = self::orders( $m );
		$labels = Settings::payments_ready() ? \SM\Subscriptions\Orders::status_labels() : array();
		$out    = array();
		foreach ( array( 'advance' => array( $t['advance'], $t['advance_paid'], $t['advance_due'] ), 'balance' => array( $t['balance'], $t['balance_paid'], $t['balance_due'] ) ) as $k => list( $amount, $paid, $due ) ) {
			$o         = $orders[ $k ] ?? null;
			$out[ $k ] = array(
				'amount' => $amount,
				'paid'   => (bool) $paid,
				'due'    => (bool) $due,
				'status' => $o ? $o->status : '',
				'label'  => $o ? ( $labels[ $o->status ] ?? $o->status ) : '',
				'url'    => $o ? self::order_url( $o->id ) : '',
			);
		}
		return $out;
	}
}

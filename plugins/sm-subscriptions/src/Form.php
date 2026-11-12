<?php
/**
 * Front-end form handlers (admin-post.php):
 *
 *   sm_sub_order    order a plan (period month|year), an AI credit pack or a listing option
 *   sm_sub_proof    send the proof of payment of an order (picture or PDF) with the method used
 *   sm_sub_cancel   cancel an order still waiting for payment
 *
 * @package SM\Subscriptions
 */

namespace SM\Subscriptions;

use SM\Core\Accounts\Companies;
use SM\Core\Content\Media;
use SM\Core\Routing\Routes as CoreRoutes;
use SM\Core\Support\Forms;

defined( 'ABSPATH' ) || exit;

final class Form {

	public static function init() {
		foreach ( array( 'sub_order', 'sub_proof', 'sub_cancel' ) as $f ) {
			add_action( 'admin_post_sm_' . $f, array( __CLASS__, 'handle_' . $f ) );
		}
	}

	/** Company of the current user that may order (owner or manager), or null. */
	public static function company() {
		$user_id = get_current_user_id();
		$company = $user_id ? Companies::for_user( $user_id ) : null;
		return $company && Companies::can_manage( $company->id, $user_id ) ? $company : null;
	}

	/** Order of the current user's company, or null. */
	private static function own_order( $id ) {
		$company = self::company();
		$order   = Orders::get( (int) $id );
		return $company && $order && (int) $order->company_id === (int) $company->id ? $order : null;
	}

	private static function plan_url( array $args = array() ) {
		return CoreRoutes::url( 'account', array_merge( array( 'view' => 'plan' ), $args ) );
	}

	public static function handle_sub_order() {
		Forms::begin( 'sub_order' );
		$company = self::company();
		if ( ! $company ) {
			Forms::back( array( __( 'Only the owner or a manager of the account can order.', 'sm-subscriptions' ) ) );
		}
		$kind = sanitize_key( Forms::post( 'kind' ) );
		if ( 'plan' === $kind && ! Plans::for_class( $company->account_class ) ) {
			Forms::back( array( __( 'Your type of account has no plan. You can buy AI credits.', 'sm-subscriptions' ) ) );
		}
		// One open order of the same thing at a time.
		foreach ( Orders::query( array( 'company' => $company->id, 'status' => array( 'awaiting', 'review' ) ) ) as $o ) {
			if ( $o->kind === $kind && $o->item === sanitize_key( Forms::post( 'item' ) ) && (int) $o->target_id === absint( Forms::post( 'target' ) ) ) {
				Forms::back( array(), '', self::plan_url( array( 'order' => $o->id ) ) );
			}
		}
		$id = Orders::create( $company, get_current_user_id(), $kind, sanitize_key( Forms::post( 'item' ) ), sanitize_key( Forms::post( 'period' ) ), absint( Forms::post( 'target' ) ) );
		if ( is_wp_error( $id ) ) {
			Forms::back( array( $id->get_error_message() ) );
		}
		Forms::back( array(), __( 'Order created. Pay with one of the methods below, then send the proof of payment.', 'sm-subscriptions' ), self::plan_url( array( 'order' => $id ) ) );
	}

	public static function handle_sub_proof() {
		Forms::begin( 'sub_proof' );
		$order = self::own_order( absint( Forms::post( 'order_id' ) ) );
		if ( ! $order || ! in_array( $order->status, array( 'awaiting', 'review' ), true ) ) {
			Forms::back( array( __( 'This order cannot receive a proof of payment.', 'sm-subscriptions' ) ) );
		}
		$method = sanitize_key( Forms::post( 'method' ) );
		$errors = array();
		if ( ! isset( Settings::methods()[ $method ] ) ) {
			$errors['method'] = __( 'Choose how you paid.', 'sm-subscriptions' );
		}
		$up = Media::upload( 'proof', array_merge( Media::IMAGE_TYPES, Media::PDF_TYPES ), 1, 10 * 1048576 );
		if ( ! $up['ids'] ) {
			$errors['proof'] = $up['errors'] ? implode( ' ', $up['errors'] ) : __( 'Add the receipt, the transfer slip or a screenshot of the payment.', 'sm-subscriptions' );
		}
		if ( $errors ) {
			Forms::back( $errors );
		}
		update_post_meta( $up['ids'][0], '_sm_owner', get_current_user_id() );
		Orders::send_proof( $order, $up['ids'][0], $method, Forms::post_textarea( 'note' ) );
		Forms::back( array(), __( 'Thank you. The platform team checks your payment, usually within one working day, and you are told by e-mail.', 'sm-subscriptions' ), self::plan_url( array( 'order' => $order->id ) ) );
	}

	public static function handle_sub_cancel() {
		Forms::begin( 'sub_cancel' );
		$order = self::own_order( absint( Forms::post( 'order_id' ) ) );
		if ( ! $order || 'awaiting' !== $order->status ) {
			Forms::back( array( __( 'This order cannot be cancelled.', 'sm-subscriptions' ) ) );
		}
		Orders::cancel( $order );
		Forms::back( array(), __( 'Order cancelled.', 'sm-subscriptions' ), self::plan_url() );
	}
}

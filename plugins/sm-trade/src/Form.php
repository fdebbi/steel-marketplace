<?php
/**
 * Front-end form handlers (admin-post.php):
 *
 *   sm_trade_create  "Buy" or "Make an offer" from a listing page
 *   sm_trade_reply   accept, refuse, propose another price, or cancel
 *
 * @package SM\Trade
 */

namespace SM\Trade;

use SM\Core\Routing\Routes as CoreRoutes;
use SM\Core\Support\Forms;
use SM\Products\Products;

defined( 'ABSPATH' ) || exit;

final class Form {

	public static function init() {
		add_action( 'admin_post_sm_trade_create', array( __CLASS__, 'handle_create' ) );
		add_action( 'admin_post_sm_trade_reply', array( __CLASS__, 'handle_reply' ) );
	}

	/** Prices typed with spaces, dots or commas ("1 200 000", "1.200.000", "1200000,50"). */
	public static function amount( $raw ) {
		$raw = preg_replace( '/[\s\x{00A0}\x{202F}]/u', '', (string) $raw );
		if ( preg_match( '/^\d{1,3}(\.\d{3})+(,\d+)?$/', $raw ) ) {
			$raw = str_replace( '.', '', $raw );
		}
		return (float) str_replace( ',', '.', $raw );
	}

	public static function handle_create() {
		Forms::begin( 'trade_create' );
		$user_id = get_current_user_id();
		$allowed = sm_can_explain( 'buy' );
		if ( is_wp_error( $allowed ) ) {
			Forms::back( array( $allowed->get_error_message() ) );
		}
		if ( ! Forms::rate_limit( 'trade|' . $user_id, 20, HOUR_IN_SECONDS ) ) {
			Forms::back( array( __( 'You sent many orders or offers in a short time. Try again later.', 'sm-trade' ) ) );
		}
		$product = Products::get( absint( Forms::post( 'product_id' ) ) );
		$id      = Deals::create(
			$product ? $product->id : 0,
			$user_id,
			array(
				'kind'     => Forms::post( 'kind' ),
				'mode'     => Forms::post( 'mode' ),
				'quantity' => self::amount( Forms::post( 'quantity' ) ),
				'amount'   => self::amount( Forms::post( 'amount' ) ),
				'amount_kg' => self::amount( Forms::post( 'amount_kg' ) ),
				'items'    => isset( $_POST['items'] ) && is_array( $_POST['items'] ) ? array_map( 'absint', wp_unslash( $_POST['items'] ) ) : array(), // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput -- nonce checked by Forms::begin(); numbers only.
				'name'     => Forms::post( 'name' ),
				'phone'    => Forms::post( 'phone' ),
				'email'    => Forms::post( 'email' ),
				'message'  => Forms::post_textarea( 'message' ),
			)
		);
		if ( is_wp_error( $id ) ) {
			$open = 'open' === $id->get_error_code() && $product ? Deals::open_for( $product->id, $user_id ) : null;
			if ( $open ) {
				Forms::back( array(), $id->get_error_message(), Notifications::deal_url( $open->id ) );
			}
			$field = in_array( $id->get_error_code(), array( 'qty', 'amount', 'amount_kg', 'items', 'name', 'phone', 'email' ), true ) ? array( 'qty' === $id->get_error_code() ? 'quantity' : $id->get_error_code() => $id->get_error_message() ) : array( $id->get_error_message() );
			Forms::back( $field );
		}
		$deal = Deals::get( $id );
		Forms::back(
			array(),
			'offer' === $deal->kind
				? __( 'Offer sent to the seller. You will be told by e-mail when the seller answers.', 'sm-trade' )
				: __( 'Order sent to the seller. Nothing is paid on the platform: the seller accepts or refuses, then you agree on payment and delivery directly.', 'sm-trade' ),
			Notifications::deal_url( $id )
		);
	}

	public static function handle_reply() {
		Forms::begin( 'trade_reply' );
		$deal_id = absint( Forms::post( 'deal_id' ) );
		$action  = sanitize_key( Forms::post( 'do' ) );
		$res     = Deals::act( $deal_id, get_current_user_id(), $action, self::amount( Forms::post( 'amount' ) ), Forms::post_textarea( 'note' ) );
		if ( is_wp_error( $res ) ) {
			Forms::back( 'amount' === $res->get_error_code() ? array( 'amount' => $res->get_error_message() ) : array( $res->get_error_message() ) );
		}
		$msg = array(
			'accept'  => __( 'Accepted. The contact details are below: agree on payment and delivery directly.', 'sm-trade' ),
			'refuse'  => __( 'Refused. The other party was told.', 'sm-trade' ),
			'cancel'  => __( 'Cancelled. The seller was told.', 'sm-trade' ),
			'counter' => __( 'Your price was sent. You will be told when the other party answers.', 'sm-trade' ),
		)[ $action ] ?? '';
		Forms::back( array(), $msg, Notifications::deal_url( $deal_id ) );
	}

	/** Sign-in page that comes back to the listing. */
	public static function login_url( $back ) {
		return add_query_arg( 'redirect_to', rawurlencode( $back ), CoreRoutes::url( 'login' ) );
	}
}

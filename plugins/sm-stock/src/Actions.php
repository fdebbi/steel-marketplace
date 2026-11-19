<?php
/**
 * Forms of the warehouse (admin-post):
 *  - sm_stock_ref    : reference and location of a variant;
 *  - sm_stock_adjust : correct a quantity, with its reason;
 *  - sm_stock_prices : one price per kg (or a change in %) for a whole type of new steel;
 *  - sm_stock_labels : labels with a QR code (PDF).
 * Publishing and withdrawing use the listing engine (product_action, to_stock / republish).
 *
 * @package SM\Stock
 */

namespace SM\Stock;

use SM\Core\Accounts\Companies;
use SM\Core\Routing\Routes;
use SM\Core\Support\Forms;
use SM\Products\Presenter;
use SM\Products\Products;

defined( 'ABSPATH' ) || exit;

final class Actions {

	public static function init() {
		add_action( 'admin_post_sm_stock_ref', array( __CLASS__, 'ref' ) );
		add_action( 'admin_post_sm_stock_adjust', array( __CLASS__, 'adjust' ) );
		add_action( 'admin_post_sm_stock_prices', array( __CLASS__, 'prices' ) );
		add_action( 'admin_post_sm_stock_labels', array( __CLASS__, 'labels' ) );
	}

	/** Company of the current user when he may manage the warehouse, else null. */
	public static function company() {
		$c = sm_current_company();
		return $c && Companies::can_manage( (int) $c->id, get_current_user_id() ) && in_array( $c->account_class, Limits::CLASSES, true ) ? $c : null;
	}

	/** A product of the warehouse of the current company. */
	private static function product( $company ) {
		$p = Products::get( absint( Forms::post( 'product_id' ) ) );
		if ( ! $p || (int) $p->company_id !== (int) $company->id || ! in_array( $p->market, Items::MARKETS, true ) ) {
			Forms::back( array( __( 'This item is not in your warehouse.', 'sm-stock' ) ) );
		}
		return $p;
	}

	/** Nothing left: off the market and out of the warehouse. */
	public static function sold_if_empty( $product_id ) {
		$after = Products::get( $product_id );
		if ( $after && 'sold' !== $after->status && $after->total_weight <= 0.0001 && ( ! $after->lines || 0 === (int) $after->pieces ) ) {
			Products::set_status( $after->id, 'sold' );
		}
	}

	public static function ref() {
		Forms::begin( 'stock_ref' );
		$c = self::company() ?: Forms::back( array( __( 'Your account has no warehouse.', 'sm-stock' ) ) );
		$p = self::product( $c );
		$ok = Items::set_ref( (int) $c->id, (int) $p->id, sanitize_text_field( (string) Forms::post( 'line_key' ) ), (string) Forms::post( 'ref' ), (string) Forms::post( 'location' ) );
		if ( is_wp_error( $ok ) ) {
			Forms::back( array( 'ref' => $ok->get_error_message() ) );
		}
		Forms::back( array(), __( 'Reference and location saved.', 'sm-stock' ) );
	}

	public static function adjust() {
		Forms::begin( 'stock_adjust' );
		$c    = self::company() ?: Forms::back( array( __( 'Your account has no warehouse.', 'sm-stock' ) ) );
		$p    = self::product( $c );
		$note = mb_substr( sanitize_text_field( (string) Forms::post( 'note' ) ), 0, 160 );
		$raw  = str_replace( ',', '.', (string) Forms::post( 'qty' ) );
		if ( '' === trim( $raw ) || ! is_numeric( $raw ) || (float) $raw < 0 ) {
			Forms::back( array( 'qty' => __( 'Give the new quantity (0 or more).', 'sm-stock' ) ) );
		}
		if ( '' === $note ) {
			Forms::back( array( 'note' => __( 'Give the reason of the correction (inventory, damaged bar, sale outside the platform…).', 'sm-stock' ) ) );
		}
		$res = Items::adjust( $p, sanitize_text_field( (string) Forms::post( 'line_key' ) ), (float) $raw );
		if ( is_wp_error( $res ) ) {
			Forms::back( array( $res->get_error_message() ) );
		}
		Moves::add( (int) $c->id, (int) $p->id, 'adjust', Presenter::title_text( $p ), $res['after'] - $res['before'], $res['unit'], $res['kg'], $note );
		self::sold_if_empty( $p->id );
		Forms::back( array(), __( 'Quantity corrected. The movement is in the history.', 'sm-stock' ) );
	}

	/**
	 * Price per kg for a whole type of new steel. Without "apply", the form shows
	 * the products concerned (preview); with "apply", the ticked products change.
	 */
	public static function prices() {
		Forms::begin( 'stock_prices' );
		$c = self::company() ?: Forms::back( array( __( 'Your account has no warehouse.', 'sm-stock' ) ) );
		if ( ! Limits::tools( $c ) ) {
			Forms::back( array( Limits::tools_reason() ) );
		}
		$sel  = Prices::read( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Forms::begin().
		$rows = Prices::preview( (int) $c->id, $sel );
		$ids  = array_map( 'absint', (array) Forms::post( 'ids', array() ) );
		$rows = array_values( array_filter( $rows, static fn( $r ) => in_array( (int) $r['id'], $ids, true ) ) );
		if ( ! $rows ) {
			Forms::back( array( __( 'Tick at least one product to change.', 'sm-stock' ) ) );
		}
		$n = Prices::apply( (int) $c->id, $rows );
		/* translators: %d: number of products. */
		Forms::back( array(), sprintf( _n( 'Price changed on %d product. Orders and offers already in progress keep their price.', 'Price changed on %d products. Orders and offers already in progress keep their price.', $n, 'sm-stock' ), $n ), Routes::url( 'account', array( 'view' => 'stock' ) ) );
	}

	/** Labels (PDF) of the variants of a tab, or of the given products. */
	public static function labels() {
		$c = self::company();
		if ( ! $c ) {
			wp_die( esc_html__( 'Your account has no warehouse.', 'sm-stock' ), '', array( 'response' => 403 ) );
		}
		if ( ! Limits::tools( $c ) ) {
			wp_die( esc_html( Limits::tools_reason() ), '', array( 'response' => 403 ) );
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only document of the user's own items.
		$tab = 'reuse' === sanitize_key( (string) ( $_GET['tab'] ?? '' ) ) ? 'reuse' : 'new';
		$ids = array_filter( array_map( 'absint', explode( ',', (string) wp_unslash( $_GET['ids'] ?? '' ) ) ) );
		// phpcs:enable
		$pdf = Labels::pdf( $c, $tab, $ids );
		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="etiquettes-magasin.pdf"' );
		header( 'Content-Length: ' . strlen( $pdf ) );
		echo $pdf; // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	}
}

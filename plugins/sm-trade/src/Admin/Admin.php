<?php
/**
 * wp-admin → SM → Orders and offers: history of every order, offer, counter-offer and
 * answer between buyers and sellers, with filters, the steps of each one and a CSV export.
 *
 * @package SM\Trade
 */

namespace SM\Trade\Admin;

use SM\Core\Accounts\Companies;
use SM\Trade\Deals;

defined( 'ABSPATH' ) || exit;

final class Admin {

	const PAGE = 'sm-trade';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 27 );
		add_action( 'admin_post_smtrade_export', array( __CLASS__, 'export' ) );
	}

	public static function menu() {
		global $wpdb;
		$open  = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . Deals::table() . " WHERE status = 'open'" ); // phpcs:ignore WordPress.DB
		$badge = $open ? ' <span class="awaiting-mod">' . $open . '</span>' : '';
		add_submenu_page( 'sm-core', __( 'Orders and offers', 'sm-trade' ), __( 'Orders and offers', 'sm-trade' ) . $badge, 'sm_moderate', self::PAGE, array( __CLASS__, 'page' ) );
	}

	private static function url( array $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => self::PAGE ), $args ), admin_url( 'admin.php' ) );
	}

	/** Filters from the address. */
	private static function filters() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$f = array(
			'status' => sanitize_key( wp_unslash( $_GET['status'] ?? '' ) ),
			'kind'   => sanitize_key( wp_unslash( $_GET['kind'] ?? '' ) ),
			'market' => sanitize_key( wp_unslash( $_GET['market'] ?? '' ) ),
			'from'   => preg_replace( '/[^\d-]/', '', (string) wp_unslash( $_GET['from'] ?? '' ) ),
			'to'     => preg_replace( '/[^\d-]/', '', (string) wp_unslash( $_GET['to'] ?? '' ) ),
			'q'      => sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) ),
		);
		// phpcs:enable
		$f['status'] = in_array( $f['status'], Deals::STATUSES, true ) ? $f['status'] : '';
		$f['kind']   = in_array( $f['kind'], array( 'order', 'offer' ), true ) ? $f['kind'] : '';
		return $f;
	}

	public static function status_labels() {
		return array(
			'open'      => __( 'Waiting for an answer', 'sm-trade' ),
			'accepted'  => __( 'Accepted', 'sm-trade' ),
			'refused'   => __( 'Refused', 'sm-trade' ),
			'cancelled' => __( 'Cancelled', 'sm-trade' ),
			'expired'   => __( 'Expired', 'sm-trade' ),
		);
	}

	private static function action_labels() {
		return array(
			'created'   => __( 'Request sent', 'sm-trade' ),
			'countered' => __( 'Other price proposed', 'sm-trade' ),
			'accepted'  => __( 'Accepted', 'sm-trade' ),
			'refused'   => __( 'Refused', 'sm-trade' ),
			'cancelled' => __( 'Cancelled', 'sm-trade' ),
			'expired'   => __( 'Expired (no answer)', 'sm-trade' ),
		);
	}

	private static function money( $v ) {
		return number_format_i18n( (float) $v, 0 ) . ' DA';
	}

	public static function page() {
		if ( ! current_user_can( 'sm_moderate' ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$id = absint( $_GET['deal'] ?? 0 );
		echo '<div class="wrap"><h1>' . esc_html__( 'Orders and offers', 'sm-trade' ) . '</h1>';
		if ( $id && ( $d = Deals::get( $id ) ) ) {
			self::detail( $d );
			echo '</div>';
			return;
		}
		echo '<p>' . esc_html__( 'Every order ("Buy") and offer between buyers and sellers, with each answer. Payment and delivery are settled directly between them; the platform keeps the history.', 'sm-trade' ) . '</p>';
		$f = self::filters();
		// Totals.
		global $wpdb;
		$t      = Deals::table();
		$counts = $wpdb->get_results( "SELECT status, COUNT(*) n, SUM(amount) s FROM {$t} GROUP BY status", OBJECT_K ); // phpcs:ignore WordPress.DB
		echo '<ul class="subsubsub">';
		$links = array( sprintf( '<li><a href="%s"%s>%s</a></li>', esc_url( self::url() ), '' === $f['status'] ? ' class="current"' : '', esc_html__( 'All', 'sm-trade' ) ) );
		foreach ( self::status_labels() as $k => $l ) {
			$links[] = sprintf( '<li><a href="%s"%s>%s <span class="count">(%d)</span></a></li>', esc_url( self::url( array( 'status' => $k ) ) ), $k === $f['status'] ? ' class="current"' : '', esc_html( $l ), (int) ( $counts[ $k ]->n ?? 0 ) );
		}
		echo implode( ' | ', $links ) . '</ul><br class="clear">'; // phpcs:ignore WordPress.Security.EscapeOutput
		if ( ! empty( $counts['accepted'] ) ) {
			/* translators: %s: total of the accepted orders and offers. */
			echo '<p><strong>' . esc_html( sprintf( __( 'Total accepted: %s', 'sm-trade' ), self::money( $counts['accepted']->s ) ) ) . '</strong></p>';
		}

		// Filters.
		echo '<form method="get" style="display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;margin:12px 0"><input type="hidden" name="page" value="' . esc_attr( self::PAGE ) . '">';
		echo '<p style="margin:0"><label>' . esc_html__( 'Status', 'sm-trade' ) . '<br><select name="status"><option value="">' . esc_html__( 'All', 'sm-trade' ) . '</option>';
		foreach ( self::status_labels() as $k => $l ) {
			echo '<option value="' . esc_attr( $k ) . '"' . selected( $f['status'], $k, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select></label></p>';
		echo '<p style="margin:0"><label>' . esc_html__( 'Type', 'sm-trade' ) . '<br><select name="kind"><option value="">' . esc_html__( 'All', 'sm-trade' ) . '</option><option value="order"' . selected( $f['kind'], 'order', false ) . '>' . esc_html__( 'Order', 'sm-trade' ) . '</option><option value="offer"' . selected( $f['kind'], 'offer', false ) . '>' . esc_html__( 'Offer', 'sm-trade' ) . '</option></select></label></p>';
		echo '<p style="margin:0"><label>' . esc_html__( 'Market', 'sm-trade' ) . '<br><select name="market"><option value="">' . esc_html__( 'All', 'sm-trade' ) . '</option>';
		foreach ( \SM\Products\Markets::all() as $k => $m ) {
			echo '<option value="' . esc_attr( $k ) . '"' . selected( $f['market'], $k, false ) . '>' . esc_html( $m['label']['fr'] ?? $k ) . '</option>';
		}
		echo '</select></label></p>';
		echo '<p style="margin:0"><label>' . esc_html__( 'From', 'sm-trade' ) . '<br><input type="date" name="from" value="' . esc_attr( $f['from'] ) . '"></label></p>';
		echo '<p style="margin:0"><label>' . esc_html__( 'To', 'sm-trade' ) . '<br><input type="date" name="to" value="' . esc_attr( $f['to'] ) . '"></label></p>';
		echo '<p style="margin:0"><label>' . esc_html__( 'Search (reference, listing, buyer)', 'sm-trade' ) . '<br><input type="search" name="q" value="' . esc_attr( $f['q'] ) . '"></label></p>';
		echo '<p style="margin:0"><button class="button">' . esc_html__( 'Filter', 'sm-trade' ) . '</button> ';
		echo '<a class="button" href="' . esc_url( wp_nonce_url( add_query_arg( array_merge( array( 'action' => 'smtrade_export' ), array_filter( $f ) ), admin_url( 'admin-post.php' ) ), 'smtrade_export' ) ) . '">' . esc_html__( 'Export (CSV, Excel)', 'sm-trade' ) . '</a></p>';
		echo '</form>';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$r = Deals::query( array_merge( array_filter( $f ), array( 'per_page' => 50, 'page' => max( 1, absint( $_GET['paged'] ?? 1 ) ) ) ) );
		if ( ! $r['items'] ) {
			echo '<p>' . esc_html__( 'No order or offer yet.', 'sm-trade' ) . '</p></div>';
			return;
		}
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Reference', 'sm-trade' ) . '</th><th>' . esc_html__( 'Date (UTC)', 'sm-trade' ) . '</th><th>' . esc_html__( 'Listing', 'sm-trade' ) . '</th><th>' . esc_html__( 'Seller', 'sm-trade' ) . '</th><th>' . esc_html__( 'Buyer', 'sm-trade' ) . '</th><th>' . esc_html__( 'Quantity', 'sm-trade' ) . '</th><th>' . esc_html__( 'Listed price', 'sm-trade' ) . '</th><th>' . esc_html__( 'Price agreed or proposed', 'sm-trade' ) . '</th><th>' . esc_html__( 'Status', 'sm-trade' ) . '</th></tr></thead><tbody>';
		$labels = self::status_labels();
		foreach ( $r['items'] as $d ) {
			$s = Companies::get( (int) $d->seller_company_id );
			echo '<tr><td><a href="' . esc_url( self::url( array( 'deal' => $d->id ) ) ) . '"><strong>' . esc_html( $d->ref ) . '</strong></a><br><small>' . esc_html( 'offer' === $d->kind ? __( 'Offer', 'sm-trade' ) : __( 'Order', 'sm-trade' ) ) . '</small></td>';
			echo '<td>' . esc_html( $d->created_at ) . '</td>';
			echo '<td><a href="' . esc_url( sm_url( 'listing', array( 'id' => (int) $d->listing_id ) ) ) . '" target="_blank">' . esc_html( $d->title ) . '</a></td>';
			echo '<td>' . esc_html( $s ? $s->name : '—' ) . '</td>';
			echo '<td>' . esc_html( $d->buyer_name ) . '<br><small>' . esc_html( $d->buyer_phone . ' · ' . $d->buyer_email ) . '</small></td>';
			echo '<td>' . esc_html( 'lot' === $d->mode ? __( 'whole lot', 'sm-trade' ) : number_format_i18n( (float) $d->quantity, fmod( (float) $d->quantity, 1 ) ? 2 : 0 ) . ' ' . \SM\Products\Presenter::unit_label( $d->unit ) ) . '</td>';
			echo '<td>' . esc_html( self::money( $d->listed_total ) ) . '</td><td><strong>' . esc_html( self::money( $d->amount ) ) . '</strong>' . ( (int) $d->counters ? '<br><small>' . esc_html( sprintf( /* translators: %d: number of counter-offers. */ _n( '%d counter-offer', '%d counter-offers', (int) $d->counters, 'sm-trade' ), (int) $d->counters ) ) . '</small>' : '' ) . '</td>';
			echo '<td>' . esc_html( $labels[ $d->status ] ?? $d->status ) . ( 'open' === $d->status ? '<br><small>' . esc_html( 'seller' === $d->turn ? __( 'Seller to answer', 'sm-trade' ) : __( 'Buyer to answer', 'sm-trade' ) ) . '</small>' : '' ) . '</td></tr>';
		}
		echo '</tbody></table>';
		if ( $r['pages'] > 1 ) {
			echo '<p>' . paginate_links( array( 'base' => add_query_arg( 'paged', '%#%' ), 'format' => '', 'total' => $r['pages'], 'current' => max( 1, absint( $_GET['paged'] ?? 1 ) ) ) ) . '</p>'; // phpcs:ignore WordPress.Security
		}
		echo '</div>';
	}

	private static function detail( $d ) {
		$s      = Companies::get( (int) $d->seller_company_id );
		$labels = self::status_labels();
		echo '<p><a href="' . esc_url( self::url() ) . '">← ' . esc_html__( 'All orders and offers', 'sm-trade' ) . '</a></p>';
		echo '<h2>' . esc_html( $d->ref . ' · ' . ( 'offer' === $d->kind ? __( 'Offer', 'sm-trade' ) : __( 'Order', 'sm-trade' ) ) ) . '</h2>';
		echo '<table class="form-table" role="presentation">';
		$rows = array(
			__( 'Status', 'sm-trade' )       => $labels[ $d->status ] ?? $d->status,
			__( 'Listing', 'sm-trade' )      => $d->title,
			__( 'Seller', 'sm-trade' )       => $s ? $s->name : '—',
			__( 'Buyer', 'sm-trade' )        => $d->buyer_name . ' · ' . $d->buyer_phone . ' · ' . $d->buyer_email,
			__( 'Quantity', 'sm-trade' )     => 'lot' === $d->mode ? __( 'whole lot', 'sm-trade' ) : number_format_i18n( (float) $d->quantity, fmod( (float) $d->quantity, 1 ) ? 2 : 0 ) . ' ' . \SM\Products\Presenter::unit_label( $d->unit ),
			__( 'Listed price', 'sm-trade' ) => self::money( $d->listed_total ),
			__( 'Price agreed or proposed', 'sm-trade' ) => self::money( $d->amount ),
			__( 'Message of the buyer', 'sm-trade' ) => (string) $d->message,
		);
		foreach ( $rows as $k => $v ) {
			echo '<tr><th>' . esc_html( $k ) . '</th><td>' . esc_html( $v ) . '</td></tr>';
		}
		echo '<tr><th></th><td><a href="' . esc_url( sm_url( 'listing', array( 'id' => (int) $d->listing_id ) ) ) . '" target="_blank">' . esc_html__( 'Open the listing', 'sm-trade' ) . '</a></td></tr></table>';
		echo '<h2>' . esc_html__( 'Steps', 'sm-trade' ) . '</h2><table class="widefat striped" style="max-width:900px"><thead><tr><th>' . esc_html__( 'Date (UTC)', 'sm-trade' ) . '</th><th>' . esc_html__( 'By', 'sm-trade' ) . '</th><th>' . esc_html__( 'Step', 'sm-trade' ) . '</th><th>' . esc_html__( 'Price', 'sm-trade' ) . '</th><th>' . esc_html__( 'Message', 'sm-trade' ) . '</th></tr></thead><tbody>';
		$acts = self::action_labels();
		$who  = array( 'buyer' => __( 'Buyer', 'sm-trade' ), 'seller' => __( 'Seller', 'sm-trade' ), 'system' => __( 'Platform', 'sm-trade' ) );
		foreach ( Deals::events( $d->id ) as $e ) {
			$u = $e->user_id ? get_userdata( (int) $e->user_id ) : null;
			echo '<tr><td>' . esc_html( $e->created_at ) . '</td><td>' . esc_html( ( $who[ $e->actor ] ?? $e->actor ) . ( $u ? ' (' . $u->display_name . ')' : '' ) ) . '</td><td>' . esc_html( $acts[ $e->action ] ?? $e->action ) . '</td><td>' . esc_html( self::money( $e->amount ) ) . '</td><td>' . esc_html( (string) $e->note ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}

	/** CSV of the filtered history (UTF-8 with BOM, ";" separator: opens in Excel in French). */
	public static function export() {
		if ( ! current_user_can( 'sm_moderate' ) ) {
			wp_die( '', 403 );
		}
		check_admin_referer( 'smtrade_export' );
		$f      = self::filters();
		$r      = Deals::query( array_merge( array_filter( $f ), array( 'per_page' => 0 ) ) );
		$labels = self::status_labels();
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="commandes-offres-' . gmdate( 'Y-m-d' ) . '.csv"' );
		$out = fopen( 'php://output', 'w' );
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		fputcsv( $out, array( __( 'Reference', 'sm-trade' ), __( 'Type', 'sm-trade' ), __( 'Status', 'sm-trade' ), __( 'Created (UTC)', 'sm-trade' ), __( 'Closed (UTC)', 'sm-trade' ), __( 'Market', 'sm-trade' ), __( 'Listing', 'sm-trade' ), __( 'Seller', 'sm-trade' ), __( 'Buyer', 'sm-trade' ), __( 'Buyer phone', 'sm-trade' ), __( 'Buyer e-mail', 'sm-trade' ), __( 'Quantity', 'sm-trade' ), __( 'Unit', 'sm-trade' ), __( 'Listed price (DA)', 'sm-trade' ), __( 'Price agreed or proposed (DA)', 'sm-trade' ), __( 'Counter-offers', 'sm-trade' ) ), ';' );
		foreach ( $r['items'] as $d ) {
			$s = Companies::get( (int) $d->seller_company_id );
			fputcsv( $out, array( $d->ref, 'offer' === $d->kind ? __( 'Offer', 'sm-trade' ) : __( 'Order', 'sm-trade' ), $labels[ $d->status ] ?? $d->status, $d->created_at, (string) $d->closed_at, $d->market, $d->title, $s ? $s->name : '', $d->buyer_name, $d->buyer_phone, $d->buyer_email, 'lot' === $d->mode ? 1 : (float) $d->quantity, 'lot' === $d->mode ? 'lot' : $d->unit, number_format( (float) $d->listed_total, 2, ',', '' ), number_format( (float) $d->amount, 2, ',', '' ), (int) $d->counters ), ';' );
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}
}

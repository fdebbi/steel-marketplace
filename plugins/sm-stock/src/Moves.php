<?php
/**
 * Movements of the warehouse: arrivals, sales accepted on the platform (taken
 * off automatically by the listing engine, noted here from its activity log),
 * corrections with their reason, publications and withdrawals.
 *
 * @package SM\Stock
 */

namespace SM\Stock;

use SM\Products\Presenter;
use SM\Products\Products;

defined( 'ABSPATH' ) || exit;

final class Moves {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sm_stock_moves';
	}

	public static function init() {
		add_action( 'sm_audit_logged', array( __CLASS__, 'from_audit' ) );
		add_action( 'sm_products_status_changed', array( __CLASS__, 'status_changed' ), 10, 3 );
	}

	public static function add( $company_id, $product_id, $kind, $label, $qty, $unit, $kg, $note = '', $ref = '', $at = null ) {
		global $wpdb;
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			array(
				'company_id' => (int) $company_id,
				'product_id' => (int) $product_id,
				'kind'       => sanitize_key( $kind ),
				'label'      => mb_substr( (string) $label, 0, 160 ),
				'qty'        => round( (float) $qty, 3 ),
				'unit'       => (string) $unit,
				'kg'         => round( (float) $kg, 3 ),
				'note'       => mb_substr( (string) $note, 0, 160 ),
				'ref'        => mb_substr( (string) $ref, 0, 40 ),
				'user_id'    => get_current_user_id(),
				'created_at' => $at ? (string) $at : current_time( 'mysql', true ),
			)
		);
		return (int) $wpdb->insert_id;
	}

	public static function has( $product_id ) {
		global $wpdb;
		return (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::table() . ' WHERE product_id = %d LIMIT 1', $product_id ) ); // phpcs:ignore WordPress.DB
	}

	/** Movements of a company, newest first. */
	public static function recent( $company_id, $limit = 20, $page = 1, $market = '' ) {
		global $wpdb;
		$join  = '';
		$where = $wpdb->prepare( 'm.company_id = %d', $company_id );
		if ( $market ) {
			$join   = ' LEFT JOIN ' . Products::table() . ' p ON p.id = m.product_id';
			$where .= $wpdb->prepare( ' AND p.market = %s', $market );
		}
		return $wpdb->get_results( $wpdb->prepare( 'SELECT m.* FROM ' . self::table() . " m$join WHERE $where ORDER BY m.created_at DESC, m.id DESC LIMIT %d OFFSET %d", $limit, ( max( 1, $page ) - 1 ) * $limit ) ); // phpcs:ignore WordPress.DB
	}

	public static function count( $company_id ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE company_id = %d', $company_id ) ); // phpcs:ignore WordPress.DB
	}

	/** A sale accepted on the platform took stock from a listing (sm-products activity "product.stock_taken"). */
	public static function from_audit( $entry ) {
		if ( ! is_object( $entry ) || 'product.stock_taken' !== ( $entry->action ?? '' ) ) {
			return;
		}
		$p = Products::get( (int) ( $entry->object_id ?? 0 ) );
		if ( ! $p || ! in_array( $p->market, Items::MARKETS, true ) ) {
			return;
		}
		$d    = (array) ( $entry->data ?? array() );
		$mode = (string) ( $d['mode'] ?? '' );
		$ref  = (string) ( $d['ref'] ?? '' );
		if ( 'lot' === $mode ) {
			self::add( $p->company_id, $p->id, 'sale', Presenter::title_text( $p ), -(float) $p->pieces, 'piece', -(float) $p->total_weight, __( 'whole lot', 'sm-stock' ), $ref );
			return;
		}
		$qty  = (float) ( $d['qty'] ?? 0 );
		$unit = (string) ( $d['unit'] ?? '' );
		if ( 'kg' === $unit ) {
			self::add( $p->company_id, $p->id, 'sale', Presenter::title_text( $p ), 0, 'kg', -$qty, '', $ref );
			return;
		}
		$kg = 'unit' === $unit && (float) $p->unit_weight > 0 ? $qty * (float) $p->unit_weight : 0.0;
		self::add( $p->company_id, $p->id, 'sale', Presenter::title_text( $p ), -$qty, 'bar', -$kg, '', $ref );
	}

	/** Publications and withdrawals between the market and the warehouse. */
	public static function status_changed( $id, $status, $before ) {
		if ( ! $before || ! in_array( $before->market, Items::MARKETS, true ) || $before->status === $status ) {
			return;
		}
		if ( 'stock' === $status ) {
			self::add( $before->company_id, $id, 'withdraw', Presenter::title_text( $before ), 0, '', 0 );
		} elseif ( 'stock' === $before->status && in_array( $status, array( 'published', 'pending' ), true ) ) {
			self::add( $before->company_id, $id, 'publish', Presenter::title_text( $before ), 0, '', 0 );
		}
	}
}

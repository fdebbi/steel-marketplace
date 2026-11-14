<?php
/**
 * How a building is sold (0.4.0), as decided by the platform owner:
 *
 * - The seller ticks "Whole building" (ticked by default) and / or "By element".
 *   Stored in the sale mode of SM Products: lot, both, detail.
 * - One price, typed either per kg or for the whole building: the other figure is
 *   calculated from the steel weight of the listing (the weight given by the
 *   seller, or the weight of the declared elements). The whole-building price is
 *   the sale price; the price per kg is its equivalent, and the price of the
 *   elements when they are sold one by one.
 * - By element: the buyer picks pieces of the inventory and their number; the
 *   weight comes from the section tables. It needs an inventory.
 * - Once pieces are sold the building is no longer complete: the whole-building
 *   sale closes (SM Products Stock) and the figures of the listing follow.
 *
 * @package SM\Buildings
 */

namespace SM\Buildings;

use SM\Products\Products;
use SM\Products\Stock;

defined( 'ABSPATH' ) || exit;

final class Sale {

	public static function init() {
		add_action( 'sm_products_price_input', array( __CLASS__, 'price_input' ), 10, 5 );
		add_filter( 'sm_products_stock_options', array( __CLASS__, 'stock_options' ), 10, 2 );
		add_filter( 'sm_products_stock_row', array( __CLASS__, 'stock_row' ), 10, 3 );
		add_filter( 'sm_products_form_values', array( __CLASS__, 'form_values' ), 30, 2 );
	}

	/** lot | both | detail from the two boxes of the form (or from a sale mode already given). */
	public static function mode( array $in ) {
		if ( isset( $in['sell_set'] ) ) {
			$lot = ! empty( $in['sell_lot'] );
			$el  = ! empty( $in['sell_elements'] );
			return $lot && $el ? 'both' : ( $el ? 'detail' : 'lot' );
		}
		$mode = sanitize_key( (string) ( $in['sale_mode'] ?? '' ) );
		return in_array( $mode, array( 'lot', 'both', 'detail' ), true ) ? $mode : 'lot';
	}

	/** kg | total: the price the seller typed; the other one is calculated. */
	public static function basis( array $in ) {
		$b = sanitize_key( (string) ( $in['price_basis'] ?? '' ) );
		if ( in_array( $b, array( 'kg', 'total' ), true ) ) {
			return $b;
		}
		return \SM\Products\Spec::num( $in['price'] ?? '' ) > 0 && 'lot' !== ( $in['price_unit'] ?? '' ) ? 'kg' : 'total';
	}

	/**
	 * The two linked prices for a weight: [ per kg, whole building ].
	 * Per kg typed: total rounded down to the dinar. Total typed: per kg rounded up to
	 * the centime, so the elements bought one by one never cost less than the building.
	 */
	public static function prices( $basis, $per_kg, $total, $weight ) {
		$per_kg = (float) $per_kg;
		$total  = (float) $total;
		$weight = (float) $weight;
		if ( 'kg' === $basis ) {
			$total = $weight > 0 && $per_kg > 0 ? floor( $per_kg * $weight + 1e-6 ) : 0.0;
		} else {
			$per_kg = $weight > 0 && $total > 0 ? ceil( $total / $weight * 100 - 1e-6 ) / 100 : 0.0;
		}
		return array( $per_kg, $total );
	}

	public static function price_input( $cat, &$in, &$d, &$e, $for_save = true ) {
		if ( Market::KIND !== ( $cat->kind ?? '' ) ) {
			return;
		}
		$num    = static fn( $v ) => \SM\Products\Spec::num( $v );
		$mode   = self::mode( $in );
		$basis  = self::basis( $in );
		$weight = (float) ( $d['total_weight'] ?? 0 );
		$raw_pu = (string) ( $in['price_unit'] ?? '' );
		// A price typed "for the whole building" in the older form.
		$typed_total = $num( $in['lot_price'] ?? '' );
		if ( $typed_total <= 0 && 'lot' === $raw_pu ) {
			$typed_total = $num( $in['price'] ?? '' );
		}
		list( $per_kg, $total ) = self::prices( $basis, 'lot' === $raw_pu ? 0 : $num( $in['price'] ?? '' ), $typed_total, $weight );
		if ( isset( $in['sell_set'] ) && empty( $in['sell_lot'] ) && empty( $in['sell_elements'] ) ) {
			$e['sale_mode'] = __( 'Choose how you sell: the whole building, by element, or both.', 'sm-buildings' );
		}
		if ( 'lot' !== $mode && empty( $d['lines'] ) ) {
			$e['sale_mode'] = __( 'To sell by element, list the elements of the structure first (step "Elements").', 'sm-buildings' );
		}
		if ( 'kg' === $basis && $per_kg > 0 && $weight <= 0 && 'detail' !== $mode ) {
			$e['price'] = __( 'A price per kg needs the steel weight: list the elements or give the total weight, or type the price of the whole building.', 'sm-buildings' );
		}
		$in['sale_mode']  = $mode;
		$in['price_unit'] = 'lot' === $mode ? 'lot' : 'kg';
		$in['price']      = 'lot' === $mode ? '' : (string) $per_kg;
		$in['lot_price']  = 'detail' === $mode ? '' : (string) $total;
		$d['price_unit']  = $in['price_unit'];
		// For the form: both figures, whichever was typed.
		$d['specs']['price_basis']  = $basis;
		$d['specs']['price_kg_eq']  = $per_kg;
		$d['specs']['price_total']  = 'detail' === $mode ? 0 : $total;
	}

	/** Inventory rows a buyer can still pick. */
	public static function stock_options( $out, $p ) {
		if ( Market::CODE !== ( $p->market ?? '' ) ) {
			return $out;
		}
		if ( empty( $out['detail'] ) ) {
			return $out;
		}
		$lines = Stock::pick_lines( $p );
		if ( ! $lines || 'kg' !== $out['detail']['unit'] ) {
			$out['detail'] = null; // By element needs an inventory and a price per kg.
			return $out;
		}
		$out['detail']['lines'] = $lines;
		$out['detail']['max']   = round( array_sum( array_map( static fn( $l ) => $l['qty'] * $l['piece_kg'], $lines ) ), 3 );
		return $out;
	}

	/** After pieces are sold: the weight of the declared elements follows. */
	public static function stock_row( $row, $p, $lines ) {
		if ( Market::CODE !== ( $p->market ?? '' ) ) {
			return $row;
		}
		$specs                = (array) $p->specs;
		$specs['elements_kg'] = round( array_sum( array_map( static fn( $l ) => (float) ( $l['total'] ?? 0 ), (array) $lines ) ), 1 );
		$row['specs']         = wp_json_encode( $specs );
		if ( (float) ( $row['total_weight'] ?? 0 ) > 0 && (float) ( $row['total_price'] ?? 0 ) > 0 ) {
			$row['price_per_kg'] = round( (float) $row['total_price'] / (float) $row['total_weight'], 2 );
		}
		return $row;
	}

	/** Edit form: the two boxes and the price that was typed. */
	public static function form_values( $v, $p ) {
		if ( Market::CODE !== ( $p->market ?? '' ) ) {
			return $v;
		}
		$mode               = in_array( $p->sale_mode, array( 'lot', 'both', 'detail' ), true ) ? $p->sale_mode : 'lot';
		$v['sell_set']      = '1';
		$v['sell_lot']      = 'detail' === $mode ? '' : '1';
		$v['sell_elements'] = 'lot' === $mode ? '' : '1';
		$basis              = (string) ( $p->specs['price_basis'] ?? '' );
		$v['price_basis']   = in_array( $basis, array( 'kg', 'total' ), true ) ? $basis : ( 'lot' === $mode ? 'total' : 'kg' );
		if ( 'lot' === $mode ) {
			$v['price']     = '';
			$v['lot_price'] = (float) $p->lot_price > 0 ? (string) ( 0 + $p->lot_price ) : ( (float) $p->price > 0 ? (string) ( 0 + $p->price ) : '' );
			if ( 'kg' === $v['price_basis'] && ! empty( $p->specs['price_kg_eq'] ) ) {
				$v['price'] = (string) ( 0 + $p->specs['price_kg_eq'] );
			}
		}
		return $v;
	}

	/**
	 * Listings published before 0.4.0. A building priced per kg without the "by
	 * element" choice was meant as a whole building: its total becomes the sale
	 * price. Buildings sold by the kg without an inventory too (nothing to pick).
	 */
	public static function migrate() {
		global $wpdb;
		$t    = $wpdb->prefix . 'sm_products';
		$rows = $wpdb->get_results( "SELECT id, variant, sale_mode, price_unit, price, total_price, lot_price, inventory FROM {$t} WHERE market = 'buildings'" ); // phpcs:ignore WordPress.DB
		foreach ( (array) $rows as $r ) {
			$row = array();
			if ( 'lot' === $r->price_unit && 'detail' === $r->sale_mode ) {
				$row = array( 'sale_mode' => 'lot', 'lot_price' => (float) $r->price ); // Asking price of an assembled building.
			} elseif ( 'detail' === $r->sale_mode && 'kg' === $r->price_unit && (float) $r->total_price > 0 ) {
				$has_lines = (bool) ( json_decode( (string) $r->inventory, true ) ?: array() );
				if ( 'assembled' === $r->variant || ! $has_lines ) {
					$total = floor( (float) $r->total_price );
					$row   = array( 'sale_mode' => 'lot', 'price_unit' => 'lot', 'price' => $total, 'lot_price' => $total, 'total_price' => $total );
				}
			}
			if ( $row ) {
				$wpdb->update( $t, $row, array( 'id' => (int) $r->id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				if ( method_exists( Products::class, 'index' ) ) {
					Products::index( (int) $r->id );
				}
			}
		}
	}
}

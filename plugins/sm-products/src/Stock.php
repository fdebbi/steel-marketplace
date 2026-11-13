<?php
/**
 * What a buyer can order on a listing, and the stock taken by an accepted order (0.10.0).
 *
 * Ways to buy, from what the seller chose when publishing:
 *   lot     the whole lot at the lot price (sale mode "lot" or "both", or a price per lot,
 *           like an assembled building);
 *   detail  a quantity at the detail price: whole bars for listings counted in bars,
 *           otherwise kg, pieces, m² or linear metres, up to what is listed.
 *
 * Stock: an accepted order of the whole lot marks the listing sold; an accepted order
 * of a quantity takes it from the listing (pieces, weight, quantity and total price).
 * Once a part is sold the lot price no longer applies (the listing goes to detail).
 * At zero the listing is sold. Nothing is guessed: a listing without a price cannot be
 * bought (an offer is still possible).
 *
 * @package SM\Products
 */

namespace SM\Products;

use SM\Core\Audit\AuditLog;

defined( 'ABSPATH' ) || exit;

final class Stock {

	/**
	 * @return array{
	 *   lot: array{total: float}|null,
	 *   detail: array{unit: string, max: float, step: float, unit_price: float}|null,
	 * }
	 */
	public static function options( $p ) {
		$out = array( 'lot' => null, 'detail' => null );
		if ( ! $p ) {
			return $out;
		}
		$lot_total = 0.0;
		if ( in_array( $p->sale_mode, array( 'lot', 'both' ), true ) && (float) $p->lot_price > 0 ) {
			$lot_total = (float) $p->lot_price;
		} elseif ( 'lot' === $p->price_unit && (float) $p->price > 0 ) {
			$lot_total = (float) $p->price;
		}
		if ( $lot_total > 0 ) {
			$out['lot'] = array( 'total' => round( $lot_total, 2 ) );
		}
		if ( 'lot' === $p->sale_mode || 'lot' === $p->price_unit || (float) $p->price <= 0 ) {
			return $out;
		}
		$price  = (float) $p->price;
		$pieces = (float) $p->pieces;
		$weight = (float) $p->total_weight;
		if ( 'bars' === $p->qty_mode && $pieces >= 1 ) {
			// Whole bars: price of one bar from the price per kg, per piece or per metre.
			$per = array(
				'kg'   => $weight > 0 ? $price * $weight / $pieces : 0,
				'unit' => $price,
				'ml'   => $price * (float) $p->length_mm / 1000,
			)[ $p->price_unit ] ?? 0;
			if ( $per > 0 ) {
				$out['detail'] = array( 'unit' => 'unit', 'max' => floor( $pieces ), 'step' => 1.0, 'unit_price' => round( $per, 2 ) );
			}
			return $out;
		}
		if ( 'kg' === $p->price_unit ) {
			if ( $weight > 0 ) {
				$out['detail'] = array( 'unit' => 'kg', 'max' => round( $weight, 3 ), 'step' => 1.0, 'unit_price' => $price );
			}
			return $out;
		}
		$qty = 'unit' === $p->price_unit && $pieces > 0 ? $pieces : (float) $p->quantity;
		if ( $qty > 0 ) {
			$out['detail'] = array( 'unit' => $p->price_unit, 'max' => round( $qty, 3 ), 'step' => 'unit' === $p->price_unit ? 1.0 : 0.01, 'unit_price' => $price );
		}
		return $out;
	}

	/**
	 * Check a wanted quantity. Returns [ quantity, unit, total at the listed price ] or a WP_Error.
	 *
	 * @param string $mode lot|detail
	 */
	public static function check( $p, $mode, $qty ) {
		$o = self::options( $p );
		if ( 'lot' === $mode ) {
			if ( ! $o['lot'] ) {
				return new \WP_Error( 'mode', __( 'This listing is not sold as a whole lot.', 'sm-products' ) );
			}
			return array( 1.0, 'lot', $o['lot']['total'] );
		}
		$d = $o['detail'];
		if ( ! $d ) {
			return new \WP_Error( 'mode', __( 'This listing is sold as a whole lot only.', 'sm-products' ) );
		}
		$qty = round( (float) $qty, 3 );
		if ( $qty <= 0 ) {
			return new \WP_Error( 'qty', __( 'Enter the quantity you want.', 'sm-products' ) );
		}
		if ( 1.0 === $d['step'] && abs( $qty - round( $qty ) ) > 0.0001 ) {
			return new \WP_Error( 'qty', __( 'Enter a whole number.', 'sm-products' ) );
		}
		if ( $qty > $d['max'] + 0.0001 ) {
			/* translators: 1: available quantity, 2: unit. */
			return new \WP_Error( 'qty', sprintf( __( 'Only %1$s %2$s available.', 'sm-products' ), Presenter::n( $d['max'], fmod( $d['max'], 1 ) ? 2 : 0 ), Presenter::unit_label( $d['unit'] ) ) );
		}
		return array( $qty, $d['unit'], round( $qty * $d['unit_price'], 2 ) );
	}

	/**
	 * Take an accepted order from the listing. Returns true or a WP_Error (not enough left).
	 *
	 * @param string $mode lot|detail
	 */
	public static function take( $product_id, $mode, $qty, $ref = '' ) {
		global $wpdb;
		$p = Products::get( (int) $product_id );
		if ( ! $p || ! in_array( $p->status, array( 'published', 'paused' ), true ) ) {
			return new \WP_Error( 'gone', __( 'This listing is no longer on sale.', 'sm-products' ) );
		}
		$ok = self::check( $p, $mode, $qty );
		if ( is_wp_error( $ok ) ) {
			return $ok;
		}
		if ( 'lot' === $mode ) {
			Products::set_status( $p->id, 'sold' );
			AuditLog::add( 'product.stock_taken', 'product', $p->id, array( 'mode' => 'lot', 'ref' => $ref ), null, $p->company_id );
			return true;
		}
		$d      = self::options( $p )['detail'];
		$qty    = (float) $ok[0];
		$row    = array();
		$pieces = (float) $p->pieces;
		$weight = (float) $p->total_weight;
		if ( 'bars' === $p->qty_mode && 'unit' === $d['unit'] ) {
			$per_kg          = $pieces > 0 ? $weight / $pieces : 0;
			$row['pieces']   = max( 0, $pieces - $qty );
			$row['total_weight'] = max( 0, round( $weight - $qty * $per_kg, 3 ) );
			$left            = $row['pieces'];
		} elseif ( 'kg' === $d['unit'] ) {
			$row['total_weight'] = max( 0, round( $weight - $qty, 3 ) );
			if ( $pieces > 0 && $weight > 0 ) {
				$row['pieces'] = round( $pieces * $row['total_weight'] / $weight, 2 );
			}
			$left = $row['total_weight'];
		} elseif ( 'unit' === $d['unit'] && $pieces > 0 ) {
			$row['pieces'] = max( 0, $pieces - $qty );
			$left          = $row['pieces'];
		} else {
			$row['quantity'] = max( 0, round( (float) $p->quantity - $qty, 3 ) );
			$left            = $row['quantity'];
		}
		$row['total_price'] = max( 0, round( (float) $p->total_price - $qty * $d['unit_price'], 2 ) );
		if ( 'both' === $p->sale_mode ) {
			$row['sale_mode'] = 'detail'; // Part of the lot is sold: the lot price no longer applies.
			$row['lot_price'] = 0;
		}
		$row['updated_at'] = current_time( 'mysql', true );
		$wpdb->update( $wpdb->prefix . 'sm_products', $row, array( 'id' => (int) $p->id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		AuditLog::add( 'product.stock_taken', 'product', $p->id, array( 'mode' => 'detail', 'qty' => $qty, 'unit' => $d['unit'], 'left' => $left, 'ref' => $ref ), null, $p->company_id );
		if ( $left <= 0.0001 ) {
			Products::set_status( $p->id, 'sold' );
		} else {
			PriceTrend::flush();
			Products::index( $p->id );
		}
		return true;
	}
}

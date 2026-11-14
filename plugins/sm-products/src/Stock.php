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
		/**
		 * A market plugin may change what can be bought (0.14.0). When it adds
		 * detail.lines (self::pick_lines()), the buyer chooses pieces of the
		 * inventory instead of typing a quantity.
		 *
		 * @param array  $out Options.
		 * @param object $p   Product.
		 */
		return (array) apply_filters( 'sm_products_stock_options', self::base_options( $p ), $p );
	}

	/**
	 * Inventory rows a buyer can pick from: one entry per element, size and length
	 * (identical rows are added up).
	 *
	 * @return array[] { key, element, label, designation, length_mm, qty, piece_kg }
	 */
	public static function pick_lines( $p ) {
		$out   = array();
		$names = (array) ( Markets::get( (string) ( $p->market ?? '' ) )['line_elements'] ?? array() );
		foreach ( (array) ( $p->lines ?? array() ) as $l ) {
			$qty = (int) ( $l['qty'] ?? 0 );
			if ( $qty < 1 ) {
				continue;
			}
			$key = self::line_key( $l );
			if ( isset( $out[ $key ] ) ) {
				$out[ $key ]['qty'] += $qty;
				continue;
			}
			$len = (int) ( $l['length_mm'] ?? 0 );
			$out[ $key ] = array(
				'key'         => $key,
				'element'     => (string) ( $l['element'] ?? '' ),
				'label'       => (array) ( $names[ (string) ( $l['element'] ?? '' ) ] ?? array() ), // { fr, ar } or empty.
				'designation' => (string) ( $l['designation'] ?? '' ),
				'length_mm'   => $len,
				'qty'         => $qty,
				'piece_kg'    => ! empty( $l['kg_m'] ) && $len > 0 ? round( (float) $l['kg_m'] * $len / 1000, 3 ) : round( (float) ( $l['total'] ?? 0 ) / $qty, 3 ),
			);
		}
		return array_values( $out );
	}

	private static function line_key( $l ) {
		return sanitize_key( (string) ( $l['element'] ?? '' ) ) . '-' . (int) ( $l['section_id'] ?? 0 ) . '-' . (int) ( $l['length_mm'] ?? 0 );
	}

	private static function base_options( $p ) {
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
	 * For a listing bought by pieces of its inventory, a fourth entry lists the pieces:
	 * { key, element, designation, length_mm, n, kg }.
	 *
	 * @param string $mode  lot|detail
	 * @param array  $items Pieces wanted: key of the inventory row => number.
	 */
	public static function check( $p, $mode, $qty, $items = array() ) {
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
		if ( ! empty( $d['lines'] ) ) {
			$kg   = 0.0;
			$norm = array();
			foreach ( $d['lines'] as $l ) {
				$n = (int) ( $items[ $l['key'] ] ?? 0 );
				if ( $n < 1 ) {
					continue;
				}
				if ( $n > $l['qty'] ) {
					/* translators: 1: number of pieces, 2: profile (IPE 200). */
					return new \WP_Error( 'items', sprintf( __( 'Only %1$s pieces of %2$s are available.', 'sm-products' ), Presenter::n( $l['qty'] ), $l['designation'] ) );
				}
				$w      = round( $n * $l['piece_kg'], 3 );
				$kg    += $w;
				$norm[] = array( 'key' => $l['key'], 'element' => $l['element'], 'label' => $l['label'], 'designation' => $l['designation'], 'length_mm' => $l['length_mm'], 'n' => $n, 'kg' => $w );
			}
			if ( ! $norm ) {
				return new \WP_Error( 'items', __( 'Choose the elements you want and their number.', 'sm-products' ) );
			}
			return array( round( $kg, 3 ), 'kg', round( $kg * $d['unit_price'], 2 ), $norm );
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
	public static function take( $product_id, $mode, $qty, $ref = '', $items = array() ) {
		global $wpdb;
		$p = Products::get( (int) $product_id );
		if ( ! $p || ! in_array( $p->status, array( 'published', 'paused' ), true ) ) {
			return new \WP_Error( 'gone', __( 'This listing is no longer on sale.', 'sm-products' ) );
		}
		$ok = self::check( $p, $mode, $qty, $items );
		if ( is_wp_error( $ok ) ) {
			return $ok;
		}
		if ( 'lot' !== $mode && ! empty( $ok[3] ) ) {
			return self::take_items( $p, $ok, $ref );
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

	/** Take pieces of the inventory (accepted order by element). */
	private static function take_items( $p, array $ok, $ref ) {
		global $wpdb;
		$want = array();
		foreach ( $ok[3] as $it ) {
			$want[ $it['key'] ] = (int) $it['n'];
		}
		$lines = array();
		$sum   = 0.0;
		$count = 0;
		foreach ( (array) $p->lines as $l ) {
			$key = self::line_key( $l );
			$qty = (int) ( $l['qty'] ?? 0 );
			$n   = min( $qty, (int) ( $want[ $key ] ?? 0 ) );
			if ( $n > 0 ) {
				$want[ $key ] -= $n;
				$l['total']    = $qty > 0 ? round( (float) ( $l['total'] ?? 0 ) * ( $qty - $n ) / $qty, 3 ) : 0;
				$l['qty']      = $qty - $n;
			}
			if ( (int) $l['qty'] > 0 ) {
				$lines[] = $l;
				$sum    += (float) ( $l['total'] ?? 0 );
				$count  += (int) $l['qty'];
			}
		}
		$sold   = (float) $ok[0];
		$weight = max( round( $sum, 3 ), 'partial' === $p->weight_source || 'theoretical' === $p->weight_source ? 0 : round( (float) $p->total_weight - $sold, 3 ) );
		$row    = array(
			'inventory'    => wp_json_encode( $lines ),
			'pieces'       => $count,
			'total_weight' => $weight,
			'total_price'  => round( (float) $p->price * $weight, 2 ),
			'updated_at'   => current_time( 'mysql', true ),
		);
		if ( 'both' === $p->sale_mode ) {
			$row['sale_mode'] = 'detail'; // Part of the lot is sold: the lot price no longer applies.
			$row['lot_price'] = 0;
		}
		/**
		 * Row written after pieces were sold (a market plugin keeps its own figures in line).
		 *
		 * @param array  $row   Columns to update.
		 * @param object $p     Product before the sale.
		 * @param array  $lines Inventory left.
		 */
		$row = (array) apply_filters( 'sm_products_stock_row', $row, $p, $lines );
		$wpdb->update( $wpdb->prefix . 'sm_products', $row, array( 'id' => (int) $p->id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		AuditLog::add( 'product.stock_taken', 'product', $p->id, array( 'mode' => 'items', 'qty' => $sold, 'unit' => 'kg', 'items' => $ok[3], 'left' => $count, 'ref' => $ref ), null, $p->company_id );
		if ( ! $lines ) {
			Products::set_status( $p->id, 'sold' );
		} else {
			PriceTrend::flush();
			Products::index( $p->id );
		}
		return true;
	}
}

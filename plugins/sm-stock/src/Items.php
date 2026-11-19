<?php
/**
 * The warehouse of a company: its new (sm-products) and reused (sm-reuse)
 * items, published or kept in stock only. An item is a product of the listing
 * engine; a reused lot has one variant per inventory line (profile, length).
 * Every variant gets a reference (N-0001 new, R-0001 reused), editable by the
 * seller, and an optional location.
 *
 * @package SM\Stock
 */

namespace SM\Stock;

use SM\Products\Products;

defined( 'ABSPATH' ) || exit;

final class Items {

	/** Statuses that are in the warehouse (the steel is still there). */
	const IN_STOCK = array( 'stock', 'published', 'pending', 'paused', 'expired' );

	/** Tab → market of the listing engine. */
	const MARKETS = array( 'new' => 'products', 'reuse' => 'reuse' );

	public static function refs_table() {
		global $wpdb;
		return $wpdb->prefix . 'sm_stock_refs';
	}

	/** Key of an inventory line (same as the listing engine). */
	public static function line_key( array $l ) {
		return sanitize_key( (string) ( $l['element'] ?? '' ) ) . '-' . (int) ( $l['section_id'] ?? 0 ) . '-' . (int) ( $l['length_mm'] ?? 0 ) . ( empty( $l['width_mm'] ) ? '' : 'x' . (int) $l['width_mm'] );
	}

	/** Products of the company in the warehouse (or sold, for the "Sold" filter). */
	public static function products( $company_id, $market, $filter = '' ) {
		global $wpdb;
		$statuses = 'sold' === $filter ? array( 'sold' ) : ( 'published' === $filter ? array( 'published', 'pending', 'paused' ) : ( 'stock' === $filter ? array( 'stock', 'expired' ) : self::IN_STOCK ) );
		$in       = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
		$ids      = $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . Products::table() . " WHERE company_id = %d AND market = %s AND status IN ($in) ORDER BY family, designation, id", array_merge( array( (int) $company_id, $market ), $statuses ) ) ); // phpcs:ignore WordPress.DB
		return array_values( array_filter( array_map( array( Products::class, 'get' ), $ids ) ) );
	}

	/** Variants of a product: one for a product counted in bars, one per inventory line. */
	public static function variants( $p ) {
		$out = array();
		if ( $p->lines ) {
			foreach ( $p->lines as $l ) {
				$qty = (int) ( $l['qty'] ?? 0 );
				$len = (int) ( $l['length_mm'] ?? 0 );
				$kg  = isset( $l['total'] ) ? (float) $l['total'] : ( ! empty( $l['kg_m'] ) ? (float) $l['kg_m'] * $len / 1000 * $qty : 0.0 );
				$out[] = array(
					'key'         => self::line_key( $l ),
					'designation' => (string) ( $l['designation'] ?? $p->designation ),
					'qty'         => $qty,
					'length_mm'   => $len,
					'unit'        => 'piece',
					'kg'          => round( $kg, 3 ),
					'kg_m'        => (float) ( $l['kg_m'] ?? 0 ),
					'piece_kg'    => $qty > 0 ? round( $kg / $qty, 3 ) : 0.0,
				);
			}
			return $out;
		}
		$bars = 'bars' === $p->qty_mode && $p->pieces > 0;
		$out[] = array(
			'key'         => '',
			'designation' => (string) $p->designation,
			'qty'         => $bars ? (float) $p->pieces : (float) $p->total_weight,
			'length_mm'   => (int) $p->length_mm,
			'unit'        => $bars ? ( $p->length_mm > 0 ? 'bar' : 'piece' ) : 'kg', // Bolts and other pieces have no length.
			'kg'          => round( (float) $p->total_weight, 3 ),
			'kg_m'        => $p->length_mm > 0 && $p->unit_weight > 0 ? round( $p->unit_weight / ( $p->length_mm / 1000 ), 3 ) : 0.0,
			'piece_kg'    => (float) $p->unit_weight,
		);
		return $out;
	}

	/** References and locations of the given products: "product|line" => row. */
	public static function refs( array $product_ids ) {
		global $wpdb;
		if ( ! $product_ids ) {
			return array();
		}
		$in   = implode( ',', array_map( 'intval', $product_ids ) );
		$rows = $wpdb->get_results( 'SELECT * FROM ' . self::refs_table() . " WHERE product_id IN ($in)" ); // phpcs:ignore WordPress.DB
		$out  = array();
		foreach ( $rows as $r ) {
			$out[ $r->product_id . '|' . $r->line_key ] = $r;
		}
		return $out;
	}

	/** Next free reference of a company: N-0001 for new steel, R-0001 for reused. */
	public static function next_ref( $company_id, $prefix ) {
		global $wpdb;
		$like = $wpdb->esc_like( $prefix . '-' ) . '%';
		$refs = $wpdb->get_col( $wpdb->prepare( 'SELECT ref FROM ' . self::refs_table() . ' WHERE company_id = %d AND ref LIKE %s', $company_id, $like ) ); // phpcs:ignore WordPress.DB
		$max  = 0;
		foreach ( $refs as $r ) {
			if ( preg_match( '/^' . preg_quote( $prefix, '/' ) . '-(\d+)$/', $r, $m ) ) {
				$max = max( $max, (int) $m[1] );
			}
		}
		return sprintf( '%s-%04d', $prefix, $max + 1 );
	}

	/** Give a reference to every variant that has none (and note its arrival in the movements). */
	public static function ensure_refs( $company_id, array $products ) {
		global $wpdb;
		$refs = self::refs( array_map( static fn( $p ) => (int) $p->id, $products ) );
		foreach ( $products as $p ) {
			$prefix = 'reuse' === $p->market ? 'R' : 'N';
			$fresh  = false;
			foreach ( self::variants( $p ) as $v ) {
				if ( isset( $refs[ $p->id . '|' . $v['key'] ] ) ) {
					continue;
				}
				$row = array( 'company_id' => (int) $company_id, 'product_id' => (int) $p->id, 'line_key' => $v['key'], 'ref' => self::next_ref( $company_id, $prefix ), 'location' => '', 'created_at' => current_time( 'mysql', true ) );
				$wpdb->insert( self::refs_table(), $row ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$refs[ $p->id . '|' . $v['key'] ] = (object) $row;
				$fresh = true;
			}
			if ( $fresh && ! Moves::has( (int) $p->id ) ) {
				$total = array_sum( array_map( static fn( $v ) => 'kg' === $v['unit'] ? 0 : $v['qty'], self::variants( $p ) ) );
				Moves::add( (int) $company_id, (int) $p->id, 'add', Products::get( $p->id ) ? \SM\Products\Presenter::title_text( $p ) : $p->designation, $total, $total ? 'piece' : 'kg', (float) $p->total_weight, '', '', $p->created_at );
			}
		}
		return $refs;
	}

	/** Change the reference and the location of a variant. true or WP_Error. */
	public static function set_ref( $company_id, $product_id, $line_key, $ref, $location ) {
		global $wpdb;
		$ref = strtoupper( trim( (string) $ref ) );
		if ( ! preg_match( '/^[A-Z0-9][A-Z0-9\-\/.]{0,19}$/', $ref ) ) {
			return new \WP_Error( 'ref', __( 'A reference has 1 to 20 letters, digits, dashes, dots or slashes.', 'sm-stock' ) );
		}
		$other = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::refs_table() . ' WHERE company_id = %d AND ref = %s AND NOT ( product_id = %d AND line_key = %s )', $company_id, $ref, $product_id, $line_key ) ); // phpcs:ignore WordPress.DB
		if ( $other ) {
			/* translators: %s: reference. */
			return new \WP_Error( 'ref', sprintf( __( 'The reference %s is already used in your warehouse.', 'sm-stock' ), $ref ) );
		}
		$row = array( 'ref' => $ref, 'location' => mb_substr( sanitize_text_field( (string) $location ), 0, 120 ) );
		$has = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::refs_table() . ' WHERE product_id = %d AND line_key = %s', $product_id, $line_key ) ); // phpcs:ignore WordPress.DB
		if ( $has ) {
			$wpdb->update( self::refs_table(), $row, array( 'id' => (int) $has ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		} else {
			$wpdb->insert( self::refs_table(), $row + array( 'company_id' => (int) $company_id, 'product_id' => (int) $product_id, 'line_key' => (string) $line_key, 'created_at' => current_time( 'mysql', true ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}
		return true;
	}

	/** Weight of everything in the warehouse of a company (kg), for the limits. */
	public static function weight( $company_id, $except_product = 0 ) {
		global $wpdb;
		$in = implode( ',', array_fill( 0, count( self::IN_STOCK ), '%s' ) );
		return (float) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(total_weight),0) FROM ' . Products::table() . " WHERE company_id = %d AND id <> %d AND market IN ('products','reuse') AND status IN ($in)", array_merge( array( (int) $company_id, (int) $except_product ), self::IN_STOCK ) ) ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Change the quantity of a variant (correction). For bars, the weight and the
	 * total price follow; for an inventory line, the line and the lot totals.
	 *
	 * @return array|\WP_Error { before, after, unit, kg } of the change.
	 */
	public static function adjust( $p, $line_key, $qty ) {
		global $wpdb;
		$qty = max( 0, (float) $qty );
		if ( $p->lines ) {
			$lines  = array();
			$found  = null;
			foreach ( $p->lines as $l ) {
				if ( self::line_key( $l ) === $line_key && null === $found ) {
					$old        = (int) ( $l['qty'] ?? 0 );
					$new        = (int) floor( $qty );
					$per        = $old > 0 ? (float) ( $l['total'] ?? 0 ) / $old : ( ! empty( $l['kg_m'] ) ? (float) $l['kg_m'] * (int) $l['length_mm'] / 1000 : 0 );
					$l['qty']   = $new;
					$l['total'] = round( $per * $new, 3 );
					$found      = array( 'before' => $old, 'after' => $new, 'unit' => 'piece', 'kg' => round( $per * ( $new - $old ), 3 ) );
				}
				if ( (int) ( $l['qty'] ?? 0 ) > 0 ) {
					$lines[] = $l;
				}
			}
			if ( ! $found ) {
				return new \WP_Error( 'line', __( 'This variant is no longer in the item.', 'sm-stock' ) );
			}
			$weight = round( array_sum( array_map( static fn( $l ) => (float) ( $l['total'] ?? 0 ), $lines ) ), 3 );
			$row    = array(
				'inventory'    => wp_json_encode( $lines ),
				'pieces'       => array_sum( array_map( static fn( $l ) => (int) $l['qty'], $lines ) ),
				'total_weight' => $weight,
				'updated_at'   => current_time( 'mysql', true ),
			);
			if ( 'kg' === $p->price_unit ) {
				$row['total_price'] = round( (float) $p->price * $weight, 2 );
			}
			$row = (array) apply_filters( 'sm_products_stock_row', $row, $p, $lines );
		} else {
			$bars = 'bars' === $p->qty_mode && $p->pieces > 0;
			if ( $bars ) {
				$new    = (int) floor( $qty );
				$per    = (float) $p->unit_weight > 0 ? (float) $p->unit_weight : (float) $p->total_weight / (float) $p->pieces;
				$weight = round( $per * $new, 3 );
				$found  = array( 'before' => (float) $p->pieces, 'after' => $new, 'unit' => $p->length_mm > 0 ? 'bar' : 'piece', 'kg' => round( $weight - (float) $p->total_weight, 3 ) );
				$row    = array( 'pieces' => $new, 'total_weight' => $weight );
			} else {
				$weight = round( $qty, 3 );
				$found  = array( 'before' => (float) $p->total_weight, 'after' => $weight, 'unit' => 'kg', 'kg' => round( $weight - (float) $p->total_weight, 3 ) );
				$row    = array( 'total_weight' => $weight );
				if ( (float) $p->unit_weight > 0 ) {
					$row['pieces'] = round( $weight / (float) $p->unit_weight, 2 );
				}
			}
			$row['total_price'] = round(
				array(
					'kg'   => (float) $p->price * $weight,
					'unit' => (float) $p->price * ( $row['pieces'] ?? 0 ),
					'ml'   => (float) $p->price * (float) $p->length_mm / 1000 * ( $row['pieces'] ?? 0 ),
				)[ $p->price_unit ] ?? (float) $p->total_price,
				2
			);
			$row['updated_at'] = current_time( 'mysql', true );
		}
		$wpdb->update( Products::table(), $row, array( 'id' => (int) $p->id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		Products::index( $p->id );
		if ( class_exists( '\SM\Products\PriceTrend' ) ) {
			\SM\Products\PriceTrend::flush();
		}
		return $found;
	}
}

<?php
/**
 * The warehouse in the cutting optimiser (0.2.0, sm-weight-engine 0.6.0):
 *  - filter sm_cutting_stock_offers: the bars of the company of the same section
 *    (new steel and reused variants, published or kept in stock only), searched
 *    before the marketplace;
 *  - sm_stock_cut_out (admin-post): once the cutting is done, the bars used are
 *    taken out of the warehouse (movement « cutting », with the reference);
 *  - sm_stock_add_offcuts (admin-post): the reusable offcuts of the plan go to the
 *    reuse warehouse, kept in stock only, with automatic references.
 * Nothing is taken out by the calculation itself.
 *
 * @package SM\Stock
 */

namespace SM\Stock;

use SM\Core\Routing\Routes;
use SM\Core\Support\Forms;
use SM\Products\Presenter;
use SM\Products\Products;
use SM\Products\Spec;

defined( 'ABSPATH' ) || exit;

final class Cutting {

	const MAX_OFFCUTS = 40;

	/** Conditions of the reuse market an offcut can have (scrap never). */
	const CONDITIONS = array( 'new_surplus', 'excellent', 'good', 'inspection' );

	public static function init() {
		add_filter( 'sm_cutting_stock_offers', array( __CLASS__, 'offers' ), 10, 3 );
		add_action( 'admin_post_sm_stock_cut_out', array( __CLASS__, 'cut_out' ) );
		add_action( 'admin_post_sm_stock_add_offcuts', array( __CLASS__, 'add_offcuts' ) );
	}

	/** Key of a variant in the optimiser: P{product}:{line}. */
	public static function key( $product_id, $line_key ) {
		return 'P' . (int) $product_id . ':' . $line_key;
	}

	/**
	 * Bars of the warehouse of the current company for a section.
	 *
	 * @param array  $offers  Offers so far.
	 * @param object $section Section of the catalogue.
	 * @param array  $f       { new: bool, reuse: bool, unpublished: bool (only what is kept in stock only) }.
	 * @param object $company Company (default: the current one).
	 * @return array offer: key, product_id, line_key, ref, location, title, market, condition, grade, status,
	 *               published (bool), url, lines [ {length, qty} ].
	 */
	public static function offers( $offers, $section, array $f, $company = null ) {
		$company = $company ?: sm_current_company();
		$sid     = (int) ( is_object( $section ) ? $section->id : $section );
		if ( ! $sid || ! Limits::has_warehouse( $company ) ) {
			return $offers;
		}
		foreach ( Items::MARKETS as $tab => $market ) {
			if ( empty( $f[ $tab ] ) ) {
				continue;
			}
			$products = Items::products( (int) $company->id, $market, ! empty( $f['unpublished'] ) ? 'stock' : '' );
			$refs     = $products ? Items::ensure_refs( (int) $company->id, $products ) : array();
			foreach ( $products as $p ) {
				$cond = 'products' === $p->market ? 'new' : (string) $p->item_condition;
				if ( 'scrap' === $cond ) {
					continue;
				}
				$variants = array();
				if ( $p->lines ) {
					foreach ( $p->lines as $l ) {
						if ( (int) ( $l['section_id'] ?? 0 ) === $sid && (int) ( $l['length_mm'] ?? 0 ) > 0 && (int) ( $l['qty'] ?? 0 ) > 0 && empty( $l['width_mm'] ) ) {
							$variants[] = array( Items::line_key( $l ), (int) $l['length_mm'], (int) $l['qty'], (string) ( $l['grade'] ?? $p->grade ) );
						}
					}
				} elseif ( (int) $p->section_id === $sid && 'bars' === $p->qty_mode && $p->length_mm > 0 && floor( $p->pieces ) >= 1 ) {
					$variants[] = array( '', (int) $p->length_mm, (int) floor( $p->pieces ), (string) $p->grade );
				}
				foreach ( $variants as list( $line, $len, $qty, $grade ) ) {
					$r        = $refs[ $p->id . '|' . $line ] ?? null;
					$offers[] = array(
						'key'        => self::key( $p->id, $line ),
						'product_id' => (int) $p->id,
						'line_key'   => $line,
						'ref'        => $r ? (string) $r->ref : '',
						'location'   => $r ? (string) $r->location : '',
						'title'      => Presenter::title_text( $p ),
						'market'     => $tab,
						'condition'  => $cond,
						'grade'      => $grade,
						'status'     => (string) $p->status,
						'published'  => in_array( $p->status, array( 'published', 'pending', 'paused' ), true ),
						'url'        => Routes::url( 'account', array( 'view' => 'reuse' === $tab ? 'stock-reuse' : 'stock', 'ref' => $r ? $r->ref : '' ) ),
						'lines'      => array( array( 'length' => $len, 'qty' => $qty ) ),
					);
				}
			}
		}
		return $offers;
	}

	/** Quantity of a variant now (bars or pieces), null when it is not there. */
	private static function available( $p, $line ) {
		foreach ( Items::variants( $p ) as $v ) {
			if ( $v['key'] === $line && 'kg' !== $v['unit'] ) {
				return (int) floor( $v['qty'] );
			}
		}
		return null;
	}

	/**
	 * Take bars out of the warehouse after the cutting. $take: key => number of bars.
	 *
	 * @return array|\WP_Error { bars, kg, items } taken out.
	 */
	public static function take_out( $company, array $take ) {
		$done = array( 'bars' => 0, 'kg' => 0.0, 'items' => 0 );
		$todo = array();
		foreach ( $take as $key => $n ) {
			$n = (int) $n;
			if ( $n < 1 || ! preg_match( '/^P(\d+):([a-z0-9_\-x]*)$/', (string) $key, $m ) ) {
				continue;
			}
			$p = Products::get( (int) $m[1] );
			if ( ! $p || (int) $p->company_id !== (int) $company->id || ! in_array( $p->market, Items::MARKETS, true ) || ! in_array( $p->status, Items::IN_STOCK, true ) ) {
				return new \WP_Error( 'item', __( 'This item is not in your warehouse.', 'sm-stock' ) );
			}
			$have = self::available( $p, $m[2] );
			if ( null === $have || $have < $n ) {
				/* translators: %s: item. */
				return new \WP_Error( 'qty', sprintf( __( '%s: your warehouse no longer has these bars. Run the optimiser again.', 'sm-stock' ), Presenter::title_text( $p ) ) );
			}
			$todo[] = array( $p, $m[2], $have, $n );
		}
		if ( ! $todo ) {
			return new \WP_Error( 'none', __( 'There is no bar of your warehouse in this plan.', 'sm-stock' ) );
		}
		foreach ( $todo as list( $p, $line, $have, $n ) ) {
			$p   = Products::get( $p->id ); // Two variants of the same lot: read it again.
			$res = Items::adjust( $p, $line, $have - $n );
			if ( is_wp_error( $res ) ) {
				return $res;
			}
			$ref = Items::refs( array( (int) $p->id ) )[ $p->id . '|' . $line ]->ref ?? '';
			Moves::add( (int) $company->id, (int) $p->id, 'cutting', Presenter::title_text( $p ), $res['after'] - $res['before'], $res['unit'], $res['kg'], __( 'Cutting plan', 'sm-stock' ), $ref );
			Actions::sold_if_empty( $p->id );
			$done['bars'] += $n;
			$done['kg']   += -$res['kg'];
			++$done['items'];
		}
		return $done;
	}

	public static function cut_out() {
		Forms::begin( 'stock_cut_out' );
		$c    = Actions::company() ?: Forms::back( array( __( 'Your account has no warehouse.', 'sm-stock' ) ) );
		$take = array_map( 'absint', (array) Forms::post( 'take', array() ) );
		$res  = self::take_out( $c, $take );
		if ( is_wp_error( $res ) ) {
			Forms::back( array( $res->get_error_message() ) );
		}
		/* translators: %d: number of bars. */
		Forms::back( array(), sprintf( _n( '%d bar taken out of your warehouse for the cutting. The movement is in the history.', '%d bars taken out of your warehouse for the cutting. The movements are in the history.', $res['bars'], 'sm-stock' ), $res['bars'] ), Routes::url( 'account', array( 'view' => 'stock-moves' ) ) );
	}

	/**
	 * Add reusable offcuts to the reuse warehouse, kept in stock only. One item per
	 * condition and grade, one variant (and reference) per profile and length.
	 *
	 * @param array $rows [ {section, length (mm), count, condition, grade} ].
	 * @return array|\WP_Error { items: [product ids], pieces, kg }
	 */
	public static function add( $company, array $rows, $location = '' ) {
		$groups = array();
		foreach ( array_slice( $rows, 0, self::MAX_OFFCUTS ) as $r ) {
			$s   = \SM\WeightEngine\Catalogue::get( absint( $r['section'] ?? 0 ) );
			$len = absint( $r['length'] ?? 0 );
			$n   = min( 100, absint( $r['count'] ?? 0 ) );
			if ( ! $s || $len < 100 || $len > 24000 || $n < 1 ) {
				continue;
			}
			$cond = (string) ( $r['condition'] ?? '' );
			$cond = 'new' === $cond ? 'new_surplus' : ( in_array( $cond, self::CONDITIONS, true ) ? $cond : 'good' );
			$grade = strtoupper( preg_replace( '/[^A-Za-z0-9+ ]/', '', (string) ( $r['grade'] ?? '' ) ) );
			$groups[ $cond . '|' . $grade ][ $s->id ][] = array( $s, $len, $n );
		}
		if ( ! $groups ) {
			return new \WP_Error( 'none', __( 'Tick at least one offcut to add.', 'sm-stock' ) );
		}
		$specs = array();
		$kg    = 0.0;
		foreach ( $groups as $gk => $by_section ) {
			list( $cond, $grade ) = explode( '|', $gk );
			foreach ( $by_section as $list ) {
				$s    = $list[0][0];
				$spec = Spec::build(
					array(
						'category'   => 'reuse-profiles',
						'family'     => $s->family,
						'section'    => $s->designation,
						'grade'      => $grade,
						'condition'  => $cond,
						'price'      => '',
						'price_unit' => 'kg',
						'wilaya'     => (string) $company->wilaya,
						'attrs'      => array( 'origin' => 'workshop' ),
						'rows'       => array_map( static fn( $x ) => array( 'length' => number_format( $x[1] / 1000, 3, '.', '' ), 'qty' => (string) $x[2] ), $list ),
					)
				);
				unset( $spec['errors']['price'] ); // Kept in stock only: the price comes when it goes on the market.
				if ( $spec['errors'] ) {
					return new \WP_Error( 'spec', implode( ' ', array_map( 'strval', $spec['errors'] ) ) );
				}
				$specs[] = $spec;
				$kg     += (float) $spec['data']['total_weight'];
			}
		}
		$limit = Limits::check( '', $company, $kg, null );
		if ( $limit ) {
			return new \WP_Error( 'limit', $limit );
		}
		$out = array( 'items' => array(), 'pieces' => 0, 'kg' => round( $kg, 3 ) );
		foreach ( $specs as $spec ) {
			$pid = Products::save( 0, (int) $company->id, get_current_user_id(), $spec, 'stock' );
			$p   = Products::get( $pid );
			Moves::add( (int) $company->id, $pid, 'offcuts', Presenter::title_text( $p ), (int) $p->pieces, 'piece', (float) $p->total_weight, __( 'Cutting plan', 'sm-stock' ) );
			$refs = Items::ensure_refs( (int) $company->id, array( $p ) );
			if ( '' !== trim( (string) $location ) ) {
				foreach ( Items::variants( $p ) as $v ) {
					$r = $refs[ $pid . '|' . $v['key'] ] ?? null;
					if ( $r ) {
						Items::set_ref( (int) $company->id, $pid, $v['key'], $r->ref, $location );
					}
				}
			}
			$out['items'][] = $pid;
			$out['pieces'] += (int) $p->pieces;
		}
		return $out;
	}

	public static function add_offcuts() {
		Forms::begin( 'stock_add_offcuts' );
		$c    = Actions::company() ?: Forms::back( array( __( 'Your account has no warehouse.', 'sm-stock' ) ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Forms::begin(); nested rows, sanitised here.
		$raw  = isset( $_POST['off'] ) && is_array( $_POST['off'] ) ? map_deep( wp_unslash( $_POST['off'] ), 'sanitize_text_field' ) : array();
		$rows = array_filter( $raw, static fn( $r ) => is_array( $r ) && ! empty( $r['on'] ) );
		$res  = self::add( $c, array_values( $rows ), sanitize_text_field( (string) Forms::post( 'location' ) ) );
		if ( is_wp_error( $res ) ) {
			Forms::back( array( $res->get_error_message() ) );
		}
		/* translators: %d: number of offcuts. */
		Forms::back( array(), sprintf( _n( '%d offcut added to your reuse warehouse, kept in stock only.', '%d offcuts added to your reuse warehouse, kept in stock only.', $res['pieces'], 'sm-stock' ), $res['pieces'] ), Routes::url( 'account', array( 'view' => 'stock-reuse' ) ) );
	}
}

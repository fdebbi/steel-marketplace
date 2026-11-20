<?php
/**
 * Lines read in a document (sm-ai 0.4.0, « Read a document ») added to the
 * warehouse (0.3.0): delivery note, invoice, inventory. One item per line, kept
 * in stock only, no price (asked when it goes on the market), automatic
 * reference, optional location. A line given by its weight keeps the weight of
 * the document (weighed weight) with the number of bars rounded to the nearest.
 * The capacity of the plan is checked for the whole document.
 *
 * Filter sm_ai_doc_to_stock( null, $company, $bars, $plates, $opts ) → { items, url } | WP_Error.
 *
 * @package SM\Stock
 */

namespace SM\Stock;

use SM\Core\Routing\Routes;
use SM\Products\Categories;
use SM\Products\Presenter;
use SM\Products\Products;
use SM\Products\Spec;

defined( 'ABSPATH' ) || exit;

final class Documents {

	public static function init() {
		add_filter( 'sm_ai_doc_to_stock', array( __CLASS__, 'add' ), 10, 5 );
	}

	/** Category of the listing engine for a family in a market (products|reuse), or ''. */
	public static function category( $family, $market ) {
		foreach ( Categories::all() as $c ) {
			if ( $c->market === $market && 'engine' === $c->kind && in_array( $family, (array) $c->families, true ) ) {
				return (string) $c->slug;
			}
		}
		return '';
	}

	/**
	 * @param mixed  $done    Previous result (null).
	 * @param object $company Company.
	 * @param array  $bars    { family, section, length (m), qty_stock, doc_kg, text }.
	 * @param array  $plates  { t, l, w, qty, label }.
	 * @param array  $opts    { market: new|reuse, location }.
	 */
	public static function add( $done, $company, array $bars, array $plates, array $opts ) {
		if ( ! Limits::has_warehouse( $company ) || ! Actions::company() ) {
			return $done;
		}
		$tab    = 'reuse' === ( $opts['market'] ?? '' ) ? 'reuse' : 'new';
		$market = Items::MARKETS[ $tab ];
		$base   = array(
			'wilaya'     => (string) $company->wilaya,
			'price'      => '',
			'price_unit' => 'kg',
		);
		if ( 'reuse' === $tab ) {
			$base += array( 'condition' => 'good', 'attrs' => array( 'origin' => 'end_of_stock' ) );
		}
		$specs = array();
		$kg    = 0.0;
		foreach ( $bars as $b ) {
			$s   = \SM\WeightEngine\Catalogue::get( (int) $b['section'] );
			$cat = $s ? self::category( $s->family, $market ) : '';
			if ( ! $cat ) {
				continue;
			}
			$n  = max( 1, (int) $b['qty_stock'] );
			$in = $base + array( 'category' => $cat, 'family' => $s->family, 'section' => $s->designation );
			if ( 'reuse' === $tab ) {
				$in['rows'] = array( array( 'length' => (string) $b['length'], 'qty' => (string) $n ) );
			} else {
				$in += array( 'bars' => (string) $n, 'length' => 'custom', 'length_custom' => (string) $b['length'] );
			}
			if ( $b['doc_kg'] > 0 ) { // The weight of the document (invoice, delivery note) is kept.
				$in += array( 'weight_source' => 'actual', 'actual_weight' => (string) $b['doc_kg'] );
			}
			$specs[] = $in;
		}
		$pcat = self::category( 'PLATE', $market );
		foreach ( $plates as $p ) {
			if ( ! $pcat ) {
				break;
			}
			$in = $base + array( 'category' => $pcat, 'family' => 'PLATE', 'plate_kind' => 'hot', 'plate_t' => (string) $p['t'] );
			if ( 'reuse' === $tab ) {
				$in['rows'] = array( array( 'length' => (string) $p['l'], 'width' => (string) $p['w'], 'qty' => (string) $p['qty'] ) );
			} else {
				$in += array( 'plate_length' => (string) $p['l'], 'width' => (string) $p['w'], 'bars' => (string) $p['qty'] );
			}
			$specs[] = $in;
		}
		$built = array();
		foreach ( $specs as $in ) {
			$sp = Spec::build( $in );
			unset( $sp['errors']['price'], $sp['errors']['lot_price'] ); // In stock only: the price comes when it goes on the market.
			if ( $sp['errors'] ) {
				/* translators: %s: what is wrong. */
				return new \WP_Error( 'spec', sprintf( __( 'A line cannot be added: %s', 'sm-stock' ), implode( ' ', array_map( 'strval', $sp['errors'] ) ) ) );
			}
			$built[] = $sp;
			$kg     += (float) $sp['data']['total_weight'];
		}
		if ( ! $built ) {
			return new \WP_Error( 'none', __( 'No line can go in the warehouse.', 'sm-stock' ) );
		}
		$limit = Limits::check( '', $company, $kg, null );
		if ( $limit ) {
			return new \WP_Error( 'limit', $limit );
		}
		$items    = array();
		$location = trim( (string) ( $opts['location'] ?? '' ) );
		foreach ( $built as $sp ) {
			$pid = Products::save( 0, (int) $company->id, get_current_user_id(), $sp, 'stock' );
			$p   = Products::get( $pid );
			Moves::add( (int) $company->id, $pid, 'document', Presenter::title_text( $p ), (float) $p->pieces, 'piece', (float) $p->total_weight, __( 'Read from a document', 'sm-stock' ) );
			$refs = Items::ensure_refs( (int) $company->id, array( $p ) );
			if ( '' !== $location ) {
				foreach ( Items::variants( $p ) as $v ) {
					$r = $refs[ $pid . '|' . $v['key'] ] ?? null;
					if ( $r ) {
						Items::set_ref( (int) $company->id, $pid, $v['key'], $r->ref, $location );
					}
				}
			}
			$items[] = $pid;
		}
		return array( 'items' => $items, 'kg' => round( $kg, 3 ), 'url' => Routes::url( 'account', array( 'view' => 'reuse' === $tab ? 'stock-reuse' : 'stock' ) ) );
	}
}

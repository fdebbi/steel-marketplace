<?php
/**
 * Data for the theme (sm_theme_data):
 *  - stock_enabled : the current account has a warehouse;
 *  - stock         : one tab of the warehouse (args: tab new|reuse, filter, q):
 *                    items with their variants, references and locations, figures, limits;
 *  - stock_moves   : movements (args: page);
 *  - stock_prices  : preview of a price for a whole type (args: the selection).
 *
 * @package SM\Stock
 */

namespace SM\Stock\Theme;

use SM\Products\Presenter;
use SM\Stock\Items;
use SM\Stock\Limits;
use SM\Stock\Moves;
use SM\Stock\Prices;

defined( 'ABSPATH' ) || exit;

final class Bridge {

	public static function init() {
		add_filter( 'sm_theme_data_stock_enabled', static fn( $r ) => null !== $r ? $r : Limits::has_warehouse( sm_current_company() ) );
		add_filter( 'sm_theme_data_stock', array( __CLASS__, 'stock' ), 10, 2 );
		add_filter( 'sm_theme_data_stock_moves', array( __CLASS__, 'moves' ), 10, 2 );
		add_filter( 'sm_theme_data_stock_prices', array( __CLASS__, 'prices' ), 10, 2 );
	}

	public static function stock( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$c = sm_current_company();
		if ( ! Limits::has_warehouse( $c ) ) {
			return null;
		}
		$tab    = 'reuse' === ( $args['tab'] ?? '' ) ? 'reuse' : 'new';
		$filter = in_array( $args['filter'] ?? '', array( 'published', 'stock', 'sold' ), true ) ? $args['filter'] : '';
		$q      = mb_strtolower( trim( (string) ( $args['q'] ?? '' ) ) );
		$all    = Items::products( (int) $c->id, Items::MARKETS[ $tab ] );
		$refs   = Items::ensure_refs( (int) $c->id, $all );
		$list   = $filter ? Items::products( (int) $c->id, Items::MARKETS[ $tab ], $filter ) : $all;
		if ( 'sold' === $filter ) {
			$refs += Items::refs( array_map( static fn( $p ) => (int) $p->id, $list ) );
		}
		$counts = array( 'all' => count( $all ), 'published' => 0, 'stock' => 0 );
		$t      = array( 'kg' => 0.0, 'published_kg' => 0.0, 'published_value' => 0.0, 'stock_kg' => 0.0 );
		foreach ( $all as $p ) {
			$t['kg'] += (float) $p->total_weight;
			if ( in_array( $p->status, array( 'published', 'pending', 'paused' ), true ) ) {
				++$counts['published'];
				$t['published_kg']    += (float) $p->total_weight;
				$t['published_value'] += (float) $p->total_price;
			} else {
				++$counts['stock'];
				$t['stock_kg'] += (float) $p->total_weight;
			}
		}
		$items = array();
		foreach ( $list as $p ) {
			$vars = array();
			foreach ( Items::variants( $p ) as $v ) {
				$r      = $refs[ $p->id . '|' . $v['key'] ] ?? null;
				$v['ref']      = $r ? $r->ref : '';
				$v['location'] = $r ? $r->location : '';
				if ( $q && false === mb_strpos( mb_strtolower( $v['ref'] . ' ' . $v['location'] . ' ' . $v['designation'] . ' ' . $p->grade ), $q ) ) {
					continue;
				}
				$vars[] = $v;
			}
			if ( ! $vars ) {
				continue;
			}
			$items[] = array(
				'id'           => (int) $p->id,
				'listing_id'   => (int) $p->listing_id,
				'title'        => Presenter::title_text( $p ),
				'designation'  => $p->designation,
				'grade'        => $p->grade,
				'family'       => $p->family,
				'condition'    => 'products' === $p->market ? 'new' : (string) $p->item_condition,
				'status'       => $p->status,
				'price'        => (float) $p->price,
				'price_unit'   => (string) $p->price_unit,
				'kg'           => (float) $p->total_weight,
				'variants'     => $vars,
				'url'          => sm_url( 'listing', array( 'id' => $p->listing_id ) ),
				'edit'         => sm_url( 'account', array( 'view' => 'publish', 'edit' => $p->id ) ),
			);
		}
		$max = Limits::capacity( $c );
		return array(
			'tab'      => $tab,
			'filter'   => $filter,
			'items'    => $items,
			'counts'   => $counts,
			'totals'   => $t,
			'tools'    => Limits::tools( $c ),
			'capacity' => array( 'max_kg' => $max, 'used_kg' => Items::weight( (int) $c->id ) ),
			'moves'    => array_map( array( __CLASS__, 'move' ), Moves::recent( (int) $c->id, 5, 1, Items::MARKETS[ $tab ] ) ),
		);
	}

	public static function move( $m ) {
		return array( 'kind' => $m->kind, 'label' => $m->label, 'qty' => (float) $m->qty, 'unit' => $m->unit, 'kg' => (float) $m->kg, 'note' => $m->note, 'ref' => $m->ref, 'at' => get_date_from_gmt( $m->created_at, 'Y-m-d H:i' ) );
	}

	public static function moves( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$c = sm_current_company();
		if ( ! Limits::has_warehouse( $c ) ) {
			return null;
		}
		$page = max( 1, (int) ( $args['page'] ?? 1 ) );
		return array(
			'items' => array_map( array( __CLASS__, 'move' ), Moves::recent( (int) $c->id, 30, $page ) ),
			'total' => Moves::count( (int) $c->id ),
			'page'  => $page,
			'per'   => 30,
		);
	}

	public static function prices( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$c = sm_current_company();
		if ( ! Limits::has_warehouse( $c ) ) {
			return null;
		}
		$sel  = Prices::read( (array) $args );
		$fams = array();
		foreach ( Items::products( (int) $c->id, 'products' ) as $p ) {
			if ( $p->family ) {
				$fams[ $p->family ][ (int) $p->section_id ] = $p->designation;
			}
		}
		ksort( $fams );
		return array(
			'tools'    => Limits::tools( $c ),
			'sel'      => $sel,
			'families' => $fams,
			'rows'     => Prices::preview( (int) $c->id, $sel ),
		);
	}
}

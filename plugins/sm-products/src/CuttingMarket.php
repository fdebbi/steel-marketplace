<?php
/**
 * Offers of the marketplace for the cutting optimiser (0.19.0, sm-weight-engine
 * 0.5.0): published bars of a given section, new (sm-products) and reused
 * (inventory lines of sm-reuse), with what can be bought (part or whole lot,
 * from the same rules as the « Buy » button) and the public data of the seller.
 *
 * Filter sm_cutting_market_offers( $offers, $section, $filters ):
 *   filters: grade_min (int, 0 = any), conds (list), wilaya (code or ''), lots (bool), exclude_company (int).
 *   offer:   key, listing_id, product_id, title, url, company, verified, wilaya, condition, grade,
 *            price_per_kg, lot_price, part (bool), lot (bool), lines [ {length, qty} ].
 *
 * @package SM\Products
 */

namespace SM\Products;

use SM\Core\Accounts\Companies;

defined( 'ABSPATH' ) || exit;

final class CuttingMarket {

	const MAX_LISTINGS = 60;

	public static function init() {
		add_filter( 'sm_cutting_market_offers', array( __CLASS__, 'offers' ), 10, 3 );
	}

	/** Strength of a grade (S275JR → 275), 0 when unknown. */
	public static function grade_value( $grade ) {
		return preg_match( '/S\s*(\d{3})/i', (string) $grade, $m ) ? (int) $m[1] : 0;
	}

	public static function offers( $offers, $section, array $f ) {
		global $wpdb;
		$sid = (int) ( is_object( $section ) ? $section->id : $section );
		if ( ! $sid ) {
			return $offers;
		}
		$t    = Products::table();
		$like = '%' . $wpdb->esc_like( '"section_id":' . $sid . ',' ) . '%';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM $t WHERE status = 'published' AND market IN ('products','reuse') AND company_id <> %d AND ( section_id = %d OR inventory LIKE %s ) ORDER BY price_per_kg = 0, price_per_kg ASC, id DESC LIMIT %d", (int) ( $f['exclude_company'] ?? 0 ), $sid, $like, self::MAX_LISTINGS ) );
		$conds = (array) ( $f['conds'] ?? array() );
		foreach ( $ids as $id ) {
			$p = Products::get( (int) $id );
			if ( ! $p ) {
				continue;
			}
			$cond = 'products' === $p->market ? 'new' : (string) $p->item_condition;
			if ( 'scrap' === $cond || ( $conds && ! in_array( $cond, $conds, true ) ) ) {
				continue;
			}
			if ( ! empty( $f['wilaya'] ) && (string) $p->wilaya !== (string) $f['wilaya'] ) {
				continue;
			}
			$lines = array();
			$grade = (string) $p->grade;
			if ( $p->lines ) {
				foreach ( $p->lines as $l ) {
					if ( (int) ( $l['section_id'] ?? 0 ) === $sid && (int) ( $l['length_mm'] ?? 0 ) > 0 && (int) ( $l['qty'] ?? 0 ) > 0 ) {
						$lines[] = array( 'length' => (int) $l['length_mm'], 'qty' => (int) $l['qty'] );
						$grade   = (string) ( $l['grade'] ?? $grade );
					}
				}
			} elseif ( (int) $p->section_id === $sid && $p->length_mm > 0 && floor( $p->pieces ) >= 1 ) {
				$lines[] = array( 'length' => (int) $p->length_mm, 'qty' => (int) floor( $p->pieces ) );
			}
			if ( ! $lines ) {
				continue;
			}
			$min = (int) ( $f['grade_min'] ?? 0 );
			if ( $min && self::grade_value( $grade ) < $min ) {
				continue; // Unknown or lower grade.
			}
			$opt  = Stock::options( $p );
			$part = ! empty( $opt['detail'] );
			$lot  = ! empty( $opt['lot'] ) && ! empty( $f['lots'] );
			if ( ! $part && ! $lot ) {
				continue;
			}
			$company  = Companies::get( (int) $p->company_id );
			$offers[] = array(
				'key'          => 'L' . (int) $p->listing_id,
				'listing_id'   => (int) $p->listing_id,
				'product_id'   => (int) $p->id,
				'title'        => Presenter::title_text( $p ),
				'url'          => sm_url( 'listing', array( 'id' => $p->listing_id ) ),
				'company'      => $company ? (string) $company->name : '',
				'verified'     => $company && (int) $company->verification_level >= 2,
				'wilaya'       => (string) $p->wilaya,
				'condition'    => $cond,
				'grade'        => $grade,
				'price_per_kg' => (float) $p->price_per_kg,
				'lot_price'    => $lot ? (float) $opt['lot']['total'] : 0.0,
				'part'         => $part,
				'lot'          => ! $part && $lot,
				'lines'        => $lines,
			);
		}
		return $offers;
	}
}

<?php
/**
 * Array shapes of a request ("rfq") and of an offer for the theme and the REST API.
 * Offers are only given to the buyer (all) and to the supplier (his own).
 *
 * @package SM\Rfq
 */

namespace SM\Rfq;

use SM\Core\Accounts\Companies;
use SM\Core\Content\Media;
use SM\Core\Content\Translations;

defined( 'ABSPATH' ) || exit;

final class Presenter {

	public static function pick( array $values ) {
		if ( function_exists( 'sm_pick' ) ) {
			return sm_pick( $values ); // Includes the English of default data (sm-core 0.8.0).
		}
		$lang = function_exists( 'sm_current_language' ) ? sm_current_language() : 'fr';
		return (string) ( $values[ $lang ] ?? '' ) !== '' ? $values[ $lang ] : (string) ( array_values( array_filter( $values ) )[0] ?? '' );
	}

	public static function title_text( $r ) {
		$t = Translations::get( 'rfq', $r->id );
		return self::pick( (array) ( $t['title'] ?? array() ) ) ?: $r->designation;
	}

	private static function files( $entity, $id ) {
		$out = array();
		foreach ( Media::get( $entity, $id, 'file' ) as $aid ) {
			$file  = get_attached_file( $aid );
			$out[] = array(
				'id'   => $aid,
				'name' => get_the_title( $aid ) ? get_the_title( $aid ) : basename( (string) $file ),
				'url'  => wp_get_attachment_url( $aid ),
				'type' => (string) get_post_mime_type( $aid ),
				'size' => $file && file_exists( $file ) ? filesize( $file ) : 0,
			);
		}
		return $out;
	}

	/** Public shape (board, page, REST). */
	public static function rfq( $r, $texts = null ) {
		$texts   = $texts ?? Translations::get( 'rfq', $r->id );
		$company = Companies::get( $r->company_id );
		$markets = Spec::markets();
		$cat     = $r->category ? \SM\Products\Categories::get( $r->category ) : null;
		$days    = $r->deadline ? (int) floor( ( strtotime( $r->deadline . ' 23:59:59 UTC' ) - time() ) / DAY_IN_SECONDS ) : null;
		return array(
			'id'             => $r->id,
			'title'          => array_filter( (array) ( $texts['title'] ?? array() ) ) ?: array( 'fr' => $r->designation ),
			'description'    => array_filter( (array) ( $texts['description'] ?? array() ) ),
			'notes'          => array_filter( (array) ( $texts['notes'] ?? array() ) ),
			'market'         => $r->market,
			'market_label'   => $markets[ $r->market ] ?? array(),
			'category'       => $r->category,
			'category_label' => $cat ? array( 'fr' => $cat->name_fr, 'ar' => $cat->name_ar ) : array(),
			'designation'    => $r->designation,
			'dimensions'     => $r->dimensions,
			'quantity'       => $r->quantity,
			'unit'           => $r->unit,
			'weight_kg'      => $r->weight_kg,
			'weight_source'  => $r->weight_source,
			'lines'          => array_map(
				static fn( $l ) => array(
					'designation' => $l['designation'],
					'qty'         => (int) $l['qty'],
					'length'      => $l['length_mm'] / 1000,
					'unit_weight' => (float) $l['kg_m'],
					'total'       => (float) $l['total'],
				),
				$r->lines
			),
			'wilaya'         => $r->wilaya,
			'place'          => $r->place,
			'needed_by'      => $r->needed_by,
			'deadline'       => $r->deadline,
			'days_left'      => $days,
			'status'         => $r->status,
			'is_open'        => Rfqs::is_open( $r ),
			'offers_count'   => $r->offers_count,
			'company'        => $company ? $company->slug : '',
			'company_name'   => $company ? array( 'fr' => $company->name, 'ar' => $company->name_ar ?: $company->name ) : array(),
			'verification'   => $company ? (int) $company->verification_level : 0,
			'date'           => $r->published_at ? $r->published_at : $r->created_at,
			'reject_reason'  => (string) $r->reject_reason,
		);
	}

	/** Detail: public shape + files. */
	public static function detail( $r ) {
		return array_merge( self::rfq( $r ), array( 'files' => self::files( 'rfq', $r->id ) ) );
	}

	/** Offer with the supplier's public information. */
	public static function offer( $o, $rfq = null ) {
		$c = Companies::get( $o->company_id );
		return array(
			'id'                => $o->id,
			'rfq_id'            => $o->rfq_id,
			'status'            => $o->status,
			'price_mode'        => $o->price_mode,
			'price'             => $o->price,
			'weight_kg'         => $o->weight_kg > 0 ? $o->weight_kg : (float) ( $rfq->weight_kg ?? 0 ),
			'weight_from'       => $o->weight_kg > 0 ? 'supplier' : 'request',
			'total_price'       => $o->total_price,
			'price_per_kg'      => $o->price_per_kg,
			'delivery_days'     => $o->delivery_days,
			'delivery_included' => (bool) $o->delivery_included,
			'validity_days'     => $o->validity_days,
			'valid_until'       => gmdate( 'Y-m-d', strtotime( $o->updated_at . ' UTC' ) + $o->validity_days * DAY_IN_SECONDS ),
			'payment_terms'     => $o->payment_terms,
			'message'           => (string) $o->message,
			'files'             => self::files( 'rfq_offer', $o->id ),
			'date'              => $o->updated_at,
			'supplier'          => $c ? array(
				'slug'         => $c->slug,
				'name'         => array( 'fr' => $c->name, 'ar' => $c->name_ar ?: $c->name ),
				'wilaya'       => $c->wilaya,
				'verification' => (int) $c->verification_level,
				'phone'        => $c->phone,
				'email'        => $c->email,
				'since'        => substr( (string) $c->created_at, 0, 4 ),
			) : array(),
		);
	}

	/**
	 * Offers of a request for its buyer, with the best values flagged
	 * (lowest total, lowest price per kg, shortest delivery) among active offers.
	 */
	public static function comparison( $r, $sort = 'total' ) {
		$offers = array_map( static fn( $o ) => self::offer( $o, $r ), Offers::for_rfq( $r->id, false ) );
		$active = array_filter( $offers, static fn( $o ) => in_array( $o['status'], Offers::ACTIVE, true ) );
		$best   = array( 'total' => null, 'kg' => null, 'days' => null );
		foreach ( $active as $o ) {
			if ( $o['total_price'] > 0 && ( null === $best['total'] || $o['total_price'] < $best['total'] ) ) {
				$best['total'] = $o['total_price'];
			}
			if ( $o['price_per_kg'] > 0 && ( null === $best['kg'] || $o['price_per_kg'] < $best['kg'] ) ) {
				$best['kg'] = $o['price_per_kg'];
			}
			if ( null === $best['days'] || $o['delivery_days'] < $best['days'] ) {
				$best['days'] = $o['delivery_days'];
			}
		}
		foreach ( $offers as &$o ) {
			$on        = in_array( $o['status'], Offers::ACTIVE, true );
			$o['best'] = array(
				'total' => $on && null !== $best['total'] && $o['total_price'] == $best['total'], // phpcs:ignore Universal.Operators.StrictComparisons
				'kg'    => $on && null !== $best['kg'] && $o['price_per_kg'] == $best['kg'], // phpcs:ignore Universal.Operators.StrictComparisons
				'days'  => $on && null !== $best['days'] && $o['delivery_days'] === $best['days'],
			);
		}
		unset( $o );
		$keys = array(
			'total' => static fn( $o ) => array( $o['total_price'] ),
			'kg'    => static fn( $o ) => array( $o['price_per_kg'] > 0 ? $o['price_per_kg'] : PHP_INT_MAX ),
			'days'  => static fn( $o ) => array( $o['delivery_days'], $o['total_price'] ),
			'trust' => static fn( $o ) => array( -( $o['supplier']['verification'] ?? 0 ), $o['total_price'] ),
		);
		$key = $keys[ $sort ] ?? $keys['total'];
		usort(
			$offers,
			static function ( $a, $b ) use ( $key ) {
				$ra = in_array( $a['status'], Offers::ACTIVE, true ) ? 0 : 1;
				$rb = in_array( $b['status'], Offers::ACTIVE, true ) ? 0 : 1;
				return array_merge( array( $ra ), $key( $a ) ) <=> array_merge( array( $rb ), $key( $b ) );
			}
		);
		return $offers;
	}
}

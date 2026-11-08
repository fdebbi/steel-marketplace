<?php
/**
 * Data for the sm-marketplace theme (data contract): rfqs, rfq, rfq_form,
 * rfq_preview, my_rfqs, my_offers, rfq_settings.
 *
 * @package SM\Rfq
 */

namespace SM\Rfq\Theme;

use SM\Core\Accounts\Companies;
use SM\Core\Content\Media;
use SM\Core\Content\Translations;
use SM\Rfq\Form;
use SM\Rfq\Offers;
use SM\Rfq\Presenter;
use SM\Rfq\Rfqs;
use SM\Rfq\Settings;
use SM\Rfq\Spec;

defined( 'ABSPATH' ) || exit;

final class Bridge {

	public static function init() {
		foreach ( array( 'rfqs', 'rfq', 'rfq_form', 'rfq_preview', 'my_rfqs', 'my_offers', 'rfq_settings' ) as $r ) {
			add_filter( 'sm_theme_data_' . $r, array( __CLASS__, $r ), 10, 2 );
		}
	}

	/** Public board: open requests. */
	public static function rfqs( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$q     = Rfqs::query(
			array(
				'status'   => 'open',
				'market'   => sanitize_key( (string) ( $args['market'] ?? '' ) ),
				'category' => sanitize_key( (string) ( $args['category'] ?? '' ) ),
				'wilaya'   => preg_replace( '/\D/', '', (string) ( $args['wilaya'] ?? '' ) ),
				'q'        => sanitize_text_field( (string) ( $args['q'] ?? '' ) ),
				'sort'     => 'deadline' === ( $args['sort'] ?? '' ) ? 'deadline' : 'recent',
				'per_page' => (int) ( $args['per_page'] ?? 12 ),
				'page'     => (int) ( $args['page'] ?? 1 ),
			)
		);
		$texts = Translations::get_many( 'rfq', wp_list_pluck( $q['items'], 'id' ) );
		return array(
			'items' => array_map( static fn( $r ) => Presenter::rfq( $r, $texts[ $r->id ] ?? array() ), $q['items'] ),
			'total' => $q['total'],
			'pages' => $q['pages'],
		);
	}

	/** One request with what the viewer may see and do. */
	public static function rfq( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$r = Rfqs::get( (int) ( $args['id'] ?? 0 ) );
		if ( ! $r ) {
			return null;
		}
		$user     = get_current_user_id();
		$is_buyer = Form::is_buyer( $r, $user );
		$is_mod   = current_user_can( 'sm_moderate' );
		if ( in_array( $r->status, array( 'pending', 'rejected' ), true ) && ! $is_buyer && ! $is_mod ) {
			return null;
		}
		if ( ! $is_buyer && empty( $args['no_count'] ) ) {
			Rfqs::add_view( $r->id );
		}
		$out     = Presenter::detail( $r );
		$company = $user ? Companies::for_user( $user ) : null;
		$mine    = $company && ! $is_buyer ? Offers::by_company( $r->id, $company->id ) : null;
		$block   = Form::offer_block( $r, $user );
		if ( ! $company && ! $is_mod ) {
			$out['files_count'] = count( $out['files'] );
			$out['files']       = array(); // Drawings are for signed-in companies only.
		}
		$out['viewer'] = array(
			'is_buyer'    => $is_buyer,
			'can_edit'    => $is_buyer && in_array( $r->status, array( 'pending', 'open', 'rejected' ), true ) && 0 === $r->offers_count,
			'can_close'   => $is_buyer && in_array( $r->status, array( 'pending', 'open' ), true ),
			'can_delete'  => $is_buyer && 0 === $r->offers_count && 'awarded' !== $r->status,
			'can_decide'  => $is_buyer && in_array( $r->status, array( 'open', 'expired' ), true ),
			'can_offer'   => '' === $block && ( ! $mine || in_array( $mine->status, array( 'submitted', 'shortlisted', 'withdrawn' ), true ) ),
			'offer_block' => $block,
			'signed_in'   => (bool) $user,
		);
		$out['my_offer'] = $mine ? Presenter::offer( $mine, $r ) : null;
		$out['offers']   = $is_buyer ? Presenter::comparison( $r, sanitize_key( (string) ( $args['sort'] ?? 'total' ) ) ) : array();
		if ( $is_buyer && $r->awarded_offer ) {
			$out['awarded_offer'] = $r->awarded_offer;
		}
		return $out;
	}

	/** Saved request as form values (edit). */
	public static function rfq_form( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$r = Rfqs::get( (int) ( $args['id'] ?? 0 ) );
		if ( ! $r || ! Form::is_buyer( $r ) ) {
			return null;
		}
		$t = Translations::get( 'rfq', $r->id );
		$v = array(
			'rfq_id'      => $r->id,
			'market'      => $r->market,
			'category'    => $r->category,
			'designation' => $r->designation,
			'dimensions'  => $r->dimensions,
			'quantity'    => $r->lines ? '' : ( $r->quantity ? (string) ( 0 + $r->quantity ) : '' ),
			'unit'        => $r->unit,
			'weight_kg'   => $r->lines || ! $r->weight_kg ? '' : (string) ( 0 + $r->weight_kg ),
			'title'       => array( 'fr' => $t['title']['fr'] ?? '', 'ar' => $t['title']['ar'] ?? '' ),
			'description' => trim( implode( '', (array) ( $t['description'] ?? array() ) ) ),
			'notes'       => trim( implode( '', (array) ( $t['notes'] ?? array() ) ) ),
			'wilaya'      => $r->wilaya,
			'place'       => $r->place,
			'needed_by'   => (string) $r->needed_by,
			'deadline'    => (string) $r->deadline,
			'keep_files'  => Media::get( 'rfq', $r->id, 'file' ),
			'lines'       => array_map(
				static fn( $l ) => array(
					'family'  => $l['family'],
					'section' => (string) $l['section_id'],
					'qty'     => (string) $l['qty'],
					'length'  => (string) ( $l['length_mm'] / 1000 ),
				),
				$r->lines
			),
		);
		return array( 'values' => $v, 'status' => $r->status, 'offers_count' => $r->offers_count, 'reject_reason' => (string) $r->reject_reason );
	}

	/** Live summary of the request form (profile rows weighed by the engine). */
	public static function rfq_preview( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$s = Spec::build( (array) $args, false );
		return array( 'errors' => $s['errors'], 'summary' => $s['summary'] );
	}

	public static function my_rfqs( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$company = Companies::for_user( get_current_user_id() );
		if ( ! $company ) {
			return array( 'items' => array(), 'counts' => array(), 'total' => 0, 'pages' => 0 );
		}
		$status = sanitize_key( (string) ( $args['status'] ?? '' ) );
		$q      = Rfqs::query(
			array(
				'status'   => in_array( $status, Rfqs::STATUSES, true ) ? $status : 'any',
				'company'  => $company->id,
				'sort'     => 'updated',
				'per_page' => 20,
				'page'     => (int) ( $args['page'] ?? 1 ),
			)
		);
		$texts  = Translations::get_many( 'rfq', wp_list_pluck( $q['items'], 'id' ) );
		return array(
			'items'  => array_map( static fn( $r ) => Presenter::rfq( $r, $texts[ $r->id ] ?? array() ), $q['items'] ),
			'counts' => Rfqs::count_by_status( $company->id ),
			'total'  => $q['total'],
			'pages'  => $q['pages'],
		);
	}

	public static function my_offers( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$company = Companies::for_user( get_current_user_id() );
		if ( ! $company ) {
			return array( 'items' => array() );
		}
		$status = sanitize_key( (string) ( $args['status'] ?? '' ) );
		$items  = array();
		foreach ( Offers::for_company( $company->id, in_array( $status, Offers::STATUSES, true ) ? $status : '' ) as $o ) {
			$r = Rfqs::get( $o->rfq_id );
			if ( ! $r ) {
				continue;
			}
			$items[] = array_merge( Presenter::offer( $o, $r ), array( 'rfq' => Presenter::rfq( $r ) ) );
		}
		return array( 'items' => $items );
	}

	public static function rfq_settings( $result ) {
		if ( null !== $result ) {
			return $result;
		}
		$s    = Settings::all();
		$cats = array();
		foreach ( \SM\Products\Categories::all() as $slug => $c ) {
			$cats[ $c->market ][ $slug ] = array( 'fr' => $c->name_fr, 'ar' => $c->name_ar );
		}
		$today = current_time( 'Y-m-d', true );
		return array(
			'markets'          => Spec::markets(),
			'categories'       => $cats,
			'units'            => Rfqs::UNITS,
			'families'         => Spec::families(),
			'default_deadline' => gmdate( 'Y-m-d', strtotime( $today . ' +' . (int) $s['default_days'] . ' days' ) ),
			'min_deadline'     => gmdate( 'Y-m-d', strtotime( $today . ' +1 day' ) ),
			'max_deadline'     => gmdate( 'Y-m-d', strtotime( $today . ' +' . (int) $s['max_days'] . ' days' ) ),
			'today'            => $today,
			'max_files'        => (int) $s['max_files'],
			'file_mb'          => (int) $s['file_mb'],
			'moderation'       => (int) $s['moderation'],
			'can_create'       => ! is_wp_error( sm_can_explain( 'rfq.create' ) ),
			'create_reason'    => is_wp_error( sm_can_explain( 'rfq.create' ) ) ? sm_can_explain( 'rfq.create' )->get_error_message() : '',
		);
	}
}

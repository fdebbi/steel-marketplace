<?php
/**
 * Data for the sm-marketplace theme (data contract).
 *
 * @package SM\Products
 */

namespace SM\Products\Theme;

use SM\Core\Accounts\Companies;
use SM\Core\Content\Media;
use SM\Core\Content\Translations;
use SM\Products\Categories;
use SM\Products\Form;
use SM\Products\Presenter;
use SM\Products\Products;
use SM\Products\Settings;
use SM\Products\Spec;

defined( 'ABSPATH' ) || exit;

final class Bridge {

	public static function init() {
		add_filter( 'sm_theme_data_listings', array( __CLASS__, 'listings' ), 10, 2 );
		add_filter( 'sm_theme_data_listing', array( __CLASS__, 'listing' ), 10, 2 );
		add_filter( 'sm_theme_data_categories', array( __CLASS__, 'categories' ), 10, 2 );
		add_filter( 'sm_theme_data_product_categories', array( __CLASS__, 'product_categories' ) );
		add_filter( 'sm_theme_data_product_form', array( __CLASS__, 'product_form' ), 10, 2 );
		add_filter( 'sm_theme_data_product_preview', array( __CLASS__, 'product_preview' ), 10, 2 );
		add_filter( 'sm_theme_data_my_products', array( __CLASS__, 'my_products' ), 10, 2 );
		add_filter( 'sm_theme_data_product_settings', array( __CLASS__, 'settings' ) );
	}

	private static function only_products( $args ) {
		$m = (array) ( $args['market'] ?? array() );
		return array( 'products' ) === array_values( $m );
	}

	public static function listings( $result, $args ) {
		if ( null !== $result || ! self::only_products( $args ) ) {
			return $result;
		}
		$sort = array( 'price-asc' => 'price_asc', 'price-desc' => 'price_desc', 'weight' => 'weight_desc' )[ $args['sort'] ?? '' ] ?? 'recent';
		$q    = Products::query(
			array(
				'status'    => 'published',
				'category'  => sanitize_key( (string) ( $args['category'] ?? '' ) ),
				'family'    => strtoupper( sanitize_key( (string) ( $args['family'] ?? '' ) ) ),
				'wilaya'    => preg_replace( '/\D/', '', (string) ( $args['wilaya'] ?? '' ) ),
				'company'   => ! empty( $args['company'] ) ? (int) ( Companies::get_by_slug( $args['company'] )->id ?? -1 ) : 0,
				'q'         => sanitize_text_field( (string) ( $args['q'] ?? '' ) ),
				'min_price' => (float) ( $args['min_price'] ?? 0 ),
				'max_price' => (float) ( $args['max_price'] ?? 0 ),
				'sort'      => $sort,
				'per_page'  => (int) ( $args['per_page'] ?? 12 ),
				'page'      => (int) ( $args['page'] ?? 1 ),
			)
		);
		$ids    = wp_list_pluck( $q['items'], 'id' );
		$texts  = Translations::get_many( 'product', $ids );
		$photos = Media::first_many( 'product', $ids );
		return array(
			'items' => array_map( static fn( $p ) => Presenter::listing( $p, $texts[ $p->id ] ?? array(), $photos[ $p->id ] ?? 0 ), $q['items'] ),
			'total' => $q['total'],
			'pages' => $q['pages'],
		);
	}

	/** Published product, or any status for its own company and the moderators (preview). */
	public static function listing( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$p = Products::by_listing( (int) ( $args['id'] ?? 0 ) );
		if ( ! $p ) {
			return null;
		}
		if ( 'published' !== $p->status && ! current_user_can( 'sm_moderate' ) && ! Form::editable( $p->id ) ) {
			return in_array( $p->status, array( 'sold', 'expired' ), true ) ? array_merge( Presenter::detail( $p ), array( 'unavailable' => true ) ) : null;
		}
		if ( 'published' === $p->status && ! Form::editable( $p->id ) && empty( $args['no_count'] ) ) {
			Products::add_view( $p->id );
		}
		return Presenter::detail( $p );
	}

	public static function categories( $result, $args ) {
		if ( null !== $result || 'products' !== ( $args['market'] ?? '' ) ) {
			return $result;
		}
		return Categories::labels();
	}

	public static function product_categories( $result ) {
		if ( null !== $result ) {
			return $result;
		}
		$out = array();
		foreach ( Categories::all() as $slug => $c ) {
			$out[ $slug ] = array(
				'slug'     => $slug,
				'name'     => array( 'fr' => $c->name_fr, 'ar' => $c->name_ar ),
				'icon'     => $c->icon,
				'kind'     => $c->kind,
				'families' => $c->families,
				'units'    => $c->units,
				'fields'   => $c->fields,
			);
		}
		return $out;
	}

	/** Saved product as form values (edit), plus its files. */
	public static function product_form( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$p = Form::editable( (int) ( $args['id'] ?? 0 ) );
		if ( ! $p ) {
			return null;
		}
		$t = Translations::get( 'product', $p->id );
		$v = array(
			'product_id'    => $p->id,
			'category'      => $p->category,
			'family'        => $p->family,
			'section'       => $p->section_id ? $p->section_id : '',
			'dims'          => $p->section_id ? array() : $p->dims,
			'grade'         => $p->grade,
			'qty_mode'      => $p->qty_mode,
			'bars'          => 'bars' === $p->qty_mode && $p->pieces ? (string) (int) $p->pieces : '',
			'total_kg'      => 'weight' === $p->qty_mode && $p->total_weight ? (string) $p->total_weight : '',
			'weight_source' => $p->weight_source,
			'actual_weight' => 'actual' === $p->weight_source && 'bars' === $p->qty_mode ? (string) $p->total_weight : '',
			'price'         => $p->price ? (string) $p->price : '',
			'price_unit'    => $p->price_unit,
			'specs'         => $p->specs,
			'quantity'      => $p->quantity ? (string) $p->quantity : '',
			'unit'          => $p->unit,
			'wilaya'        => $p->wilaya,
			'title'         => array( 'fr' => $t['title']['fr'] ?? '', 'ar' => $t['title']['ar'] ?? '' ),
			'description'   => trim( ( $t['description']['fr'] ?? '' ) . ( $t['description']['ar'] ?? '' ) ),
			'keep_photos'   => Media::get( 'product', $p->id, 'photo' ),
			'keep_sheets'   => Media::get( 'product', $p->id, 'sheet' ),
		);
		if ( 'PLATE' === $p->family ) {
			$v['width']        = (string) $p->width_mm;
			$v['plate_length'] = (string) $p->length_mm;
		} elseif ( $p->length_mm ) {
			$lengths = (array) sm_weight_setting( 'bar_lengths' );
			if ( in_array( $p->length_mm, array_map( 'intval', $lengths ), true ) ) {
				$v['length'] = (string) $p->length_mm;
			} else {
				$v['length']        = 'custom';
				$v['length_custom'] = (string) ( $p->length_mm / 1000 );
			}
		}
		if ( 'actual' === $p->weight_source && 'specs' === ( Categories::get( $p->category )->kind ?? '' ) && 'kg' !== $p->unit ) {
			$v['actual_weight'] = (string) $p->total_weight;
		}
		return array( 'values' => $v, 'status' => $p->status, 'reject_reason' => (string) $p->reject_reason, 'listing_id' => $p->listing_id );
	}

	/** Live summary of the publication form: weight, pieces, totals, errors of the technical part. */
	public static function product_preview( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$spec = Spec::build( (array) $args, false );
		return array(
			'errors'  => $spec['errors'],
			'summary' => $spec['summary'],
			'calc'    => $spec['calc'],
		);
	}

	/** Products of the signed-in company, for "My listings". */
	public static function my_products( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$company = Companies::for_user( get_current_user_id() );
		if ( ! $company ) {
			return array( 'items' => array(), 'counts' => array(), 'total' => 0, 'pages' => 0 );
		}
		$status = sanitize_key( (string) ( $args['status'] ?? '' ) );
		$q      = Products::query(
			array(
				'status'   => in_array( $status, Products::STATUSES, true ) ? $status : 'any',
				'company'  => $company->id,
				'sort'     => 'updated',
				'per_page' => 20,
				'page'     => (int) ( $args['page'] ?? 1 ),
			)
		);
		$ids    = wp_list_pluck( $q['items'], 'id' );
		$texts  = Translations::get_many( 'product', $ids );
		$photos = Media::first_many( 'product', $ids );
		return array(
			'items'  => array_map( static fn( $p ) => Presenter::listing( $p, $texts[ $p->id ] ?? array(), $photos[ $p->id ] ?? 0 ), $q['items'] ),
			'counts' => Products::count_by_status( $company->id ),
			'total'  => $q['total'],
			'pages'  => $q['pages'],
		);
	}

	public static function settings( $result ) {
		if ( null !== $result ) {
			return $result;
		}
		$s = Settings::all();
		return array(
			'grades'     => $s['grades'],
			'max_photos' => $s['max_photos'],
			'photo_mb'   => $s['photo_mb'],
			'sheet_mb'   => $s['sheet_mb'],
			'moderation' => (int) $s['moderation'],
		);
	}
}

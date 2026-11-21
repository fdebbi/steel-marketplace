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
use SM\Products\Markets;
use SM\Products\Presenter;
use SM\Products\Products;
use SM\Products\Settings;
use SM\Products\Spec;

defined( 'ABSPATH' ) || exit;

final class Bridge {

	public static function init() {
		add_filter( 'sm_theme_data_listings', array( __CLASS__, 'listings' ), 10, 2 );
		add_filter( 'sm_theme_data_listing_facets', static fn( $r, $a ) => \SM\Products\Facets::get( sanitize_key( (string) ( $a['market'] ?? 'products' ) ), sanitize_key( (string) ( $a['category'] ?? '' ) ), sanitize_text_field( (string) ( $a['family'] ?? '' ) ) ), 10, 2 );
		add_filter( 'sm_theme_data_listing', array( __CLASS__, 'listing' ), 10, 2 );
		add_filter( 'sm_theme_data_stats', array( __CLASS__, 'stats' ) ); // 0.22.1: real figures of the home page.
		add_filter( 'sm_theme_data_account_stock', array( __CLASS__, 'account_stock' ), 10, 2 );
		add_filter( 'sm_theme_data_categories', array( __CLASS__, 'categories' ), 10, 2 );
		add_filter( 'sm_theme_data_product_categories', array( __CLASS__, 'product_categories' ), 10, 2 );
		add_filter( 'sm_theme_data_product_markets', array( __CLASS__, 'product_markets' ) );
		add_filter( 'sm_theme_data_product_form', array( __CLASS__, 'product_form' ), 10, 2 );
		add_filter( 'sm_theme_data_product_preview', array( __CLASS__, 'product_preview' ), 10, 2 );
		add_filter( 'sm_theme_data_product_form_state', array( __CLASS__, 'product_form_state' ), 10, 2 );
		add_filter( 'sm_theme_data_my_products', array( __CLASS__, 'my_products' ), 10, 2 );
		add_filter( 'sm_theme_data_product_settings', array( __CLASS__, 'settings' ) );
		add_filter( 'sm_theme_data_price_trend', array( __CLASS__, 'price_trend' ) );
	}

	/**
	 * Requested markets this engine serves. A mixed request that includes a market
	 * no plugin serves yet is left to the demo provider while demo mode is on.
	 */
	private static function served_markets( $args ) {
		$asked = array_values( array_filter( (array) ( $args['market'] ?? array() ) ) );
		$mine  = array_values( array_filter( $asked, array( Markets::class, 'exists' ) ) );
		if ( ! $mine || ( count( $mine ) < count( $asked ) && function_exists( 'sm_theme_demo_enabled' ) && sm_theme_demo_enabled() ) ) {
			return array();
		}
		return $mine;
	}

	public static function listings( $result, $args ) {
		$markets = null === $result ? self::served_markets( $args ) : array();
		if ( ! $markets ) {
			return $result;
		}
		$sort = array( 'price-asc' => 'price_asc', 'price-desc' => 'price_desc', 'weight' => 'weight_desc' )[ $args['sort'] ?? '' ] ?? 'recent';
		$q    = Products::query(
			array(
				'status'     => 'published',
				'market'     => $markets,
				'condition'  => sanitize_key( (string) ( $args['condition'] ?? '' ) ),
				'mode'       => sanitize_key( (string) ( $args['mode'] ?? '' ) ),
				'length_min' => (float) ( $args['length_min'] ?? 0 ),
				'qty_min'    => (float) ( $args['qty_min'] ?? 0 ),
				'category'  => sanitize_key( (string) ( $args['category'] ?? '' ) ),
				'family'    => strtoupper( sanitize_key( (string) ( $args['family'] ?? '' ) ) ),
				'wilaya'    => preg_replace( '/\D/', '', (string) ( $args['wilaya'] ?? '' ) ),
				'company'   => ! empty( $args['company'] ) ? (int) ( Companies::get_by_slug( $args['company'] )->id ?? -1 ) : 0,
				'q'         => sanitize_text_field( (string) ( $args['q'] ?? '' ) ),
				'verified'   => ! empty( $args['verified'] ),
				'designation' => sanitize_text_field( (string) ( $args['designation'] ?? '' ) ),
				'weight_min'  => (float) ( $args['weight_min'] ?? 0 ),
				'sale'        => sanitize_key( (string) ( $args['sale'] ?? '' ) ),
				'spec'        => array_intersect_key( array_map( 'sanitize_text_field', (array) ( $args['spec'] ?? array() ) ), array_flip( \SM\Products\Facets::spec_keys( (string) ( $args['category'] ?? '' ) ) ) ),
				'area_min'   => (float) ( $args['area_min'] ?? 0 ),
				'height_min' => (float) ( $args['height_min'] ?? 0 ),
				'min_price' => (float) ( $args['min_price'] ?? 0 ),
				'max_price' => (float) ( $args['max_price'] ?? 0 ),
				'sort'      => $sort,
				'per_page'  => (int) ( $args['per_page'] ?? 12 ),
				'page'      => (int) ( $args['page'] ?? 1 ),
			)
		);
		/*
		 * 0.22.0: a search of the catalogue (first page, a type or a size asked) for the
		 * Business report (sm-insights counts it, without knowing who searched).
		 */
		if ( empty( $args['silent'] ) && (int) ( $args['page'] ?? 1 ) <= 1 && ( ! empty( $args['family'] ) || ! empty( $args['designation'] ) || ! empty( $args['q'] ) ) ) { // 0.23.1: « silent » = not a search (catalogue pages).
			do_action(
				'sm_products_searched',
				array(
					'market'      => $markets,
					'family'      => strtoupper( sanitize_key( (string) ( $args['family'] ?? '' ) ) ),
					'designation' => sanitize_text_field( (string) ( $args['designation'] ?? '' ) ),
					'q'           => sanitize_text_field( (string) ( $args['q'] ?? '' ) ),
					'wilaya'      => preg_replace( '/\D/', '', (string) ( $args['wilaya'] ?? '' ) ),
				)
			);
		}
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
		$market = (string) ( $args['market'] ?? '' );
		if ( null !== $result || ! Markets::exists( $market ) ) {
			return $result;
		}
		return Categories::labels( $market );
	}

	/** Markets a seller can publish in, with conditions and extra fields. */
	public static function product_markets( $result ) {
		if ( null !== $result ) {
			return $result;
		}
		$out = array();
		foreach ( Markets::all() as $key => $m ) {
			$allowed     = sm_can_explain( $m['cap'] );
			$out[ $key ] = array(
				'key'        => $key,
				'label'      => $m['label'],
				'intro'      => $m['intro'] ?? array(),
				'icon'       => $m['icon'] ?? '',
				'conditions' => $m['conditions'],
				'fields'     => $m['fields'],
				'elements'   => $m['line_elements'] ?? array(),
				'order'      => (string) ( $m['order'] ?? '' ), // 0.18.0: "after" = condition after the technical data.
				'min_photos' => (int) ( $m['min_photos'] ?? 0 ),
				'allowed'    => ! is_wp_error( $allowed ),
				'reason'     => is_wp_error( $allowed ) ? $allowed->get_error_message() : '',
				'count'      => count( Categories::all( false, $key ) ),
			);
		}
		return $out;
	}

	/** Categories a seller can publish in; all markets unless args.market is given. */
	public static function product_categories( $result, $args = array() ) {
		if ( null !== $result ) {
			return $result;
		}
		$market = (string) ( $args['market'] ?? '' );
		$out    = array();
		foreach ( Categories::all( false, '' !== $market ? $market : null ) as $slug => $c ) {
			if ( ! empty( $args['all'] ) || ! in_array( $c->kind, (array) apply_filters( 'sm_products_unpublished_kinds', array() ), true ) ) { // 0.18.0: lots are made from "My listings".
			$out[ $slug ] = array(
				'slug'     => $slug,
				'market'   => $c->market,
				'max_lines' => (int) apply_filters( 'sm_products_max_lines', 30, $c ),
				'name'     => array( 'fr' => $c->name_fr, 'ar' => $c->name_ar ),
				'icon'     => $c->icon,
				'kind'     => $c->kind,
				'families' => $c->families,
				'units'    => $c->units,
				'detail_units' => Spec::detail_units( $c ),
				'lot'      => Spec::lot_allowed( $c ),
				'fields'   => $c->fields,
				'auto_title' => Spec::auto_title( $c ) || Spec::is_bolts( $c ), // 0.18.0: no title to type.
				'rows'     => 'engine' === $c->kind && ! empty( Markets::get( $c->market )['piece_rows'] ),
				'min_photos' => (int) ( Markets::get( $c->market )['min_photos'] ?? 0 ),
			);
			}
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
		// A change waiting for review is what the seller edits further (0.5.0).
		$rev = Products::revision( $p );
		if ( $rev ) {
			$p = clone $p;
			foreach ( (array) $rev['data'] as $k => $val ) {
				if ( 'lines' === $k ) {
					$p->lines = (array) $val;
				} elseif ( property_exists( $p, $k ) ) {
					$p->$k = $val;
				}
			}
			$t = array( 'title' => (array) $rev['title'], 'description' => array( 'fr' => (string) $rev['description'], 'ar' => '' ) );
		} else {
			$t = Translations::get( 'product', $p->id );
		}
		$v = array(
			'revision'      => (bool) $rev,
			'product_id'    => $p->id,
			'market'        => $p->market,
			'condition'     => (string) $p->item_condition,
			'mode'          => $p->variant,
			'attrs'         => array_map( 'strval', (array) $p->attrs ),
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
			'price'         => $p->price && 'lot' !== ( $p->sale_mode ?? '' ) ? (string) $p->price : '',
			'price_unit'    => $p->price_unit,
			'sale_mode'     => (string) ( $p->sale_mode ?? 'detail' ),
			'lot_price'     => (float) ( $p->lot_price ?? 0 ) > 0 ? (string) ( 0 + $p->lot_price ) : '',
			'specs'         => $p->specs,
			'quantity'      => $p->quantity ? (string) $p->quantity : '',
			'unit'          => $p->unit,
			'wilaya'        => $p->wilaya,
			'commune'       => (string) ( $p->commune ?? '' ),
			'address'       => (string) ( $p->address ?? '' ),
			'lat'           => null === $p->lat ? '' : (string) $p->lat,
			'lng'           => null === $p->lng ? '' : (string) $p->lng,
			'map_public'    => (string) (int) $p->map_public,
			'title'         => array( 'fr' => $t['title']['fr'] ?? '', 'ar' => $t['title']['ar'] ?? '' ),
			'description'   => trim( ( $t['description']['fr'] ?? '' ) . ( $t['description']['ar'] ?? '' ) ),
			'keep_photos'   => $rev ? $rev['photos'] : Media::get( 'product', $p->id, 'photo' ),
			'keep_sheets'   => $rev ? $rev['sheets'] : Media::get( 'product', $p->id, 'sheet' ),
		);
		$kind = Categories::get( $p->category )->kind ?? 'specs';
		$cat_kind = Categories::get( $p->category )->kind ?? '';
		if ( $p->lines && 'engine' === $cat_kind ) {
			// 0.18.0: one size in several lengths or sizes (reuse).
			$v['qty_mode'] = 'rows';
			$v['rows']     = array();
			foreach ( $p->lines as $l ) {
				$v['rows'][] = 'PLATE' === $p->family
					? array( 'length' => (string) $l['length_mm'], 'width' => (string) ( $l['width_mm'] ?? '' ), 'qty' => (string) $l['qty'] )
					: array( 'length' => (string) ( $l['length_mm'] / 1000 ), 'qty' => (string) $l['qty'] );
			}
			$v['section']       = $p->section_id ? $p->section_id : ( $p->dims ? 'custom' : '' );
			$v['actual_weight'] = 'actual' === $p->weight_source ? (string) $p->total_weight : '';
			if ( 'PLATE' === $p->family ) {
				$specs           = (array) $p->specs;
				$v['plate_kind'] = (string) ( $specs['plate_kind'] ?? 'hot' );
				$v['dims']       = array( 't' => (string) ( $p->dims['t'] ?? '' ) );
				$v['hole_d']     = isset( $specs['hole_d'] ) ? (string) $specs['hole_d'] : '';
				$v['hole_p']     = isset( $specs['hole_p'] ) ? (string) $specs['hole_p'] : '';
			}
		} elseif ( $p->lines ) {
			$v['lines'] = array();
			foreach ( $p->lines as $l ) {
				$v['lines'][] = array(
					'element' => (string) ( $l['element'] ?? '' ),
					'family'  => $l['family'],
					'section' => (string) $l['section_id'],
					'qty'     => (string) $l['qty'],
					'length'  => (string) ( $l['length_mm'] / 1000 ),
				);
			}
			$v['actual_weight'] = 'actual' === $p->weight_source ? (string) $p->total_weight : '';
			$v['section']       = '';
		}
		if ( 'lines' === $kind || ! in_array( $kind, array( 'engine', 'weight', 'specs' ), true ) ) {
			$v = apply_filters( 'sm_products_form_values', $v, $p );
		} elseif ( 'weight' === $kind ) {
			$v['total_kg'] = $p->total_weight ? (string) $p->total_weight : '';
		} elseif ( 'engine' === $kind && $p->lines ) {
			// Rows already set above.
		} elseif ( 'PLATE' === $p->family ) {
			$v['width']        = (string) $p->width_mm;
			$v['plate_length'] = (string) $p->length_mm;
			// 0.17.0: kind, thickness and format of the plate (plates published before have no kind: black plate).
			$specs           = (array) $p->specs;
			$v['plate_kind'] = (string) ( $specs['plate_kind'] ?? 'hot' );
			$t               = (float) ( $p->dims['t'] ?? 0 );
			$offered         = class_exists( '\\SM\\WeightEngine\\Plates' ) ? wp_list_pluck( \SM\WeightEngine\Plates::thicknesses( $v['plate_kind'] ), 'value' ) : array();
			$v['plate_t']    = in_array( $t, array_map( 'floatval', $offered ), true ) ? (string) $t : 'custom';
			$v['dims']       = array( 't' => (string) $t );
			$formats         = class_exists( '\\SM\\WeightEngine\\Plates' ) ? \SM\WeightEngine\Plates::formats() : array();
			$v['plate_format'] = in_array( array( (int) $p->width_mm, (int) $p->length_mm ), $formats, true ) ? $p->width_mm . 'x' . $p->length_mm : 'custom';
			$v['hole_d']       = isset( $specs['hole_d'] ) ? (string) $specs['hole_d'] : '';
			$v['hole_p']       = isset( $specs['hole_p'] ) ? (string) $specs['hole_p'] : '';
			$v['piece_kg']     = 'actual' === $p->weight_source && $p->unit_weight ? (string) $p->unit_weight : '';
			$v['actual_weight'] = '';
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

	/**
	 * State of the publication form before it is shown (0.13.0): a market plugin
	 * may complete it, e.g. sm-buildings proposes the elements of the structure
	 * from the dimensions. Args: state (typed values), category (slug).
	 */
	public static function product_form_state( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$state = (array) ( $args['state'] ?? array() );
		$cat   = Categories::get( sanitize_key( (string) ( $args['category'] ?? '' ) ) );
		/**
		 * @param array  $state Values of the form.
		 * @param object $cat   Category.
		 */
		return $cat ? (array) apply_filters( 'sm_products_form_state', $state, $cat ) : $state;
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
			'title'   => $spec['title'] ?? array(),
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
		$market = Markets::exists( (string) ( $args['market'] ?? '' ) ) ? (string) $args['market'] : '';
		$q      = Products::query(
			array(
				'status'   => in_array( $status, Products::STATUSES, true ) ? $status : 'any',
				'market'   => $market,
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
			'counts' => Products::count_by_status( $company->id, $market ),
			'markets' => Products::count_by_market( $company->id ), // 0.18.1: tabs New / Reuse / Buildings.
			'market' => $market,
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
			'client'     => self::client_status(),
		);
	}

	/** For a client account: will the listing be checked, how many listings may be online. */
	public static function client_status() {
		$co = is_user_logged_in() ? \SM\Core\Accounts\Companies::for_user( get_current_user_id() ) : null;
		if ( ! $co || 'client' !== $co->account_class ) {
			return null;
		}
		$s = Settings::all();
		$n = \SM\Products\Products::count_by_status( $co->id );
		return array(
			'moderation' => (int) $s['moderation'] || (int) $s['client_moderation'],
			'max'        => (int) $s['client_max_active'],
			'active'     => (int) ( $n['published'] ?? 0 ) + (int) ( $n['pending'] ?? 0 ),
		);
	}

	/**
	 * Home page price curve: average asking price per kg of new sections, week by
	 * week (see PriceTrend). Empty when there are not enough listings.
	 */
	public static function price_trend() {
		return \SM\Products\PriceTrend::summary();
	}

	/**
	 * Steel the account has on sale (0.16.0, overview): listings online, their
	 * weight and value, by market, and the views they received since published.
	 *
	 * @return array { count, kg, value, views, markets: code => { count, kg } }
	 */
	public static function account_stock( $result, $args ) {
		if ( null !== $result || ! is_user_logged_in() ) {
			return $result;
		}
		$company = Companies::for_user( get_current_user_id() );
		$out     = array( 'count' => 0, 'kg' => 0.0, 'value' => 0.0, 'views' => 0, 'markets' => array() );
		if ( ! $company ) {
			return $out;
		}
		global $wpdb;
		$rows = (array) $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT market, COUNT(*) AS n, SUM(total_weight) AS kg, SUM(CASE WHEN sale_mode = 'lot' AND lot_price > 0 THEN lot_price ELSE total_price END) AS v, SUM(views) AS views
				FROM {$wpdb->prefix}sm_products WHERE company_id = %d AND status = 'published' GROUP BY market",
				(int) $company->id
			)
		);
		foreach ( $rows as $r ) {
			$out['count']                  += (int) $r->n;
			$out['kg']                     += (float) $r->kg;
			$out['value']                  += (float) $r->v;
			$out['views']                  += (int) $r->views;
			$out['markets'][ $r->market ]   = array( 'count' => (int) $r->n, 'kg' => round( (float) $r->kg, 1 ) );
		}
		$out['kg']    = round( $out['kg'], 1 );
		$out['value'] = round( $out['value'], 2 );
		return $out;
	}

	/**
	 * Figures of the home page (0.22.1): listings online in every market, active
	 * selling accounts (companies and professionals), steel offered for reuse (kg).
	 * Real figures only; kept one hour.
	 */
	public static function stats( $result ) {
		if ( null !== $result ) {
			return $result;
		}
		$got = get_transient( 'sm_products_home_stats' );
		if ( is_array( $got ) ) {
			return $got;
		}
		global $wpdb;
		$t   = $wpdb->prefix . 'sm_products';
		$out = array(
			'listings'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE status = 'published'" ), // phpcs:ignore WordPress.DB
			'companies' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_companies WHERE status = 'active' AND account_class IN ('company','professional')" ), // phpcs:ignore WordPress.DB
			'reuse_kg'  => (float) $wpdb->get_var( "SELECT COALESCE(SUM(total_weight), 0) FROM $t WHERE status = 'published' AND market = 'reuse'" ), // phpcs:ignore WordPress.DB
			'wilayas'   => (int) $wpdb->get_var( "SELECT COUNT(DISTINCT wilaya) FROM $t WHERE status = 'published' AND wilaya <> ''" ), // phpcs:ignore WordPress.DB
		);
		set_transient( 'sm_products_home_stats', $out, HOUR_IN_SECONDS );
		return $out;
	}
}

<?php
/**
 * Lots of several reuse listings (0.3.0).
 *
 * In "My listings" a seller ticks published reuse listings (same company, same
 * wilaya) and groups them: the lot is a new listing (category reuse-lot, kind
 * "group") sold as a whole only, at the price the seller gives (the sum of the
 * prices is proposed). The grouped listings get the status "grouped": they are
 * no longer sold separately. Selling the lot sells them; deleting ("ungrouping")
 * the lot puts them back on sale one by one.
 *
 * @package SM\Reuse
 */

namespace SM\Reuse;

use SM\Core\Accounts\Companies;
use SM\Core\Content\Media;
use SM\Core\Support\Forms;
use SM\Products\Products;

defined( 'ABSPATH' ) || exit;

final class Groups {

	const CATEGORY = 'reuse-lot';
	const KIND     = 'group';
	const MAX      = 20;

	public static function init() {
		add_filter( 'sm_products_kinds', static fn( $k ) => array_merge( (array) $k, array( self::KIND ) ) );
		add_filter( 'sm_products_unpublished_kinds', static fn( $k ) => array_merge( (array) $k, array( self::KIND ) ) );
		add_action( 'admin_post_sm_reuse_group', array( __CLASS__, 'handle_create' ) );
		add_action( 'admin_post_sm_reuse_ungroup', array( __CLASS__, 'handle_dissolve' ) );
		add_action( 'sm_products_status_changed', array( __CLASS__, 'on_status' ), 10, 3 );
		add_action( 'sm_products_deleted', array( __CLASS__, 'on_deleted' ) );
		add_filter( 'sm_products_listing', array( __CLASS__, 'listing' ), 20, 2 );
		add_filter( 'sm_products_listing_detail', array( __CLASS__, 'detail' ), 20, 2 );
		add_filter( 'sm_products_stock_options', array( __CLASS__, 'stock_options' ), 10, 2 );
	}

	/**
	 * 0.4.0: a reuse listing in several lengths (or plate sizes) is bought by
	 * pieces of its inventory (each length up to its stock) or as a whole lot;
	 * never by a free number of kilos or by bars of one length.
	 */
	public static function stock_options( $out, $p ) {
		if ( Market::CODE !== ( $p->market ?? '' ) || empty( $p->lines ) || self::is_lot( $p ) || empty( $out['detail'] ) ) {
			return $out;
		}
		$lines = \SM\Products\Stock::pick_lines( $p );
		$per   = 'kg' === $p->price_unit ? (float) $p->price : (float) $p->price_per_kg;
		if ( ! $lines || $per <= 0 ) {
			$out['detail'] = null;
			return $out;
		}
		$out['detail'] = array(
			'unit'       => 'kg',
			'step'       => 1.0,
			'unit_price' => $per,
			'lines'      => $lines,
			'max'        => round( array_sum( array_map( static fn( $l ) => $l['qty'] * $l['piece_kg'], $lines ) ), 3 ),
		);
		return $out;
	}

	public static function is_lot( $p ) {
		return $p && self::CATEGORY === ( $p->category ?? '' );
	}

	/** IDs of the listings of a lot. */
	public static function members( $lot ) {
		return array_values( array_filter( array_map( 'intval', (array) ( $lot->specs['members'] ?? array() ) ) ) );
	}

	/** Listings that may be grouped: published reuse listings of the company, not lots, not scrap. */
	public static function groupable( $p, $company_id ) {
		return $p && (int) $p->company_id === (int) $company_id && Market::CODE === $p->market && 'published' === $p->status && ! self::is_lot( $p ) && Rules::SCRAP_CATEGORY !== $p->category;
	}

	/**
	 * Create a lot.
	 *
	 * @param int    $company_id Company.
	 * @param int    $user_id    User.
	 * @param int[]  $ids        Product IDs.
	 * @param string $title      Title typed by the seller ('' = made from the products).
	 * @param float  $price      Price of the lot (0 = sum of the prices).
	 * @return int|\WP_Error Lot product ID.
	 */
	public static function create( $company_id, $user_id, array $ids, $title, $price ) {
		$ids = array_slice( array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) ), 0, self::MAX );
		$ps  = array();
		foreach ( $ids as $id ) {
			$p = Products::get( $id );
			if ( ! self::groupable( $p, $company_id ) ) {
				return new \WP_Error( 'sm_group', __( 'Only your published reuse listings can be grouped (not scrap, not lots).', 'sm-reuse' ) );
			}
			$ps[] = $p;
		}
		if ( count( $ps ) < 2 ) {
			return new \WP_Error( 'sm_group', __( 'Tick at least two listings to make a lot.', 'sm-reuse' ) );
		}
		if ( count( array_unique( wp_list_pluck( $ps, 'wilaya' ) ) ) > 1 ) {
			return new \WP_Error( 'sm_group', __( 'The products of a lot must be in the same wilaya.', 'sm-reuse' ) );
		}
		$sum    = self::sum_prices( $ps );
		$price  = (float) $price > 0 ? round( (float) $price, 2 ) : $sum;
		$s      = \SM\Products\Settings::all();
		if ( $price <= 0 || $price < $s['min_price'] || $price > $s['max_price'] ) {
			return new \WP_Error( 'sm_group', __( 'Enter the price of the lot.', 'sm-reuse' ) );
		}
		$weight = round( array_sum( array_map( static fn( $p ) => (float) $p->total_weight, $ps ) ), 3 );
		$pieces = array_sum( array_map( static fn( $p ) => (float) $p->pieces, $ps ) );
		$first  = $ps[0];
		$title  = mb_substr( trim( sanitize_text_field( (string) $title ) ), 0, 140 );
		if ( '' === $title ) {
			/* translators: %d: number of products. */
			$title = sprintf( __( 'Lot of %d reuse products', 'sm-reuse' ), count( $ps ) );
		}
		$conds  = array_keys( Market::conditions() );
		$worst  = $first->item_condition;
		foreach ( $ps as $p ) {
			if ( array_search( $p->item_condition, $conds, true ) > array_search( $worst, $conds, true ) ) {
				$worst = $p->item_condition; // The lot shows the least good condition; each product keeps its own.
			}
		}
		$spec = array(
			'data'        => array(
				'market'         => Market::CODE,
				'category'       => self::CATEGORY,
				'item_condition' => $worst,
				'designation'    => mb_substr( implode( ' + ', array_unique( array_map( array( '\\SM\\Products\\Presenter', 'designation' ), $ps ) ) ), 0, 80 ),
				'qty_mode'       => 'bars',
				'pieces'         => $pieces,
				'total_weight'   => $weight,
				'weight_source'  => count( array_unique( wp_list_pluck( $ps, 'weight_source' ) ) ) === 1 ? $first->weight_source : 'theoretical',
				'price'          => $price,
				'price_unit'     => 'lot',
				'sale_mode'      => 'lot',
				'lot_price'      => $price,
				'total_price'    => $price,
				'price_per_kg'   => $weight > 0 ? round( $price / $weight, 2 ) : 0,
				'wilaya'         => $first->wilaya,
				'commune'        => (string) $first->commune,
				'address'        => (string) $first->address,
				'lat'            => $first->lat,
				'lng'            => $first->lng,
				'map_public'     => (int) $first->map_public,
				'specs'          => array( 'members' => wp_list_pluck( $ps, 'id' ), 'sum' => $sum ),
				'attrs'          => self::common_attrs( $ps ),
			),
			'title'       => array( 'fr' => $title, 'ar' => $title ),
			'description' => '',
		);
		// Photos of the products, in their order (they stay theirs).
		$photos = array();
		foreach ( $ps as $p ) {
			$photos = array_merge( $photos, Media::get( 'product', $p->id, 'photo' ) );
		}
		$photos = array_slice( array_values( array_unique( $photos ) ), 0, (int) $s['max_photos'] );
		$lot    = Products::save( 0, $company_id, $user_id, $spec, 'published', $photos, array() );
		foreach ( $ps as $p ) {
			self::mark( $p, $lot );
		}
		\SM\Core\Audit\AuditLog::add( 'reuse.lot_created', 'product', $lot, array( 'members' => wp_list_pluck( $ps, 'id' ), 'price' => $price ), $user_id, $company_id );
		return $lot;
	}

	/** What the price includes: only what every product includes. */
	private static function common_attrs( array $ps ) {
		$out = array();
		foreach ( array( 'loading', 'transport', 'cut' ) as $k ) {
			if ( ! array_filter( $ps, static fn( $p ) => empty( $p->attrs[ $k ] ) ) ) {
				$out[ $k ] = 1;
			}
		}
		return $out;
	}

	/** Sum of the prices of the products (lot price, else total price). */
	public static function sum_prices( array $ps ) {
		$sum = 0.0;
		foreach ( $ps as $p ) {
			$sum += (float) $p->lot_price > 0 && 'detail' !== $p->sale_mode ? (float) $p->lot_price : (float) $p->total_price;
		}
		return round( $sum, 2 );
	}

	/** A product enters a lot (or leaves it with $lot = 0). */
	private static function mark( $p, $lot ) {
		global $wpdb;
		$specs = (array) $p->specs;
		if ( $lot ) {
			$specs['group'] = (int) $lot;
		} else {
			unset( $specs['group'] );
		}
		$wpdb->update( Products::table(), array( 'specs' => wp_json_encode( $specs ) ), array( 'id' => (int) $p->id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		Products::set_status( $p->id, $lot ? 'grouped' : 'published' );
	}

	/** Undo a lot: its products go back on sale one by one, the lot is deleted. */
	public static function dissolve( $lot_id ) {
		$lot = Products::get( $lot_id );
		if ( ! self::is_lot( $lot ) ) {
			return false;
		}
		Products::delete( $lot->id ); // on_deleted() frees the products.
		return true;
	}

	/** The lot is sold: so are its products. */
	public static function on_status( $id, $status, $before ) {
		$lot = Products::get( $id );
		if ( ! self::is_lot( $lot ) || 'sold' !== $status ) {
			return;
		}
		foreach ( self::members( $lot ) as $mid ) {
			$m = Products::get( $mid );
			if ( $m && 'grouped' === $m->status ) {
				Products::set_status( $mid, 'sold' );
			}
		}
	}

	/** The lot is deleted: its products still in it go back on sale. */
	public static function on_deleted( $lot ) {
		if ( ! self::is_lot( $lot ) ) {
			return;
		}
		foreach ( self::members( $lot ) as $mid ) {
			$m = Products::get( $mid );
			if ( $m && 'grouped' === $m->status && (int) ( $m->specs['group'] ?? 0 ) === (int) $lot->id ) {
				self::mark( $m, 0 );
			}
		}
	}

	/** Cards and "My listings": number of products of a lot; the lot a product belongs to. */
	public static function listing( $out, $p ) {
		if ( self::is_lot( $p ) ) {
			$out['group_count'] = count( self::members( $p ) );
		}
		if ( ! empty( $p->specs['group'] ) ) {
			$lot              = Products::get( (int) $p->specs['group'] );
			$out['in_group']  = $lot ? array( 'id' => (int) $lot->listing_id, 'product_id' => (int) $lot->id, 'title' => \SM\Products\Presenter::title_text( $lot ) ) : null;
		}
		return $out;
	}

	/** Product page of a lot: every product with its sizes, condition and weight. */
	public static function detail( $out, $p ) {
		if ( ! self::is_lot( $p ) ) {
			return $out;
		}
		$conds = Market::conditions();
		$items = array();
		foreach ( self::members( $p ) as $mid ) {
			$m = Products::get( $mid );
			if ( ! $m ) {
				continue;
			}
			$parts = array();
			$rows  = array(); // 0.38.0: one row per length (plates: per size) under the product.
			foreach ( (array) $m->lines as $l ) {
				$size    = ! empty( $l['width_mm'] ) ? self::n( $l['length_mm'] ) . ' × ' . self::n( $l['width_mm'] ) . ' mm' : self::m( $l['length_mm'] ) . ' m';
				$parts[] = $size . ' × ' . (int) $l['qty'];
				$kg      = isset( $l['total'] ) ? (float) $l['total'] : ( ! empty( $l['kg_m'] ) ? (float) $l['kg_m'] * (float) $l['length_mm'] / 1000 * (int) $l['qty'] : 0.0 );
				if ( isset( $rows[ $size ] ) ) {
					$rows[ $size ]['qty']    += (int) $l['qty'];
					$rows[ $size ]['weight'] += $kg;
				} else {
					$rows[ $size ] = array( 'size' => $size, 'qty' => (int) $l['qty'], 'weight' => $kg );
				}
			}
			if ( ! $parts && $m->pieces > 0 && $m->length_mm > 0 && ! $m->lines ) {
				$parts[] = self::m( $m->length_mm ) . ' m × ' . self::n( $m->pieces );
			}
			if ( ! $parts && 'unit' === $m->unit && $m->quantity > 0 ) {
				/* translators: %s: number of pieces. */
				$parts[] = sprintf( __( '%s pieces', 'sm-reuse' ), self::n( $m->quantity ) );
			}
			$items[] = array(
				'product_id'  => (int) $m->id,
				'title'       => \SM\Products\Presenter::title_text( $m ),
				'designation' => \SM\Products\Presenter::designation( $m ),
				'sizes'       => implode( ' · ', $parts ),
				'rows'        => array_values( $rows ),
				'pieces'      => (float) $m->pieces,
				'condition'   => $conds[ $m->item_condition ] ?? array(),
				'weight'      => (float) $m->total_weight,
			);
		}
		$out['group'] = array(
			'items'  => $items,
			'weight' => (float) $p->total_weight,
			'pieces' => array_sum( wp_list_pluck( $items, 'pieces' ) ),
		);
		$out['condition_mixed'] = count( array_unique( array_map( static fn( $i ) => wp_json_encode( $i['condition'] ), $items ) ) ) > 1;
		return $out;
	}

	/** Length in metres without trailing zeros: 2,5 / 12 / 3,25. */
	private static function m( $mm ) {
		$s = number_format( (float) $mm / 1000, 2, ',', "\u{202F}" );
		return rtrim( rtrim( $s, '0' ), ',' );
	}

	private static function n( $v, $d = 0 ) {
		return number_format( (float) $v, $d, ',', "\u{202F}" );
	}

	/* ---- Forms (admin-post.php) ----------------------------------------- */

	public static function handle_create() {
		Forms::begin( 'reuse_group' );
		$user_id = get_current_user_id();
		$company = Companies::for_user( $user_id );
		if ( ! $company || ! Companies::member_role( $company->id, $user_id ) ) {
			Forms::back( array( __( 'Your account has no company.', 'sm-reuse' ) ) );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in Forms::begin().
		$ids   = array_map( 'absint', (array) wp_unslash( $_POST['products'] ?? array() ) );
		$price = (float) str_replace( array( ' ', ',' ), array( '', '.' ), (string) Forms::post( 'lot_price' ) );
		$lot   = self::create( $company->id, $user_id, $ids, Forms::post( 'lot_title' ), $price );
		if ( is_wp_error( $lot ) ) {
			Forms::back( array( $lot->get_error_message() ) );
		}
		Forms::back( array(), __( 'The lot is online. Its products are no longer sold separately.', 'sm-reuse' ) );
	}

	public static function handle_dissolve() {
		Forms::begin( 'reuse_ungroup' );
		$lot = \SM\Products\Form::editable( absint( Forms::post( 'product_id' ) ) );
		if ( ! self::is_lot( $lot ) ) {
			Forms::back( array( __( 'You cannot change this lot.', 'sm-reuse' ) ) );
		}
		self::dissolve( $lot->id );
		Forms::back( array(), __( 'The lot is undone: its products are on sale again one by one.', 'sm-reuse' ) );
	}
}

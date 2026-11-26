<?php
/**
 * Pages « type of product × wilaya » (0.24.0), for the people who search
 * « profilés M'Sila » or « réemploi acier Oran »:
 *
 *   /produits/profiles/msila/    a type of a market in a wilaya
 *   /reemploi/msila/             a whole market in a wilaya
 *
 * Real figures of the published listings only: number, weight, range of the asked
 * prices per kilogram (from 3 listings with such a price), last update, sizes
 * offered, the same type in the other wilayas, the other types of the wilaya.
 * A page is given to the search engines only from 3 published listings (MIN):
 * under it the page still answers the visitor but is out of the index and of the
 * sitemap, so the pages come and go with the real listings. The name of the
 * wilaya is in latin letters, the same in the 3 languages.
 *
 * @package SM\Products
 */

namespace SM\Products;

defined( 'ABSPATH' ) || exit;

final class Landing {

	/** Published listings needed for a page to be indexed, and for a price range. */
	const MIN = 3;

	const QV_CAT = 'sm_lcat';
	const QV_W   = 'sm_lw';

	const MARKETS = array( 'products', 'reuse', 'buildings' );

	public static function init() {
		add_action( 'init', array( __CLASS__, 'rewrite' ), 21 );
		add_filter( 'query_vars', static fn( $v ) => array_merge( $v, array( self::QV_CAT, self::QV_W ) ) );
		add_filter( 'sm_theme_data_landing', array( __CLASS__, 'data' ), 10, 2 );
	}

	private static function page_id( $market ) {
		$map = (array) get_option( 'sm_theme_pages', array() );
		return (int) ( $map[ $market ] ?? 0 );
	}

	public static function rewrite() {
		$sig = SM_PRODUCTS_VERSION;
		foreach ( self::MARKETS as $market ) {
			$id   = self::page_id( $market );
			$path = $id ? trim( (string) get_page_uri( $id ), '/' ) : '';
			if ( '' === $path ) {
				continue;
			}
			$p = preg_quote( $path, '#' );
			add_rewrite_rule( '^' . $p . '/([a-z0-9-]+)/([a-z0-9-]+)/?$', 'index.php?page_id=' . $id . '&' . self::QV_CAT . '=$matches[1]&' . self::QV_W . '=$matches[2]', 'top' );
			add_rewrite_rule( '^' . $p . '/([a-z0-9-]+)/?$', 'index.php?page_id=' . $id . '&' . self::QV_W . '=$matches[1]', 'top' );
			$sig .= '|' . $path . ':' . $id;
		}
		if ( get_option( 'sm_products_landing_rules' ) !== $sig ) {
			update_option( 'sm_products_landing_rules', $sig, false );
			flush_rewrite_rules( false );
		}
	}

	/** M'Sila → msila, Bordj Bou Arréridj → bordj-bou-arreridj (French name, latin letters). */
	public static function wilaya_slug( $code ) {
		$name = \SM\Core\Accounts\Wilayas::name( (string) $code, 'fr' );
		$name = str_replace( array( "'", '’' ), '', (string) $name );
		return trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( remove_accents( $name ) ) ), '-' );
	}

	public static function wilaya_code( $slug ) {
		static $map = null;
		if ( null === $map ) {
			$map = array();
			foreach ( array_keys( \SM\Core\Accounts\Wilayas::all() ) as $code ) {
				$map[ self::wilaya_slug( $code ) ] = (string) $code;
			}
		}
		return $map[ (string) $slug ] ?? '';
	}

	/** Address of a page: market, type ('' = the whole market), wilaya code. */
	public static function url( $market, $category, $wilaya ) {
		$id = self::page_id( $market );
		if ( ! $id ) {
			return '';
		}
		$base = get_permalink( $id );
		$ws   = self::wilaya_slug( $wilaya );
		if ( ! get_option( 'permalink_structure' ) || '' === $ws ) {
			return add_query_arg( array_filter( array( 'category' => $category, 'wilaya' => $wilaya ) ), $base );
		}
		return trailingslashit( $base ) . ( '' !== $category ? $category . '/' : '' ) . $ws . '/';
	}

	/**
	 * Published listings by market, type and wilaya: [ [ market, category, wilaya, n ], … ].
	 * $min: only the groups with at least that number.
	 */
	public static function groups( $min = self::MIN ) {
		global $wpdb;
		$rows = (array) $wpdb->get_results( "SELECT module, category, wilaya, COUNT(*) AS n FROM {$wpdb->prefix}sm_listing_index WHERE status = 'published' AND wilaya <> '' GROUP BY module, category, wilaya", ARRAY_A ); // phpcs:ignore WordPress.DB
		$out  = array();
		foreach ( $rows as $r ) {
			if ( in_array( $r['module'], self::MARKETS, true ) && (int) $r['n'] >= $min ) {
				$out[] = array( $r['module'], (string) $r['category'], (string) $r['wilaya'], (int) $r['n'] );
			}
		}
		return $out;
	}

	/** Whole markets by wilaya: [ [ market, wilaya, n ], … ]. */
	public static function market_groups( $min = self::MIN ) {
		global $wpdb;
		$rows = (array) $wpdb->get_results( "SELECT module, wilaya, COUNT(*) AS n FROM {$wpdb->prefix}sm_listing_index WHERE status = 'published' AND wilaya <> '' GROUP BY module, wilaya", ARRAY_A ); // phpcs:ignore WordPress.DB
		$out  = array();
		foreach ( $rows as $r ) {
			if ( in_array( $r['module'], self::MARKETS, true ) && (int) $r['n'] >= $min ) {
				$out[] = array( $r['module'], (string) $r['wilaya'], (int) $r['n'] );
			}
		}
		return $out;
	}

	/** Every indexable page: [ [ market, category ('' = market), wilaya ], … ] (for the sitemap). */
	public static function pages() {
		$out = array();
		foreach ( self::market_groups() as $g ) {
			$out[] = array( $g[0], '', $g[1] );
		}
		$cats = array();
		foreach ( self::groups() as $g ) {
			$cats[ $g[0] ] = $cats[ $g[0] ] ?? Categories::labels( $g[0] );
			if ( '' !== $g[1] && isset( $cats[ $g[0] ][ $g[1] ] ) ) {
				$out[] = array( $g[0], $g[1], $g[2] );
			}
		}
		return $out;
	}

	/**
	 * Data of a page: { market, category, wilaya, labels, count, kg, prices, updated,
	 * index (bool), items, sizes, wilayas, others, url, see_all } — null when the
	 * market, the type or the wilaya is unknown (the page answers « not found »).
	 */
	public static function data( $r, $args ) {
		if ( null !== $r ) {
			return $r;
		}
		$market = (string) ( $args['market'] ?? '' );
		$cat    = sanitize_title( (string) ( $args['category'] ?? '' ) );
		$wilaya = self::wilaya_code( sanitize_title( (string) ( $args['wilaya'] ?? '' ) ) );
		if ( ! in_array( $market, self::MARKETS, true ) || '' === $wilaya ) {
			return null;
		}
		$labels = Categories::labels( $market );
		if ( '' !== $cat && ! isset( $labels[ $cat ] ) ) {
			return null;
		}
		global $wpdb;
		$t     = $wpdb->prefix . 'sm_listing_index';
		$where = $wpdb->prepare( "module = %s AND status = 'published' AND wilaya = %s", $market, $wilaya ) . ( '' !== $cat ? $wpdb->prepare( ' AND category = %s', $cat ) : '' );
		$rows  = (array) $wpdb->get_results( "SELECT designation, total_weight, price_per_kg, price_unit, updated_at FROM {$t} WHERE {$where}", ARRAY_A ); // phpcs:ignore WordPress.DB
		$kg    = 0.0;
		$price = array();
		$last  = '';
		$sizes = array();
		foreach ( $rows as $row ) {
			$kg += max( 0, (float) $row['total_weight'] );
			if ( 'kg' === $row['price_unit'] && (float) $row['price_per_kg'] > 0 ) {
				$price[] = (float) $row['price_per_kg'];
			}
			$last = max( $last, (string) $row['updated_at'] );
			if ( '' !== (string) $row['designation'] ) {
				$sizes[ $row['designation'] ] = ( $sizes[ $row['designation'] ] ?? 0 ) + 1;
			}
		}
		$count = count( $rows );

		// Sizes offered that have a page in the catalogue of sections.
		$size_list = array();
		if ( 'buildings' !== $market && class_exists( '\SM\WeightEngine\CataloguePages' ) && \SM\WeightEngine\CataloguePages::page_id() ) {
			arsort( $sizes );
			$map = \SM\WeightEngine\CataloguePages::map();
			foreach ( $sizes as $designation => $n ) {
				$slug = \SM\WeightEngine\CataloguePages::slug( $designation );
				if ( isset( $map[ $slug ] ) ) {
					$size_list[] = array( 'designation' => $designation, 'count' => $n, 'url' => \SM\WeightEngine\CataloguePages::url( $slug ) );
				}
				if ( count( $size_list ) >= 10 ) {
					break;
				}
			}
		}

		// The same type in the other wilayas, the other types in this wilaya (3 listings or more).
		$wilayas = array();
		$others  = array();
		foreach ( self::groups() as $g ) {
			list( $m, $c, $w, $n ) = $g;
			if ( $m === $market && $c === $cat && '' !== $cat && $w !== $wilaya ) {
				$wilayas[] = array( 'wilaya' => $w, 'name' => self::names( $w ), 'count' => $n, 'url' => self::url( $m, $c, $w ) );
			}
			if ( $w === $wilaya && ( $m !== $market || $c !== $cat ) && '' !== $c ) {
				$ml = Categories::labels( $m );
				if ( empty( $ml[ $c ] ) ) {
					continue; // Type no longer offered.
				}
				$others[] = array( 'market' => $m, 'category' => $c, 'label' => $ml[ $c ] ?? array(), 'count' => $n, 'url' => self::url( $m, $c, $w ) );
			}
		}
		if ( '' === $cat ) {
			foreach ( self::market_groups() as $g ) {
				if ( $g[0] === $market && $g[1] !== $wilaya ) {
					$wilayas[] = array( 'wilaya' => $g[1], 'name' => self::names( $g[1] ), 'count' => $g[2], 'url' => self::url( $market, '', $g[1] ) );
				}
			}
		}
		usort( $wilayas, static fn( $a, $b ) => $b['count'] <=> $a['count'] );
		usort( $others, static fn( $a, $b ) => $b['count'] <=> $a['count'] );

		$list = Theme\Bridge::listings(
			null,
			array(
				'market'   => $market,
				'category' => $cat,
				'wilaya'   => $wilaya,
				'per_page' => 12,
				'silent'   => 1,
			)
		);
		sort( $price );
		return array(
			'market'   => $market,
			'category' => $cat,
			'wilaya'   => $wilaya,
			'cat_name' => '' !== $cat ? $labels[ $cat ] : array(),
			'w_name'   => self::names( $wilaya ),
			'count'    => $count,
			'kg'       => round( $kg ),
			'prices'   => count( $price ) >= self::MIN ? array( 'min' => $price[0], 'max' => end( $price ), 'n' => count( $price ) ) : null,
			'updated'  => $last,
			'index'    => $count >= self::MIN,
			'items'    => is_array( $list ) ? (array) ( $list['items'] ?? array() ) : array(),
			'sizes'    => $size_list,
			'wilayas'  => array_slice( $wilayas, 0, 12 ),
			'others'   => array_slice( $others, 0, 12 ),
			'url'      => self::url( $market, $cat, $wilaya ),
			'see_all'  => add_query_arg( array_filter( array( 'category' => $cat, 'wilaya' => $wilaya ) ), get_permalink( self::page_id( $market ) ) ),
		);
	}

	/** Page « type × wilaya » of a listing, when it is indexed: { url, count } or null. */
	public static function for_listing( $p ) {
		if ( ! in_array( (string) $p->market, self::MARKETS, true ) || '' === (string) $p->wilaya || '' === (string) $p->category ) {
			return null;
		}
		global $wpdb;
		if ( '' === self::wilaya_slug( $p->wilaya ) ) {
			return null;
		}
		$n = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_listing_index WHERE module = %s AND category = %s AND wilaya = %s AND status = 'published'", $p->market, $p->category, $p->wilaya ) ); // phpcs:ignore WordPress.DB
		if ( $n >= self::MIN ) {
			return array( 'url' => self::url( $p->market, $p->category, $p->wilaya ), 'count' => $n, 'scope' => 'type' );
		}
		// 0.24.1: fewer than 3 listings of this type → the page of the whole market in the wilaya.
		$n = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_listing_index WHERE module = %s AND wilaya = %s AND status = 'published'", $p->market, $p->wilaya ) ); // phpcs:ignore WordPress.DB
		if ( $n < self::MIN ) {
			return null;
		}
		return array( 'url' => self::url( $p->market, '', $p->wilaya ), 'count' => $n, 'scope' => 'market' );
	}

	private static function names( $code ) {
		$all = \SM\Core\Accounts\Wilayas::all( true );
		return $all[ $code ] ?? array( 'fr' => (string) $code, 'ar' => (string) $code );
	}
}

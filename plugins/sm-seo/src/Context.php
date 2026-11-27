<?php
/**
 * What search engines get for the current page: title, description, canonical
 * address, indexed or not, share image, structured data and breadcrumb.
 *
 * Everything comes from the real data of the page. Never a review, a rating, a
 * market price, a data source, the exact address or the contacts of a seller, and
 * no page of a partner engineer (the engineer and the client never see each other):
 * the engineering services are presented as services of the platform.
 *
 * @package SM\Seo
 */

namespace SM\Seo;

defined( 'ABSPATH' ) || exit;

final class Context {

	/** Pages never indexed (private, forms, internal). */
	const PRIVATE_KEYS = array( 'account', 'login', 'register', 'lost', 'styleguide', 'mission-request' );

	/** Query arguments that keep a listing page indexed (a type of product, a page number). */
	const KEPT_ARGS = array(
		'products'      => array( 'category', 'family', 'pg' ),
		'reuse'         => array( 'category', 'family', 'pg' ),
		'buildings'     => array( 'category', 'pg' ),
		'services'      => array( 'category', 'pg' ),
		'companies'     => array( 'activity', 'pg' ),
		'professionals' => array( 'activity', 'pg' ),
		'rfq'           => array( 'pg' ),
	);

	/** Tracking arguments: ignored (the page stays indexed, the canonical drops them). */
	const TRACKING = array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'fbclid', 'gclid', 'lang' );

	private static $cache = null;

	public static function reset() {
		self::$cache = null;
	}

	public static function get() {
		if ( null === self::$cache ) {
			self::$cache = self::build();
		}
		return self::$cache;
	}

	/** GET arguments of the request, without the tracking ones. */
	private static function args() {
		$out = array();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		foreach ( (array) $_GET as $k => $v ) {
			if ( in_array( $k, self::TRACKING, true ) || is_array( $v ) || '' === trim( (string) $v ) ) {
				continue;
			}
			$out[ sanitize_key( $k ) ] = sanitize_text_field( wp_unslash( (string) $v ) );
		}
		return $out;
	}

	private static function blank() {
		return array(
			'key'         => '',
			'index'       => true,
			'title'       => '',
			'description' => '',
			'canonical'   => '',
			'image'       => '',
			'og_type'     => 'website',
			'schema'      => array(),
			'crumbs'      => array(),
		);
	}

	public static function build() {
		$m = self::blank();
		if ( is_admin() || is_feed() ) {
			return $m;
		}
		if ( is_404() ) {
			$m['index'] = false;
			return $m;
		}
		if ( is_search() || is_author() || is_date() || is_attachment() ) {
			$m['index'] = false;
			return $m;
		}
		$key = is_front_page() ? 'home' : ( is_page() ? Text::page_key( get_queried_object_id() ) : '' );
		$m['key'] = $key;
		if ( '' === $key ) {
			if ( is_singular() ) {
				$m['canonical']   = (string) get_permalink();
				$m['description'] = Text::cut( get_the_excerpt( get_queried_object_id() ), 300 );
				$m['image']       = (string) get_the_post_thumbnail_url( get_queried_object_id(), 'large' );
				$m['og_type']     = 'article';
			} elseif ( ! is_home() ) {
				$m['index'] = false; // Archives of categories and tags: thin.
			}
			return self::finish( $m );
		}
		if ( in_array( $key, self::PRIVATE_KEYS, true ) ) {
			$m['index']     = false;
			$m['canonical'] = Text::url( $key );
			return self::finish( $m );
		}

		$args = self::args();
		switch ( $key ) {
			case 'home':
				$m = Pages::home( $m );
				break;
			case 'listing':
				$m = Pages::listing( $m, absint( get_query_var( 'sm_id' ) ? get_query_var( 'sm_id' ) : ( $args['id'] ?? 0 ) ) );
				$args = array();
				break;
			case 'company':
				$m = Pages::company( $m, sanitize_title( (string) ( get_query_var( 'sm_c' ) ? get_query_var( 'sm_c' ) : ( $args['c'] ?? '' ) ) ) );
				$args = array();
				break;
			case 'service':
				$m = Pages::service( $m, absint( get_query_var( 'sm_id' ) ? get_query_var( 'sm_id' ) : ( $args['id'] ?? 0 ) ) );
				$args = array();
				break;
			case 'products':
			case 'reuse':
			case 'buildings':
				if ( get_query_var( 'sm_lw' ) ) { // 0.3.0: page « type × wilaya ».
					$m    = Pages::landing( $m, $key, (string) get_query_var( 'sm_lcat' ), (string) get_query_var( 'sm_lw' ) );
					$args = array();
					break;
				}
				$m = Pages::page( $m, $key, $args );
				break;
			case 'catalogue':
				$m    = Pages::catalogue( $m, sanitize_title( (string) ( get_query_var( 'sm_cat' ) ? get_query_var( 'sm_cat' ) : ( $args['sm_cat'] ?? '' ) ) ) );
				$args = array();
				break;
			case 'guides': // 0.4.0 (sm-guides).
				$m    = Pages::guides( $m, sanitize_title( (string) ( get_query_var( 'sm_guide' ) ? get_query_var( 'sm_guide' ) : ( $args['sm_guide'] ?? '' ) ) ) );
				$args = array();
				break;
			case 'faq': // 0.5.0 (sm-guides 0.2.0).
				$m = Pages::faq( Pages::page( $m, $key, array() ) );
				break;
			case 'rfq':
				if ( get_query_var( 'sm_id' ) || ! empty( $args['id'] ) ) {
					$m['index']     = false; // A request of a buyer: not for the search engines.
					$m['canonical'] = Text::url( 'rfq' );
					return self::finish( $m );
				}
				$m = Pages::page( $m, $key, $args );
				break;
			default:
				$m = Pages::page( $m, $key, $args );
		}

		// Any other argument (text searched, filters, sorting): the page is a search,
		// kept out of the index; the links on it are still followed.
		if ( $args ) {
			$kept = self::KEPT_ARGS[ $key ] ?? array();
			if ( array_diff( array_keys( $args ), $kept ) ) {
				$m['index'] = false;
			}
		}
		return self::finish( $m );
	}

	private static function finish( array $m ) {
		if ( '' === $m['image'] ) {
			$m['image'] = self::default_image();
		}
		return apply_filters( 'sm_seo_context', $m );
	}

	public static function default_image() {
		// 0.6.0: the image of the language of the page, else the French one, else the logo.
		$lang = function_exists( 'sm_current_language' ) ? sm_current_language() : 'fr';
		$img  = (string) Settings::get( Settings::image_key( $lang ) );
		if ( '' === $img ) {
			$img = (string) Settings::get( 'default_image' );
		}
		if ( '' === $img ) {
			$logo = (int) get_theme_mod( 'custom_logo' );
			$img  = $logo ? Text::image( $logo, 'full' ) : (string) get_site_icon_url( 512 );
		}
		return (string) apply_filters( 'sm_seo_default_image', $img );
	}
}

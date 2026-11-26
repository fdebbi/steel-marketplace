<?php
/**
 * XML sitemaps (wp-sitemap.xml of WordPress): the pages of the marketplace, the types
 * of product, the published listings, the companies with something to show and the
 * services, each address in French, Arabic and English. The core sitemap of the pages
 * is replaced (it listed the account and login pages), the users and the taxonomies
 * are removed.
 *
 * @package SM\Seo
 */

namespace SM\Seo;

defined( 'ABSPATH' ) || exit;

final class Sitemaps {

	/** Pages of the marketplace that are not in the sitemap (private, or shown with an id). */
	const SKIP_KEYS = array( 'listing', 'company', 'service', 'styleguide', 'catalogue', 'guides' ); // catalogue, guides: their own sitemap.

	public static function init() {
		add_filter( 'wp_sitemaps_add_provider', array( __CLASS__, 'core_provider' ), 10, 2 );
		add_filter( 'wp_sitemaps_post_types', array( __CLASS__, 'post_types' ) );
		add_filter( 'wp_sitemaps_taxonomies', '__return_empty_array' );
		add_action( 'init', array( __CLASS__, 'register' ), 20 );
	}

	public static function core_provider( $provider, $name ) {
		return 'users' === $name ? false : $provider;
	}

	public static function post_types( $types ) {
		unset( $types['page'] ); // Replaced by « smpages » (languages, private pages left out).
		return $types;
	}

	public static function register() {
		if ( ! function_exists( 'wp_register_sitemap_provider' ) || ! class_exists( '\WP_Sitemaps_Provider' ) ) {
			return;
		}
		wp_register_sitemap_provider( 'smpages', new Sitemap\Pages() );
		wp_register_sitemap_provider( 'smlistings', new Sitemap\Listings() );
		wp_register_sitemap_provider( 'smcompanies', new Sitemap\Companies() );
		if ( class_exists( '\\SM\\WeightEngine\\CataloguePages' ) && Text::page_id( 'catalogue' ) ) {
			wp_register_sitemap_provider( 'smcatalogue', new Sitemap\Catalogue() ); // 0.2.0
		}
		if ( class_exists( '\\SM\\Guides\\Pages' ) && Text::page_id( 'guides' ) ) {
			wp_register_sitemap_provider( 'smguides', new Sitemap\Guides() ); // 0.4.0
		}
		if ( class_exists( '\\SM\\Products\\Landing' ) ) {
			wp_register_sitemap_provider( 'smlanding', new Sitemap\Landing() ); // 0.3.0
		}
		if ( self::table_exists( $GLOBALS['wpdb']->prefix . 'sm_services' ) ) {
			wp_register_sitemap_provider( 'smservices', new Sitemap\Services() );
		}
	}

	public static function table_exists( $table ) {
		static $seen = array();
		if ( ! isset( $seen[ $table ] ) ) {
			global $wpdb;
			$seen[ $table ] = (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		}
		return $seen[ $table ];
	}

	/** Addresses per sitemap page, so that every language of an address fits in it. */
	public static function per_page() {
		return max( 1, (int) floor( wp_sitemaps_get_max_urls( 'smlistings' ) / max( 1, count( Text::langs() ) ) ) );
	}

	/**
	 * One entry per language of an address (the default language unprefixed).
	 *
	 * @param string $url     address in the default language.
	 * @param string $updated date (UTC, MySQL) or ''.
	 */
	public static function entries( $url, $updated = '' ) {
		$out = array();
		foreach ( Text::langs() as $code ) {
			$e = array( 'loc' => Text::in_lang( $url, $code ) );
			if ( $updated && '0000' !== substr( $updated, 0, 4 ) ) {
				$e['lastmod'] = gmdate( 'Y-m-d\TH:i:s+00:00', strtotime( $updated . ' UTC' ) );
			}
			$out[] = $e;
		}
		return $out;
	}

	/** Types of product with at least one published listing: [ market => [ category, … ] ]. */
	public static function categories() {
		global $wpdb;
		$out = array();
		foreach ( (array) $wpdb->get_results( "SELECT module, category, MAX(updated_at) updated FROM {$wpdb->prefix}sm_listing_index WHERE status = 'published' AND category <> '' GROUP BY module, category ORDER BY module, category" ) as $r ) { // phpcs:ignore WordPress.DB
			$out[ $r->module ][ $r->category ] = (string) $r->updated;
		}
		return $out;
	}
}

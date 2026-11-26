<?php
/**
 * Sitemap of the pages: home, pages of the marketplace (without the private ones),
 * types of product with listings, other published pages of the site.
 *
 * @package SM\Seo
 */

namespace SM\Seo\Sitemap;

use SM\Seo\Context;
use SM\Seo\Sitemaps;
use SM\Seo\Text;

defined( 'ABSPATH' ) || exit;

final class Pages extends \WP_Sitemaps_Provider {

	public function __construct() {
		$this->name        = 'smpages';
		$this->object_type = 'smpages';
	}

	public function get_url_list( $page_num, $object_subtype = '' ) {
		if ( 1 !== (int) $page_num ) {
			return array();
		}
		$list = Sitemaps::entries( home_url( '/' ) );
		$map  = array_map( 'intval', (array) get_option( 'sm_theme_pages', array() ) );
		foreach ( $map as $key => $id ) {
			if ( 'home' === $key || in_array( $key, Context::PRIVATE_KEYS, true ) || in_array( $key, Sitemaps::SKIP_KEYS, true ) || 'publish' !== get_post_status( $id ) ) {
				continue;
			}
			$list = array_merge( $list, Sitemaps::entries( Text::url( $key ), (string) get_post_field( 'post_modified_gmt', $id ) ) );
		}
		foreach ( Sitemaps::categories() as $market => $cats ) {
			if ( empty( $map[ $market ] ) ) {
				continue;
			}
			foreach ( $cats as $cat => $updated ) {
				$list = array_merge( $list, Sitemaps::entries( add_query_arg( 'category', rawurlencode( $cat ), Text::url( $market ) ), $updated ) );
			}
		}
		// Other pages of the site (legal notice, guides…).
		$others = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'post__not_in'   => array_values( $map ),
				'posts_per_page' => 500,
				'has_password'   => false,
				'fields'         => 'ids',
			)
		);
		foreach ( $others as $id ) {
			$list = array_merge( $list, Sitemaps::entries( (string) get_permalink( $id ), (string) get_post_field( 'post_modified_gmt', $id ) ) );
		}
		// Each address once (a page of the marketplace may also exist as a plain page with the same address, 0.3.1).
		$seen = array();
		$out  = array();
		foreach ( $list as $e ) {
			if ( isset( $seen[ $e['loc'] ] ) ) {
				continue;
			}
			$seen[ $e['loc'] ] = true;
			$out[]             = $e;
		}
		return $out;
	}

	public function get_max_num_pages( $object_subtype = '' ) {
		return 1;
	}
}

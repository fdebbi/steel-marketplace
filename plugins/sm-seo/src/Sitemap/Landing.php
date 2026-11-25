<?php
/**
 * Sitemap of the pages « type × wilaya » (0.3.0, sm-products 0.24.0): only the
 * pages with at least 3 published listings, in each language.
 *
 * @package SM\Seo
 */

namespace SM\Seo\Sitemap;

use SM\Products\Landing as Pages;
use SM\Seo\Sitemaps;

defined( 'ABSPATH' ) || exit;

final class Landing extends \WP_Sitemaps_Provider {

	public function __construct() {
		$this->name        = 'smlanding';
		$this->object_type = 'smlanding';
	}

	public function get_url_list( $page_num, $object_subtype = '' ) {
		$per  = Sitemaps::per_page();
		$list = array();
		foreach ( array_slice( Pages::pages(), ( max( 1, (int) $page_num ) - 1 ) * $per, $per ) as $p ) {
			$url = Pages::url( $p[0], $p[1], $p[2] );
			if ( '' !== $url ) {
				$list = array_merge( $list, Sitemaps::entries( $url ) );
			}
		}
		return $list;
	}

	public function get_max_num_pages( $object_subtype = '' ) {
		return (int) ceil( count( Pages::pages() ) / Sitemaps::per_page() );
	}
}

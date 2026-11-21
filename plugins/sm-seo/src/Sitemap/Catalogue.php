<?php
/**
 * Sitemap of the catalogue of sections (0.2.0, sm-weight-engine 0.8.0): the
 * catalogue page, each family and each size, in each language.
 *
 * @package SM\Seo
 */

namespace SM\Seo\Sitemap;

use SM\Seo\Sitemaps;
use SM\Seo\Text;
use SM\WeightEngine\CataloguePages;

defined( 'ABSPATH' ) || exit;

final class Catalogue extends \WP_Sitemaps_Provider {

	public function __construct() {
		$this->name        = 'smcatalogue';
		$this->object_type = 'smcatalogue';
	}

	/** Names after /catalogue/ ('' = the catalogue page itself). */
	private function slugs() {
		$out = array( '' );
		foreach ( array_keys( CataloguePages::families() ) as $code ) {
			$out[] = strtolower( $code );
		}
		return array_merge( $out, array_keys( CataloguePages::map() ) );
	}

	public function get_url_list( $page_num, $object_subtype = '' ) {
		$per  = Sitemaps::per_page();
		$list = array();
		foreach ( array_slice( $this->slugs(), ( max( 1, (int) $page_num ) - 1 ) * $per, $per ) as $slug ) {
			$list = array_merge( $list, Sitemaps::entries( Text::url( 'catalogue', array( 'slug' => $slug ) ) ) );
		}
		return $list;
	}

	public function get_max_num_pages( $object_subtype = '' ) {
		return (int) ceil( count( $this->slugs() ) / Sitemaps::per_page() );
	}
}

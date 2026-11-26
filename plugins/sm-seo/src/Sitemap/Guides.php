<?php
/**
 * Sitemap of the guides (0.4.0, sm-guides 0.1.0): the list of the guides in each
 * language, and each guide in the languages it is written in.
 *
 * @package SM\Seo
 */

namespace SM\Seo\Sitemap;

use SM\Guides\Guides as Store;
use SM\Seo\Sitemaps;
use SM\Seo\Text;

defined( 'ABSPATH' ) || exit;

final class Guides extends \WP_Sitemaps_Provider {

	public function __construct() {
		$this->name        = 'smguides';
		$this->object_type = 'smguides';
	}

	public function get_url_list( $page_num, $object_subtype = '' ) {
		if ( (int) $page_num > 1 ) {
			return array();
		}
		$list = Sitemaps::entries( Text::url( 'guides' ) );
		foreach ( Store::published() as $p ) {
			$url   = Text::url( 'guides', array( 'slug' => $p->post_name ) );
			$langs = Store::languages( $p );
			foreach ( Sitemaps::entries( $url, (string) $p->post_modified_gmt ) as $e ) {
				foreach ( $langs as $code ) {
					if ( Text::in_lang( $url, $code ) === $e['loc'] ) {
						$list[] = $e;
						break;
					}
				}
			}
		}
		return $list;
	}

	public function get_max_num_pages( $object_subtype = '' ) {
		return 1;
	}
}

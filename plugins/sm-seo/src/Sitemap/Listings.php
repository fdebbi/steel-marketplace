<?php
/**
 * Sitemap of the published listings, one per market (products, reuse, buildings).
 *
 * @package SM\Seo
 */

namespace SM\Seo\Sitemap;

use SM\Seo\Sitemaps;
use SM\Seo\Text;

defined( 'ABSPATH' ) || exit;

final class Listings extends \WP_Sitemaps_Provider {

	const MARKETS = array( 'products', 'reuse', 'buildings' );

	public function __construct() {
		$this->name        = 'smlistings';
		$this->object_type = 'smlistings';
	}

	public function get_object_subtypes() {
		$out = array();
		foreach ( self::MARKETS as $m ) {
			$out[ $m ] = (object) array( 'name' => $m );
		}
		return $out;
	}

	public function get_url_list( $page_num, $object_subtype = '' ) {
		global $wpdb;
		if ( ! in_array( $object_subtype, self::MARKETS, true ) ) {
			return array();
		}
		$per  = Sitemaps::per_page();
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, updated_at FROM {$wpdb->prefix}sm_listing_index WHERE module = %s AND status = 'published' ORDER BY id LIMIT %d OFFSET %d", $object_subtype, $per, ( max( 1, (int) $page_num ) - 1 ) * $per ) ); // phpcs:ignore WordPress.DB
		$list = array();
		foreach ( (array) $rows as $r ) {
			$list = array_merge( $list, Sitemaps::entries( Text::url( 'listing', array( 'id' => (int) $r->id ) ), (string) $r->updated_at ) );
		}
		return $list;
	}

	public function get_max_num_pages( $object_subtype = '' ) {
		global $wpdb;
		if ( ! in_array( $object_subtype, self::MARKETS, true ) ) {
			return 0;
		}
		$n = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_listing_index WHERE module = %s AND status = 'published'", $object_subtype ) ); // phpcs:ignore WordPress.DB
		return (int) ceil( $n / Sitemaps::per_page() );
	}
}

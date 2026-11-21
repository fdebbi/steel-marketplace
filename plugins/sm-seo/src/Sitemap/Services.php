<?php
/**
 * Sitemap of the published services of the companies (sm-services).
 *
 * @package SM\Seo
 */

namespace SM\Seo\Sitemap;

use SM\Seo\Sitemaps;
use SM\Seo\Text;

defined( 'ABSPATH' ) || exit;

final class Services extends \WP_Sitemaps_Provider {

	public function __construct() {
		$this->name        = 'smservices';
		$this->object_type = 'smservices';
	}

	/** Kinds still offered (retired kinds have no page). */
	private function kinds() {
		global $wpdb;
		$kinds = (array) $wpdb->get_col( "SELECT DISTINCT kind FROM {$wpdb->prefix}sm_services WHERE status = 'published'" ); // phpcs:ignore WordPress.DB
		if ( class_exists( '\SM\Services\Catalogue' ) ) {
			$kinds = array_values( array_filter( $kinds, static fn( $k ) => (bool) \SM\Services\Catalogue::kind( $k ) ) );
		}
		return $kinds;
	}

	public function get_url_list( $page_num, $object_subtype = '' ) {
		global $wpdb;
		$kinds = $this->kinds();
		if ( ! $kinds ) {
			return array();
		}
		$per  = Sitemaps::per_page();
		$in   = implode( ',', array_map( static fn( $k ) => "'" . esc_sql( $k ) . "'", $kinds ) );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, updated_at FROM {$wpdb->prefix}sm_services WHERE status = 'published' AND kind IN ($in) ORDER BY id LIMIT %d OFFSET %d", $per, ( max( 1, (int) $page_num ) - 1 ) * $per ) ); // phpcs:ignore WordPress.DB
		$list = array();
		foreach ( (array) $rows as $r ) {
			$list = array_merge( $list, Sitemaps::entries( Text::url( 'service', array( 'id' => (int) $r->id ) ), (string) $r->updated_at ) );
		}
		return $list;
	}

	public function get_max_num_pages( $object_subtype = '' ) {
		global $wpdb;
		$kinds = $this->kinds();
		if ( ! $kinds ) {
			return 0;
		}
		$in = implode( ',', array_map( static fn( $k ) => "'" . esc_sql( $k ) . "'", $kinds ) );
		$n  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_services WHERE status = 'published' AND kind IN ($in)" ); // phpcs:ignore WordPress.DB
		return (int) ceil( $n / Sitemaps::per_page() );
	}
}

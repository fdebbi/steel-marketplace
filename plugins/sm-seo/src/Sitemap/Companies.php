<?php
/**
 * Sitemap of the public companies and professionals that have something to show:
 * a published listing or service, or a presentation. Clients and partner engineers
 * have no public page.
 *
 * @package SM\Seo
 */

namespace SM\Seo\Sitemap;

use SM\Seo\Sitemaps;
use SM\Seo\Text;

defined( 'ABSPATH' ) || exit;

final class Companies extends \WP_Sitemaps_Provider {

	public function __construct() {
		$this->name        = 'smcompanies';
		$this->object_type = 'smcompanies';
	}

	private function where() {
		global $wpdb;
		$p   = $wpdb->prefix;
		$svc = Sitemaps::table_exists( $p . 'sm_services' ) ? " OR EXISTS (SELECT 1 FROM {$p}sm_services s WHERE s.company_id = c.id AND s.status = 'published')" : '';
		return "FROM {$p}sm_companies c WHERE c.status = 'active' AND c.account_class IN ('company','professional') AND c.deleted_at IS NULL AND c.slug <> ''"
			. " AND ( c.about_fr <> '' OR c.about_ar <> '' OR EXISTS (SELECT 1 FROM {$p}sm_listing_index l WHERE l.company_id = c.id AND l.status = 'published')$svc )";
	}

	public function get_url_list( $page_num, $object_subtype = '' ) {
		global $wpdb;
		$per  = Sitemaps::per_page();
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT c.slug, c.updated_at ' . $this->where() . ' ORDER BY c.id LIMIT %d OFFSET %d', $per, ( max( 1, (int) $page_num ) - 1 ) * $per ) ); // phpcs:ignore WordPress.DB
		$list = array();
		foreach ( (array) $rows as $r ) {
			$list = array_merge( $list, Sitemaps::entries( Text::url( 'company', array( 'c' => $r->slug ) ), (string) $r->updated_at ) );
		}
		return $list;
	}

	public function get_max_num_pages( $object_subtype = '' ) {
		global $wpdb;
		$n = (int) $wpdb->get_var( 'SELECT COUNT(*) ' . $this->where() ); // phpcs:ignore WordPress.DB
		return (int) ceil( $n / Sitemaps::per_page() );
	}
}

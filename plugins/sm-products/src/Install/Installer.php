<?php
/**
 * Tables and default categories.
 *
 * @package SM\Products
 */

namespace SM\Products\Install;

defined( 'ABSPATH' ) || exit;

final class Installer {

	public static function activate() {
		self::migrate();
		self::seed_categories();
		if ( ! wp_next_scheduled( 'sm_products_daily' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'sm_products_daily' );
		}
	}

	public static function maybe_upgrade() {
		if ( (int) get_option( 'sm_products_db_version', 0 ) < SM_PRODUCTS_DB_VERSION ) {
			self::activate();
		}
	}

	public static function migrate() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$c = $wpdb->get_charset_collate();
		$p = $wpdb->prefix . 'sm_';
		dbDelta(
			"CREATE TABLE {$p}product_categories (
				slug varchar(40) NOT NULL,
				market varchar(20) NOT NULL DEFAULT 'products',
				name_fr varchar(120) NOT NULL,
				name_ar varchar(120) NOT NULL,
				icon varchar(30) NOT NULL DEFAULT 'layers',
				kind varchar(10) NOT NULL DEFAULT 'specs',
				families text,
				units text,
				fields longtext,
				sort int(11) NOT NULL DEFAULT 0,
				active tinyint(1) NOT NULL DEFAULT 1,
				PRIMARY KEY  (slug)
			) $c;"
		);
		dbDelta(
			"CREATE TABLE {$p}products (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				listing_id bigint(20) unsigned NOT NULL DEFAULT 0,
				market varchar(20) NOT NULL DEFAULT 'products',
				company_id bigint(20) unsigned NOT NULL,
				user_id bigint(20) unsigned NOT NULL,
				category varchar(40) NOT NULL,
				status varchar(20) NOT NULL DEFAULT 'draft',
				family varchar(12) NOT NULL DEFAULT '',
				section_id bigint(20) unsigned NOT NULL DEFAULT 0,
				designation varchar(80) NOT NULL DEFAULT '',
				grade varchar(30) NOT NULL DEFAULT '',
				dims text,
				specs longtext,
				item_condition varchar(20) NOT NULL DEFAULT '',
				variant varchar(20) NOT NULL DEFAULT '',
				attrs text,
				inventory longtext,
				qty_mode varchar(10) NOT NULL DEFAULT 'bars',
				length_mm int(11) NOT NULL DEFAULT 0,
				width_mm int(11) NOT NULL DEFAULT 0,
				pieces decimal(14,2) NOT NULL DEFAULT 0,
				quantity decimal(14,3) NOT NULL DEFAULT 0,
				unit varchar(10) NOT NULL DEFAULT '',
				unit_weight decimal(14,3) NOT NULL DEFAULT 0,
				total_weight decimal(14,3) NOT NULL DEFAULT 0,
				weight_source varchar(12) NOT NULL DEFAULT 'theoretical',
				engine_version varchar(20) NOT NULL DEFAULT '',
				ref_version varchar(20) NOT NULL DEFAULT '',
				price decimal(14,2) NOT NULL DEFAULT 0,
				price_unit varchar(10) NOT NULL DEFAULT 'kg',
				total_price decimal(16,2) NOT NULL DEFAULT 0,
				price_per_kg decimal(14,2) NOT NULL DEFAULT 0,
				wilaya char(2) NOT NULL DEFAULT '',
				reject_reason text,
				views int(11) NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				submitted_at datetime DEFAULT NULL,
				published_at datetime DEFAULT NULL,
				expires_at datetime DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY company_status (company_id,status),
				KEY status_category (status,category,published_at),
				KEY market_status (market,status,published_at),
				KEY listing_id (listing_id)
			) $c;"
		);
		update_option( 'sm_products_db_version', SM_PRODUCTS_DB_VERSION, false );
	}

	/** Insert missing default categories; existing ones (maybe edited) are kept. */
	public static function seed_categories() {
		self::insert_categories( 'products', require SM_PRODUCTS_DIR . '/data/categories.php' );
	}

	/**
	 * Add the default categories of a market (used by sm-reuse too). Existing
	 * slugs are left as they are, so admin edits are never overwritten.
	 */
	public static function insert_categories( $market, array $categories ) {
		global $wpdb;
		$t    = $wpdb->prefix . 'sm_product_categories';
		$sort = 'products' === $market ? 0 : 1000;
		foreach ( $categories as $slug => $c ) {
			$sort += 10;
			if ( $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM {$t} WHERE slug = %s", $slug ) ) ) { // phpcs:ignore WordPress.DB
				continue;
			}
			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$t,
				array(
					'slug'     => $slug,
					'market'   => $market,
					'name_fr'  => $c['name']['fr'],
					'name_ar'  => $c['name']['ar'],
					'icon'     => $c['icon'],
					'kind'     => $c['kind'],
					'families' => wp_json_encode( $c['families'] ?? array() ),
					'units'    => wp_json_encode( $c['units'] ),
					'fields'   => wp_json_encode( $c['fields'] ?? array() ),
					'sort'     => $sort,
					'active'   => 1,
				)
			);
		}
		\SM\Products\Categories::flush();
	}
}

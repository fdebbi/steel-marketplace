<?php
/**
 * Tables: reference and location of each variant of the warehouse, and the
 * movements (additions, sales, corrections).
 *
 * @package SM\Stock
 */

namespace SM\Stock\Install;

defined( 'ABSPATH' ) || exit;

final class Installer {

	public static function activate() {
		self::migrate();
		update_option( 'sm_stock_db_version', SM_STOCK_DB_VERSION, false );
	}

	public static function maybe_upgrade() {
		if ( (int) get_option( 'sm_stock_db_version', 0 ) < SM_STOCK_DB_VERSION ) {
			self::activate();
		}
	}

	public static function migrate() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$c = $wpdb->get_charset_collate();
		$p = $wpdb->prefix . 'sm_';
		dbDelta(
			"CREATE TABLE {$p}stock_refs (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				company_id bigint(20) unsigned NOT NULL,
				product_id bigint(20) unsigned NOT NULL,
				line_key varchar(80) NOT NULL DEFAULT '',
				ref varchar(20) NOT NULL,
				location varchar(120) NOT NULL DEFAULT '',
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY item (product_id, line_key),
				UNIQUE KEY company_ref (company_id, ref)
			) $c;"
		);
		dbDelta(
			"CREATE TABLE {$p}stock_moves (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				company_id bigint(20) unsigned NOT NULL,
				product_id bigint(20) unsigned NOT NULL DEFAULT 0,
				kind varchar(20) NOT NULL,
				label varchar(160) NOT NULL DEFAULT '',
				qty decimal(14,3) NOT NULL DEFAULT 0,
				unit varchar(10) NOT NULL DEFAULT '',
				kg decimal(14,3) NOT NULL DEFAULT 0,
				note varchar(160) NOT NULL DEFAULT '',
				ref varchar(40) NOT NULL DEFAULT '',
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY company_date (company_id, created_at),
				KEY product (product_id)
			) $c;"
		);
	}
}

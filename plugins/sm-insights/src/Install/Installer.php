<?php
/**
 * Table of the searches of the catalogue, counted per day, profile, market and
 * wilaya — never who searched (no user, no address).
 *
 * @package SM\Insights
 */

namespace SM\Insights\Install;

defined( 'ABSPATH' ) || exit;

final class Installer {

	public static function activate() {
		self::migrate();
		update_option( 'sm_insights_db_version', SM_INSIGHTS_DB_VERSION, false );
	}

	public static function maybe_upgrade() {
		if ( (int) get_option( 'sm_insights_db_version', 0 ) < SM_INSIGHTS_DB_VERSION ) {
			self::activate();
		}
	}

	public static function migrate() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$c = $wpdb->get_charset_collate();
		$p = $wpdb->prefix . 'sm_';
		dbDelta(
			"CREATE TABLE {$p}search_counts (
				day date NOT NULL,
				term varchar(60) NOT NULL,
				family varchar(12) NOT NULL DEFAULT '',
				market varchar(20) NOT NULL DEFAULT '',
				wilaya char(2) NOT NULL DEFAULT '',
				hits int(10) unsigned NOT NULL DEFAULT 0,
				PRIMARY KEY  (day,term,market,wilaya),
				KEY term (term)
			) $c;"
		);
	}
}

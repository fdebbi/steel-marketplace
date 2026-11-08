<?php
/**
 * Table: usage log of the AI services (one row per call or refusal).
 *
 * @package SM\Ai
 */

namespace SM\Ai\Install;

defined( 'ABSPATH' ) || exit;

final class Installer {

	public static function activate() {
		self::migrate();
		update_option( 'sm_ai_db_version', SM_AI_DB_VERSION, false );
	}

	public static function maybe_upgrade() {
		if ( (int) get_option( 'sm_ai_db_version', 0 ) < SM_AI_DB_VERSION ) {
			self::activate();
		}
	}

	public static function migrate() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$c = $wpdb->get_charset_collate();
		$p = $wpdb->prefix . 'sm_';
		dbDelta(
			"CREATE TABLE {$p}ai_usage (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				feature varchar(20) NOT NULL,
				provider varchar(20) NOT NULL DEFAULT '',
				model varchar(80) NOT NULL DEFAULT '',
				company_id bigint(20) unsigned NOT NULL DEFAULT 0,
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				ip varchar(45) NOT NULL DEFAULT '',
				status varchar(10) NOT NULL DEFAULT 'ok',
				tokens_in int(10) unsigned NOT NULL DEFAULT 0,
				tokens_out int(10) unsigned NOT NULL DEFAULT 0,
				cost decimal(12,6) NOT NULL DEFAULT 0,
				duration_ms int(10) unsigned NOT NULL DEFAULT 0,
				error varchar(255) NOT NULL DEFAULT '',
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY feature_company (feature, company_id, created_at),
				KEY user_day (user_id, created_at),
				KEY ip_day (ip, created_at),
				KEY created_at (created_at)
			) $c;"
		);
	}
}

<?php
/**
 * Tables: subscriptions (one row per period of a plan), orders (plans, credit
 * packs and listing options, paid outside the site), credit ledger.
 *
 * @package SM\Subscriptions
 */

namespace SM\Subscriptions\Install;

defined( 'ABSPATH' ) || exit;

final class Installer {

	public static function activate() {
		self::migrate();
		update_option( 'sm_subscriptions_db_version', SM_SUBSCRIPTIONS_DB_VERSION, false );
	}

	public static function maybe_upgrade() {
		if ( (int) get_option( 'sm_subscriptions_db_version', 0 ) < SM_SUBSCRIPTIONS_DB_VERSION ) {
			self::activate();
		}
	}

	public static function migrate() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$c = $wpdb->get_charset_collate();
		$p = $wpdb->prefix . 'sm_';
		dbDelta(
			"CREATE TABLE {$p}subscriptions (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				company_id bigint(20) unsigned NOT NULL,
				plan varchar(40) NOT NULL,
				period varchar(10) NOT NULL DEFAULT 'year',
				status varchar(20) NOT NULL DEFAULT 'active',
				price decimal(14,2) NOT NULL DEFAULT 0,
				founder tinyint(1) NOT NULL DEFAULT 0,
				order_id bigint(20) unsigned NOT NULL DEFAULT 0,
				starts_at datetime NOT NULL,
				ends_at datetime NOT NULL,
				reminded tinyint(1) NOT NULL DEFAULT 0,
				note varchar(255) NOT NULL DEFAULT '',
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY company_status (company_id,status),
				KEY ends_at (ends_at)
			) $c;"
		);
		dbDelta(
			"CREATE TABLE {$p}sub_orders (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				ref varchar(20) NOT NULL DEFAULT '',
				company_id bigint(20) unsigned NOT NULL,
				user_id bigint(20) unsigned NOT NULL,
				kind varchar(20) NOT NULL,
				item varchar(40) NOT NULL,
				period varchar(10) NOT NULL DEFAULT '',
				target_id bigint(20) unsigned NOT NULL DEFAULT 0,
				label varchar(200) NOT NULL DEFAULT '',
				amount_ht decimal(14,2) NOT NULL DEFAULT 0,
				tva_rate decimal(5,2) NOT NULL DEFAULT 0,
				amount_ttc decimal(14,2) NOT NULL DEFAULT 0,
				method varchar(20) NOT NULL DEFAULT '',
				status varchar(20) NOT NULL DEFAULT 'awaiting',
				proof_id bigint(20) unsigned NOT NULL DEFAULT 0,
				proof_file text,
				payer_note text,
				reject_reason text,
				invoice_no varchar(30) NOT NULL DEFAULT '',
				billing longtext,
				created_at datetime NOT NULL,
				proof_at datetime DEFAULT NULL,
				paid_at datetime DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY company_status (company_id,status),
				KEY status (status),
				KEY ref (ref)
			) $c;"
		);
		dbDelta(
			"CREATE TABLE {$p}sub_credits (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				company_id bigint(20) unsigned NOT NULL,
				delta int(11) NOT NULL,
				reason varchar(40) NOT NULL DEFAULT '',
				order_id bigint(20) unsigned NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY company_id (company_id)
			) $c;"
		);
	}
}

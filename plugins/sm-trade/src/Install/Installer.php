<?php
/**
 * Tables: deals (one order or offer between a buyer and a seller) and their events
 * (every step, kept for the history of the administration).
 *
 * @package SM\Trade
 */

namespace SM\Trade\Install;

defined( 'ABSPATH' ) || exit;

final class Installer {

	public static function activate() {
		self::migrate();
		update_option( 'sm_trade_db_version', SM_TRADE_DB_VERSION, false );
		\SM\Trade\Cron::schedule();
	}

	public static function maybe_upgrade() {
		if ( (int) get_option( 'sm_trade_db_version', 0 ) < SM_TRADE_DB_VERSION ) {
			self::activate();
		}
	}

	public static function migrate() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$c = $wpdb->get_charset_collate();
		$p = $wpdb->prefix . 'sm_';
		dbDelta(
			"CREATE TABLE {$p}trade_deals (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				ref varchar(20) NOT NULL DEFAULT '',
				kind varchar(10) NOT NULL DEFAULT 'order',
				status varchar(12) NOT NULL DEFAULT 'open',
				turn varchar(10) NOT NULL DEFAULT 'seller',
				product_id bigint(20) unsigned NOT NULL,
				listing_id bigint(20) unsigned NOT NULL DEFAULT 0,
				market varchar(20) NOT NULL DEFAULT '',
				title varchar(255) NOT NULL DEFAULT '',
				seller_company_id bigint(20) unsigned NOT NULL,
				buyer_company_id bigint(20) unsigned NOT NULL DEFAULT 0,
				buyer_user_id bigint(20) unsigned NOT NULL,
				buyer_name varchar(190) NOT NULL DEFAULT '',
				buyer_phone varchar(40) NOT NULL DEFAULT '',
				buyer_email varchar(190) NOT NULL DEFAULT '',
				mode varchar(10) NOT NULL DEFAULT 'detail',
				quantity decimal(16,3) NOT NULL DEFAULT 0,
				unit varchar(10) NOT NULL DEFAULT '',
				listed_total decimal(16,2) NOT NULL DEFAULT 0,
				amount decimal(16,2) NOT NULL DEFAULT 0,
				counters tinyint(3) unsigned NOT NULL DEFAULT 0,
				message text,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				closed_at datetime DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY seller_status (seller_company_id,status),
				KEY buyer_status (buyer_user_id,status),
				KEY product_id (product_id),
				KEY status_updated (status,updated_at),
				KEY ref (ref)
			) $c;"
		);
		dbDelta(
			"CREATE TABLE {$p}trade_events (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				deal_id bigint(20) unsigned NOT NULL,
				actor varchar(10) NOT NULL DEFAULT '',
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				action varchar(20) NOT NULL,
				amount decimal(16,2) NOT NULL DEFAULT 0,
				note text,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY deal_id (deal_id),
				KEY created_at (created_at)
			) $c;"
		);
	}
}

<?php
/**
 * Tables of requests for quotation and offers; daily job.
 *
 * @package SM\Rfq
 */

namespace SM\Rfq\Install;

defined( 'ABSPATH' ) || exit;

final class Installer {

	public static function activate() {
		self::migrate();
		if ( ! wp_next_scheduled( 'sm_rfq_daily' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'sm_rfq_daily' );
		}
		update_option( 'sm_rfq_db_version', SM_RFQ_DB_VERSION, false );
	}

	public static function maybe_upgrade() {
		if ( (int) get_option( 'sm_rfq_db_version', 0 ) < SM_RFQ_DB_VERSION ) {
			self::activate();
		}
	}

	public static function migrate() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$c = $wpdb->get_charset_collate();
		$p = $wpdb->prefix . 'sm_';
		dbDelta(
			"CREATE TABLE {$p}rfqs (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				company_id bigint(20) unsigned NOT NULL,
				user_id bigint(20) unsigned NOT NULL,
				status varchar(20) NOT NULL DEFAULT 'open',
				market varchar(20) NOT NULL DEFAULT '',
				category varchar(40) NOT NULL DEFAULT '',
				designation varchar(120) NOT NULL DEFAULT '',
				dimensions varchar(190) NOT NULL DEFAULT '',
				quantity decimal(14,3) NOT NULL DEFAULT 0,
				unit varchar(10) NOT NULL DEFAULT '',
				weight_kg decimal(14,3) NOT NULL DEFAULT 0,
				weight_source varchar(12) NOT NULL DEFAULT '',
				inventory longtext,
				engine_version varchar(20) NOT NULL DEFAULT '',
				wilaya char(2) NOT NULL DEFAULT '',
				place varchar(190) NOT NULL DEFAULT '',
				needed_by date DEFAULT NULL,
				deadline date DEFAULT NULL,
				offers_count int(11) NOT NULL DEFAULT 0,
				awarded_offer bigint(20) unsigned NOT NULL DEFAULT 0,
				reject_reason text,
				views int(11) NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				published_at datetime DEFAULT NULL,
				closed_at datetime DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY status_deadline (status,deadline),
				KEY company_status (company_id,status),
				KEY market_status (market,status,published_at)
			) $c;"
		);
		dbDelta(
			"CREATE TABLE {$p}rfq_offers (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				rfq_id bigint(20) unsigned NOT NULL,
				company_id bigint(20) unsigned NOT NULL,
				user_id bigint(20) unsigned NOT NULL,
				status varchar(20) NOT NULL DEFAULT 'submitted',
				price_mode varchar(10) NOT NULL DEFAULT 'total',
				price decimal(16,2) NOT NULL DEFAULT 0,
				weight_kg decimal(14,3) NOT NULL DEFAULT 0,
				total_price decimal(16,2) NOT NULL DEFAULT 0,
				price_per_kg decimal(12,2) NOT NULL DEFAULT 0,
				delivery_days int(11) NOT NULL DEFAULT 0,
				delivery_included tinyint(1) NOT NULL DEFAULT 0,
				validity_days int(11) NOT NULL DEFAULT 0,
				payment_terms varchar(190) NOT NULL DEFAULT '',
				message text,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				decided_at datetime DEFAULT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY rfq_company (rfq_id,company_id),
				KEY company_status (company_id,status)
			) $c;"
		);
	}
}

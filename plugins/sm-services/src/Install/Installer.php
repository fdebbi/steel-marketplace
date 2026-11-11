<?php
/**
 * Tables: services, service requests, references (portfolio).
 *
 * @package SM\Services
 */

namespace SM\Services\Install;

defined( 'ABSPATH' ) || exit;

final class Installer {

	public static function activate() {
		$from = (int) get_option( 'sm_services_db_version', 0 );
		self::migrate();
		if ( $from && $from < 2 ) {
			self::retire_engineering();
		}
		if ( $from && $from < 3 ) {
			self::one_service_per_page();
		}
		update_option( 'sm_services_db_version', SM_SERVICES_DB_VERSION, false );
	}

	public static function maybe_upgrade() {
		if ( (int) get_option( 'sm_services_db_version', 0 ) < SM_SERVICES_DB_VERSION ) {
			self::activate();
		}
	}

	/** 0.3.1: one service per page; pages with several keep the first one. */
	public static function one_service_per_page() {
		global $wpdb;
		$t = $wpdb->prefix . 'sm_services';
		// phpcs:disable WordPress.DB
		foreach ( $wpdb->get_results( "SELECT id, categories FROM $t WHERE categories LIKE '%,%'" ) as $r ) {
			$cats = (array) json_decode( (string) $r->categories, true );
			$wpdb->update( $t, array( 'categories' => wp_json_encode( array_slice( array_values( $cats ), 0, 1 ) ) ), array( 'id' => $r->id ) );
		}
		// phpcs:enable
	}

	/**
	 * 0.2.0: engineering is no longer published by accounts. Former engineering
	 * pages are kept (status "retired") but never shown; requests already sent
	 * stay readable in the dashboards.
	 */
	public static function retire_engineering() {
		global $wpdb;
		$wpdb->query( "UPDATE {$wpdb->prefix}sm_services SET status = 'retired' WHERE kind = 'engineering'" ); // phpcs:ignore WordPress.DB
	}

	public static function migrate() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$c = $wpdb->get_charset_collate();
		$p = $wpdb->prefix . 'sm_';
		dbDelta(
			"CREATE TABLE {$p}services (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				company_id bigint(20) unsigned NOT NULL,
				user_id bigint(20) unsigned NOT NULL,
				kind varchar(20) NOT NULL,
				status varchar(20) NOT NULL DEFAULT 'published',
				categories text,
				custom_service varchar(120) NOT NULL DEFAULT '',
				wilayas text,
				lead_days int(11) NOT NULL DEFAULT 0,
				price_mode varchar(10) NOT NULL DEFAULT 'quote',
				price_from decimal(14,2) NOT NULL DEFAULT 0,
				price_unit varchar(10) NOT NULL DEFAULT '',
				reject_reason text,
				views int(11) NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				published_at datetime DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY kind_status (kind,status,published_at),
				KEY company_status (company_id,status)
			) $c;"
		);
		dbDelta(
			"CREATE TABLE {$p}service_requests (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				service_id bigint(20) unsigned NOT NULL,
				provider_id bigint(20) unsigned NOT NULL,
				client_id bigint(20) unsigned NOT NULL,
				client_user bigint(20) unsigned NOT NULL,
				status varchar(20) NOT NULL DEFAULT 'new',
				wilaya char(2) NOT NULL DEFAULT '',
				needed_by date DEFAULT NULL,
				budget decimal(14,2) NOT NULL DEFAULT 0,
				message text,
				quote_amount decimal(14,2) NOT NULL DEFAULT 0,
				quote_days int(11) NOT NULL DEFAULT 0,
				quote_message text,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				quoted_at datetime DEFAULT NULL,
				decided_at datetime DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY provider_status (provider_id,status),
				KEY client_status (client_id,status),
				KEY service_id (service_id)
			) $c;"
		);
		dbDelta(
			"CREATE TABLE {$p}portfolio (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				company_id bigint(20) unsigned NOT NULL,
				year smallint(5) unsigned NOT NULL DEFAULT 0,
				wilaya char(2) NOT NULL DEFAULT '',
				sort int(11) NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY company_id (company_id)
			) $c;"
		);
	}
}

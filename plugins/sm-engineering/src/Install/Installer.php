<?php
/**
 * Tables: engineers (partner profile and approval), missions, mission files,
 * mission messages.
 *
 * @package SM\Engineering
 */

namespace SM\Engineering\Install;

defined( 'ABSPATH' ) || exit;

final class Installer {

	public static function activate() {
		$from = (int) get_option( 'sm_engineering_db_version', 0 );
		self::migrate();
		// 0.2.0: the engineering page shows the services; the list of partner engineers is hidden.
		if ( $from > 0 && $from < 2 ) {
			$s = get_option( \SM\Engineering\Settings::OPTION, array() );
			$s = is_array( $s ) ? $s : array();
			$s['public_list'] = 0;
			update_option( \SM\Engineering\Settings::OPTION, $s );
		}
		update_option( 'sm_engineering_db_version', SM_ENGINEERING_DB_VERSION, false );
	}

	public static function maybe_upgrade() {
		if ( (int) get_option( 'sm_engineering_db_version', 0 ) < SM_ENGINEERING_DB_VERSION ) {
			self::activate();
		}
	}

	public static function migrate() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$c = $wpdb->get_charset_collate();
		$p = $wpdb->prefix . 'sm_';
		dbDelta(
			"CREATE TABLE {$p}engineers (
				company_id bigint(20) unsigned NOT NULL,
				user_id bigint(20) unsigned NOT NULL,
				status varchar(20) NOT NULL DEFAULT 'pending',
				title varchar(160) NOT NULL DEFAULT '',
				diploma varchar(160) NOT NULL DEFAULT '',
				school varchar(160) NOT NULL DEFAULT '',
				grad_year smallint(5) unsigned NOT NULL DEFAULT 0,
				experience smallint(5) unsigned NOT NULL DEFAULT 0,
				specialties text,
				bio text,
				available tinyint(1) NOT NULL DEFAULT 1,
				verification_id bigint(20) unsigned NOT NULL DEFAULT 0,
				reject_reason text,
				applied_at datetime NOT NULL,
				decided_at datetime DEFAULT NULL,
				PRIMARY KEY  (company_id),
				KEY status (status),
				KEY user_id (user_id)
			) $c;"
		);
		dbDelta(
			"CREATE TABLE {$p}missions (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				client_id bigint(20) unsigned NOT NULL,
				client_user bigint(20) unsigned NOT NULL,
				type varchar(40) NOT NULL,
				title varchar(200) NOT NULL DEFAULT '',
				description text,
				prestations text,
				wilaya char(2) NOT NULL DEFAULT '',
				needed_by date DEFAULT NULL,
				budget decimal(14,2) NOT NULL DEFAULT 0,
				preferred_id bigint(20) unsigned NOT NULL DEFAULT 0,
				engineer_id bigint(20) unsigned NOT NULL DEFAULT 0,
				status varchar(20) NOT NULL DEFAULT 'submitted',
				quote_amount decimal(14,2) NOT NULL DEFAULT 0,
				quote_days int(11) NOT NULL DEFAULT 0,
				quote_message text,
				quote_valid_until date DEFAULT NULL,
				engineer_fee decimal(14,2) NOT NULL DEFAULT 0,
				engineer_days int(11) NOT NULL DEFAULT 0,
				engineer_note text,
				fee_status varchar(10) NOT NULL DEFAULT 'none',
				fee_paid_at datetime DEFAULT NULL,
				fee_reference varchar(80) NOT NULL DEFAULT '',
				client_paid_at datetime DEFAULT NULL,
				reason text,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				quoted_at datetime DEFAULT NULL,
				accepted_at datetime DEFAULT NULL,
				assigned_at datetime DEFAULT NULL,
				started_at datetime DEFAULT NULL,
				submitted_at datetime DEFAULT NULL,
				delivered_at datetime DEFAULT NULL,
				closed_at datetime DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY client_status (client_id,status),
				KEY engineer_status (engineer_id,status),
				KEY status (status,updated_at)
			) $c;"
		);
		dbDelta(
			"CREATE TABLE {$p}mission_files (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				mission_id bigint(20) unsigned NOT NULL,
				kind varchar(20) NOT NULL,
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				path varchar(255) NOT NULL,
				original varchar(190) NOT NULL DEFAULT '',
				mime varchar(100) NOT NULL DEFAULT '',
				size bigint(20) unsigned NOT NULL DEFAULT 0,
				to_client tinyint(1) NOT NULL DEFAULT 0,
				to_engineer tinyint(1) NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY mission_kind (mission_id,kind)
			) $c;"
		);
		dbDelta(
			"CREATE TABLE {$p}mission_messages (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				mission_id bigint(20) unsigned NOT NULL,
				channel varchar(10) NOT NULL,
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				from_platform tinyint(1) NOT NULL DEFAULT 0,
				body text,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY mission_channel (mission_id,channel)
			) $c;"
		);
	}
}

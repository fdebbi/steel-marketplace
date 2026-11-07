<?php
/**
 * Activation, versioned migrations, roles and default settings.
 *
 * Migrations are forward-only and idempotent (dbDelta). Data is never dropped on
 * deactivation; only Uninstall::run() (uninstall.php, with the option
 * "delete data on uninstall" enabled) removes tables.
 *
 * @package SM\Core
 */

namespace SM\Core\Install;

use SM\Core\Permissions\Permissions;

defined( 'ABSPATH' ) || exit;

final class Installer {

	public static function activate() {
		self::migrate();
		self::roles();
		self::default_settings();
		update_option( 'sm_core_flush_rewrite', 1 );
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( 'sm_core_daily' );
		flush_rewrite_rules( false );
	}

	/** Run pending migrations after a plugin update. */
	public static function maybe_upgrade() {
		if ( (int) get_option( 'sm_core_db_version', 0 ) < SM_CORE_DB_VERSION ) {
			self::activate();
		}
	}

	public static function migrate() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$p       = $wpdb->prefix . 'sm_';

		$tables = array(
			"CREATE TABLE {$p}companies (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				slug varchar(190) NOT NULL,
				account_class varchar(20) NOT NULL DEFAULT 'commercial',
				name varchar(190) NOT NULL,
				name_ar varchar(190) NOT NULL DEFAULT '',
				wilaya char(2) NOT NULL DEFAULT '',
				commune varchar(120) NOT NULL DEFAULT '',
				address varchar(255) NOT NULL DEFAULT '',
				lat decimal(10,7) DEFAULT NULL,
				lng decimal(10,7) DEFAULT NULL,
				map_public tinyint(1) NOT NULL DEFAULT 1,
				phone varchar(32) NOT NULL DEFAULT '',
				email varchar(190) NOT NULL DEFAULT '',
				website varchar(190) NOT NULL DEFAULT '',
				logo_id bigint(20) unsigned NOT NULL DEFAULT 0,
				title_fr varchar(190) NOT NULL DEFAULT '',
				title_ar varchar(190) NOT NULL DEFAULT '',
				about_fr text,
				about_ar text,
				profile longtext,
				rc_number text,
				nif text,
				nis text,
				verification_level tinyint(3) unsigned NOT NULL DEFAULT 0,
				status varchar(20) NOT NULL DEFAULT 'active',
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				deleted_at datetime DEFAULT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY slug (slug),
				KEY class_status (account_class,status),
				KEY wilaya (wilaya)
			) $charset;",
			"CREATE TABLE {$p}company_members (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				company_id bigint(20) unsigned NOT NULL,
				user_id bigint(20) unsigned NOT NULL,
				company_role varchar(20) NOT NULL DEFAULT 'member',
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY company_user (company_id,user_id),
				KEY user_id (user_id)
			) $charset;",
			"CREATE TABLE {$p}company_activities (
				company_id bigint(20) unsigned NOT NULL,
				activity_code varchar(40) NOT NULL,
				enabled_at datetime NOT NULL,
				PRIMARY KEY  (company_id,activity_code),
				KEY activity_code (activity_code)
			) $charset;",
			"CREATE TABLE {$p}verifications (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				company_id bigint(20) unsigned NOT NULL,
				level tinyint(3) unsigned NOT NULL,
				documents longtext,
				note text,
				status varchar(20) NOT NULL DEFAULT 'pending',
				reviewer_id bigint(20) unsigned NOT NULL DEFAULT 0,
				decision_note text,
				decided_at datetime DEFAULT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY company_status (company_id,status),
				KEY status (status)
			) $charset;",
			"CREATE TABLE {$p}wilayas (
				code char(2) NOT NULL,
				name_fr varchar(120) NOT NULL,
				name_ar varchar(120) NOT NULL,
				lat decimal(10,7) DEFAULT NULL,
				lng decimal(10,7) DEFAULT NULL,
				active tinyint(1) NOT NULL DEFAULT 1,
				PRIMARY KEY  (code)
			) $charset;",
			"CREATE TABLE {$p}translations (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				entity_type varchar(40) NOT NULL,
				entity_id bigint(20) unsigned NOT NULL,
				field varchar(40) NOT NULL,
				locale varchar(10) NOT NULL,
				value longtext,
				source varchar(20) NOT NULL DEFAULT 'author',
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY entity_field_locale (entity_type,entity_id,field,locale),
				KEY entity (entity_type,entity_id)
			) $charset;",
			"CREATE TABLE {$p}media_links (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				entity_type varchar(40) NOT NULL,
				entity_id bigint(20) unsigned NOT NULL,
				attachment_id bigint(20) unsigned NOT NULL,
				role varchar(20) NOT NULL DEFAULT 'photo',
				sort int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				KEY entity (entity_type,entity_id)
			) $charset;",
			"CREATE TABLE {$p}listing_index (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				module varchar(40) NOT NULL,
				entity_id bigint(20) unsigned NOT NULL,
				company_id bigint(20) unsigned NOT NULL,
				category varchar(60) NOT NULL DEFAULT '',
				designation varchar(60) NOT NULL DEFAULT '',
				item_condition varchar(30) NOT NULL DEFAULT '',
				wilaya char(2) NOT NULL DEFAULT '',
				price_per_kg decimal(14,2) NOT NULL DEFAULT 0,
				total_weight decimal(14,3) NOT NULL DEFAULT 0,
				status varchar(20) NOT NULL DEFAULT 'draft',
				featured tinyint(1) NOT NULL DEFAULT 0,
				published_at datetime DEFAULT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY module_entity (module,entity_id),
				KEY status_module (status,module,published_at),
				KEY search (designation,item_condition,wilaya),
				KEY company_status (company_id,status),
				KEY price (price_per_kg)
			) $charset;",
			"CREATE TABLE {$p}notifications (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL,
				company_id bigint(20) unsigned NOT NULL DEFAULT 0,
				type varchar(60) NOT NULL,
				payload longtext,
				read_at datetime DEFAULT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY user_read (user_id,read_at)
			) $charset;",
			"CREATE TABLE {$p}enquiries (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				kind varchar(20) NOT NULL DEFAULT 'listing',
				module varchar(40) NOT NULL DEFAULT '',
				entity_id bigint(20) unsigned NOT NULL DEFAULT 0,
				to_company_id bigint(20) unsigned NOT NULL DEFAULT 0,
				to_company_slug varchar(190) NOT NULL DEFAULT '',
				from_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				from_company_id bigint(20) unsigned NOT NULL DEFAULT 0,
				name varchar(190) NOT NULL DEFAULT '',
				phone varchar(32) NOT NULL DEFAULT '',
				email varchar(190) NOT NULL DEFAULT '',
				quantity varchar(64) NOT NULL DEFAULT '',
				subject varchar(255) NOT NULL DEFAULT '',
				message text,
				status varchar(20) NOT NULL DEFAULT 'new',
				created_at datetime NOT NULL,
				read_at datetime DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY to_company (to_company_id,status),
				KEY from_user (from_user_id)
			) $charset;",
			"CREATE TABLE {$p}audit_log (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				company_id bigint(20) unsigned NOT NULL DEFAULT 0,
				action varchar(64) NOT NULL,
				object_type varchar(40) NOT NULL DEFAULT '',
				object_id bigint(20) unsigned NOT NULL DEFAULT 0,
				ip varchar(45) NOT NULL DEFAULT '',
				data longtext,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY action (action),
				KEY company_id (company_id),
				KEY created_at (created_at)
			) $charset;",
		);

		foreach ( $tables as $sql ) {
			dbDelta( $sql );
		}

		self::seed_wilayas();
		update_option( 'sm_core_db_version', SM_CORE_DB_VERSION, false );
	}

	/**
	 * Insert the official wilaya list when missing, and fill map centres that are
	 * still empty (never overwrites admin edits).
	 */
	public static function seed_wilayas() {
		global $wpdb;
		$table   = $wpdb->prefix . 'sm_wilayas';
		$rows    = require SM_CORE_DIR . '/data/wilayas.php';
		$centres = require SM_CORE_DIR . '/data/wilaya-centres.php';
		foreach ( $rows as $code => list( $fr, $ar ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT code, lat FROM {$table} WHERE code = %s", $code ) );
			if ( ! $row ) {
				$wpdb->insert( $table, array( 'code' => $code, 'name_fr' => $fr, 'name_ar' => $ar, 'active' => 1 ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}
			if ( isset( $centres[ $code ] ) && ( ! $row || null === $row->lat ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->update( $table, array( 'lat' => $centres[ $code ][0], 'lng' => $centres[ $code ][1] ), array( 'code' => $code ) );
			}
		}
		wp_cache_delete( 'sm_wilayas', 'sm' );
	}

	public static function roles() {
		add_role( 'sm_member', 'SM Member', array( 'read' => true ) );
		add_role( 'sm_moderator', 'SM Moderator', array( 'read' => true, 'sm_moderate' => true, 'upload_files' => true ) );
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$admin->add_cap( 'sm_moderate' );
			$admin->add_cap( 'sm_manage_settings' );
		}
	}

	public static function default_settings() {
		$defaults = array(
			'default_language'  => 'fr',
			'otp_channel'       => 'email',  // email until an SMS gateway is configured.
			'sms_provider'      => 'none',
			'rights'            => Permissions::default_rights(),
			'min_levels'        => Permissions::default_min_levels(),
			'enquiry_per_hour'  => 10,
			'login_attempts'    => 5,
			'register_per_hour' => 5,
			'delete_on_uninstall' => false,
		);
		$current = get_option( 'sm_core_settings', array() );
		update_option( 'sm_core_settings', array_merge( $defaults, is_array( $current ) ? $current : array() ) );
	}
}

<?php
/**
 * Tables: subscriptions (one row per period of a plan), orders (plans, credit
 * packs and listing options, paid outside the site), credit ledger.
 *
 * @package SM\Subscriptions
 */

namespace SM\Subscriptions\Install;

use SM\Subscriptions\Credits;
use SM\Subscriptions\Plans;

defined( 'ABSPATH' ) || exit;

final class Installer {

	public static function activate() {
		$from = (int) get_option( 'sm_subscriptions_db_version', 0 );
		self::migrate();
		if ( $from > 0 && $from < 3 ) {
			self::free_ai_credits_off();
		}
		update_option( 'sm_subscriptions_db_version', SM_SUBSCRIPTIONS_DB_VERSION, false );
	}

	public static function maybe_upgrade() {
		if ( (int) get_option( 'sm_subscriptions_db_version', 0 ) < SM_SUBSCRIPTIONS_DB_VERSION ) {
			self::activate();
		}
	}

	/**
	 * 0.3.0: the free plan and the accounts without plans have no AI credits any
	 * more. The credits they had this month stay until the 1st of next month.
	 */
	public static function free_ai_credits_off() {
		$saved   = (array) get_option( Plans::OPTION, array() );
		$credits = array( 'no_plan' => 5 );
		foreach ( array( 'company', 'professional' ) as $class ) {
			$credits[ $class ] = isset( $saved[ $class ]['free']['ai_credits'] ) ? (int) $saved[ $class ]['free']['ai_credits'] : 5;
			if ( isset( $saved[ $class ]['free'] ) ) {
				$saved[ $class ]['free']['ai_credits'] = 0;
			}
		}
		if ( $saved ) {
			update_option( Plans::OPTION, $saved, false );
		}
		$next = new \DateTimeImmutable( 'first day of next month midnight', wp_timezone() );
		update_option( Credits::GRACE_OPTION, array( 'until' => $next->getTimestamp(), 'credits' => $credits ), false );
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

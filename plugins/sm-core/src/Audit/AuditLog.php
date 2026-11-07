<?php
/**
 * Audit log (sm_audit_log): who did what, when, from which IP.
 * Entries older than 365 days are purged daily.
 *
 * @package SM\Core
 */

namespace SM\Core\Audit;

use SM\Core\Support\Forms;

defined( 'ABSPATH' ) || exit;

final class AuditLog {

	const RETENTION_DAYS = 365;

	public static function init() {
		add_action( 'sm_core_daily', array( __CLASS__, 'purge' ) );
		if ( ! wp_next_scheduled( 'sm_core_daily' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'sm_core_daily' );
		}
	}

	public static function add( $action, $object_type = '', $object_id = 0, array $data = array(), $user_id = null, $company_id = 0 ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert(
			sm_table( 'audit_log' ),
			array(
				'user_id'     => null === $user_id ? get_current_user_id() : (int) $user_id,
				'company_id'  => (int) $company_id,
				'action'      => substr( $action, 0, 64 ),
				'object_type' => substr( $object_type, 0, 40 ),
				'object_id'   => (int) $object_id,
				'ip'          => Forms::ip(),
				'data'        => wp_json_encode( $data ),
				'created_at'  => current_time( 'mysql', true ),
			)
		);
	}

	public static function query( array $args = array() ) {
		global $wpdb;
		$args  = wp_parse_args( $args, array( 'action' => '', 'company_id' => 0, 'per_page' => 50, 'page' => 1 ) );
		$where = '1=1';
		$vals  = array();
		if ( $args['action'] ) {
			$where .= ' AND action LIKE %s';
			$vals[] = $wpdb->esc_like( $args['action'] ) . '%';
		}
		if ( $args['company_id'] ) {
			$where .= ' AND company_id = %d';
			$vals[] = (int) $args['company_id'];
		}
		$vals[] = (int) $args['per_page'];
		$vals[] = ( max( 1, (int) $args['page'] ) - 1 ) * (int) $args['per_page'];
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . sm_table( 'audit_log' ) . " WHERE $where ORDER BY id DESC LIMIT %d OFFSET %d", $vals ) );
	}

	public static function purge() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . sm_table( 'audit_log' ) . ' WHERE created_at < %s', gmdate( 'Y-m-d H:i:s', time() - self::RETENTION_DAYS * DAY_IN_SECONDS ) ) );
	}
}

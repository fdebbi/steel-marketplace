<?php
/**
 * Audit log (sm_audit_log): who did what, when, from which IP.
 * Entries older than the retention chosen in SM → E-mails (365 days by
 * default) are purged daily. Every new entry fires sm_audit_logged, which
 * the e-mail alerts and the activity journal build on.
 *
 * @package SM\Core
 */

namespace SM\Core\Audit;

use SM\Core\Mail\Settings as MailSettings;
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
		$row = array(
			'user_id'     => null === $user_id ? get_current_user_id() : (int) $user_id,
			'company_id'  => (int) $company_id,
			'action'      => substr( $action, 0, 64 ),
			'object_type' => substr( $object_type, 0, 40 ),
			'object_id'   => (int) $object_id,
			'ip'          => Forms::ip(),
			'data'        => wp_json_encode( $data ),
			'created_at'  => current_time( 'mysql', true ),
		);
		$wpdb->insert( sm_table( 'audit_log' ), $row ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$id = (int) $wpdb->insert_id;
		/**
		 * A new audit entry (object: id, action, user_id, company_id, object_type, object_id, ip, data array, created_at).
		 */
		do_action( 'sm_audit_logged', self::entry( (object) ( array( 'id' => $id ) + $row ) ) );
		return $id;
	}

	/** Database row → entry object with decoded data. */
	public static function entry( $row ) {
		$e       = clone (object) $row;
		$e->data = is_array( $row->data ) ? $row->data : ( json_decode( (string) $row->data, true ) ?: array() );
		foreach ( array( 'id', 'user_id', 'company_id', 'object_id' ) as $k ) {
			$e->$k = (int) ( $row->$k ?? 0 );
		}
		return $e;
	}

	/**
	 * Entries, newest first.
	 * Args: action (prefix), prefixes (any of), company_id, user_id, from / to (Y-m-d, local),
	 * q (text in the data), per_page, page, count (true → number of matches).
	 */
	public static function query( array $args = array() ) {
		global $wpdb;
		$args  = wp_parse_args( $args, array( 'action' => '', 'prefixes' => array(), 'company_id' => 0, 'user_id' => 0, 'from' => '', 'to' => '', 'q' => '', 'exclude' => array(), 'per_page' => 50, 'page' => 1, 'count' => false ) );
		$where = '1=1';
		$vals  = array();
		if ( $args['action'] ) {
			$where .= ' AND action LIKE %s';
			$vals[] = $wpdb->esc_like( $args['action'] ) . '%';
		}
		if ( $args['prefixes'] ) {
			$or = array();
			foreach ( (array) $args['prefixes'] as $p ) {
				$or[]   = 'action LIKE %s';
				$vals[] = $wpdb->esc_like( $p ) . '%';
			}
			$where .= ' AND (' . implode( ' OR ', $or ) . ')';
		}
		foreach ( (array) $args['exclude'] as $x ) {
			$where .= ' AND action <> %s';
			$vals[] = (string) $x;
		}
		if ( $args['company_id'] ) {
			$where .= ' AND company_id = %d';
			$vals[] = (int) $args['company_id'];
		}
		if ( $args['user_id'] ) {
			$where .= ' AND user_id = %d';
			$vals[] = (int) $args['user_id'];
		}
		foreach ( array( 'from' => '>=', 'to' => '<' ) as $k => $op ) {
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $args[ $k ] ) ) {
				$local  = 'to' === $k ? gmdate( 'Y-m-d', strtotime( $args[ $k ] . ' +1 day' ) ) : $args[ $k ];
				$where .= " AND created_at $op %s";
				$vals[] = get_gmt_from_date( $local . ' 00:00:00' );
			}
		}
		if ( '' !== (string) $args['q'] ) {
			// Text in the details, the action, the IP, the person (name, e-mail) or the company name.
			$where .= ' AND (data LIKE %s OR action LIKE %s OR ip LIKE %s'
				. " OR user_id IN (SELECT ID FROM {$wpdb->users} WHERE user_email LIKE %s OR display_name LIKE %s)"
				. ' OR company_id IN (SELECT id FROM ' . sm_table( 'companies' ) . ' WHERE name LIKE %s OR name_ar LIKE %s))';
			$like   = '%' . $wpdb->esc_like( (string) $args['q'] ) . '%';
			array_push( $vals, $like, $like, $like, $like, $like, $like, $like );
		}
		if ( $args['count'] ) {
			$sql = 'SELECT COUNT(*) FROM ' . sm_table( 'audit_log' ) . " WHERE $where";
			return (int) ( $vals ? $wpdb->get_var( $wpdb->prepare( $sql, $vals ) ) : $wpdb->get_var( $sql ) ); // phpcs:ignore WordPress.DB
		}
		$vals[] = (int) $args['per_page'];
		$vals[] = ( max( 1, (int) $args['page'] ) - 1 ) * (int) $args['per_page'];
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . sm_table( 'audit_log' ) . " WHERE $where ORDER BY id DESC LIMIT %d OFFSET %d", $vals ) );
	}

	public static function purge() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . sm_table( 'audit_log' ) . ' WHERE created_at < %s', gmdate( 'Y-m-d H:i:s', time() - MailSettings::retention_days() * DAY_IN_SECONDS ) ) );
	}
}

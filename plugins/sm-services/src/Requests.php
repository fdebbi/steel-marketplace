<?php
/**
 * Service requests: a client asks a provider for a service; the provider answers
 * with a quote (amount, time, message) or declines; the client accepts or declines.
 *
 * Status: new → quoted → accepted | declined; declined_provider; cancelled (by the client).
 *
 * @package SM\Services
 */

namespace SM\Services;

use SM\Core\Audit\AuditLog;
use SM\Core\Content\Media;

defined( 'ABSPATH' ) || exit;

final class Requests {

	const STATUSES = array( 'new', 'quoted', 'accepted', 'declined', 'declined_provider', 'cancelled' );

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sm_service_requests';
	}

	private static function hydrate( $r ) {
		foreach ( array( 'id', 'service_id', 'provider_id', 'client_id', 'client_user', 'quote_days' ) as $k ) {
			$r->$k = (int) $r->$k;
		}
		$r->budget       = (float) $r->budget;
		$r->quote_amount = (float) $r->quote_amount;
		return $r;
	}

	public static function get( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) ); // phpcs:ignore WordPress.DB
		return $row ? self::hydrate( $row ) : null;
	}

	public static function create( $service, $client_company, $client_user, array $d, array $files = array() ) {
		global $wpdb;
		$now = current_time( 'mysql', true );
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			array(
				'service_id'  => (int) $service->id,
				'provider_id' => (int) $service->company_id,
				'client_id'   => (int) $client_company,
				'client_user' => (int) $client_user,
				'status'      => 'new',
				'wilaya'      => $d['wilaya'],
				'needed_by'   => $d['needed_by'],
				'budget'      => $d['budget'],
				'message'     => $d['message'],
				'created_at'  => $now,
				'updated_at'  => $now,
			)
		);
		$id = (int) $wpdb->insert_id;
		if ( $files ) {
			Media::set( 'service_request', $id, 'file', $files );
		}
		AuditLog::add( 'service.request_sent', 'service_request', $id, array( 'service_id' => (int) $service->id ), $client_user, $client_company );
		return $id;
	}

	public static function update( $id, array $row ) {
		global $wpdb;
		$row['updated_at'] = current_time( 'mysql', true );
		$wpdb->update( self::table(), $row, array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		AuditLog::add( 'service.request_' . ( $row['status'] ?? 'updated' ), 'service_request', $id, array() );
	}

	/** @param string $box received|sent */
	public static function for_company( $company_id, $box = 'received', $status = '', $limit = 50 ) {
		global $wpdb;
		$col  = 'sent' === $box ? 'client_id' : 'provider_id';
		$sql  = 'SELECT * FROM ' . self::table() . " WHERE {$col} = %d" . ( $status ? ' AND status = %s' : '' ) . ' ORDER BY updated_at DESC, id DESC LIMIT %d';
		$args = $status ? array( $company_id, $status, $limit ) : array( $company_id, $limit );
		return array_map( array( __CLASS__, 'hydrate' ), $wpdb->get_results( $wpdb->prepare( $sql, $args ) ) ); // phpcs:ignore WordPress.DB
	}

	public static function count_new( $company_id ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . " WHERE provider_id = %d AND status = 'new'", $company_id ) ); // phpcs:ignore WordPress.DB
	}

	/** An open request of this client on this service (avoid duplicates). */
	public static function open_for( $service_id, $client_id ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::table() . " WHERE service_id = %d AND client_id = %d AND status IN ('new','quoted') LIMIT 1", $service_id, $client_id ) ); // phpcs:ignore WordPress.DB
	}
}

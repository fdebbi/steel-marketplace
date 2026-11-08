<?php
/**
 * Offers of suppliers on a request for quotation (one per company and request).
 *
 * Status: submitted → shortlisted → accepted | declined; withdrawn by the supplier.
 *
 * @package SM\Rfq
 */

namespace SM\Rfq;

use SM\Core\Audit\AuditLog;
use SM\Core\Content\Media;

defined( 'ABSPATH' ) || exit;

final class Offers {

	const STATUSES = array( 'submitted', 'shortlisted', 'accepted', 'declined', 'withdrawn' );
	const ACTIVE   = array( 'submitted', 'shortlisted', 'accepted' );

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sm_rfq_offers';
	}

	private static function hydrate( $r ) {
		foreach ( array( 'id', 'rfq_id', 'company_id', 'user_id', 'delivery_days', 'delivery_included', 'validity_days' ) as $k ) {
			$r->$k = (int) $r->$k;
		}
		foreach ( array( 'price', 'weight_kg', 'total_price', 'price_per_kg' ) as $k ) {
			$r->$k = (float) $r->$k;
		}
		return $r;
	}

	public static function get( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) ); // phpcs:ignore WordPress.DB
		return $row ? self::hydrate( $row ) : null;
	}

	public static function by_company( $rfq_id, $company_id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE rfq_id = %d AND company_id = %d', $rfq_id, $company_id ) ); // phpcs:ignore WordPress.DB
		return $row ? self::hydrate( $row ) : null;
	}

	/** @return object[] */
	public static function for_rfq( $rfq_id, $include_withdrawn = true ) {
		global $wpdb;
		$sql  = 'SELECT * FROM ' . self::table() . ' WHERE rfq_id = %d' . ( $include_withdrawn ? '' : " AND status <> 'withdrawn'" ) . ' ORDER BY total_price ASC, id ASC';
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $rfq_id ) ); // phpcs:ignore WordPress.DB
		return array_map( array( __CLASS__, 'hydrate' ), $rows );
	}

	/** Offers sent by a company, newest first. */
	public static function for_company( $company_id, $status = '', $limit = 50 ) {
		global $wpdb;
		$sql  = 'SELECT * FROM ' . self::table() . ' WHERE company_id = %d' . ( $status ? ' AND status = %s' : '' ) . ' ORDER BY updated_at DESC, id DESC LIMIT %d';
		$args = $status ? array( $company_id, $status, $limit ) : array( $company_id, $limit );
		return array_map( array( __CLASS__, 'hydrate' ), $wpdb->get_results( $wpdb->prepare( $sql, $args ) ) ); // phpcs:ignore WordPress.DB
	}

	/** Create or update the company's offer from a checked OfferSpec result. */
	public static function save( $rfq_id, $company_id, $user_id, array $data, $files = null ) {
		global $wpdb;
		$now      = current_time( 'mysql', true );
		$existing = self::by_company( $rfq_id, $company_id );
		$row      = array_intersect_key( $data, array_flip( array( 'price_mode', 'price', 'weight_kg', 'total_price', 'price_per_kg', 'delivery_days', 'delivery_included', 'validity_days', 'payment_terms', 'message' ) ) );
		$row['updated_at'] = $now;
		$row['status']     = 'submitted';
		if ( $existing ) {
			$wpdb->update( self::table(), $row, array( 'id' => $existing->id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$id = $existing->id;
		} else {
			$wpdb->insert( self::table(), $row + array( 'rfq_id' => (int) $rfq_id, 'company_id' => (int) $company_id, 'user_id' => (int) $user_id, 'created_at' => $now ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$id = (int) $wpdb->insert_id;
		}
		if ( null !== $files ) {
			Media::set( 'rfq_offer', $id, 'file', $files );
		}
		Rfqs::refresh_count( $rfq_id );
		AuditLog::add( $existing ? 'rfq.offer_updated' : 'rfq.offer_sent', 'rfq_offer', $id, array( 'rfq_id' => (int) $rfq_id ), $user_id, $company_id );
		return array( (int) $id, ! $existing || 'withdrawn' === $existing->status );
	}

	public static function set_status( $id, $status ) {
		global $wpdb;
		if ( ! in_array( $status, self::STATUSES, true ) ) {
			return false;
		}
		$o   = self::get( $id );
		$row = array( 'status' => $status, 'updated_at' => current_time( 'mysql', true ) );
		if ( in_array( $status, array( 'accepted', 'declined' ), true ) ) {
			$row['decided_at'] = current_time( 'mysql', true );
		}
		$wpdb->update( self::table(), $row, array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( $o ) {
			Rfqs::refresh_count( $o->rfq_id );
		}
		AuditLog::add( 'rfq.offer_' . $status, 'rfq_offer', $id, array() );
		return true;
	}

	public static function delete( $id ) {
		global $wpdb;
		$wpdb->delete( self::table(), array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		Media::set( 'rfq_offer', $id, 'file', array() );
	}
}

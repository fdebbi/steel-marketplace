<?php
/**
 * Enquiries (decision 7 of Phase 0: enquiry forms now, full messaging later).
 *
 * Kinds:
 *  - listing : a buyer contacts the seller of a listing (cap "buy");
 *  - service : a client requests a service from a company or engineer (cap "service.request");
 * The recipient company comes from the filter sm_listing_owner (listing plugins)
 * or the company slug. Enquiries about demo listings go to the moderators.
 *
 * @package SM\Core
 */

namespace SM\Core\Enquiries;

use SM\Core\Accounts\Companies;
use SM\Core\Audit\AuditLog;
use SM\Core\Notifications\Notifications;
use SM\Core\Support\Forms;

defined( 'ABSPATH' ) || exit;

final class Enquiries {

	public static function init() {
		add_action( 'admin_post_sm_enquiry', array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_nopriv_sm_enquiry', array( __CLASS__, 'handle_guest' ) );
		add_action( 'admin_post_sm_enquiry_status', array( __CLASS__, 'handle_status' ) );
	}

	/** Guests must sign in first (Phase 0: contact requires an account). */
	public static function handle_guest() {
		Forms::begin( 'enquiry' );
		Forms::back( array( __( 'Sign in or create an account to contact the seller.', 'sm-core' ) ) );
	}

	public static function handle() {
		Forms::begin( 'enquiry' );
		$user_id = get_current_user_id();
		$kind    = 'service' === Forms::post( 'kind' ) ? 'service' : 'listing';
		$cap     = 'service' === $kind ? 'service.request' : 'buy';
		$allowed = sm_can_explain( $cap );
		if ( is_wp_error( $allowed ) ) {
			Forms::back( array( $allowed->get_error_message() ) );
		}
		if ( ! Forms::rate_limit( 'enquiry|' . $user_id, (int) sm_setting( 'enquiry_per_hour', 10 ), HOUR_IN_SECONDS ) ) {
			Forms::back( array( __( 'You sent many enquiries in a short time. Try again later.', 'sm-core' ) ) );
		}

		$message = Forms::post_textarea( 'message' );
		$errors  = array();
		if ( mb_strlen( trim( $message ) ) < 10 ) {
			$errors['message'] = __( 'Write a message of at least 10 characters.', 'sm-core' );
		}
		$phone = Forms::post( 'phone' );
		if ( '' !== $phone && ! preg_match( '/^[0-9 +().-]{9,20}$/', $phone ) ) {
			$errors['phone'] = __( 'Enter a valid phone number.', 'sm-core' );
		}
		if ( $errors ) {
			Forms::back( $errors );
		}

		$module    = sanitize_key( Forms::post( 'module' ) );
		$entity_id = absint( Forms::post( 'entity_id' ) );
		$slug      = sanitize_title( Forms::post( 'company' ) );

		/**
		 * Owner company of a listing. Listing plugins return a company ID.
		 *
		 * @param int    $company_id 0 when unknown.
		 * @param string $module
		 * @param int    $entity_id
		 */
		$to_company_id = (int) apply_filters( 'sm_listing_owner', 0, $module, $entity_id );
		if ( ! $to_company_id && $slug ) {
			$company       = Companies::get_by_slug( $slug );
			$to_company_id = $company ? (int) $company->id : 0;
		}

		$from = Companies::for_user( $user_id );
		if ( $from && $to_company_id === (int) $from->id ) {
			Forms::back( array( __( 'You cannot send an enquiry to your own company.', 'sm-core' ) ) );
		}

		$user = wp_get_current_user();
		global $wpdb;
		$row = array(
			'kind'            => $kind,
			'module'          => $module,
			'entity_id'       => $entity_id,
			'to_company_id'   => $to_company_id,
			'to_company_slug' => $slug,
			'from_user_id'    => $user_id,
			'from_company_id' => $from ? (int) $from->id : 0,
			'name'            => Forms::post( 'name' ) ?: $user->display_name,
			'phone'           => $phone ?: (string) get_user_meta( $user_id, 'sm_phone', true ),
			'email'           => $user->user_email,
			'quantity'        => mb_substr( Forms::post( 'qty' ), 0, 64 ),
			'subject'         => mb_substr( Forms::post( 'subject' ), 0, 255 ),
			'message'         => $message,
			'status'          => 'new',
			'created_at'      => current_time( 'mysql', true ),
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert( sm_table( 'enquiries' ), $row );
		$id = (int) $wpdb->insert_id;

		$payload = array( 'enquiry_id' => $id, 'name' => $row['name'], 'subject' => $row['subject'] );
		if ( $to_company_id ) {
			Notifications::notify_company( $to_company_id, 'enquiry.received', $payload );
		} else {
			Notifications::notify_moderators( 'enquiry.received', $payload );
		}
		AuditLog::add( 'enquiry.sent', 'enquiry', $id, array( 'kind' => $kind, 'module' => $module, 'entity' => $entity_id ), $user_id, $from ? $from->id : 0 );
		sm_event( 'enquiry.sent', $payload + array( 'to_company_id' => $to_company_id ) );

		Forms::back( array(), __( 'Your enquiry was sent. The company will contact you by phone or e-mail.', 'sm-core' ) );
	}

	/** Enquiries received by a company. */
	public static function received( $company_id, $limit = 50, $status = '' ) {
		global $wpdb;
		$sql  = 'SELECT * FROM ' . sm_table( 'enquiries' ) . ' WHERE to_company_id = %d' . ( $status ? ' AND status = %s' : '' ) . ' ORDER BY id DESC LIMIT %d';
		$args = $status ? array( $company_id, $status, $limit ) : array( $company_id, $limit );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		return $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
	}

	/** Enquiries sent by a user. */
	public static function sent( $user_id, $limit = 50 ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . sm_table( 'enquiries' ) . ' WHERE from_user_id = %d ORDER BY id DESC LIMIT %d', $user_id, $limit ) );
	}

	public static function count_new( $company_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . sm_table( 'enquiries' ) . " WHERE to_company_id = %d AND status = 'new'", $company_id ) );
	}

	public static function count_all( $company_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . sm_table( 'enquiries' ) . ' WHERE to_company_id = %d', $company_id ) );
	}

	/** Mark read / archived (recipient company members only). */
	public static function handle_status() {
		Forms::begin( 'enquiry_status' );
		global $wpdb;
		$id      = absint( Forms::post( 'id' ) );
		$status  = in_array( Forms::post( 'status' ), array( 'read', 'archived', 'new' ), true ) ? Forms::post( 'status' ) : 'read';
		$company = Companies::for_user( get_current_user_id() );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT to_company_id FROM ' . sm_table( 'enquiries' ) . ' WHERE id = %d', $id ) );
		if ( ! $company || ! $row || (int) $row->to_company_id !== (int) $company->id ) {
			Forms::back( array( __( 'Enquiry not found.', 'sm-core' ) ) );
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update( sm_table( 'enquiries' ), array( 'status' => $status, 'read_at' => current_time( 'mysql', true ) ), array( 'id' => $id ) );
		Forms::back();
	}
}

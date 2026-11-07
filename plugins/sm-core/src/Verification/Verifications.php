<?php
/**
 * Business / engineer verification (levels 2 and 3).
 *
 * The company uploads its documents (RC, NIF, NIS — or diploma and approval for
 * engineers). Files are stored outside the web root when possible (else in
 * uploads/sm-private/ with deny-all rules and random names) and served only to
 * moderators and the company owner through admin-post.php?action=sm_document.
 *
 * A moderator approves (level 2 for businesses, 3 for engineers) or rejects with
 * a reason; the company is notified in its language.
 *
 * @package SM\Core
 */

namespace SM\Core\Verification;

use SM\Core\Accounts\Companies;
use SM\Core\Audit\AuditLog;
use SM\Core\Notifications\Notifications;
use SM\Core\Support\Forms;

defined( 'ABSPATH' ) || exit;

final class Verifications {

	const MAX_BYTES = 5242880; // 5 MB.

	public static function init() {
		add_action( 'admin_post_sm_verification_submit', array( __CLASS__, 'handle_submit' ) );
		add_action( 'admin_post_sm_document', array( __CLASS__, 'serve_document' ) );
	}

	/** Documents expected per account class: key => label. */
	public static function document_types( $class ) {
		if ( 'engineering' === $class ) {
			return array(
				'diploma'  => __( 'Engineering diploma', 'sm-core' ),
				'approval' => __( 'Approval or registration of the engineering office (if any)', 'sm-core' ),
				'id'       => __( 'Identity document of the engineer', 'sm-core' ),
			);
		}
		return array(
			'rc'  => __( 'Commercial register (RC)', 'sm-core' ),
			'nif' => __( 'Tax identification number (NIF)', 'sm-core' ),
			'nis' => __( 'Statistical identification number (NIS)', 'sm-core' ),
		);
	}

	/** Level granted on approval. */
	public static function target_level( $class ) {
		return 'engineering' === $class ? 3 : 2;
	}

	/**
	 * Private storage folder. Preferably OUTSIDE the web root (the folder above
	 * WordPress, writable on Hostinger), so files can never be fetched by URL
	 * whatever the web server. Fallback: uploads/sm-private with deny-all rules.
	 * Filter: sm_private_dir.
	 */
	public static function private_dir() {
		static $dir = null;
		if ( null !== $dir ) {
			return $dir;
		}
		$outside = trailingslashit( dirname( untrailingslashit( ABSPATH ) ) ) . 'sm-private-' . substr( hash( 'sha256', wp_salt( 'secure_auth' ) ), 0, 12 );
		if ( is_dir( $outside ) || ( wp_is_writable( dirname( $outside ) ) && wp_mkdir_p( $outside ) ) ) {
			$dir = $outside;
		} else {
			$uploads = wp_upload_dir();
			$dir     = trailingslashit( $uploads['basedir'] ) . 'sm-private';
			wp_mkdir_p( $dir );
		}
		$dir = apply_filters( 'sm_private_dir', $dir );
		if ( ! file_exists( $dir . '/.htaccess' ) ) {
			file_put_contents( $dir . '/.htaccess', "Require all denied\nDeny from all\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		if ( ! file_exists( $dir . '/index.php' ) ) {
			file_put_contents( $dir . '/index.php', "<?php // Silence.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		return $dir;
	}

	/** Whether documents are stored outside the public web folder. */
	public static function is_outside_webroot() {
		return 0 !== strpos( wp_normalize_path( self::private_dir() ), wp_normalize_path( ABSPATH ) );
	}

	/** Latest verification request of a company, or null. */
	public static function latest( $company_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . sm_table( 'verifications' ) . ' WHERE company_id = %d ORDER BY id DESC LIMIT 1', $company_id ) );
		if ( $row ) {
			$row->documents = json_decode( (string) $row->documents, true ) ?: array();
		}
		return $row;
	}

	public static function get( $id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . sm_table( 'verifications' ) . ' WHERE id = %d', $id ) );
		if ( $row ) {
			$row->documents = json_decode( (string) $row->documents, true ) ?: array();
		}
		return $row;
	}

	public static function pending( $limit = 50 ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . sm_table( 'verifications' ) . " WHERE status = 'pending' ORDER BY id ASC LIMIT %d", $limit ) );
	}

	public static function handle_submit() {
		Forms::begin( 'verification_submit' );
		$user_id = get_current_user_id();
		$company = Companies::for_user( $user_id );
		if ( ! $company || ! Companies::can_manage( $company->id, $user_id ) ) {
			Forms::back( array( __( 'Only the account owner or a manager can request verification.', 'sm-core' ) ) );
		}
		if ( (int) $company->verification_level < 1 ) {
			Forms::back( array( __( 'Verify your contact details first.', 'sm-core' ) ) );
		}
		$latest = self::latest( $company->id );
		if ( $latest && 'pending' === $latest->status ) {
			Forms::back( array( __( 'A verification request is already being reviewed.', 'sm-core' ) ) );
		}

		$types   = self::document_types( $company->account_class );
		$stored  = array();
		$errors  = array();
		$dir     = self::private_dir() . '/' . $company->id . '-' . wp_generate_password( 12, false );
		$allowed = array( 'pdf' => 'application/pdf', 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png' );

		foreach ( $types as $key => $label ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			$file = $_FILES[ 'doc_' . $key ] ?? null;
			if ( ! $file || UPLOAD_ERR_NO_FILE === (int) $file['error'] ) {
				continue;
			}
			if ( UPLOAD_ERR_OK !== (int) $file['error'] || (int) $file['size'] > self::MAX_BYTES ) {
				/* translators: %s: document name. */
				$errors[ 'doc_' . $key ] = sprintf( __( '%s: the file must be a PDF, JPG or PNG under 5 MB.', 'sm-core' ), $label );
				continue;
			}
			$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $allowed );
			if ( empty( $check['ext'] ) || empty( $check['type'] ) ) {
				/* translators: %s: document name. */
				$errors[ 'doc_' . $key ] = sprintf( __( '%s: the file must be a PDF, JPG or PNG under 5 MB.', 'sm-core' ), $label );
				continue;
			}
			if ( ! is_dir( $dir ) ) {
				wp_mkdir_p( $dir );
			}
			$name = $key . '-' . wp_generate_password( 8, false ) . '.' . $check['ext'];
			if ( ! move_uploaded_file( $file['tmp_name'], $dir . '/' . $name ) ) { // phpcs:ignore Generic.PHP.ForbiddenFunctions
				$errors[ 'doc_' . $key ] = __( 'The file could not be saved. Try again.', 'sm-core' );
				continue;
			}
			$stored[ $key ] = array(
				'path'     => basename( $dir ) . '/' . $name,
				'original' => sanitize_file_name( $file['name'] ),
				'type'     => $check['type'],
				'size'     => (int) $file['size'],
			);
		}

		$first_type = array_key_first( $types );
		if ( ! $errors && empty( $stored[ $first_type ] ) ) {
			/* translators: %s: document name. */
			$errors[ 'doc_' . $first_type ] = sprintf( __( '%s is required.', 'sm-core' ), $types[ $first_type ] );
		}
		if ( $errors ) {
			Forms::back( $errors );
		}

		// Registration numbers typed in the form are stored encrypted on the company.
		Companies::update_profile(
			$company->id,
			array_filter(
				array(
					'rc_number' => Forms::post( 'rc_number' ),
					'nif'       => Forms::post( 'nif' ),
					'nis'       => Forms::post( 'nis' ),
				),
				'strlen'
			)
		);

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert(
			sm_table( 'verifications' ),
			array(
				'company_id' => $company->id,
				'level'      => self::target_level( $company->account_class ),
				'documents'  => wp_json_encode( $stored ),
				'note'       => Forms::post_textarea( 'note' ),
				'status'     => 'pending',
				'created_at' => current_time( 'mysql', true ),
			)
		);
		$request_id = (int) $wpdb->insert_id;
		AuditLog::add( 'verification.submitted', 'verification', $request_id, array( 'documents' => array_keys( $stored ) ), $user_id, $company->id );
		Notifications::notify_moderators( 'verification.submitted', array( 'company_id' => $company->id, 'request_id' => $request_id ) );
		sm_event( 'verification.submitted', array( 'company_id' => $company->id, 'request_id' => $request_id ) );

		Forms::back( array(), __( 'Documents received. The platform team will review them, usually within 2 working days.', 'sm-core' ) );
	}

	/**
	 * Approve or reject a request (moderators).
	 *
	 * @return true|\WP_Error
	 */
	public static function decide( $request_id, $approve, $note = '' ) {
		global $wpdb;
		if ( ! current_user_can( 'sm_moderate' ) ) {
			return new \WP_Error( 'sm_forbidden', 'Forbidden' );
		}
		$request = self::get( $request_id );
		if ( ! $request || 'pending' !== $request->status ) {
			return new \WP_Error( 'sm_not_pending', __( 'This request was already reviewed.', 'sm-core' ) );
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update(
			sm_table( 'verifications' ),
			array(
				'status'        => $approve ? 'approved' : 'rejected',
				'reviewer_id'   => get_current_user_id(),
				'decision_note' => sanitize_textarea_field( $note ),
				'decided_at'    => current_time( 'mysql', true ),
			),
			array( 'id' => $request_id )
		);
		if ( $approve ) {
			Companies::raise_level( $request->company_id, (int) $request->level );
		}
		AuditLog::add( $approve ? 'verification.approved' : 'verification.rejected', 'verification', $request_id, array( 'note' => $note ), get_current_user_id(), $request->company_id );
		Notifications::notify_company(
			$request->company_id,
			$approve ? 'verification.approved' : 'verification.rejected',
			array( 'note' => sanitize_textarea_field( $note ) ),
			array( 'owner', 'manager' )
		);
		return true;
	}

	/** Stream a private document to a moderator or the company owner/manager. */
	public static function serve_document() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$request_id = isset( $_GET['request'] ) ? absint( $_GET['request'] ) : 0;
		$key        = isset( $_GET['doc'] ) ? sanitize_key( wp_unslash( $_GET['doc'] ) ) : '';
		$nonce      = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		// phpcs:enable
		if ( ! wp_verify_nonce( $nonce, 'sm_document_' . $request_id ) ) {
			wp_die( esc_html__( 'Link expired.', 'sm-core' ), 403 );
		}
		$request = self::get( $request_id );
		if ( ! $request || empty( $request->documents[ $key ] ) ) {
			wp_die( esc_html__( 'Document not found.', 'sm-core' ), 404 );
		}
		if ( ! current_user_can( 'sm_moderate' ) && ! Companies::can_manage( $request->company_id, get_current_user_id() ) ) {
			wp_die( esc_html__( 'You are not allowed to view this document.', 'sm-core' ), 403 );
		}
		$doc  = $request->documents[ $key ];
		$path = self::private_dir() . '/' . $doc['path'];
		$real = realpath( $path );
		if ( ! $real || 0 !== strpos( $real, realpath( self::private_dir() ) ) || ! is_readable( $real ) ) {
			wp_die( esc_html__( 'Document not found.', 'sm-core' ), 404 );
		}
		AuditLog::add( 'verification.document_viewed', 'verification', $request_id, array( 'doc' => $key ) );
		nocache_headers();
		header( 'Content-Type: ' . $doc['type'] );
		header( 'Content-Disposition: inline; filename="' . sanitize_file_name( $doc['original'] ) . '"' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Content-Length: ' . filesize( $real ) );
		readfile( $real ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}

	public static function document_url( $request_id, $key ) {
		return add_query_arg(
			array(
				'action'   => 'sm_document',
				'request'  => $request_id,
				'doc'      => $key,
				'_wpnonce' => wp_create_nonce( 'sm_document_' . $request_id ),
			),
			admin_url( 'admin-post.php' )
		);
	}
}

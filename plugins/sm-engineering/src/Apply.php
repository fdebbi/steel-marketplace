<?php
/**
 * Partner engineer application and profile.
 *
 *   sm_engineer_apply    New application (creates a separate engineer account,
 *                        signed in at once, status "pending"), or a new sending
 *                        after a refusal.
 *   sm_engineer_profile  The engineer edits the public profile and availability.
 *
 * The documents (diploma, identity, approval, CV) go to SM Core's private
 * verification store; approving the application approves them too.
 *
 * @package SM\Engineering
 */

namespace SM\Engineering;

use SM\Core\Accounts\Companies;
use SM\Core\Accounts\PhoneVerification;
use SM\Core\Accounts\Registration;
use SM\Core\Audit\AuditLog;
use SM\Core\Content\Media;
use SM\Core\Notifications\Notifications as Notify;
use SM\Core\Routing\Routes as CoreRoutes;
use SM\Core\Support\Forms;

defined( 'ABSPATH' ) || exit;

final class Apply {

	public static function init() {
		add_action( 'admin_post_nopriv_sm_engineer_apply', array( __CLASS__, 'handle_apply' ) );
		add_action( 'admin_post_sm_engineer_apply', array( __CLASS__, 'handle_apply' ) );
		add_action( 'admin_post_sm_engineer_profile', array( __CLASS__, 'handle_profile' ) );
	}

	/** Documents asked with the application: key => [ label, required ]. */
	public static function documents() {
		return array(
			'diploma'  => array( __( 'Engineering diploma', 'sm-engineering' ), true ),
			'id'       => array( __( 'Identity card or passport', 'sm-engineering' ), true ),
			'approval' => array( __( 'Approval or registration of the engineering office (if any)', 'sm-engineering' ), false ),
			'cv'       => array( __( 'CV or list of references', 'sm-engineering' ), false ),
		);
	}

	/**
	 * Validate the engineer fields.
	 *
	 * @param bool $locked Diploma, school and year cannot change (approved engineer).
	 */
	public static function validate_profile( $locked = false ) {
		$errors = array();
		$types  = Settings::types();
		$d      = array(
			'title'       => mb_substr( trim( Forms::post( 'title' ) ), 0, 160 ),
			'diploma'     => mb_substr( trim( Forms::post( 'diploma' ) ), 0, 160 ),
			'school'      => mb_substr( trim( Forms::post( 'school' ) ), 0, 160 ),
			'grad_year'   => absint( Forms::post( 'grad_year' ) ),
			'experience'  => min( 60, absint( Forms::post( 'experience' ) ) ),
			'specialties' => array_values( array_intersect( array_map( 'sanitize_key', (array) Forms::post( 'specialties', array() ) ), array_keys( $types ) ) ),
			'bio'         => mb_substr( Forms::post_textarea( 'bio' ), 0, 2000 ),
		);
		if ( mb_strlen( $d['title'] ) < 3 ) {
			$errors['title'] = __( 'Enter your professional title, for example “Civil engineer — steel structures”.', 'sm-engineering' );
		}
		if ( ! $locked ) {
			if ( mb_strlen( $d['diploma'] ) < 3 ) {
				$errors['diploma'] = __( 'Enter your diploma.', 'sm-engineering' );
			}
			if ( mb_strlen( $d['school'] ) < 2 ) {
				$errors['school'] = __( 'Enter the university or school.', 'sm-engineering' );
			}
			if ( $d['grad_year'] < 1960 || $d['grad_year'] > (int) gmdate( 'Y' ) ) {
				$errors['grad_year'] = __( 'Enter the year of the diploma.', 'sm-engineering' );
			}
		}
		if ( ! $d['specialties'] ) {
			$errors['specialties'] = __( 'Choose at least one specialty.', 'sm-engineering' );
		}
		if ( mb_strlen( $d['bio'] ) < 40 ) {
			$errors['bio'] = __( 'Describe your experience in a few lines (40 characters at least).', 'sm-engineering' );
		}
		return array( 'data' => $d, 'errors' => $errors );
	}

	/** Store the documents and open a verification request. */
	private static function store_documents( $company_id, $require ) {
		$stored = array();
		$errors = array();
		$sub    = 'engineers/' . (int) $company_id . '-' . wp_generate_password( 10, false );
		foreach ( self::documents() as $key => list( $label, $required ) ) {
			$r = Files::store( 'doc_' . $key, $sub, 1, array( 'pdf', 'jpg', 'jpeg', 'png' ) );
			if ( $r['errors'] ) {
				$errors[ 'doc_' . $key ] = $label . ' : ' . $r['errors'][0];
			} elseif ( $r['files'] ) {
				$f              = $r['files'][0];
				$stored[ $key ] = array( 'path' => $f['path'], 'original' => $f['original'], 'type' => $f['mime'], 'size' => $f['size'] );
			} elseif ( $required && $require ) {
				/* translators: %s: document name. */
				$errors[ 'doc_' . $key ] = sprintf( __( '%s is required.', 'sm-engineering' ), $label );
			}
		}
		if ( $errors ) { // Nothing is kept from an incomplete sending.
			foreach ( $stored as $doc ) {
				wp_delete_file( \SM\Core\Verification\Verifications::private_dir() . '/' . $doc['path'] );
			}
			$stored = array();
		}
		return array( 'docs' => $stored, 'errors' => $errors );
	}

	private static function open_verification( $company_id, array $docs, $user_id ) {
		global $wpdb;
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prefix . 'sm_verifications',
			array(
				'company_id' => (int) $company_id,
				'level'      => 3,
				'documents'  => wp_json_encode( $docs ),
				'note'       => __( 'Partner engineer application', 'sm-engineering' ),
				'status'     => 'pending',
				'created_at' => current_time( 'mysql', true ),
			)
		);
		$id = (int) $wpdb->insert_id;
		AuditLog::add( 'verification.submitted', 'verification', $id, array( 'documents' => array_keys( $docs ) ), $user_id, $company_id );
		return $id;
	}

	private static function photo( $company_id ) {
		$r = Media::upload( 'photo', Media::IMAGE_TYPES, 1, 4 * MB_IN_BYTES );
		if ( $r['ids'] ) {
			Companies::update_profile( $company_id, array( 'logo_id' => $r['ids'][0] ) );
		}
		return $r['errors'];
	}

	public static function handle_apply() {
		Forms::begin( 'engineer_apply' );
		if ( ! Settings::get( 'applications_open' ) ) {
			Forms::back( array( __( 'Applications are closed for the moment.', 'sm-engineering' ) ) );
		}
		$user_id = get_current_user_id();
		if ( $user_id ) {
			self::reapply( $user_id );
			return;
		}
		if ( ! Forms::rate_limit( 'register', (int) sm_setting( 'register_per_hour', 5 ), HOUR_IN_SECONDS ) ) {
			Forms::back( array( __( 'Too many sign-ups from this connection. Try again in an hour.', 'sm-engineering' ) ) );
		}
		$account = Registration::validate( 'engineer' );
		$profile = self::validate_profile();
		$errors  = $account['errors'] + $profile['errors'];
		unset( $errors['company'] );
		if ( $errors ) {
			Forms::back( $errors );
		}
		$account['company'] = $account['full_name'];
		$ids                = Registration::create_account( $account );
		if ( is_wp_error( $ids ) ) {
			Forms::back( array( $ids->get_error_code() => $ids->get_error_message() ) );
		}
		list( $new_user, $company_id ) = $ids;
		$docs = self::store_documents( $company_id, true );
		if ( $docs['errors'] ) {
			// Nothing is kept when a required document is missing: the person sends the form again.
			require_once ABSPATH . 'wp-admin/includes/user.php';
			self::remove_account( $new_user, $company_id );
			Forms::back( $docs['errors'] );
		}
		Engineers::save( $company_id, $new_user, $profile['data'], 'pending' );
		Engineers::set( $company_id, array( 'verification_id' => self::open_verification( $company_id, $docs['docs'], $new_user ) ) );
		$photo_errors = self::photo( $company_id );
		Registration::sign_in( $new_user, $account );
		AuditLog::add( 'engineer.applied', 'engineer', $company_id, array( 'specialties' => $profile['data']['specialties'] ), $new_user, $company_id );
		Notify::notify_moderators( 'engineer.applied', array( 'company_id' => $company_id ) );
		PhoneVerification::send_code( $new_user );
		Forms::back( $photo_errors, __( 'Thank you, your application is sent. The platform team checks your documents and answers by e-mail.', 'sm-engineering' ), CoreRoutes::url( 'account' ) );
	}

	/** A partner engineer sends the application again (after a refusal) or completes it. */
	private static function reapply( $user_id ) {
		$e = Engineers::for_user( $user_id );
		if ( ! $e ) {
			Forms::back( array( __( 'Partner engineers have their own account. Sign out, then apply with another e-mail address.', 'sm-engineering' ) ) );
		}
		if ( 'rejected' !== $e->status ) {
			Forms::back( array( __( 'Your application is already being reviewed or approved.', 'sm-engineering' ) ), '', CoreRoutes::url( 'account' ) );
		}
		$profile = self::validate_profile();
		if ( $profile['errors'] ) {
			Forms::back( $profile['errors'] );
		}
		$docs = self::store_documents( $e->company_id, true );
		if ( $docs['errors'] ) {
			Forms::back( $docs['errors'] );
		}
		Engineers::save( $e->company_id, $user_id, $profile['data'], 'pending' );
		Engineers::set( $e->company_id, array( 'verification_id' => self::open_verification( $e->company_id, $docs['docs'], $user_id ), 'reject_reason' => '', 'applied_at' => current_time( 'mysql', true ) ) );
		$photo_errors = self::photo( $e->company_id );
		AuditLog::add( 'engineer.applied', 'engineer', $e->company_id, array( 'again' => 1 ), $user_id, $e->company_id );
		Notify::notify_moderators( 'engineer.applied', array( 'company_id' => $e->company_id ) );
		Forms::back( $photo_errors, __( 'Your application is sent again. The platform team will review it.', 'sm-engineering' ), CoreRoutes::url( 'account' ) );
	}

	private static function remove_account( $user_id, $company_id ) {
		global $wpdb;
		foreach ( array( 'company_members', 'company_activities' ) as $t ) {
			$wpdb->delete( $wpdb->prefix . 'sm_' . $t, array( 'company_id' => (int) $company_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}
		$wpdb->delete( $wpdb->prefix . 'sm_companies', array( 'id' => (int) $company_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		wp_delete_user( $user_id );
	}

	public static function handle_profile() {
		Forms::begin( 'engineer_profile' );
		$user_id = get_current_user_id();
		$e       = Engineers::for_user( $user_id );
		if ( ! $e ) {
			Forms::back( array( __( 'This page is for partner engineers.', 'sm-engineering' ) ) );
		}
		$locked  = in_array( $e->status, array( 'approved', 'suspended' ), true );
		$profile = self::validate_profile( $locked );
		if ( $profile['errors'] ) {
			Forms::back( $profile['errors'] );
		}
		$d = $profile['data'];
		if ( $locked ) { // The diploma was checked by the platform: it stays as approved.
			$d['diploma']   = $e->diploma;
			$d['school']    = $e->school;
			$d['grad_year'] = $e->grad_year;
		}
		Engineers::save( $e->company_id, $user_id, $d );
		Engineers::set( $e->company_id, array( 'available' => Forms::post( 'available' ) ? 1 : 0 ) );
		$errors = self::photo( $e->company_id );
		if ( Forms::post( 'photo_remove' ) ) {
			Companies::update_profile( $e->company_id, array( 'logo_id' => 0 ) );
		}
		AuditLog::add( 'engineer.profile_saved', 'engineer', $e->company_id, array(), $user_id, $e->company_id );
		Forms::back( $errors, __( 'Your profile is saved.', 'sm-engineering' ) );
	}
}

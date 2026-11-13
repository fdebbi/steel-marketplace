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

	/**
	 * Documents asked with the application (0.4.0): key => [ label, required ].
	 * All are required; the approval of the engineering office only for a BET.
	 *
	 * @param bool $bet The engineer applies as an engineering office (BET).
	 */
	public static function documents( $bet = false ) {
		$docs = array(
			'diploma' => array( __( 'Diploma', 'sm-engineering' ), true ),
			'id'      => array( __( 'Identity card or passport', 'sm-engineering' ), true ),
		);
		if ( $bet ) {
			$docs['bet_agrement'] = array( __( 'BET approval (MHUV, valid)', 'sm-engineering' ), true );
		}
		return $docs;
	}

	/** Name of a document of a verification (also the documents of older applications). */
	public static function document_label( $key ) {
		$labels = array(
			'diploma'      => __( 'Diploma', 'sm-engineering' ),
			'id'           => __( 'Identity card or passport', 'sm-engineering' ),
			'bet_agrement' => __( 'BET approval (MHUV, valid)', 'sm-engineering' ),
			'approval'     => __( 'Approval or registration of the engineering office (if any)', 'sm-engineering' ),
			'cv'           => __( 'CV or list of references', 'sm-engineering' ),
		);
		return $labels[ $key ] ?? $key;
	}

	/**
	 * Validate the engineer fields (0.4.0: type of partner, title and speciality
	 * chosen in lists, years of experience, services, presentation).
	 *
	 * @param bool $locked The title cannot change (approved engineer).
	 */
	public static function validate_profile( $locked = false ) {
		$errors  = array();
		$types   = Settings::types();
		$titles  = Lists::all( 'titles' );
		$specs   = Lists::all( 'specialities' );
		$d       = array(
			'partner_type'     => 'bet' === Forms::post( 'partner_type' ) ? 'bet' : 'independent',
			'bet_name'         => mb_substr( trim( Forms::post( 'bet_name' ) ), 0, 160 ),
			'degree'           => sanitize_key( Forms::post( 'degree' ) ),
			'degree_other'     => mb_substr( trim( Forms::post( 'degree_other' ) ), 0, 120 ),
			'speciality'       => sanitize_key( Forms::post( 'speciality' ) ),
			'speciality_other' => mb_substr( trim( Forms::post( 'speciality_other' ) ), 0, 120 ),
			'experience'       => min( 60, absint( Forms::post( 'experience' ) ) ),
			'specialties'      => array_values( array_intersect( array_map( 'sanitize_key', (array) Forms::post( 'specialties', array() ) ), array_keys( $types ) ) ),
			'bio'              => mb_substr( Forms::post_textarea( 'bio' ), 0, 2000 ),
			'school'           => '',
			'grad_year'        => 0,
		);
		if ( 'bet' !== $d['partner_type'] ) {
			$d['bet_name'] = '';
		} elseif ( mb_strlen( $d['bet_name'] ) < 2 ) {
			$errors['bet_name'] = __( 'Enter the name of the engineering office.', 'sm-engineering' );
		}
		if ( ! $locked ) {
			if ( ! isset( $titles[ $d['degree'] ] ) ) {
				$errors['degree'] = __( 'Choose your professional title.', 'sm-engineering' );
			} elseif ( 'other' === $d['degree'] && mb_strlen( $d['degree_other'] ) < 3 ) {
				$errors['degree_other'] = __( 'Write your professional title.', 'sm-engineering' );
			}
			if ( 'other' !== $d['degree'] ) {
				$d['degree_other'] = '';
			}
		}
		if ( ! isset( $specs[ $d['speciality'] ] ) ) {
			$errors['speciality'] = __( 'Choose your speciality.', 'sm-engineering' );
		} elseif ( 'other' === $d['speciality'] && mb_strlen( $d['speciality_other'] ) < 3 ) {
			$errors['speciality_other'] = __( 'Write your speciality.', 'sm-engineering' );
		}
		if ( 'other' !== $d['speciality'] ) {
			$d['speciality_other'] = '';
		}
		if ( ! $d['specialties'] ) {
			$errors['specialties'] = __( 'Choose at least one service.', 'sm-engineering' );
		}
		if ( mb_strlen( $d['bio'] ) < 20 ) {
			$errors['bio'] = __( 'Present your experience in a few words (20 characters at least).', 'sm-engineering' );
		}
		// Title shown to the platform team: "Ingénieur d'État en génie civil — Structure métallique".
		$degree       = Lists::label( 'titles', $d['degree'], $d['degree_other'], 'fr' );
		$d['diploma'] = $degree;
		$d['title']   = mb_substr( trim( $degree . ' — ' . Lists::label( 'specialities', $d['speciality'], $d['speciality_other'], 'fr' ), ' —' ), 0, 160 );
		return array( 'data' => $d, 'errors' => $errors );
	}

	/** Store the documents and open a verification request. */
	private static function store_documents( $company_id, $require, $bet = false ) {
		$stored = array();
		$errors = array();
		$sub    = 'engineers/' . (int) $company_id . '-' . wp_generate_password( 10, false );
		foreach ( self::documents( $bet ) as $key => list( $label, $required ) ) {
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
		// 0.4.0: first name and last name in two fields.
		$first = trim( Forms::post( 'first_name' ) );
		$last  = trim( Forms::post( 'last_name' ) );
		if ( '' !== $first || '' !== $last ) {
			$_POST['name'] = trim( $first . ' ' . $last ); // phpcs:ignore WordPress.Security.NonceVerification -- checked in Forms::begin().
		}
		$account = Registration::validate( 'engineer' );
		$profile = self::validate_profile();
		$errors  = $account['errors'] + $profile['errors'];
		unset( $errors['company'] );
		if ( isset( $errors['name'] ) ) {
			$errors[ '' === $first ? 'first_name' : 'last_name' ] = __( 'Enter your last name and first name.', 'sm-engineering' );
			unset( $errors['name'] );
		}
		// The e-mail address is confirmed with a code before the account is created.
		if ( ! isset( $errors['email'] ) && ! EmailCode::confirmed( $account['email'] ) ) {
			$errors['email_code'] = __( 'Confirm your e-mail address with the code you received.', 'sm-engineering' );
		}
		if ( $errors ) {
			Forms::back( $errors );
		}
		$account['company'] = 'bet' === $profile['data']['partner_type'] ? $profile['data']['bet_name'] : $account['full_name'];
		$ids                = Registration::create_account( $account );
		if ( is_wp_error( $ids ) ) {
			Forms::back( array( $ids->get_error_code() => $ids->get_error_message() ) );
		}
		list( $new_user, $company_id ) = $ids;
		$docs = self::store_documents( $company_id, true, 'bet' === $profile['data']['partner_type'] );
		if ( $docs['errors'] ) {
			// Nothing is kept when a required document is missing: the person sends the form again.
			require_once ABSPATH . 'wp-admin/includes/user.php';
			self::remove_account( $new_user, $company_id );
			Forms::back( $docs['errors'] );
		}
		Engineers::save( $company_id, $new_user, $profile['data'], 'pending' );
		Engineers::set( $company_id, array( 'verification_id' => self::open_verification( $company_id, $docs['docs'], $new_user ) ) );
		$photo_errors = self::photo( $company_id );
		if ( '' !== $first || '' !== $last ) {
			wp_update_user( array( 'ID' => $new_user, 'first_name' => $first, 'last_name' => $last ) );
		}
		// The address was confirmed with the code: the contact of the account is verified.
		update_user_meta( $new_user, 'sm_contact_verified', 'email' );
		update_user_meta( $new_user, 'sm_contact_verified_at', time() );
		Companies::raise_level( $company_id, 1 );
		EmailCode::forget( $account['email'] );
		Registration::sign_in( $new_user, $account );
		AuditLog::add( 'engineer.applied', 'engineer', $company_id, array( 'specialties' => $profile['data']['specialties'], 'partner_type' => $profile['data']['partner_type'] ), $new_user, $company_id );
		Notify::notify_moderators( 'engineer.applied', array( 'company_id' => $company_id ) );
		Forms::back( $photo_errors, __( 'Thank you, your application is sent. The platform team checks your documents and answers by e-mail.', 'sm-engineering' ), CoreRoutes::url( 'engineer-apply', array( 'sent' => 1 ) ) );
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
		$docs = self::store_documents( $e->company_id, true, 'bet' === $profile['data']['partner_type'] );
		if ( $docs['errors'] ) {
			Forms::back( $docs['errors'] );
		}
		Engineers::save( $e->company_id, $user_id, $profile['data'], 'pending' );
		Engineers::set( $e->company_id, array( 'verification_id' => self::open_verification( $e->company_id, $docs['docs'], $user_id ), 'reject_reason' => '', 'applied_at' => current_time( 'mysql', true ) ) );
		$photo_errors = self::photo( $e->company_id );
		AuditLog::add( 'engineer.applied', 'engineer', $e->company_id, array( 'again' => 1 ), $user_id, $e->company_id );
		Notify::notify_moderators( 'engineer.applied', array( 'company_id' => $e->company_id ) );
		Forms::back( $photo_errors, __( 'Your application is sent again. The platform team will review it.', 'sm-engineering' ), CoreRoutes::url( 'engineer-apply', array( 'sent' => 1 ) ) );
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
		if ( $locked ) { // The title was checked by the platform with the diploma: it stays as approved.
			$d['degree']       = $e->degree;
			$d['degree_other'] = $e->degree_other;
			$d['diploma']      = $e->diploma;
			$d['school']       = $e->school;
			$d['grad_year']    = $e->grad_year;
			$d['title']        = mb_substr( trim( ( '' !== $e->degree ? Lists::label( 'titles', $e->degree, $e->degree_other, 'fr' ) : $e->diploma ) . ' — ' . Lists::label( 'specialities', $d['speciality'], $d['speciality_other'], 'fr' ), ' —' ), 0, 160 );
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

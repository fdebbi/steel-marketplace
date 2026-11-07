<?php
/**
 * Registration: business account or engineering account, in one step.
 *
 * Creates the WordPress user (role sm_member, login = e-mail), the company,
 * its owner membership and activities, signs the user in, sends the
 * verification code and opens the "verify" step of the dashboard.
 *
 * @package SM\Core
 */

namespace SM\Core\Accounts;

use SM\Core\Audit\AuditLog;
use SM\Core\Routing\Routes;
use SM\Core\Support\Forms;

defined( 'ABSPATH' ) || exit;

final class Registration {

	public static function init() {
		add_action( 'admin_post_nopriv_sm_register', array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_sm_register', array( __CLASS__, 'handle' ) );
	}

	public static function handle() {
		Forms::begin( 'register' );

		if ( ! Forms::rate_limit( 'register', (int) sm_setting( 'register_per_hour', 5 ), HOUR_IN_SECONDS ) ) {
			Forms::back( array( __( 'Too many sign-ups from this connection. Try again in an hour.', 'sm-core' ) ) );
		}

		$data   = self::validate();
		$errors = $data['errors'];
		if ( $errors ) {
			Forms::back( $errors );
		}

		$user_id = wp_insert_user(
			array(
				'user_login'   => $data['email'],
				'user_email'   => $data['email'],
				'user_pass'    => $data['password'],
				'display_name' => $data['full_name'],
				'first_name'   => $data['full_name'],
				'role'         => 'sm_member',
				'locale'       => '',
			)
		);
		if ( is_wp_error( $user_id ) ) {
			Forms::back( array( 'email' => $user_id->get_error_message() ) );
		}
		update_user_meta( $user_id, 'sm_phone', $data['phone'] );
		update_user_meta( $user_id, \SM\Core\I18n\Language::META, sm_current_language() );

		$company_id = Companies::create(
			array(
				'account_class' => $data['class'],
				'name'          => $data['company'],
				'wilaya'        => $data['wilaya'],
				'phone'         => $data['phone'],
				'email'         => $data['email'],
			),
			$user_id,
			$data['activities']
		);
		if ( is_wp_error( $company_id ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( $user_id );
			Forms::back( array( $company_id->get_error_message() ) );
		}

		AuditLog::add( 'account.registered', 'company', $company_id, array( 'class' => $data['class'], 'activities' => $data['activities'] ), $user_id, $company_id );

		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true, is_ssl() );
		do_action( 'wp_login', $data['email'], get_user_by( 'id', $user_id ) );

		sm_event( 'account.registered', array( 'user_id' => $user_id, 'company_id' => $company_id ) );

		$sent = PhoneVerification::send_code( $user_id );
		$msg  = __( 'Welcome! Your account is created.', 'sm-core' );
		if ( true === $sent ) {
			$msg .= ' ' . PhoneVerification::sent_message( $user_id );
		}
		Forms::back( array(), $msg, Routes::url( 'account', array( 'view' => 'verify-phone' ) ) );
	}

	/**
	 * Validate the posted form.
	 *
	 * @return array Clean data + 'errors' (field => message).
	 */
	public static function validate() {
		$errors = array();
		$class  = Forms::post( 'account_class' ) === 'engineering' ? 'engineering' : 'commercial';

		$activities = array_values( array_intersect( (array) Forms::post( 'activities', array() ), Companies::activities( 'commercial' ) ) );
		if ( 'engineering' === $class ) {
			$activities = array( 'engineer' );
		} elseif ( ! $activities ) {
			$errors['activities'] = __( 'Choose at least one business activity.', 'sm-core' );
		}

		$company = trim( Forms::post( 'company' ) );
		if ( mb_strlen( $company ) < 2 || mb_strlen( $company ) > 120 ) {
			$errors['company'] = __( 'Enter the company or office name (2 to 120 characters).', 'sm-core' );
		}

		$full_name = trim( Forms::post( 'name' ) );
		if ( mb_strlen( $full_name ) < 3 ) {
			$errors['name'] = __( 'Enter your full name.', 'sm-core' );
		}

		$wilaya = Forms::post( 'wilaya' );
		if ( ! Wilayas::exists( $wilaya ) ) {
			$errors['wilaya'] = __( 'Choose your wilaya.', 'sm-core' );
		}

		$phone = Forms::normalize_mobile( Forms::post( 'phone' ) );
		if ( ! $phone ) {
			$errors['phone'] = __( 'Enter a valid Algerian mobile number (05, 06 or 07 followed by 8 digits).', 'sm-core' );
		} elseif ( Auth::user_by_phone( $phone ) ) {
			$errors['phone'] = __( 'This phone number is already used by another account.', 'sm-core' );
		}

		$email = sanitize_email( Forms::post( 'email' ) );
		if ( ! is_email( $email ) ) {
			$errors['email'] = __( 'Enter a valid e-mail address.', 'sm-core' );
		} elseif ( email_exists( $email ) || username_exists( $email ) ) {
			$errors['email'] = __( 'An account already exists with this e-mail. Sign in or reset your password.', 'sm-core' );
		}

		// phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput -- passwords are not sanitized.
		$password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
		if ( strlen( $password ) < 10 ) {
			$errors['password'] = __( 'The password must have at least 10 characters.', 'sm-core' );
		}

		if ( ! Forms::post( 'terms' ) ) {
			$errors['terms'] = __( 'Accept the terms of use to continue.', 'sm-core' );
		}

		return array(
			'errors'     => $errors,
			'class'      => $class,
			'activities' => $activities,
			'company'    => $company,
			'full_name'  => $full_name,
			'wilaya'     => $wilaya,
			'phone'      => $phone,
			'email'      => $email,
			'password'   => $password,
		);
	}
}

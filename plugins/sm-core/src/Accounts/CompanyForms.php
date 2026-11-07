<?php
/**
 * Dashboard forms: company profile and personal settings.
 *
 * @package SM\Core
 */

namespace SM\Core\Accounts;

use SM\Core\Audit\AuditLog;
use SM\Core\I18n\Language;
use SM\Core\Support\Forms;

defined( 'ABSPATH' ) || exit;

final class CompanyForms {

	public static function init() {
		add_action( 'admin_post_sm_company_save', array( __CLASS__, 'save_company' ) );
		add_action( 'admin_post_sm_settings_save', array( __CLASS__, 'save_settings' ) );
	}

	public static function save_company() {
		Forms::begin( 'company_save' );
		$user_id = get_current_user_id();
		$company = Companies::for_user( $user_id );
		if ( ! $company || ! Companies::can_manage( $company->id, $user_id ) ) {
			Forms::back( array( __( 'Only the account owner or a manager can edit the company.', 'sm-core' ) ) );
		}

		$errors = array();
		$name   = trim( Forms::post( 'name' ) );
		if ( mb_strlen( $name ) < 2 ) {
			$errors['name'] = __( 'Enter the company or office name (2 to 120 characters).', 'sm-core' );
		}
		$phone = Forms::post( 'phone' );
		if ( '' !== $phone && ! preg_match( '/^[0-9 +().-]{9,20}$/', $phone ) ) {
			$errors['phone'] = __( 'Enter a valid phone number.', 'sm-core' );
		}
		if ( ! Wilayas::exists( Forms::post( 'wilaya' ) ) ) {
			$errors['wilaya'] = __( 'Choose your wilaya.', 'sm-core' );
		}
		$email = Forms::post( 'email' );
		if ( '' !== $email && ! is_email( $email ) ) {
			$errors['email'] = __( 'Enter a valid e-mail address.', 'sm-core' );
		}

		$activities = array();
		if ( 'commercial' === $company->account_class ) {
			$activities = array_values( array_intersect( (array) Forms::post( 'activities', array() ), Companies::activities( 'commercial' ) ) );
			if ( ! $activities ) {
				$errors['activities'] = __( 'Choose at least one business activity.', 'sm-core' );
			}
		}
		if ( $errors ) {
			Forms::back( $errors );
		}

		$data = array(
			'name'     => $name,
			'name_ar'  => Forms::post( 'name_ar' ),
			'wilaya'   => Forms::post( 'wilaya' ),
			'commune'  => Forms::post( 'commune' ),
			'address'  => Forms::post( 'address' ),
			'phone'    => $phone,
			'email'    => $email,
			'website'  => Forms::post( 'website' ),
			'about_fr' => Forms::post_textarea( 'about_fr' ),
			'about_ar' => Forms::post_textarea( 'about_ar' ),
			'rc_number' => Forms::post( 'rc_number' ),
			'nif'      => Forms::post( 'nif' ),
			'nis'      => Forms::post( 'nis' ),
		);
		if ( 'engineering' === $company->account_class ) {
			$data['title_fr'] = Forms::post( 'title_fr' );
			$data['title_ar'] = Forms::post( 'title_ar' );
			$data['profile']  = array(
				'qualifications' => preg_split( '/\r\n|\n/', Forms::post_textarea( 'qualifications' ) ),
				'specialities'   => preg_split( '/\r\n|\n/', Forms::post_textarea( 'specialities' ) ),
			);
		}
		Companies::update_profile( $company->id, $data );
		if ( $activities ) {
			Companies::set_activities( $company->id, $activities );
		}
		AuditLog::add( 'company.updated', 'company', $company->id, array( 'activities' => $activities ), $user_id, $company->id );
		Forms::back( array(), __( 'Company profile saved.', 'sm-core' ) );
	}

	public static function save_settings() {
		Forms::begin( 'settings_save' );
		$user_id = get_current_user_id();
		$user    = get_userdata( $user_id );
		$errors  = array();

		$name = trim( Forms::post( 'display_name' ) );
		if ( mb_strlen( $name ) < 3 ) {
			$errors['display_name'] = __( 'Enter your full name.', 'sm-core' );
		}
		$lang = Forms::post( 'language' );
		if ( ! isset( Language::languages()[ $lang ] ) ) {
			$lang = Language::current();
		}

		// phpcs:disable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput -- verified in begin(); passwords are not sanitized.
		$current_pw = isset( $_POST['current_password'] ) ? (string) wp_unslash( $_POST['current_password'] ) : '';
		$new_pw     = isset( $_POST['new_password'] ) ? (string) wp_unslash( $_POST['new_password'] ) : '';
		// phpcs:enable
		if ( '' !== $new_pw ) {
			if ( ! wp_check_password( $current_pw, $user->user_pass, $user_id ) ) {
				$errors['current_password'] = __( 'Your current password is not correct.', 'sm-core' );
			} elseif ( strlen( $new_pw ) < 10 ) {
				$errors['new_password'] = __( 'The password must have at least 10 characters.', 'sm-core' );
			}
		}
		if ( $errors ) {
			Forms::back( $errors );
		}

		wp_update_user( array( 'ID' => $user_id, 'display_name' => $name, 'first_name' => $name ) );
		update_user_meta( $user_id, Language::META, $lang );
		Language::remember( $lang );
		if ( '' !== $new_pw ) {
			wp_set_password( $new_pw, $user_id );
			wp_set_auth_cookie( $user_id, true, is_ssl() );
			AuditLog::add( 'auth.password_changed', 'user', $user_id, array(), $user_id );
		}
		// Redirect to the same page in the chosen language.
		$target = Language::url_for( Forms::redirect_target(), $lang );
		Language::force( $lang );
		Forms::back( array(), __( 'Settings saved.', 'sm-core' ), $target );
	}
}

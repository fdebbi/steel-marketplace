<?php
/**
 * Sign in (e-mail or phone), lost password, logout, and keeping members out of wp-admin.
 *
 * @package SM\Core
 */

namespace SM\Core\Accounts;

use SM\Core\Audit\AuditLog;
use SM\Core\I18n\Language;
use SM\Core\Routing\Routes;
use SM\Core\Support\Forms;

defined( 'ABSPATH' ) || exit;

final class Auth {

	public static function init() {
		add_action( 'admin_post_nopriv_sm_login', array( __CLASS__, 'handle_login' ) );
		add_action( 'admin_post_sm_login', array( __CLASS__, 'handle_login' ) );
		add_action( 'admin_post_nopriv_sm_lost', array( __CLASS__, 'handle_lost' ) );
		add_action( 'admin_post_sm_lost', array( __CLASS__, 'handle_lost' ) );
		add_filter( 'logout_redirect', array( __CLASS__, 'logout_redirect' ), 10, 3 );
		add_action( 'admin_init', array( __CLASS__, 'keep_members_out_of_admin' ) );
		add_filter( 'show_admin_bar', array( __CLASS__, 'admin_bar' ) );
		add_action( 'wp_login_failed', array( __CLASS__, 'log_failure' ) );
	}

	/** User whose verified phone or registration phone is $phone. */
	public static function user_by_phone( $phone ) {
		$phone = Forms::normalize_mobile( $phone );
		if ( ! $phone ) {
			return null;
		}
		$users = get_users( array( 'meta_key' => 'sm_phone', 'meta_value' => $phone, 'number' => 1, 'fields' => 'all' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		return $users ? $users[0] : null;
	}

	/** User from an e-mail or phone identifier. */
	public static function find_user( $identifier ) {
		$identifier = trim( (string) $identifier );
		if ( is_email( $identifier ) ) {
			return get_user_by( 'email', $identifier ) ?: null;
		}
		$by_phone = self::user_by_phone( $identifier );
		if ( $by_phone ) {
			return $by_phone;
		}
		return get_user_by( 'login', $identifier ) ?: null;
	}

	public static function handle_login() {
		Forms::begin( 'login' );
		$identifier = Forms::post( 'log' );
		$key        = 'login|' . strtolower( $identifier );
		$max        = (int) sm_setting( 'login_attempts', 5 );

		if ( ! Forms::rate_limit( $key, $max, 15 * MINUTE_IN_SECONDS ) ) {
			AuditLog::add( 'auth.locked', 'user', 0, array( 'identifier' => $identifier ) );
			Forms::back( array( __( 'Too many attempts. Wait 15 minutes, or reset your password.', 'sm-core' ) ) );
		}

		$user = self::find_user( $identifier );
		// phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
		$password = isset( $_POST['pwd'] ) ? (string) wp_unslash( $_POST['pwd'] ) : '';
		$signed   = $user ? wp_signon(
			array(
				'user_login'    => $user->user_login,
				'user_password' => $password,
				'remember'      => (bool) Forms::post( 'rememberme' ),
			),
			is_ssl()
		) : new \WP_Error( 'invalid' );

		if ( is_wp_error( $signed ) ) {
			if ( ! $user ) {
				do_action( 'wp_login_failed', $identifier, $signed );
			}
			Forms::back( array( 'log' => __( 'Incorrect e-mail, phone or password.', 'sm-core' ) ) );
		}

		Forms::reset_rate_limit( $key );
		AuditLog::add( 'auth.login', 'user', $signed->ID, array(), $signed->ID );

		// Continue in the user's saved language.
		$lang   = Language::user_language( $signed->ID );
		// phpcs:ignore WordPress.Security.NonceVerification
		$target = ! empty( $_POST['sm_redirect'] ) ? wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['sm_redirect'] ) ), '' ) : '';
		if ( ! $target ) {
			$target = Routes::url( 'account' );
		}
		wp_safe_redirect( Language::url_for( $target, $lang ) );
		exit;
	}

	public static function handle_lost() {
		Forms::begin( 'lost' );
		if ( Forms::rate_limit( 'lost', 5, HOUR_IN_SECONDS ) ) {
			$user = self::find_user( Forms::post( 'user_login' ) );
			if ( $user ) {
				retrieve_password( $user->user_login );
				AuditLog::add( 'auth.password_reset_requested', 'user', $user->ID, array(), $user->ID );
			}
		}
		// Same answer whether the account exists or not.
		Forms::back( array(), __( 'If an account matches, a reset link has been sent to its e-mail address.', 'sm-core' ) );
	}

	public static function log_failure( $username ) {
		AuditLog::add( 'auth.failed', 'user', 0, array( 'identifier' => sanitize_text_field( $username ) ) );
	}

	public static function logout_redirect( $redirect_to, $requested, $user ) {
		$lang = $user instanceof \WP_User ? Language::user_language( $user->ID ) : Language::default_language();
		return Language::url_for( home_url( '/' ), $lang );
	}

	/** Platform members use the front-end dashboard, not wp-admin. */
	public static function keep_members_out_of_admin() {
		if ( wp_doing_ajax() || ! is_user_logged_in() ) {
			return;
		}
		if ( current_user_can( 'edit_posts' ) || current_user_can( 'sm_moderate' ) ) {
			return;
		}
		$page = isset( $GLOBALS['pagenow'] ) ? $GLOBALS['pagenow'] : '';
		if ( in_array( $page, array( 'admin-post.php', 'async-upload.php' ), true ) ) {
			return;
		}
		wp_safe_redirect( Routes::url( 'account' ) );
		exit;
	}

	public static function admin_bar( $show ) {
		return ( current_user_can( 'edit_posts' ) || current_user_can( 'sm_moderate' ) ) ? $show : false;
	}
}

<?php
/**
 * E-mail check of a partner engineer application (0.4.0), before the account
 * exists: a 6-digit code is sent to the address given in the form; the account
 * is created only once the code is confirmed.
 *
 *   admin-ajax.php?action=sm_eng_code   send (or send again) the code
 *   admin-ajax.php?action=sm_eng_check  check the code typed by the visitor
 *
 * The state is kept 30 minutes in a transient named after the address (code
 * hashed, attempts, time sent, confirmed). Never stored in clear.
 *
 * @package SM\Engineering
 */

namespace SM\Engineering;

use SM\Core\Support\Forms;

defined( 'ABSPATH' ) || exit;

final class EmailCode {

	const TTL    = 1800;  // The state is kept 30 minutes.
	const CODE   = 600;   // A code is valid 10 minutes.
	const RESEND = 45;    // Seconds before a new code.
	const TRIES  = 5;     // Wrong codes before a new one is needed.
	const HOURLY = 8;     // Codes per hour from one connection.

	public static function init() {
		foreach ( array( 'sm_eng_code' => 'ajax_send', 'sm_eng_check' => 'ajax_check' ) as $action => $method ) {
			add_action( 'wp_ajax_nopriv_' . $action, array( __CLASS__, $method ) );
			add_action( 'wp_ajax_' . $action, array( __CLASS__, $method ) );
		}
	}

	private static function key( $email ) {
		return 'sme_code_' . md5( strtolower( trim( (string) $email ) ) );
	}

	private static function hash( $code ) {
		return hash_hmac( 'sha256', (string) $code, wp_salt( 'nonce' ) );
	}

	private static function state( $email ) {
		$s = get_transient( self::key( $email ) );
		return is_array( $s ) ? $s : array();
	}

	/**
	 * Send a code to the address.
	 *
	 * @return true|\WP_Error
	 */
	public static function send( $email ) {
		$email = sanitize_email( (string) $email );
		if ( ! is_email( $email ) ) {
			return new \WP_Error( 'email', __( 'Enter a valid e-mail address.', 'sm-engineering' ) );
		}
		if ( email_exists( $email ) ) {
			return new \WP_Error( 'email', __( 'An account already exists with this e-mail. Sign in or reset your password.', 'sm-engineering' ) );
		}
		$s = self::state( $email );
		if ( ! empty( $s['sent_at'] ) && time() - (int) $s['sent_at'] < self::RESEND ) {
			/* translators: %d: seconds. */
			return new \WP_Error( 'wait', sprintf( __( 'Wait %d seconds before asking for a new code.', 'sm-engineering' ), self::RESEND - ( time() - (int) $s['sent_at'] ) ) );
		}
		$code = (string) random_int( 100000, 999999 );
		$s    = array(
			'hash'     => self::hash( $code ),
			'expires'  => time() + self::CODE,
			'attempts' => 0,
			'sent_at'  => time(),
			'ok'       => 0,
		);
		// Development helper: tests read the code in a "local" environment only.
		if ( 'local' === wp_get_environment_type() ) {
			$s['dev'] = $code;
		}
		set_transient( self::key( $email ), $s, self::TTL );
		$brand = function_exists( 'sm_brand_name' ) ? sm_brand_name() : get_bloginfo( 'name' );
		$lang  = sm_current_language();
		\SM\Core\Mail\Mailer::send(
			$email,
			$lang,
			/* translators: %s: platform name. */
			sprintf( __( 'Your %s verification code', 'sm-engineering' ), $brand ),
			sprintf(
				/* translators: 1: 6-digit code. */
				__( 'Here is the code to confirm your e-mail address and send your partner engineer application: %1$s. It expires in 10 minutes. If you did not ask for it, ignore this message.', 'sm-engineering' ),
				'<strong style="font-size:22px;letter-spacing:4px">' . $code . '</strong>'
			)
		);
		return true;
	}

	/**
	 * Check a code. A confirmed address stays confirmed for 30 minutes.
	 *
	 * @return true|\WP_Error
	 */
	public static function check( $email, $code ) {
		$s = self::state( $email );
		if ( ! $s ) {
			return new \WP_Error( 'code', __( 'Ask for a new code.', 'sm-engineering' ) );
		}
		if ( ! empty( $s['ok'] ) ) {
			return true;
		}
		if ( time() > (int) $s['expires'] ) {
			return new \WP_Error( 'code', __( 'This code has expired. Ask for a new one.', 'sm-engineering' ) );
		}
		if ( (int) $s['attempts'] >= self::TRIES ) {
			return new \WP_Error( 'code', __( 'Too many wrong codes. Ask for a new one.', 'sm-engineering' ) );
		}
		$code = preg_replace( '/\D/', '', (string) $code );
		if ( ! hash_equals( (string) $s['hash'], self::hash( $code ) ) ) {
			$s['attempts'] = (int) $s['attempts'] + 1;
			set_transient( self::key( $email ), $s, self::TTL );
			return new \WP_Error( 'code', __( 'Wrong code. Check it and try again.', 'sm-engineering' ) );
		}
		$s['ok'] = 1;
		unset( $s['dev'] );
		set_transient( self::key( $email ), $s, self::TTL );
		return true;
	}

	/** The address was confirmed with a code (and the application not yet sent). */
	public static function confirmed( $email ) {
		return ! empty( self::state( $email )['ok'] );
	}

	/** The application is sent: the confirmation is used. */
	public static function forget( $email ) {
		delete_transient( self::key( $email ) );
	}

	private static function guard() {
		$nonce = isset( $_POST['_sm_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_sm_nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! wp_verify_nonce( $nonce, 'sm_engineer_apply' ) ) {
			wp_send_json_error( array( 'message' => __( 'The page stayed open too long: reload it.', 'sm-engineering' ) ), 403 );
		}
		if ( isset( $_POST['sm_lang'] ) && class_exists( '\\SM\\Core\\I18n\\Language' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			\SM\Core\I18n\Language::force( sanitize_key( wp_unslash( $_POST['sm_lang'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}
		return sanitize_email( wp_unslash( (string) ( $_POST['email'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	public static function ajax_send() {
		$email = self::guard();
		if ( ! Forms::rate_limit( 'eng_code', self::HOURLY, HOUR_IN_SECONDS ) ) {
			wp_send_json_error( array( 'message' => __( 'Too many codes asked from this connection. Try again in an hour.', 'sm-engineering' ) ), 429 );
		}
		$r = self::send( $email );
		if ( is_wp_error( $r ) ) {
			wp_send_json_error( array( 'message' => $r->get_error_message(), 'field' => 'email' === $r->get_error_code() ? 'email' : '' ) );
		}
		wp_send_json_success( array( 'wait' => self::RESEND ) );
	}

	public static function ajax_check() {
		$email = self::guard();
		$r     = self::check( $email, wp_unslash( (string) ( $_POST['code'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( is_wp_error( $r ) ) {
			wp_send_json_error( array( 'message' => $r->get_error_message() ) );
		}
		wp_send_json_success();
	}
}

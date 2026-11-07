<?php
/**
 * Contact verification (level 1) with a 6-digit code.
 *
 * Channel: SMS when an SMS gateway is plugged in (filter sm_sms_provider or a
 * future sm-sms plugin), otherwise e-mail. Codes are hashed, expire after 10
 * minutes, allow 5 attempts, and can be re-sent after 60 seconds (5 per hour).
 *
 * @package SM\Core
 */

namespace SM\Core\Accounts;

use SM\Core\Audit\AuditLog;
use SM\Core\Notifications\Notifications;
use SM\Core\Routing\Routes;
use SM\Core\Support\Forms;

defined( 'ABSPATH' ) || exit;

final class PhoneVerification {

	const META   = 'sm_otp';
	const TTL    = 600;
	const TRIES  = 5;
	const RESEND = 60;

	public static function init() {
		add_action( 'admin_post_sm_verify_code', array( __CLASS__, 'handle_verify' ) );
		add_action( 'admin_post_sm_resend_code', array( __CLASS__, 'handle_resend' ) );
	}

	/** Active SMS provider object or null. */
	public static function sms_provider() {
		$provider = apply_filters( 'sm_sms_provider', null );
		return ( is_object( $provider ) && method_exists( $provider, 'send' ) ) ? $provider : null;
	}

	/** "sms" or "email". */
	public static function channel() {
		return self::sms_provider() ? 'sms' : 'email';
	}

	private static function hash( $code ) {
		return hash_hmac( 'sha256', (string) $code, wp_salt( 'nonce' ) );
	}

	/** @return true|\WP_Error */
	public static function send_code( $user_id ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return new \WP_Error( 'sm_no_user', 'No user' );
		}
		$state = (array) get_user_meta( $user_id, self::META, true );
		if ( ! empty( $state['sent_at'] ) && time() - (int) $state['sent_at'] < self::RESEND ) {
			return new \WP_Error( 'sm_wait', __( 'Wait one minute before asking for a new code.', 'sm-core' ) );
		}
		$code    = (string) random_int( 100000, 999999 );
		$channel = self::channel();
		update_user_meta(
			$user_id,
			self::META,
			array(
				'hash'     => self::hash( $code ),
				'expires'  => time() + self::TTL,
				'attempts' => 0,
				'sent_at'  => time(),
				'channel'  => $channel,
			)
		);

		$lang = \SM\Core\I18n\Language::user_language( $user_id );
		if ( 'sms' === $channel ) {
			$phone = get_user_meta( $user_id, 'sm_phone', true );
			$text  = \SM\Core\I18n\Language::with_language(
				$lang,
				static fn() => sprintf( /* translators: %s: 6-digit code. */ __( 'Steel Marketplace: your verification code is %s. It expires in 10 minutes.', 'sm-core' ), $code )
			);
			$result = self::sms_provider()->send( $phone, $text );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		} else {
			Notifications::email(
				$user_id,
				static fn() => __( 'Your verification code', 'sm-core' ),
				static fn() => sprintf(
					/* translators: %s: 6-digit code. */
					__( 'Your Steel Marketplace verification code is %s. It expires in 10 minutes. If you did not create an account, ignore this message.', 'sm-core' ),
					'<strong style="font-size:22px;letter-spacing:4px">' . $code . '</strong>'
				)
			);
		}

		/**
		 * Development helper: the code is exposed to tests in a "local" environment only.
		 */
		if ( 'local' === wp_get_environment_type() ) {
			update_user_meta( $user_id, 'sm_otp_dev', $code );
		}
		return true;
	}

	/** Translated "code sent to …" message. */
	public static function sent_message( $user_id ) {
		if ( 'sms' === self::channel() ) {
			$phone = (string) get_user_meta( $user_id, 'sm_phone', true );
			/* translators: %s: masked phone number. */
			return sprintf( __( 'A 6-digit code was sent by SMS to %s.', 'sm-core' ), substr( $phone, 0, 4 ) . '••••' . substr( $phone, -2 ) );
		}
		$email = get_userdata( $user_id )->user_email;
		$at    = strpos( $email, '@' );
		/* translators: %s: masked e-mail address. */
		return sprintf( __( 'A 6-digit code was sent to %s.', 'sm-core' ), substr( $email, 0, 2 ) . '•••' . substr( $email, $at ) );
	}

	/** @return true|\WP_Error */
	public static function verify( $user_id, $code ) {
		$state = get_user_meta( $user_id, self::META, true );
		if ( ! is_array( $state ) || empty( $state['hash'] ) ) {
			return new \WP_Error( 'sm_no_code', __( 'Ask for a new code.', 'sm-core' ) );
		}
		if ( time() > (int) $state['expires'] ) {
			return new \WP_Error( 'sm_expired', __( 'This code has expired. Ask for a new one.', 'sm-core' ) );
		}
		if ( (int) $state['attempts'] >= self::TRIES ) {
			return new \WP_Error( 'sm_tries', __( 'Too many wrong codes. Ask for a new one.', 'sm-core' ) );
		}
		$code = preg_replace( '/\D/', '', (string) $code );
		if ( ! hash_equals( $state['hash'], self::hash( $code ) ) ) {
			$state['attempts'] = (int) $state['attempts'] + 1;
			update_user_meta( $user_id, self::META, $state );
			return new \WP_Error( 'sm_wrong', __( 'Wrong code. Check it and try again.', 'sm-core' ) );
		}

		delete_user_meta( $user_id, self::META );
		delete_user_meta( $user_id, 'sm_otp_dev' );
		update_user_meta( $user_id, 'sm_contact_verified', $state['channel'] );
		update_user_meta( $user_id, 'sm_contact_verified_at', time() );

		$company = Companies::for_user( $user_id );
		if ( $company ) {
			Companies::raise_level( $company->id, 1 );
			AuditLog::add( 'verification.contact', 'company', $company->id, array( 'channel' => $state['channel'] ), $user_id, $company->id );
		}
		return true;
	}

	public static function handle_verify() {
		Forms::begin( 'verify_code' );
		$result = self::verify( get_current_user_id(), Forms::post( 'code' ) );
		if ( is_wp_error( $result ) ) {
			Forms::back( array( 'code' => $result->get_error_message() ) );
		}
		$company = Companies::for_user( get_current_user_id() );
		$message = ( $company && 'engineering' === $company->account_class )
			? __( 'Your account is verified. You can now publish your services.', 'sm-core' )
			: __( 'Your account is verified. You can now publish.', 'sm-core' );
		Forms::back( array(), $message, Routes::url( 'account' ) );
	}

	public static function handle_resend() {
		Forms::begin( 'resend_code' );
		if ( ! Forms::rate_limit( 'otp_resend|' . get_current_user_id(), 5, HOUR_IN_SECONDS ) ) {
			Forms::back( array( __( 'Too many codes requested. Try again in an hour.', 'sm-core' ) ) );
		}
		$sent = self::send_code( get_current_user_id() );
		if ( is_wp_error( $sent ) ) {
			Forms::back( array( $sent->get_error_message() ) );
		}
		Forms::back( array(), self::sent_message( get_current_user_id() ) );
	}
}

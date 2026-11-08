<?php
/**
 * Contact form of the platform (form "contact", action sm_contact):
 * name, e-mail, phone, subject, message. The message is kept in the
 * activity journal ("contact.sent"), which alerts the administrators
 * (Reply-To = the visitor) and confirms receipt to the visitor.
 *
 * @package SM\Core
 */

namespace SM\Core\Mail;

use SM\Core\Audit\AuditLog;
use SM\Core\I18n\Language;
use SM\Core\Support\Forms;

defined( 'ABSPATH' ) || exit;

final class Contact {

	const PER_HOUR = 5;

	public static function init() {
		add_action( 'admin_post_nopriv_sm_contact', array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_sm_contact', array( __CLASS__, 'handle' ) );
	}

	/** Validate. Returns [errors, data]. */
	public static function validate( array $in ) {
		$e    = array();
		$name = mb_substr( trim( sanitize_text_field( (string) ( $in['name'] ?? '' ) ) ), 0, 120 );
		$mail = sanitize_email( (string) ( $in['email'] ?? '' ) );
		$tel  = mb_substr( trim( sanitize_text_field( (string) ( $in['phone'] ?? '' ) ) ), 0, 32 );
		$subj = mb_substr( trim( sanitize_text_field( (string) ( $in['subject'] ?? '' ) ) ), 0, 160 );
		$msg  = trim( sanitize_textarea_field( (string) ( $in['message'] ?? '' ) ) );
		if ( mb_strlen( $name ) < 2 ) {
			$e['name'] = __( 'Give your name.', 'sm-core' );
		}
		if ( ! is_email( $mail ) ) {
			$e['email'] = __( 'Give a valid e-mail address so we can answer you.', 'sm-core' );
		}
		if ( '' !== $tel && ! preg_match( '/^[0-9 +().-]{8,20}$/', $tel ) ) {
			$e['phone'] = __( 'Check the phone number.', 'sm-core' );
		}
		if ( mb_strlen( $subj ) < 3 ) {
			$e['subject'] = __( 'Give a subject.', 'sm-core' );
		}
		if ( mb_strlen( $msg ) < 10 ) {
			$e['message'] = __( 'Write your message (10 characters at least).', 'sm-core' );
		} elseif ( mb_strlen( $msg ) > 4000 ) {
			$e['message'] = __( 'The message is too long (4000 characters at most).', 'sm-core' );
		}
		return array( $e, array( 'name' => $name, 'email' => $mail, 'phone' => $tel, 'subject' => $subj, 'message' => $msg ) );
	}

	public static function handle() {
		Forms::begin( 'contact' );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in Forms::begin().
		list( $errors, $data ) = self::validate( (array) wp_unslash( $_POST ) );
		if ( $errors ) {
			Forms::back( $errors );
		}
		if ( ! Forms::rate_limit( 'contact', self::PER_HOUR, HOUR_IN_SECONDS ) ) {
			Forms::back( array( __( 'You sent several messages in a short time. Try again in an hour.', 'sm-core' ) ) );
		}
		AuditLog::add( 'contact.sent', 'contact', 0, $data + array( 'lang' => Language::current() ) );
		Forms::back( array(), __( 'Thank you, your message is sent. We answer you by e-mail as soon as possible.', 'sm-core' ) );
	}
}

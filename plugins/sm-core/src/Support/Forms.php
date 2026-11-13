<?php
/**
 * Front-end form plumbing shared by every sm-* handler.
 *
 * Forms post to admin-post.php with:
 *   action=sm_{form}, _sm_nonce, sm_lang (page language), redirect_to, a honeypot field.
 * After processing, the handler redirects back with ?sm_n={token}; the token points
 * to a short-lived transient holding the messages and the submitted values, so the
 * theme can show errors next to the form and refill it (sm_theme_notices / sm_form_old).
 *
 * @package SM\Core
 */

namespace SM\Core\Support;

use SM\Core\I18n\Language;

defined( 'ABSPATH' ) || exit;

final class Forms {

	const HONEYPOT = 'sm_website';

	/** URL the theme puts in <form action>. */
	public static function action_url() {
		return admin_url( 'admin-post.php' );
	}

	/**
	 * Fresh security code of a form (0.8.2): admin-ajax.php?action=sm_nonce&form=<form>.
	 * The theme asks for it just before sending a form, so a page served by a page
	 * cache (LiteSpeed, CDN) or left open for hours is not refused as "expired".
	 */
	public static function init() {
		add_action( 'wp_ajax_sm_nonce', array( __CLASS__, 'ajax_nonce' ) );
		add_action( 'wp_ajax_nopriv_sm_nonce', array( __CLASS__, 'ajax_nonce' ) );
	}

	public static function ajax_nonce() {
		$form = sanitize_key( (string) ( $_GET['form'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$form = preg_replace( '/^sm_/', '', $form );
		if ( '' === $form ) {
			wp_send_json_error( null, 400 );
		}
		nocache_headers();
		wp_send_json_success( array( 'nonce' => wp_create_nonce( 'sm_' . $form ) ) );
	}

	/** Pages with a form are never kept by a page cache (LiteSpeed Cache, WP Super Cache, W3TC). */
	private static function no_page_cache() {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		do_action( 'litespeed_control_set_nocache', 'sm: page with a form' );
		if ( ! headers_sent() ) {
			nocache_headers();
		}
	}

	/** Hidden fields for a form. */
	public static function fields( $form, $redirect = '' ) {
		self::no_page_cache();
		$redirect = $redirect ? $redirect : self::current_url();
		printf( '<input type="hidden" name="action" value="%s">', esc_attr( 'sm_' . $form ) );
		// Time the page was made: the theme renews the security code of an old page (cache, open tab).
		printf( '<input type="hidden" name="sm_ts" value="%d">', time() );
		printf( '<input type="hidden" name="sm_lang" value="%s">', esc_attr( Language::current() ) );
		printf( '<input type="hidden" name="redirect_to" value="%s">', esc_url( remove_query_arg( array( 'sm_n' ), $redirect ) ) );
		wp_nonce_field( 'sm_' . $form, '_sm_nonce', false );
		// Honeypot: invisible to people, often filled by bots.
		printf(
			'<div class="sm-hp" aria-hidden="true" style="position:absolute;inset-inline-start:-9999px;inline-size:1px;block-size:1px;overflow:hidden"><label>Website<input type="text" name="%s" value="" tabindex="-1" autocomplete="off"></label></div>',
			esc_attr( self::HONEYPOT )
		);
	}

	public static function current_url() {
		return Language::url_for( Language::current_unprefixed_url(), Language::current() );
	}

	/**
	 * Start processing a POST: language, nonce, honeypot.
	 * Returns false (and redirects) when the request must be rejected.
	 */
	public static function begin( $form ) {
		$lang = isset( $_POST['sm_lang'] ) ? sanitize_key( wp_unslash( $_POST['sm_lang'] ) ) : Language::default_language(); // phpcs:ignore WordPress.Security.NonceVerification
		Language::force( $lang );

		// Files heavier than the server accepts (post_max_size): PHP drops the whole form.
		$length = isset( $_SERVER['CONTENT_LENGTH'] ) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
		$max    = wp_convert_hr_to_bytes( (string) ini_get( 'post_max_size' ) );
		if ( empty( $_POST ) && $max > 0 && $length > $max ) { // phpcs:ignore WordPress.Security.NonceVerification
			/* translators: %s: maximum size, e.g. 8 MB. */
			self::back( array( sprintf( __( 'The files sent are too heavy: %s maximum in total. Send lighter files (PDF, or compressed photos) and try again.', 'sm-core' ), size_format( $max ) ) ) );
		}
		$nonce = isset( $_POST['_sm_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_sm_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'sm_' . $form ) ) {
			self::back( array( __( 'The form expired. Please try again.', 'sm-core' ) ) );
		}
		if ( ! empty( $_POST[ self::HONEYPOT ] ) ) {
			// Pretend success to bots, do nothing.
			self::back( array(), __( 'Thank you.', 'sm-core' ) );
		}
		return true;
	}

	/** Sanitized POST value. */
	public static function post( $key, $default = '' ) {
		// phpcs:ignore WordPress.Security.NonceVerification -- verified in begin().
		if ( ! isset( $_POST[ $key ] ) ) {
			return $default;
		}
		$value = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
		if ( is_array( $value ) ) {
			return array_map( 'sanitize_text_field', $value );
		}
		return sanitize_text_field( $value );
	}

	public static function post_textarea( $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification
		return isset( $_POST[ $key ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) : '';
	}

	/** Where to go back to (validated, same site). */
	public static function redirect_target( $fallback = '' ) {
		// phpcs:ignore WordPress.Security.NonceVerification
		$target = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';
		if ( ! $target ) {
			$target = $fallback ? $fallback : wp_get_referer();
		}
		return wp_validate_redirect( $target, home_url( '/' ) );
	}

	/**
	 * Redirect back to the form with errors (array field => message or list) or a success message.
	 *
	 * @param array  $errors  Messages; string keys name the field in error.
	 * @param string $success Success message.
	 * @param string $target  Override redirect URL.
	 */
	public static function back( array $errors = array(), $success = '', $target = '' ) {
		$old = array();
		// phpcs:ignore WordPress.Security.NonceVerification
		foreach ( (array) $_POST as $k => $v ) {
			if ( in_array( $k, array( 'password', 'pwd', 'password2', '_sm_nonce', 'action', self::HONEYPOT, 'code' ), true ) ) {
				continue;
			}
			$old[ $k ] = is_array( $v ) ? map_deep( wp_unslash( $v ), 'sanitize_text_field' ) : sanitize_text_field( wp_unslash( $v ) ); // Nested rows (lines[0][qty]) kept.
		}
		$token = wp_generate_password( 20, false );
		set_transient(
			'sm_n_' . $token,
			array(
				'errors'  => $errors,
				'success' => $success,
				'old'     => $errors ? $old : array(),
			),
			10 * MINUTE_IN_SECONDS
		);
		$target = $target ? $target : self::redirect_target();
		wp_safe_redirect( add_query_arg( 'sm_n', $token, $target ) );
		exit;
	}

	/**
	 * Open a form already filled with values (e.g. suggestions of the AI assistant):
	 * the theme reads them like values sent back after an error, and shows $message.
	 * Values are sanitized the same way; nothing is saved until the user submits.
	 */
	public static function prefill( array $values, $target, $message = '' ) {
		$token = wp_generate_password( 20, false );
		set_transient(
			'sm_n_' . $token,
			array(
				'errors'  => array(),
				'success' => (string) $message,
				'old'     => map_deep( $values, static fn( $v ) => is_scalar( $v ) ? sanitize_textarea_field( (string) $v ) : '' ),
				'prefill' => true,
			),
			30 * MINUTE_IN_SECONDS
		);
		wp_safe_redirect( add_query_arg( 'sm_n', $token, $target ) );
		exit;
	}

	/** Messages for the current page request (theme side). */
	public static function current_notice() {
		static $notice = null;
		if ( null !== $notice ) {
			return $notice;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$token  = isset( $_GET['sm_n'] ) ? preg_replace( '/[^A-Za-z0-9]/', '', wp_unslash( $_GET['sm_n'] ) ) : '';
		$notice = $token ? get_transient( 'sm_n_' . $token ) : false;
		$notice = is_array( $notice ) ? $notice : array( 'errors' => array(), 'success' => '', 'old' => array() );
		return $notice;
	}

	/** Client IP (REMOTE_ADDR; proxies are not trusted). */
	public static function ip() {
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
	}

	/**
	 * Fixed-window rate limit. Returns true when the action is allowed (and counts it).
	 */
	public static function rate_limit( $key, $max, $window ) {
		$id    = 'sm_rl_' . md5( $key . '|' . self::ip() );
		$count = (int) get_transient( $id );
		if ( $count >= $max ) {
			return false;
		}
		set_transient( $id, $count + 1, $window );
		return true;
	}

	public static function reset_rate_limit( $key ) {
		delete_transient( 'sm_rl_' . md5( $key . '|' . self::ip() ) );
	}

	/** Normalize an Algerian mobile number to 0XXXXXXXXX; '' when invalid. */
	public static function normalize_mobile( $phone ) {
		$digits = preg_replace( '/\D+/', '', (string) $phone );
		if ( 0 === strpos( $digits, '00213' ) ) {
			$digits = '0' . substr( $digits, 5 );
		} elseif ( 0 === strpos( $digits, '213' ) && 12 === strlen( $digits ) ) {
			$digits = '0' . substr( $digits, 3 );
		}
		return preg_match( '/^0[567]\d{8}$/', $digits ) ? $digits : '';
	}
}

<?php
/**
 * Sends the platform's e-mails with one layout (logo bar, greeting, body,
 * footer; right-to-left in Arabic), the sender chosen in the settings, and
 * records every e-mail in the e-mail log (sm_mail_log) with its status.
 *
 * Delivery itself is WordPress' wp_mail(): with WP Mail SMTP the e-mails
 * leave through the authenticated SMTP account of the domain.
 *
 * @package SM\Core
 */

namespace SM\Core\Mail;

use SM\Core\I18n\Language;

defined( 'ABSPATH' ) || exit;

final class Mailer {

	private static $last_error = '';

	public static function table() {
		return sm_table( 'mail_log' );
	}

	/**
	 * Send one e-mail.
	 *
	 * @param string $to        Address.
	 * @param string $lang      fr|ar (layout direction and fixed texts).
	 * @param string $subject   Plain text.
	 * @param string $body_html Safe HTML of the body.
	 * @param array  $args      event (log key), name (greeting), reply_to, footer (text), resend_of.
	 * @return bool
	 */
	public static function send( $to, $lang, $subject, $body_html, array $args = array() ) {
		if ( ! is_email( $to ) ) {
			return false;
		}
		$args = wp_parse_args( $args, array( 'event' => '', 'name' => '', 'reply_to' => '', 'footer' => null ) );
		$html = Language::with_language( $lang, static fn() => self::layout( $lang, $body_html, $args['name'], $args['footer'] ) );
		return self::deliver( $to, $subject, $html, $args['event'], $args['reply_to'] );
	}

	/** Send an already laid-out message (used for re-sending from the log). */
	public static function deliver( $to, $subject, $html, $event = '', $reply_to = '' ) {
		$s       = Settings::all();
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$from    = sanitize_email( $s['from_email'] );
		if ( is_email( $from ) ) {
			$headers[] = 'From: ' . self::header_name( $s['from_name'] ) . ' <' . $from . '>';
		}
		if ( is_email( $reply_to ) ) {
			$headers[] = 'Reply-To: ' . $reply_to;
		}
		self::$last_error = '';
		add_action( 'wp_mail_failed', array( __CLASS__, 'capture_error' ) );
		$ok = (bool) wp_mail( $to, $subject, $html, $headers );
		remove_action( 'wp_mail_failed', array( __CLASS__, 'capture_error' ) );
		self::log( $event, $to, $subject, $html, $ok, self::$last_error, $reply_to );
		return $ok;
	}

	public static function capture_error( $error ) {
		self::$last_error = is_wp_error( $error ) ? $error->get_error_message() : 'error';
	}

	private static function header_name( $name ) {
		$name = trim( str_replace( array( '"', "\r", "\n", '<', '>' ), '', (string) $name ) );
		return '"' . ( '' !== $name ? $name : sm_brand_name() ) . '"';
	}

	/** The common layout. Call inside the recipient's language. */
	public static function layout( $lang, $body_html, $name = '', $footer = null ) {
		$dir    = Language::dir( $lang );
		$align  = 'rtl' === $dir ? 'right' : 'left';
		$footer = null === $footer ? __( 'You receive this e-mail because of your activity on the platform.', 'sm-core' ) : $footer;
		$hello  = '' !== (string) $name ? '<p style="margin:0 0 14px">' . esc_html( sprintf( /* translators: %s: person name. */ __( 'Hello %s,', 'sm-core' ), $name ) ) . '</p>' : '';
		return '<!doctype html><html lang="' . esc_attr( $lang ) . '" dir="' . esc_attr( $dir ) . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head>'
			. '<body style="margin:0;background:#F1F5F9;font-family:Arial,Tahoma,sans-serif;color:#0f172a">'
			. '<div dir="' . esc_attr( $dir ) . '" style="max-width:560px;margin:24px auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;text-align:' . $align . '">'
			. Branding::header_html( $lang )
			. '<div style="padding:24px;font-size:15px;line-height:1.6">' . $hello . $body_html . '</div>'
			. Branding::signature_html( $lang )
			. '<div style="padding:12px 24px;font-size:12px;color:#64748b;border-top:1px solid #e2e8f0">' . esc_html( $footer ) . ' · <a href="' . esc_url( home_url( '/' ) ) . '" style="color:#64748b">' . esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ) . '</a></div>'
			. '</div></body></html>';
	}

	/* ---- E-mail log ---------------------------------------------------- */

	private static function log( $event, $to, $subject, $html, $ok, $error, $reply_to ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert(
			self::table(),
			array(
				'event'      => substr( (string) $event, 0, 64 ),
				'recipient'  => substr( $to, 0, 190 ),
				'reply_to'   => substr( (string) $reply_to, 0, 190 ),
				'subject'    => mb_substr( $subject, 0, 255 ),
				'body'       => Settings::get( 'log_bodies' ) && 'otp' !== $event ? ( 'account.registered:user' === $event ? preg_replace( '/>\s*\d{6}\s*</', '>••••••<', $html ) : $html ) : '', // Verification codes are never kept.
				'status'     => $ok ? 'sent' : 'failed',
				'error'      => mb_substr( (string) $error, 0, 255 ),
				'created_at' => current_time( 'mysql', true ),
			)
		);
	}

	public static function query( array $args = array() ) {
		global $wpdb;
		$args  = wp_parse_args( $args, array( 'status' => '', 'q' => '', 'per_page' => 50, 'page' => 1 ) );
		$where = '1=1';
		$vals  = array();
		if ( in_array( $args['status'], array( 'sent', 'failed' ), true ) ) {
			$where .= ' AND status = %s';
			$vals[] = $args['status'];
		}
		if ( '' !== $args['q'] ) {
			$where .= ' AND (recipient LIKE %s OR subject LIKE %s)';
			$like   = '%' . $wpdb->esc_like( $args['q'] ) . '%';
			$vals[] = $like;
			$vals[] = $like;
		}
		$total  = (int) $wpdb->get_var( $vals ? $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . " WHERE $where", $vals ) : 'SELECT COUNT(*) FROM ' . self::table() ); // phpcs:ignore WordPress.DB
		$vals[] = (int) $args['per_page'];
		$vals[] = ( max( 1, (int) $args['page'] ) - 1 ) * (int) $args['per_page'];
		$rows   = $wpdb->get_results( $wpdb->prepare( 'SELECT id, event, recipient, reply_to, subject, status, error, created_at FROM ' . self::table() . " WHERE $where ORDER BY id DESC LIMIT %d OFFSET %d", $vals ) ); // phpcs:ignore WordPress.DB
		return array( 'total' => $total, 'items' => $rows );
	}

	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	public static function resend( $id ) {
		$row = self::get( $id );
		if ( ! $row || '' === (string) $row->body ) {
			return false;
		}
		return self::deliver( $row->recipient, $row->subject, $row->body, $row->event, $row->reply_to );
	}

	public static function purge() {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::table() . ' WHERE created_at < %s', gmdate( 'Y-m-d H:i:s', time() - Settings::retention_days() * DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}
}

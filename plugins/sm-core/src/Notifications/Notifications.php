<?php
/**
 * Notifications: stored in sm_notifications (dashboard) and sent by e-mail,
 * always rendered in the recipient's language.
 *
 * Other plugins add types with the sm_notification_types filter:
 *   'rfq.offer_received' => array( 'subject' => fn( $p ) => …, 'body' => fn( $p ) => …, 'email' => true )
 *
 * @package SM\Core
 */

namespace SM\Core\Notifications;

use SM\Core\Accounts\Companies;
use SM\Core\I18n\Language;
use SM\Core\Routing\Routes;

defined( 'ABSPATH' ) || exit;

final class Notifications {

	public static function init() {
		add_action( 'admin_post_sm_notifications_read', array( __CLASS__, 'handle_mark_all_read' ) );
	}

	/** Type definitions: subject/body callables receive the payload; called inside the recipient's language. */
	public static function types() {
		$types = array(
			'enquiry.received'       => array(
				'email'   => true,
				'subject' => static fn( $p ) => __( 'New enquiry on Steel Marketplace', 'sm-core' ),
				'body'    => static fn( $p ) => sprintf(
					/* translators: 1: sender name, 2: subject of the enquiry. */
					__( '%1$s sent you an enquiry: %2$s', 'sm-core' ),
					$p['name'] ?? '',
					$p['subject'] ?? ''
				),
				'link'    => static fn( $p ) => Routes::url( 'account', array( 'view' => 'messages' ) ),
			),
			'verification.submitted' => array(
				'email'   => true,
				'subject' => static fn( $p ) => __( 'New verification request to review', 'sm-core' ),
				'body'    => static fn( $p ) => sprintf(
					/* translators: %s: company name. */
					__( '%s submitted documents for verification.', 'sm-core' ),
					Companies::get( $p['company_id'] ?? 0 )->name ?? ''
				),
				'link'    => static fn( $p ) => admin_url( 'admin.php?page=sm-verifications' ),
			),
			'verification.approved'  => array(
				'email'   => true,
				'subject' => static fn( $p ) => __( 'Your account is verified', 'sm-core' ),
				'body'    => static fn( $p ) => __( 'Good news: the platform team verified your documents. The verified badge now appears on your company page and listings.', 'sm-core' ),
				'link'    => static fn( $p ) => Routes::url( 'account', array( 'view' => 'verification' ) ),
			),
			'verification.rejected'  => array(
				'email'   => true,
				'subject' => static fn( $p ) => __( 'Your verification request needs changes', 'sm-core' ),
				'body'    => static fn( $p ) => trim( __( 'The platform team could not validate your documents.', 'sm-core' ) . ' ' . ( $p['note'] ?? '' ) ),
				'link'    => static fn( $p ) => Routes::url( 'account', array( 'view' => 'verification' ) ),
			),
		);
		return apply_filters( 'sm_notification_types', $types );
	}

	/**
	 * Notify users.
	 *
	 * @param int[]  $user_ids
	 * @param string $type
	 * @param array  $payload
	 */
	public static function notify( array $user_ids, $type, array $payload = array(), $company_id = 0 ) {
		global $wpdb;
		$types = self::types();
		$def   = $types[ $type ] ?? null;
		foreach ( array_unique( array_map( 'intval', $user_ids ) ) as $user_id ) {
			if ( ! $user_id ) {
				continue;
			}
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->insert(
				sm_table( 'notifications' ),
				array(
					'user_id'    => $user_id,
					'company_id' => (int) $company_id,
					'type'       => $type,
					'payload'    => wp_json_encode( $payload ),
					'created_at' => current_time( 'mysql', true ),
				)
			);
			if ( $def && ! empty( $def['email'] ) ) {
				self::email(
					$user_id,
					static fn() => ( $def['subject'] )( $payload ),
					static function () use ( $def, $payload ) {
						$body = esc_html( ( $def['body'] )( $payload ) );
						if ( ! empty( $def['link'] ) ) {
							$body .= '<p><a href="' . esc_url( ( $def['link'] )( $payload ) ) . '" style="display:inline-block;background:#16A36A;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none">' . esc_html__( 'Open', 'sm-core' ) . '</a></p>';
						}
						return $body;
					}
				);
			}
		}
		sm_event( 'notification.sent', array( 'type' => $type, 'users' => $user_ids ) );
	}

	public static function notify_company( $company_id, $type, array $payload = array(), array $roles = array() ) {
		self::notify( Companies::member_ids( $company_id, $roles ), $type, $payload, $company_id );
	}

	public static function notify_moderators( $type, array $payload = array() ) {
		$ids = get_users( array( 'capability' => 'sm_moderate', 'fields' => 'ID' ) );
		self::notify( array_map( 'intval', $ids ), $type, $payload );
	}

	/**
	 * Send an e-mail in the user's language. $subject and $body_html are callables
	 * evaluated after switching language, so translated strings follow the recipient.
	 */
	public static function email( $user_id, callable $subject, callable $body_html ) {
		$user = get_userdata( $user_id );
		if ( ! $user || ! is_email( $user->user_email ) ) {
			return false;
		}
		$lang = Language::user_language( $user_id );
		return Language::with_language(
			$lang,
			static function () use ( $user, $subject, $body_html, $lang ) {
				$dir     = Language::dir( $lang );
				$brand   = get_theme_mod( 'sm_brand_name', 'Steel Marketplace' );
				$primary = sanitize_hex_color( get_theme_mod( 'sm_color_primary', '#172B3A' ) ) ?: '#172B3A';
				$html    = '<!doctype html><html lang="' . esc_attr( $lang ) . '" dir="' . esc_attr( $dir ) . '"><body style="margin:0;background:#F1F5F9;font-family:Arial,Tahoma,sans-serif;color:#0f172a">'
					. '<div style="max-width:560px;margin:24px auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;text-align:' . ( 'rtl' === $dir ? 'right' : 'left' ) . '">'
					. '<div style="background:' . esc_attr( $primary ) . ';color:#fff;padding:16px 24px;font-weight:bold">' . esc_html( $brand ) . '</div>'
					. '<div style="padding:24px;font-size:15px;line-height:1.6">'
					/* translators: %s: user display name. */
					. '<p>' . esc_html( sprintf( __( 'Hello %s,', 'sm-core' ), $user->display_name ) ) . '</p>'
					. '<p>' . $body_html() . '</p>'
					. '</div><div style="padding:12px 24px;font-size:12px;color:#64748b;border-top:1px solid #e2e8f0">' . esc_html__( 'You receive this e-mail because you have an account on the platform.', 'sm-core' ) . '</div></div></body></html>';
				return wp_mail( $user->user_email, wp_strip_all_tags( $subject() ), $html, array( 'Content-Type: text/html; charset=UTF-8' ) );
			}
		);
	}

	/** Notifications of a user (newest first). */
	public static function for_user( $user_id, $limit = 20, $unread_only = false ) {
		global $wpdb;
		$sql = 'SELECT * FROM ' . sm_table( 'notifications' ) . ' WHERE user_id = %d' . ( $unread_only ? ' AND read_at IS NULL' : '' ) . ' ORDER BY id DESC LIMIT %d';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $user_id, $limit ) );
		$types = self::types();
		foreach ( $rows as $row ) {
			$payload    = json_decode( (string) $row->payload, true ) ?: array();
			$def        = $types[ $row->type ] ?? null;
			$row->title = $def ? ( $def['subject'] )( $payload ) : $row->type;
			$row->text  = $def ? ( $def['body'] )( $payload ) : '';
			$row->link  = $def && ! empty( $def['link'] ) ? ( $def['link'] )( $payload ) : '';
		}
		return $rows;
	}

	public static function unread_count( $user_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . sm_table( 'notifications' ) . ' WHERE user_id = %d AND read_at IS NULL', $user_id ) );
	}

	public static function mark_read( $user_id, $id = 0 ) {
		global $wpdb;
		$where = array( 'user_id' => $user_id );
		if ( $id ) {
			$where['id'] = $id;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $wpdb->update( sm_table( 'notifications' ), array( 'read_at' => current_time( 'mysql', true ) ), $where );
	}

	public static function handle_mark_all_read() {
		\SM\Core\Support\Forms::begin( 'notifications_read' );
		self::mark_read( get_current_user_id() );
		\SM\Core\Support\Forms::back();
	}
}

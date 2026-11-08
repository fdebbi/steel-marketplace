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
use SM\Core\Mail\Mailer;
use SM\Core\Mail\Settings as MailSettings;
use SM\Core\Mail\Templates;
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
				/* translators: %s: platform name. */
				'subject' => static fn( $p ) => sprintf( __( 'New enquiry on %s', 'sm-core' ), sm_brand_name() ),
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
			if ( $def && ! empty( $def['email'] ) && MailSettings::notice_on( $type ) ) {
				self::email_notice( $user_id, $type, $def, $payload );
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
	 * E-mail of a notification, in the recipient's language. The administrator
	 * can rewrite it in SM → E-mails (template "notice:{type}", variables
	 * {nom}, {titre}, {message}, {lien}); by default the built-in text is used.
	 */
	public static function email_notice( $user_id, $type, array $def, array $payload ) {
		$user = get_userdata( $user_id );
		if ( ! $user || ! is_email( $user->user_email ) ) {
			return false;
		}
		$lang = Language::user_language( $user_id );
		return Language::with_language(
			$lang,
			static function () use ( $user, $type, $def, $payload, $lang ) {
				$vars = array(
					'nom'     => $user->display_name,
					'titre'   => (string) ( $def['subject'] )( $payload ),
					'message' => (string) ( $def['body'] )( $payload ),
					'lien'    => ! empty( $def['link'] ) ? (string) ( $def['link'] )( $payload ) : '',
				);
				$tpl  = Templates::get( 'notice:' . $type, $lang ) ?? array( '{titre}', '{message}' );
				$body = Templates::body_html( $tpl[1], $vars );
				if ( '' !== $vars['lien'] && false === strpos( $tpl[1], '{lien}' ) ) {
					$body .= '<p><a href="' . esc_url( $vars['lien'] ) . '" style="display:inline-block;background:#16A36A;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none">' . esc_html__( 'Open', 'sm-core' ) . '</a></p>';
				}
				return Mailer::send( $user->user_email, $lang, Templates::subject( $tpl[0], $vars ), $body, array( 'event' => 'notice:' . $type, 'name' => $user->display_name, 'footer' => __( 'You receive this e-mail because you have an account on the platform.', 'sm-core' ) ) );
			}
		);
	}

	/**
	 * Send an e-mail in the user's language (used for verification codes, never
	 * kept in the e-mail log). $subject and $body_html are callables evaluated
	 * after switching language, so translated strings follow the recipient.
	 */
	public static function email( $user_id, callable $subject, callable $body_html ) {
		$user = get_userdata( $user_id );
		if ( ! $user || ! is_email( $user->user_email ) ) {
			return false;
		}
		$lang = Language::user_language( $user_id );
		return Language::with_language(
			$lang,
			static fn() => Mailer::send( $user->user_email, $lang, wp_strip_all_tags( $subject() ), '<p style="margin:0 0 14px">' . $body_html() . '</p>', array( 'event' => 'otp', 'name' => $user->display_name, 'footer' => __( 'You receive this e-mail because you have an account on the platform.', 'sm-core' ) ) )
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

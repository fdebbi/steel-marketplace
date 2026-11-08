<?php
/**
 * Turns the audit log into notifications and a readable activity journal.
 *
 * - Each audit entry is matched against the events (Events): immediate
 *   alerts to the administrators, confirmation e-mails to users.
 * - Events in "digest" mode are summed up once a day, at the chosen hour.
 * - describe() gives the sentence shown in the activity journal.
 *
 * Nothing here may break the user's action: every dispatch is guarded.
 *
 * @package SM\Core
 */

namespace SM\Core\Mail;

use SM\Core\Audit\AuditLog;
use SM\Core\I18n\Language;

defined( 'ABSPATH' ) || exit;

final class Activity {

	const DIGEST_OPTION = 'sm_mail_digest';

	public static function init() {
		add_action( 'sm_audit_logged', array( __CLASS__, 'on_logged' ) );
		add_action( 'sm_mail_hourly', array( __CLASS__, 'maybe_digest' ) );
		add_action( 'sm_core_daily', array( Mailer::class, 'purge' ) );
		if ( ! wp_next_scheduled( 'sm_mail_hourly' ) ) {
			wp_schedule_event( time() + 300, 'hourly', 'sm_mail_hourly' );
		}
	}

	/** Called for every new audit entry. */
	public static function on_logged( $entry ) {
		foreach ( Events::for_entry( $entry ) as $key => $ev ) {
			try {
				if ( 'now' === Settings::admin_mode( $key ) ) {
					self::send_admin( $key, $entry );
				}
				if ( Settings::user_on( $key ) ) {
					self::send_user( $key, $entry );
				}
			} catch ( \Throwable $t ) {
				// An e-mail problem never blocks the action itself.
				error_log( 'sm-core mail ' . $key . ': ' . $t->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
			}
		}
	}

	/** Variables of an event, computed in a language. */
	public static function vars( $key, $entry, $lang ) {
		$ev = Events::get( $key );
		return Language::with_language( $lang, static fn() => (array) call_user_func( $ev['vars'], $entry ) );
	}

	public static function admin_lang() {
		return in_array( Settings::get( 'admin_lang' ), Templates::LANGS, true ) ? Settings::get( 'admin_lang' ) : 'fr';
	}

	/** Alert to the administrators. Returns the number of e-mails sent. */
	public static function send_admin( $key, $entry, ?array $vars = null, ?array $only = null ) {
		$ev     = Events::get( $key );
		$lang   = self::admin_lang();
		$vars   = $vars ?? self::vars( $key, $entry, $lang );
		$tpl    = Templates::get( 'event:' . $key . ':admin', $lang );
		if ( ! $ev || ! $tpl ) {
			return 0;
		}
		$reply  = $ev['reply_to'] ? (string) ( $vars[ $ev['reply_to'] ] ?? '' ) : '';
		$footer = Language::with_language( $lang, static fn() => sprintf( /* translators: %s: platform name. */ __( 'Alert for the administrators of %s. Choose the alerts in SM → E-mails.', 'sm-core' ), sm_brand_name() ) );
		$sent   = 0;
		foreach ( $only ?? Settings::admin_emails() as $to ) {
			$sent += (int) Mailer::send( $to, $lang, Templates::subject( $tpl[0], $vars ), Templates::body_html( $tpl[1], $vars ), array( 'event' => $key . ':admin', 'reply_to' => $reply, 'footer' => $footer ) );
		}
		return $sent;
	}

	/** Confirmation to the user(s) of the event. */
	public static function send_user( $key, $entry ) {
		$ev = Events::get( $key );
		if ( ! $ev || empty( $ev['templates']['user'] ) ) {
			return 0;
		}
		$base = self::vars( $key, $entry, self::admin_lang() );
		$sent = 0;
		foreach ( (array) call_user_func( $ev['to'], $entry, $base ) as $r ) {
			$lang = in_array( $r['lang'] ?? '', Templates::LANGS, true ) ? $r['lang'] : 'fr';
			$vars = self::vars( $key, $entry, $lang );
			$tpl  = Templates::get( 'event:' . $key . ':user', $lang );
			$sent += (int) Mailer::send( $r['email'], $lang, Templates::subject( $tpl[0], $vars ), Templates::body_html( $tpl[1], $vars ), array( 'event' => $key . ':user', 'name' => $r['name'] ?? '' ) );
		}
		return $sent;
	}

	/* ---- Daily summary --------------------------------------------------- */

	public static function maybe_digest() {
		$hour  = (int) Settings::get( 'digest_hour' );
		$state = (array) get_option( self::DIGEST_OPTION, array() );
		$today = wp_date( 'Y-m-d' );
		if ( (int) wp_date( 'G' ) < $hour || ( $state['date'] ?? '' ) === $today ) {
			return false;
		}
		return self::send_digest();
	}

	/** Send the summary of the digest-mode events logged since the last summary. */
	public static function send_digest() {
		global $wpdb;
		$state   = (array) get_option( self::DIGEST_OPTION, array() );
		$max_id  = (int) $wpdb->get_var( 'SELECT MAX(id) FROM ' . sm_table( 'audit_log' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$last_id = isset( $state['last_id'] ) ? (int) $state['last_id'] : $max_id;
		update_option( self::DIGEST_OPTION, array( 'last_id' => $max_id, 'date' => wp_date( 'Y-m-d' ) ), false );
		$actions = array();
		foreach ( Events::all() as $key => $ev ) {
			if ( 'digest' === Settings::admin_mode( $key ) ) {
				$actions[ $ev['audit'] ] = true;
			}
		}
		if ( ! $actions || $max_id <= $last_id ) {
			return 0;
		}
		$in   = implode( ',', array_fill( 0, count( $actions ), '%s' ) );
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . sm_table( 'audit_log' ) . " WHERE id > %d AND id <= %d AND action IN ($in) ORDER BY id ASC LIMIT 500", array_merge( array( $last_id, $max_id ), array_keys( $actions ) ) ) ); // phpcs:ignore WordPress.DB
		$lang = self::admin_lang();
		$by   = array();
		foreach ( $rows as $row ) {
			$entry = AuditLog::entry( $row );
			foreach ( Events::for_entry( $entry ) as $key => $ev ) {
				if ( 'digest' !== Settings::admin_mode( $key ) ) {
					continue;
				}
				$line = Language::with_language( $lang, static fn() => self::describe( $entry ) );
				$by[ $ev['group'] ][] = wp_date( 'H:i', strtotime( $row->created_at . ' UTC' ) ) . ' — ' . $line;
			}
		}
		if ( ! $by ) {
			return 0;
		}
		return Language::with_language(
			$lang,
			static function () use ( $by, $lang ) {
				$groups = Events::groups();
				$count  = array_sum( array_map( 'count', $by ) );
				$html   = '<p style="margin:0 0 14px">' . esc_html( sprintf( /* translators: %d: number of events. */ _n( '%d event on the platform since the last summary:', '%d events on the platform since the last summary:', $count, 'sm-core' ), $count ) ) . '</p>';
				foreach ( $by as $g => $lines ) {
					$html .= '<h3 style="margin:18px 0 6px;font-size:15px">' . esc_html( $groups[ $g ] ?? $g ) . ' (' . count( $lines ) . ')</h3><ul style="margin:0;padding-inline-start:18px">';
					foreach ( $lines as $l ) {
						$html .= '<li style="margin:0 0 4px">' . esc_html( $l ) . '</li>';
					}
					$html .= '</ul>';
				}
				$html   .= '<p style="margin:18px 0 0"><a href="' . esc_url( admin_url( 'admin.php?page=sm-audit' ) ) . '" style="color:#16A36A">' . esc_html__( 'Open the activity journal', 'sm-core' ) . '</a></p>';
				/* translators: 1: platform name, 2: date. */
				$subject = sprintf( __( '%1$s — daily summary of %2$s', 'sm-core' ), sm_brand_name(), wp_date( 'Y-m-d' ) );
				$sent    = 0;
				foreach ( Settings::admin_emails() as $to ) {
					$sent += (int) Mailer::send( $to, $lang, $subject, $html, array( 'event' => 'digest:admin' ) );
				}
				return $sent;
			}
		);
	}

	/* ---- Journal ----------------------------------------------------------- */

	/** Readable sentence for an audit entry (in the current language). */
	public static function describe( $entry ) {
		foreach ( Events::for_entry( $entry ) as $key => $ev ) {
			if ( $ev['describe'] ) {
				$vars = (array) call_user_func( $ev['vars'], $entry );
				// The object may have been deleted since: fall back to the event name.
				return $vars ? (string) call_user_func( $ev['describe'], $vars ) : $ev['label'];
			}
		}
		$labels = self::labels();
		$text   = $labels[ $entry->action ] ?? '';
		if ( '' === $text ) {
			foreach ( $labels as $prefix => $label ) {
				if ( '.' === substr( $prefix, -1 ) && 0 === strpos( $entry->action, $prefix ) ) {
					$text = $label . ' (' . substr( $entry->action, strlen( $prefix ) ) . ')';
					break;
				}
			}
		}
		return '' !== $text ? $text : $entry->action;
	}

	/** Labels of audit actions that are not e-mail events. Plugins extend with sm_activity_labels. */
	public static function labels() {
		return apply_filters(
			'sm_activity_labels',
			array(
				'auth.login'                     => __( 'Signed in', 'sm-core' ),
				'auth.failed'                    => __( 'Failed sign-in', 'sm-core' ),
				'auth.password_changed'          => __( 'Password changed', 'sm-core' ),
				'auth.password_reset_requested'  => __( 'Password reset requested', 'sm-core' ),
				'company.updated'                => __( 'Company page updated', 'sm-core' ),
				'verification.approved'          => __( 'Verification approved', 'sm-core' ),
				'verification.rejected'          => __( 'Verification refused', 'sm-core' ),
				'verification.document_viewed'   => __( 'Verification document viewed', 'sm-core' ),
				'admin.company_updated'          => __( 'Company changed by an administrator', 'sm-core' ),
				'admin.rights_updated'           => __( 'Rights changed', 'sm-core' ),
				'admin.settings_updated'         => __( 'Settings changed', 'sm-core' ),
				'admin.wilayas_updated'          => __( 'Wilayas changed', 'sm-core' ),
				'admin.mail_updated'             => __( 'E-mail settings changed', 'sm-core' ),
				'admin.mail_template'            => __( 'E-mail template changed', 'sm-core' ),
			)
		);
	}

	/** Groups of the journal and the audit-action prefixes they cover. */
	public static function group_prefixes() {
		return apply_filters(
			'sm_activity_group_prefixes',
			array(
				'accounts'     => array( 'account.', 'company.' ),
				'verification' => array( 'verification.' ),
				'listings'     => array( 'product.', 'product_category.' ),
				'rfq'          => array( 'rfq.' ),
				'services'     => array( 'service.' ),
				'messages'     => array( 'enquiry.', 'contact.' ),
				'security'     => array( 'auth.' ),
				'admin'        => array( 'admin.', 'buildings.settings', 'mail.' ),
			)
		);
	}

	public static function group_of( $action ) {
		foreach ( self::group_prefixes() as $g => $prefixes ) {
			foreach ( $prefixes as $p ) {
				if ( 0 === strpos( $action, $p ) ) {
					return $g;
				}
			}
		}
		return '';
	}

	/** Link to the object of an entry (public page or admin page), or ''. */
	public static function link( $entry ) {
		return (string) apply_filters( 'sm_activity_link', '', $entry );
	}
}

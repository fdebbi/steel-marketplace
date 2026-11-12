<?php
/**
 * Administration: SM → E-mails (settings, events, user notifications,
 * template editor, e-mail log) and SM → Activity journal.
 *
 * @package SM\Core
 */

namespace SM\Core\Admin;

use SM\Core\Accounts\Companies;
use SM\Core\Audit\AuditLog;
use SM\Core\Mail\Activity;
use SM\Core\Mail\Events;
use SM\Core\Mail\Mailer;
use SM\Core\Mail\Settings;
use SM\Core\Mail\Templates;
use SM\Core\I18n\Language;
use SM\Core\Notifications\Notifications;

defined( 'ABSPATH' ) || exit;

final class MailAdmin {

	const CAP  = 'sm_manage_settings';
	const SLUG = 'sm-mail';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 20 );
		add_action( 'admin_post_sm_mail_settings', array( __CLASS__, 'save_settings' ) );
		add_action( 'admin_post_sm_mail_brand', array( __CLASS__, 'save_brand' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'media' ) );
		add_action( 'admin_post_sm_mail_events', array( __CLASS__, 'save_events' ) );
		add_action( 'admin_post_sm_mail_notices', array( __CLASS__, 'save_notices' ) );
		add_action( 'admin_post_sm_mail_template', array( __CLASS__, 'save_template' ) );
		add_action( 'admin_post_sm_mail_resend', array( __CLASS__, 'resend' ) );
		add_action( 'admin_post_sm_mail_test', array( __CLASS__, 'send_test' ) );
		add_action( 'admin_post_sm_mail_digest', array( __CLASS__, 'digest_now' ) );
		add_action( 'admin_post_sm_activity_csv', array( __CLASS__, 'export_csv' ) );
		add_action( 'admin_head', array( __CLASS__, 'styles' ) );
	}

	public static function menu() {
		add_submenu_page( 'sm-core', __( 'E-mails', 'sm-core' ), __( 'E-mails', 'sm-core' ), self::CAP, self::SLUG, array( __CLASS__, 'page' ) );
	}

	public static function styles() {
		$screen = get_current_screen();
		if ( ! $screen || ( false === strpos( $screen->id, self::SLUG ) && false === strpos( $screen->id, 'sm-audit' ) ) ) {
			return;
		}
		echo '<style>.smm-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px}.smm-card{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px}.smm-card h2{margin-top:0}.smm-lang[dir=rtl] textarea,.smm-lang[dir=rtl] input{direction:rtl}.smm-lang textarea{width:100%;min-height:280px;font-family:inherit}.smm-lang input[type=text]{width:100%}.smm-vars code{display:inline-block;margin:2px;cursor:copy}.smm-preview{width:100%;height:520px;border:1px solid #dcdcde;border-radius:8px;background:#f1f5f9}.smm-group th{background:#f6f7f7;font-weight:600}.smm-ok{color:#065f46}.smm-bad{color:#991b1b}.smm-desc{color:#50575e;font-size:12px}.smm-filters input,.smm-filters select{vertical-align:middle}.smm-pill{display:inline-block;padding:1px 8px;border-radius:999px;background:#f0f0f1;font-size:12px}.smm-pill.ok{background:#d1fae5;color:#065f46}.smm-pill.bad{background:#fee2e2;color:#991b1b}.smm-pill.edit{background:#e0e7ff;color:#3730a3}.smm-image__preview{display:flex;align-items:center;justify-content:center;min-height:90px;max-width:420px;border:1px dashed #c3c4c7;border-radius:8px;background:#f6f7f7;padding:8px}.smm-image__preview img{max-width:100%;max-height:160px;height:auto}</style>';
	}

	private static function url( array $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => self::SLUG ), $args ), admin_url( 'admin.php' ) );
	}

	private static function guard( $action ) {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You cannot change these settings.', 'sm-core' ) );
		}
		check_admin_referer( $action );
	}

	private static function back( array $args ) {
		wp_safe_redirect( self::url( $args ) );
		exit;
	}

	private static function notice() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$map = array(
			'updated' => array( 'success', __( 'Saved.', 'sm-core' ) ),
			'reset'   => array( 'success', __( 'The default template is back.', 'sm-core' ) ),
			'resent'  => array( 'success', __( 'The e-mail was sent again.', 'sm-core' ) ),
			'tested'  => array( 'success', __( 'Test e-mail sent. Check the e-mail log below if it does not arrive.', 'sm-core' ) ),
			'digest'  => array( 'success', __( 'Summary sent (when there was something to report).', 'sm-core' ) ),
			'failed'  => array( 'error', __( 'The e-mail could not be sent. See the error in the e-mail log.', 'sm-core' ) ),
		);
		foreach ( $map as $k => $m ) {
			if ( isset( $_GET[ $k ] ) ) {
				echo '<div class="notice notice-' . esc_attr( $m[0] ) . ' is-dismissible"><p>' . esc_html( $m[1] ) . '</p></div>';
			}
		}
		// phpcs:enable
	}

	/* ---- E-mails page ------------------------------------------------------ */

	public static function page() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'events';
		$edit = isset( $_GET['edit'] ) ? sanitize_text_field( wp_unslash( $_GET['edit'] ) ) : '';
		$view = isset( $_GET['view'] ) ? absint( $_GET['view'] ) : 0;
		// phpcs:enable
		echo '<div class="wrap sm-admin"><h1>' . esc_html__( 'E-mails', 'sm-core' ) . '</h1>';
		self::notice();
		if ( $edit ) {
			self::editor( $edit );
			echo '</div>';
			return;
		}
		if ( $view ) {
			self::view_mail( $view );
			echo '</div>';
			return;
		}
		$tabs = array(
			'events'   => __( 'Platform events', 'sm-core' ),
			'notices'  => __( 'User notifications', 'sm-core' ),
			'settings' => __( 'Sender and recipients', 'sm-core' ),
			'layout'   => __( 'Header and signature', 'sm-core' ),
			'log'      => __( 'E-mail log', 'sm-core' ),
		);
		echo '<nav class="nav-tab-wrapper">';
		foreach ( $tabs as $k => $l ) {
			echo '<a class="nav-tab' . ( $k === $tab ? ' nav-tab-active' : '' ) . '" href="' . esc_url( self::url( array( 'tab' => $k ) ) ) . '">' . esc_html( $l ) . '</a>';
		}
		echo '</nav>';
		self::smtp_status();
		switch ( $tab ) {
			case 'notices':
				self::tab_notices();
				break;
			case 'settings':
				self::tab_settings();
				break;
			case 'layout':
				self::tab_layout();
				break;
			case 'log':
				self::tab_log();
				break;
			default:
				self::tab_events();
		}
		echo '</div>';
	}

	private static function smtp_status() {
		$active = defined( 'WPMS_PLUGIN_VER' ) || class_exists( '\WPMailSMTP\Core' );
		if ( $active ) {
			echo '<div class="notice notice-success inline"><p>' . esc_html__( 'WP Mail SMTP is active: e-mails leave through your SMTP account. Check its "Email Test" after any change.', 'sm-core' ) . '</p></div>';
		} else {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'WP Mail SMTP is not active: e-mails are sent by the server and often land in spam. Install WP Mail SMTP and connect the e-mail account of your domain (see docs/EMAIL-SETUP.md).', 'sm-core' ) . '</p></div>';
		}
	}

	private static function tab_events() {
		$groups = Events::groups();
		$by     = array();
		foreach ( Events::all() as $key => $ev ) {
			$by[ $ev['group'] ][ $key ] = $ev;
		}
		$modes = array(
			'now'    => __( 'Immediately', 'sm-core' ),
			'digest' => __( 'In the daily summary', 'sm-core' ),
			'off'    => __( 'No', 'sm-core' ),
		);
		echo '<p>' . esc_html( sprintf( /* translators: %s: addresses. */ __( 'Choose what the administrators (%s) are told about, and the confirmation e-mails sent to users. Each e-mail can be rewritten in French and Arabic.', 'sm-core' ), implode( ', ', Settings::admin_emails() ) ) ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="sm_mail_events">';
		wp_nonce_field( 'sm_mail_events' );
		echo '<table class="widefat"><thead><tr><th>' . esc_html__( 'Event', 'sm-core' ) . '</th><th>' . esc_html__( 'Alert to the administrators', 'sm-core' ) . '</th><th>' . esc_html__( 'E-mail to the user', 'sm-core' ) . '</th><th>' . esc_html__( 'Templates', 'sm-core' ) . '</th></tr></thead><tbody>';
		foreach ( $groups as $g => $glabel ) {
			if ( empty( $by[ $g ] ) ) {
				continue;
			}
			echo '<tr class="smm-group"><th colspan="4">' . esc_html( $glabel ) . '</th></tr>';
			foreach ( $by[ $g ] as $key => $ev ) {
				echo '<tr><td><strong>' . esc_html( $ev['label'] ) . '</strong><br><code class="smm-desc">' . esc_html( $key ) . '</code></td><td>';
				if ( null === $ev['admin'] ) {
					echo '—';
				} else {
					echo '<select name="events[' . esc_attr( $key ) . '][admin]">';
					foreach ( $modes as $m => $ml ) {
						echo '<option value="' . esc_attr( $m ) . '"' . selected( Settings::admin_mode( $key ), $m, false ) . '>' . esc_html( $ml ) . '</option>';
					}
					echo '</select>';
				}
				echo '</td><td>';
				if ( null === $ev['user'] || empty( $ev['templates']['user'] ) ) {
					echo '—';
				} else {
					echo '<label><input type="hidden" name="events[' . esc_attr( $key ) . '][user]" value="0"><input type="checkbox" name="events[' . esc_attr( $key ) . '][user]" value="1"' . checked( Settings::user_on( $key ), true, false ) . '> ' . esc_html__( 'Send', 'sm-core' ) . '</label>';
				}
				echo '</td><td>';
				foreach ( array( 'admin' => __( 'Administrator', 'sm-core' ), 'user' => __( 'User', 'sm-core' ) ) as $who => $wl ) {
					if ( empty( $ev['templates'][ $who ] ) ) {
						continue;
					}
					$id = 'event:' . $key . ':' . $who;
					echo '<a class="button button-small" href="' . esc_url( self::url( array( 'edit' => $id ) ) ) . '">' . esc_html( $wl ) . '</a> ';
					if ( Templates::is_custom( $id ) ) {
						echo '<span class="smm-pill edit">' . esc_html__( 'edited', 'sm-core' ) . '</span> ';
					}
				}
				echo '</td></tr>';
			}
		}
		echo '</tbody></table>';
		submit_button();
		echo '</form>';
	}

	private static function tab_notices() {
		$lang = Activity::admin_lang();
		echo '<p>' . esc_html__( 'Messages the platform sends to users about their own activity (offer received, quote accepted, listing approved…). They always appear in the user\'s notifications; here you choose whether an e-mail is also sent, and you can rewrite it.', 'sm-core' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="sm_mail_notices">';
		wp_nonce_field( 'sm_mail_notices' );
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Notification', 'sm-core' ) . '</th><th>' . esc_html__( 'E-mail', 'sm-core' ) . '</th><th>' . esc_html__( 'Template', 'sm-core' ) . '</th></tr></thead><tbody>';
		foreach ( Notifications::types() as $type => $def ) {
			if ( empty( $def['email'] ) ) {
				continue;
			}
			$label = Language::with_language( $lang, static fn() => (string) @( $def['subject'] )( array() ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- labels from an empty payload.
			$id    = 'notice:' . $type;
			echo '<tr><td><strong>' . esc_html( $label ) . '</strong><br><code class="smm-desc">' . esc_html( $type ) . '</code></td>';
			echo '<td><label><input type="hidden" name="notices[' . esc_attr( $type ) . ']" value="0"><input type="checkbox" name="notices[' . esc_attr( $type ) . ']" value="1"' . checked( Settings::notice_on( $type ), true, false ) . '> ' . esc_html__( 'Send', 'sm-core' ) . '</label></td>';
			echo '<td><a class="button button-small" href="' . esc_url( self::url( array( 'edit' => $id ) ) ) . '">' . esc_html__( 'Edit', 'sm-core' ) . '</a> ' . ( Templates::is_custom( $id ) ? '<span class="smm-pill edit">' . esc_html__( 'edited', 'sm-core' ) . '</span>' : '' ) . '</td></tr>';
		}
		echo '</tbody></table>';
		submit_button();
		echo '</form>';
	}

	private static function tab_settings() {
		$s     = Settings::all();
		$state = (array) get_option( Activity::DIGEST_OPTION, array() );
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="sm_mail_settings">';
		wp_nonce_field( 'sm_mail_settings' );
		echo '<table class="form-table"><tbody>';
		echo '<tr><th><label for="smm-from-name">' . esc_html__( 'Sender name', 'sm-core' ) . '</label></th><td><input id="smm-from-name" class="regular-text" name="from_name" value="' . esc_attr( $s['from_name'] ) . '"></td></tr>';
		echo '<tr><th><label for="smm-from-email">' . esc_html__( 'Sender address', 'sm-core' ) . '</label></th><td><input id="smm-from-email" class="regular-text" type="email" name="from_email" value="' . esc_attr( $s['from_email'] ) . '"><p class="description">' . esc_html__( 'An address of your domain, the same as the SMTP account in WP Mail SMTP.', 'sm-core' ) . '</p></td></tr>';
		echo '<tr><th><label for="smm-admins">' . esc_html__( 'Administrators to alert', 'sm-core' ) . '</label></th><td><textarea id="smm-admins" class="large-text" rows="3" name="admins">' . esc_textarea( $s['admins'] ) . '</textarea><p class="description">' . esc_html__( 'One or more addresses, separated by commas or new lines.', 'sm-core' ) . '</p></td></tr>';
		echo '<tr><th><label for="smm-lang">' . esc_html__( 'Language of the alerts', 'sm-core' ) . '</label></th><td><select id="smm-lang" name="admin_lang"><option value="fr"' . selected( $s['admin_lang'], 'fr', false ) . '>Français</option><option value="ar"' . selected( $s['admin_lang'], 'ar', false ) . '>العربية</option></select></td></tr>';
		echo '<tr><th><label for="smm-hour">' . esc_html__( 'Daily summary at', 'sm-core' ) . '</label></th><td><select id="smm-hour" name="digest_hour">';
		for ( $h = 0; $h < 24; $h++ ) {
			echo '<option value="' . (int) $h . '"' . selected( (int) $s['digest_hour'], $h, false ) . '>' . esc_html( sprintf( '%02d:00', $h ) ) . '</option>';
		}
		echo '</select> <span class="description">' . esc_html( ! empty( $state['date'] ) ? sprintf( /* translators: %s: date. */ __( 'Last summary: %s', 'sm-core' ), $state['date'] ) : __( 'No summary sent yet.', 'sm-core' ) ) . '</span></td></tr>';
		echo '<tr><th><label for="smm-ret">' . esc_html__( 'Keep the journals', 'sm-core' ) . '</label></th><td><input id="smm-ret" type="number" min="30" max="3650" name="retention" value="' . esc_attr( Settings::retention_days() ) . '" style="width:90px"> ' . esc_html__( 'days (activity journal and e-mail log)', 'sm-core' ) . '</td></tr>';
		echo '<tr><th>' . esc_html__( 'E-mail log', 'sm-core' ) . '</th><td><label><input type="checkbox" name="log_bodies" value="1"' . checked( (int) $s['log_bodies'], 1, false ) . '> ' . esc_html__( 'Keep the content of the e-mails (to read and resend them). Verification codes are never kept.', 'sm-core' ) . '</label></td></tr>';
		echo '</tbody></table>';
		submit_button();
		echo '</form><hr>';
		echo '<div class="smm-grid"><div class="smm-card"><h2>' . esc_html__( 'Test the sending', 'sm-core' ) . '</h2><p>' . esc_html__( 'Sends a test e-mail to the administrators with the layout of the platform.', 'sm-core' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="sm_mail_test">';
		wp_nonce_field( 'sm_mail_test' );
		submit_button( __( 'Send a test e-mail', 'sm-core' ), 'secondary', 'submit', false );
		echo '</form></div><div class="smm-card"><h2>' . esc_html__( 'Daily summary', 'sm-core' ) . '</h2><p>' . esc_html__( 'Sends now the summary of the events set to "In the daily summary" since the last one.', 'sm-core' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="sm_mail_digest">';
		wp_nonce_field( 'sm_mail_digest' );
		submit_button( __( 'Send the summary now', 'sm-core' ), 'secondary', 'submit', false );
		echo '</form></div></div>';
	}

	public static function media( $hook ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( false !== strpos( (string) $hook, self::SLUG ) && 'layout' === ( $_GET['tab'] ?? '' ) ) {
			wp_enqueue_media();
		}
	}

	/** Image chooser (media library) bound to a hidden input. */
	private static function image_field( $name, $id, $label, $help ) {
		$src = $id ? wp_get_attachment_image_url( $id, 'medium' ) : '';
		echo '<div class="smm-image" data-smm-image>';
		echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . (int) $id . '">';
		echo '<div class="smm-image__preview">' . ( $src ? '<img src="' . esc_url( $src ) . '" alt="">' : '<span class="description">' . esc_html__( 'No image', 'sm-core' ) . '</span>' ) . '</div>';
		echo '<p><button type="button" class="button" data-smm-pick data-title="' . esc_attr( $label ) . '">' . esc_html__( 'Choose an image', 'sm-core' ) . '</button> <button type="button" class="button-link-delete" data-smm-clear' . ( $id ? '' : ' hidden' ) . '>' . esc_html__( 'Remove', 'sm-core' ) . '</button></p>';
		echo '<p class="description">' . esc_html( $help ) . '</p></div>';
	}

	private static function tab_layout() {
		$b = \SM\Core\Mail\Branding::get();
		echo '<p>' . esc_html__( 'Applies to every e-mail of the platform: alerts, confirmations, notifications, summaries and tests.', 'sm-core' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="sm_mail_brand">';
		wp_nonce_field( 'sm_mail_brand' );
		echo '<div class="smm-grid"><div class="smm-card"><h2>' . esc_html__( 'Header', 'sm-core' ) . '</h2>';
		self::image_field( 'brand[header_image]', (int) $b['header_image'], __( 'Header image', 'sm-core' ), __( 'Banner shown at the top of every e-mail, 1120 × 240 pixels recommended (PNG or JPG). Without an image, the logo or the name of the platform is shown on a coloured band.', 'sm-core' ) );
		echo '<p><label>' . esc_html__( 'Band colour', 'sm-core' ) . ' <input type="color" name="brand[header_bg]" value="' . esc_attr( $b['header_bg'] ?: '#172B3A' ) . '"></label></p>';
		echo '<p><label>' . esc_html__( 'Logo or name in the band', 'sm-core' ) . ' <select name="brand[header_align]"><option value="center"' . selected( $b['header_align'], 'center', false ) . '>' . esc_html__( 'Centred', 'sm-core' ) . '</option><option value="start"' . selected( $b['header_align'], 'start', false ) . '>' . esc_html__( 'At the start of the line', 'sm-core' ) . '</option></select></label></p>';
		echo '</div><div class="smm-card"><h2>' . esc_html__( 'Signature', 'sm-core' ) . '</h2>';
		echo '<p><label><input type="checkbox" name="brand[signature]" value="1"' . checked( (int) $b['signature'], 1, false ) . '> ' . esc_html__( 'Show the signature at the end of every e-mail', 'sm-core' ) . '</label></p>';
		self::image_field( 'brand[logo]', (int) $b['logo'], __( 'Logo', 'sm-core' ), __( 'Square logo shown in the signature (and in the band when there is no header image), 200 × 200 pixels recommended.', 'sm-core' ) );
		echo '<table class="form-table"><tbody>';
		echo '<tr><th><label for="smm-b-name">' . esc_html__( 'Name', 'sm-core' ) . '</label></th><td><input id="smm-b-name" class="regular-text" name="brand[name]" value="' . esc_attr( $b['name'] ) . '" placeholder="' . esc_attr( sm_brand_name() ) . '"></td></tr>';
		echo '<tr><th><label for="smm-b-phone">' . esc_html__( 'Phone', 'sm-core' ) . '</label></th><td><input id="smm-b-phone" class="regular-text" name="brand[phone]" dir="ltr" value="' . esc_attr( $b['phone'] ) . '"></td></tr>';
		echo '<tr><th><label for="smm-b-email">' . esc_html__( 'E-mail', 'sm-core' ) . '</label></th><td><input id="smm-b-email" class="regular-text" type="email" name="brand[email]" dir="ltr" value="' . esc_attr( $b['email'] ) . '"></td></tr>';
		echo '<tr><th><label for="smm-b-web">' . esc_html__( 'Website', 'sm-core' ) . '</label></th><td><input id="smm-b-web" class="regular-text" type="url" name="brand[website]" dir="ltr" value="' . esc_attr( $b['website'] ) . '" placeholder="https://reusteel.com"></td></tr>';
		echo '</tbody></table></div></div>';
		echo '<div class="smm-grid">';
		foreach ( array( 'fr' => 'Français', 'ar' => 'العربية', 'en' => 'English' ) as $l => $ll ) {
			echo '<div class="smm-card smm-lang" dir="' . ( 'ar' === $l ? 'rtl' : 'ltr' ) . '" lang="' . esc_attr( $l ) . '"><h2>' . esc_html( $ll ) . '</h2>';
			echo '<p><label>' . esc_html__( 'Closing words (before the name)', 'sm-core' ) . '<br><textarea name="brand[closing][' . esc_attr( $l ) . ']" style="min-height:70px">' . esc_textarea( $b['closing'][ $l ] ) . '</textarea></label></p>';
			echo '<p><label>' . esc_html__( 'Address', 'sm-core' ) . '<br><textarea name="brand[address][' . esc_attr( $l ) . ']" style="min-height:70px">' . esc_textarea( $b['address'][ $l ] ) . '</textarea></label></p></div>';
		}
		echo '</div><div class="smm-card" style="margin-top:16px"><h2>' . esc_html__( 'Social networks', 'sm-core' ) . '</h2><p class="description">' . esc_html__( 'Leave empty the networks the platform does not use. For WhatsApp, give the phone number.', 'sm-core' ) . '</p><table class="form-table"><tbody>';
		foreach ( \SM\Core\Mail\Branding::networks() as $k => $label ) {
			$v = (string) ( $b['social'][ $k ] ?? '' );
			echo '<tr><th><label for="smm-s-' . esc_attr( $k ) . '">' . esc_html( $label ) . '</label></th><td><input id="smm-s-' . esc_attr( $k ) . '" class="regular-text" name="brand[social][' . esc_attr( $k ) . ']" dir="ltr" value="' . esc_attr( 'whatsapp' === $k && $v ? '+' . $v : $v ) . '" placeholder="' . esc_attr( 'whatsapp' === $k ? '0550 12 34 56' : 'https://' ) . '"></td></tr>';
		}
		echo '</tbody></table></div>';
		submit_button();
		echo '</form>';
		echo '<h2>' . esc_html__( 'Preview', 'sm-core' ) . '</h2><div class="smm-grid">';
		foreach ( array( 'fr', 'ar', 'en' ) as $l ) {
			$html = Language::with_language( $l, static fn() => Mailer::layout( $l, Templates::body_html( __( "This is how your e-mails look.\n\nThe text of each message comes here, then the signature and the social links.", 'sm-core' ), array() ), 'Karim Benali' ) );
			echo '<iframe class="smm-preview" sandbox="" title="' . esc_attr( $l ) . '" srcdoc="' . esc_attr( $html ) . '"></iframe>';
		}
		echo '</div>';
		?>
		<script>
		document.querySelectorAll('[data-smm-image]').forEach(function (box) {
			var input = box.querySelector('input[type=hidden]'), prev = box.querySelector('.smm-image__preview'), clear = box.querySelector('[data-smm-clear]'), frame;
			box.querySelector('[data-smm-pick]').addEventListener('click', function (e) {
				e.preventDefault();
				if (!window.wp || !wp.media) { return; }
				frame = frame || wp.media({ title: this.dataset.title, library: { type: 'image' }, multiple: false });
				frame.off('select').on('select', function () {
					var a = frame.state().get('selection').first().toJSON();
					input.value = a.id;
					var url = (a.sizes && a.sizes.medium ? a.sizes.medium.url : a.url);
					prev.innerHTML = '';
					var img = document.createElement('img'); img.src = url; img.alt = ''; prev.appendChild(img);
					clear.hidden = false;
				});
				frame.open();
			});
			clear.addEventListener('click', function (e) { e.preventDefault(); input.value = 0; prev.innerHTML = ''; clear.hidden = true; });
		});
		</script>
		<?php
	}

	private static function tab_log() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
		$q      = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		$paged  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		// phpcs:enable
		$res = Mailer::query( array( 'status' => $status, 'q' => $q, 'page' => $paged, 'per_page' => 50 ) );
		echo '<form method="get" class="smm-filters"><input type="hidden" name="page" value="' . esc_attr( self::SLUG ) . '"><input type="hidden" name="tab" value="log"><p>';
		echo '<select name="status"><option value="">' . esc_html__( 'All', 'sm-core' ) . '</option><option value="sent"' . selected( $status, 'sent', false ) . '>' . esc_html__( 'Sent', 'sm-core' ) . '</option><option value="failed"' . selected( $status, 'failed', false ) . '>' . esc_html__( 'Failed', 'sm-core' ) . '</option></select> ';
		echo '<input type="search" name="q" value="' . esc_attr( $q ) . '" placeholder="' . esc_attr__( 'Address or subject', 'sm-core' ) . '"> <button class="button">' . esc_html__( 'Filter', 'sm-core' ) . '</button> <span class="description">' . esc_html( sprintf( /* translators: %d: count. */ _n( '%d e-mail', '%d e-mails', $res['total'], 'sm-core' ), $res['total'] ) ) . '</span></p></form>';
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Date', 'sm-core' ) . '</th><th>' . esc_html__( 'Recipient', 'sm-core' ) . '</th><th>' . esc_html__( 'Subject', 'sm-core' ) . '</th><th>' . esc_html__( 'Event', 'sm-core' ) . '</th><th>' . esc_html__( 'Status', 'sm-core' ) . '</th><th></th></tr></thead><tbody>';
		if ( ! $res['items'] ) {
			echo '<tr><td colspan="6">—</td></tr>';
		}
		foreach ( $res['items'] as $m ) {
			$ok = 'sent' === $m->status;
			echo '<tr><td>' . esc_html( get_date_from_gmt( $m->created_at, 'Y-m-d H:i' ) ) . '</td><td>' . esc_html( $m->recipient ) . '</td><td>' . esc_html( $m->subject ) . '</td><td><code class="smm-desc">' . esc_html( $m->event ) . '</code></td>';
			echo '<td><span class="smm-pill ' . ( $ok ? 'ok' : 'bad' ) . '">' . esc_html( $ok ? __( 'Sent', 'sm-core' ) : __( 'Failed', 'sm-core' ) ) . '</span>' . ( $m->error ? '<br><span class="smm-desc">' . esc_html( $m->error ) . '</span>' : '' ) . '</td>';
			echo '<td><a class="button button-small" href="' . esc_url( self::url( array( 'view' => $m->id ) ) ) . '">' . esc_html__( 'View', 'sm-core' ) . '</a></td></tr>';
		}
		echo '</tbody></table>';
		self::pager( $res['total'], 50, $paged, array( 'tab' => 'log', 'status' => $status, 'q' => $q ) );
	}

	private static function pager( $total, $per, $paged, array $args, $slug = self::SLUG ) {
		$pages = (int) ceil( $total / $per );
		if ( $pages < 2 ) {
			return;
		}
		echo '<p class="tablenav-pages">';
		for ( $i = 1; $i <= min( $pages, 50 ); $i++ ) {
			$u = add_query_arg( array_merge( array( 'page' => $slug, 'paged' => $i ), array_filter( $args ) ), admin_url( 'admin.php' ) );
			echo $i === $paged ? '<strong>' . (int) $i . '</strong> ' : '<a href="' . esc_url( $u ) . '">' . (int) $i . '</a> ';
		}
		echo '</p>';
	}

	private static function view_mail( $id ) {
		$m = Mailer::get( $id );
		echo '<p><a href="' . esc_url( self::url( array( 'tab' => 'log' ) ) ) . '">← ' . esc_html__( 'E-mail log', 'sm-core' ) . '</a></p>';
		if ( ! $m ) {
			echo '<p>—</p>';
			return;
		}
		echo '<table class="form-table"><tr><th>' . esc_html__( 'Recipient', 'sm-core' ) . '</th><td>' . esc_html( $m->recipient ) . '</td></tr><tr><th>' . esc_html__( 'Subject', 'sm-core' ) . '</th><td>' . esc_html( $m->subject ) . '</td></tr><tr><th>' . esc_html__( 'Date', 'sm-core' ) . '</th><td>' . esc_html( get_date_from_gmt( $m->created_at, 'Y-m-d H:i' ) ) . '</td></tr>';
		if ( $m->reply_to ) {
			echo '<tr><th>Reply-To</th><td>' . esc_html( $m->reply_to ) . '</td></tr>';
		}
		echo '</table>';
		if ( '' !== (string) $m->body ) {
			echo '<iframe class="smm-preview" sandbox="" srcdoc="' . esc_attr( $m->body ) . '"></iframe>';
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="sm_mail_resend"><input type="hidden" name="id" value="' . (int) $m->id . '">';
			wp_nonce_field( 'sm_mail_resend' );
			submit_button( __( 'Send again', 'sm-core' ), 'secondary' );
			echo '</form>';
		} else {
			echo '<p class="description">' . esc_html__( 'The content of this e-mail was not kept.', 'sm-core' ) . '</p>';
		}
	}

	/* ---- Template editor ---------------------------------------------------- */

	/** [title, variables] of a template id, or null. */
	private static function template_info( $id ) {
		$p = explode( ':', $id );
		if ( 'event' === $p[0] && 3 === count( $p ) && ( $ev = Events::get( $p[1] ) ) && ! empty( $ev['templates'][ $p[2] ] ) ) {
			$who = 'admin' === $p[2] ? __( 'alert to the administrators', 'sm-core' ) : __( 'e-mail to the user', 'sm-core' );
			return array( $ev['label'] . ' — ' . $who, array_merge( $ev['variables'], array( 'site', 'lien_site', 'date' ) ), 'events' );
		}
		if ( 'notice' === $p[0] && 2 === count( $p ) && isset( Notifications::types()[ $p[1] ] ) ) {
			$def = Notifications::types()[ $p[1] ];
			return array( (string) @( $def['subject'] )( array() ) . ' — ' . __( 'e-mail to the user', 'sm-core' ), array( 'nom', 'titre', 'message', 'lien', 'site', 'lien_site', 'date' ), 'notices' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}
		return null;
	}

	private static function editor( $id ) {
		$info = self::template_info( $id );
		if ( ! $info ) {
			echo '<p>—</p>';
			return;
		}
		list( $title, $vars, $tab ) = $info;
		echo '<p><a href="' . esc_url( self::url( array( 'tab' => $tab ) ) ) . '">← ' . esc_html__( 'Back to the list', 'sm-core' ) . '</a></p>';
		echo '<h2>' . esc_html( $title ) . '</h2>';
		echo '<p class="smm-vars">' . esc_html__( 'Variables (copied as they are, replaced when sending):', 'sm-core' ) . ' ';
		foreach ( $vars as $v ) {
			echo '<code>{' . esc_html( $v ) . '}</code>';
		}
		echo '</p><p class="description">' . esc_html__( 'Plain text: an empty line starts a new paragraph, addresses become links. Leave a field empty to use the default text.', 'sm-core' );
		if ( 0 === strpos( $id, 'notice:' ) ) {
			echo ' ' . esc_html__( '{titre} and {message} are the built-in texts of the notification; when {lien} is not used, an "Open" button is added.', 'sm-core' );
		}
		echo '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="sm_mail_template"><input type="hidden" name="id" value="' . esc_attr( $id ) . '">';
		wp_nonce_field( 'sm_mail_template' );
		echo '<div class="smm-grid">';
		foreach ( Templates::LANGS as $lang ) {
			$cur = Templates::get( $id, $lang );
			$dir = 'ar' === $lang ? 'rtl' : 'ltr';
			echo '<div class="smm-card smm-lang" dir="' . esc_attr( $dir ) . '" lang="' . esc_attr( $lang ) . '"><h2>' . esc_html( 'ar' === $lang ? 'العربية' : 'Français' ) . '</h2>';
			echo '<p><label>' . esc_html__( 'Subject', 'sm-core' ) . '<br><input type="text" name="tpl[' . esc_attr( $lang ) . '][subject]" value="' . esc_attr( $cur[0] ) . '"></label></p>';
			echo '<p><label>' . esc_html__( 'Message', 'sm-core' ) . '<br><textarea name="tpl[' . esc_attr( $lang ) . '][body]">' . esc_textarea( $cur[1] ) . '</textarea></label></p></div>';
		}
		echo '</div><p>';
		submit_button( __( 'Save the template', 'sm-core' ), 'primary', 'do_save', false );
		echo ' ';
		submit_button( __( 'Save and send me a test', 'sm-core' ), 'secondary', 'do_test', false );
		echo ' ';
		submit_button( __( 'Restore the default text', 'sm-core' ), 'delete', 'do_reset', false, array( 'onclick' => 'return confirm(' . wp_json_encode( __( 'Replace your text by the default one in both languages?', 'sm-core' ) ) . ')' ) );
		echo '</p></form>';
		echo '<h2>' . esc_html__( 'Preview with example values', 'sm-core' ) . '</h2><div class="smm-grid">';
		foreach ( Templates::LANGS as $lang ) {
			$html = self::render_sample( $id, $lang, $vars );
			echo '<div><iframe class="smm-preview" sandbox="" title="' . esc_attr( $lang ) . '" srcdoc="' . esc_attr( $html[1] ) . '"></iframe><p class="description"><strong>' . esc_html( $html[0] ) . '</strong></p></div>';
		}
		echo '</div>';
	}

	/** [subject, full html] of a template filled with example values. */
	private static function render_sample( $id, $lang, array $vars ) {
		return Language::with_language(
			$lang,
			static function () use ( $id, $lang, $vars ) {
				$tpl    = Templates::get( $id, $lang );
				$sample = Templates::sample_vars( $vars );
				$user   = 0 === strpos( $id, 'notice:' ) || ':user' === substr( $id, -5 );
				$name   = $user ? ( $sample['nom'] ?? 'Karim Benali' ) : '';
				return array( Templates::subject( $tpl[0], $sample ), Mailer::layout( $lang, Templates::body_html( $tpl[1], $sample ), $name ) );
			}
		);
	}

	/* ---- Handlers ----------------------------------------------------------- */

	public static function save_settings() {
		self::guard( 'sm_mail_settings' );
		$admins = implode( ', ', array_values( array_filter( array_map( 'sanitize_email', preg_split( '/[\s,;]+/', (string) wp_unslash( $_POST['admins'] ?? '' ) ) ), 'is_email' ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$from   = sanitize_email( wp_unslash( $_POST['from_email'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		Settings::save(
			array(
				'from_name'   => sanitize_text_field( wp_unslash( $_POST['from_name'] ?? '' ) ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				'from_email'  => is_email( $from ) ? $from : Settings::get( 'from_email' ),
				'admins'      => $admins,
				'admin_lang'  => 'ar' === ( $_POST['admin_lang'] ?? '' ) ? 'ar' : 'fr',
				'digest_hour' => max( 0, min( 23, (int) ( $_POST['digest_hour'] ?? 18 ) ) ),
				'retention'   => max( 30, min( 3650, (int) ( $_POST['retention'] ?? 365 ) ) ),
				'log_bodies'  => empty( $_POST['log_bodies'] ) ? 0 : 1,
			)
		);
		AuditLog::add( 'admin.mail_updated', 'settings', 0, array( 'admins' => $admins ) );
		self::back( array( 'tab' => 'settings', 'updated' => 1 ) );
	}

	public static function save_brand() {
		self::guard( 'sm_mail_brand' );
		$brand = \SM\Core\Mail\Branding::sanitize( (array) wp_unslash( $_POST['brand'] ?? array() ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized in Branding::sanitize().
		Settings::save( array( 'brand' => $brand ) );
		AuditLog::add( 'admin.mail_updated', 'settings', 0, array( 'brand' => true ) );
		self::back( array( 'tab' => 'layout', 'updated' => 1 ) );
	}

	public static function save_events() {
		self::guard( 'sm_mail_events' );
		$in     = (array) wp_unslash( $_POST['events'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$events = Settings::get( 'events' );
		foreach ( Events::all() as $key => $ev ) {
			if ( ! isset( $in[ $key ] ) ) {
				continue;
			}
			$row = array();
			if ( isset( $in[ $key ]['admin'] ) && in_array( $in[ $key ]['admin'], Settings::MODES, true ) ) {
				$row['admin'] = $in[ $key ]['admin'];
			}
			if ( isset( $in[ $key ]['user'] ) ) {
				$row['user'] = (bool) $in[ $key ]['user'];
			}
			$events[ $key ] = $row;
		}
		Settings::save( array( 'events' => $events ) );
		AuditLog::add( 'admin.mail_updated', 'settings', 0, array( 'events' => count( $events ) ) );
		self::back( array( 'tab' => 'events', 'updated' => 1 ) );
	}

	public static function save_notices() {
		self::guard( 'sm_mail_notices' );
		$in      = (array) wp_unslash( $_POST['notices'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$notices = array();
		foreach ( Notifications::types() as $type => $def ) {
			if ( isset( $in[ $type ] ) ) {
				$notices[ $type ] = (bool) $in[ $type ];
			}
		}
		Settings::save( array( 'notices' => $notices ) );
		AuditLog::add( 'admin.mail_updated', 'settings', 0, array( 'notices' => count( $notices ) ) );
		self::back( array( 'tab' => 'notices', 'updated' => 1 ) );
	}

	public static function save_template() {
		self::guard( 'sm_mail_template' );
		$id = sanitize_text_field( wp_unslash( $_POST['id'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! self::template_info( $id ) ) {
			wp_die( esc_html__( 'Unknown template.', 'sm-core' ) );
		}
		if ( isset( $_POST['do_reset'] ) ) {
			Templates::reset( $id );
			AuditLog::add( 'admin.mail_template', 'settings', 0, array( 'id' => $id, 'reset' => true ) );
			self::back( array( 'edit' => $id, 'reset' => 1 ) );
		}
		$tpl = (array) wp_unslash( $_POST['tpl'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		foreach ( Templates::LANGS as $lang ) {
			$def     = Templates::default_text( $id, $lang );
			$subject = (string) ( $tpl[ $lang ]['subject'] ?? '' );
			$body    = str_replace( "\r\n", "\n", (string) ( $tpl[ $lang ]['body'] ?? '' ) );
			// Text identical to the default is not stored, so later improvements of the default still apply.
			Templates::save( $id, $lang, trim( $subject ) === trim( $def[0] ) ? '' : $subject, trim( $body ) === trim( str_replace( "\r\n", "\n", $def[1] ) ) ? '' : $body );
		}
		AuditLog::add( 'admin.mail_template', 'settings', 0, array( 'id' => $id ) );
		if ( isset( $_POST['do_test'] ) ) {
			$ok = self::test_template( $id );
			self::back( array( 'edit' => $id, $ok ? 'tested' : 'failed' => 1 ) );
		}
		self::back( array( 'edit' => $id, 'updated' => 1 ) );
	}

	/** Send a template with example values to the current administrator, in both languages. */
	private static function test_template( $id ) {
		$user = wp_get_current_user();
		$info = self::template_info( $id );
		$ok   = false;
		foreach ( Templates::LANGS as $lang ) {
			list( $subject, $html ) = self::render_sample( $id, $lang, $info[1] );
			$ok = Mailer::deliver( $user->user_email, '[TEST] ' . $subject, $html, 'test:' . $id ) || $ok;
		}
		return $ok;
	}

	public static function send_test() {
		self::guard( 'sm_mail_test' );
		$lang = Activity::admin_lang();
		$ok   = false;
		foreach ( Settings::admin_emails() as $to ) {
			$ok = Language::with_language(
				$lang,
				static fn() => Mailer::send( $to, $lang, sprintf( /* translators: %s: platform name. */ __( '%s — test e-mail', 'sm-core' ), sm_brand_name() ), Templates::body_html( __( "This test e-mail shows that the platform can send e-mails.\n\nIf it arrived in the spam folder, check the SPF, DKIM and DMARC records of your domain.", 'sm-core' ), array() ), array( 'event' => 'test' ) )
			) || $ok;
		}
		self::back( array( 'tab' => 'settings', $ok ? 'tested' : 'failed' => 1 ) );
	}

	public static function digest_now() {
		self::guard( 'sm_mail_digest' );
		Activity::send_digest();
		self::back( array( 'tab' => 'settings', 'digest' => 1 ) );
	}

	public static function resend() {
		self::guard( 'sm_mail_resend' );
		$ok = Mailer::resend( absint( $_POST['id'] ?? 0 ) );
		self::back( array( 'tab' => 'log', $ok ? 'resent' : 'failed' => 1 ) );
	}

	/* ---- Activity journal ----------------------------------------------------- */

	/** Filters of the journal from the request. */
	private static function journal_args() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$group = isset( $_GET['group'] ) ? sanitize_key( $_GET['group'] ) : '';
		$args  = array(
			'group' => $group,
			'from'  => isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '',
			'to'    => isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '',
			'q'     => isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '',
			'c'     => isset( $_GET['c'] ) ? absint( $_GET['c'] ) : 0,
			'paged' => isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1,
		);
		// phpcs:enable
		$args['prefixes'] = Activity::group_prefixes()[ $group ] ?? array();
		return $args;
	}

	private static function journal_query( array $a, $per_page, $page, $count = false ) {
		// Successful sign-ins are many: they are listed under "Security" only.
		$exclude = 'security' === $a['group'] ? array() : array( 'auth.login' );
		return AuditLog::query( array( 'prefixes' => $a['prefixes'], 'exclude' => $exclude, 'from' => $a['from'], 'to' => $a['to'], 'q' => $a['q'], 'company_id' => $a['c'], 'per_page' => $per_page, 'page' => $page, 'count' => $count ) );
	}

	private static function group_labels() {
		return Events::groups() + array( 'admin' => __( 'Administration', 'sm-core' ) );
	}

	public static function page_activity() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		$a      = self::journal_args();
		$total  = self::journal_query( $a, 0, 1, true );
		$rows   = self::journal_query( $a, 50, $a['paged'] );
		$groups = self::group_labels();
		echo '<div class="wrap sm-admin"><h1>' . esc_html__( 'Activity journal', 'sm-core' ) . '</h1>';
		echo '<p>' . esc_html__( 'Everything that happens on the platform: registrations, listings, requests, quotes, services, messages, verifications and administration changes. Successful sign-ins are listed under "Security".', 'sm-core' ) . '</p>';
		echo '<form method="get" class="smm-filters"><input type="hidden" name="page" value="sm-audit"><p>';
		echo '<select name="group"><option value="">' . esc_html__( 'All activities', 'sm-core' ) . '</option>';
		foreach ( $groups as $g => $l ) {
			echo '<option value="' . esc_attr( $g ) . '"' . selected( $a['group'], $g, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select> <label>' . esc_html__( 'From', 'sm-core' ) . ' <input type="date" name="from" value="' . esc_attr( $a['from'] ) . '"></label> <label>' . esc_html__( 'to', 'sm-core' ) . ' <input type="date" name="to" value="' . esc_attr( $a['to'] ) . '"></label> ';
		echo '<input type="search" name="q" value="' . esc_attr( $a['q'] ) . '" placeholder="' . esc_attr__( 'Name, e-mail, IP…', 'sm-core' ) . '"> ';
		if ( $a['c'] ) {
			echo '<input type="hidden" name="c" value="' . (int) $a['c'] . '">';
		}
		echo '<button class="button">' . esc_html__( 'Filter', 'sm-core' ) . '</button> ';
		$csv = wp_nonce_url( add_query_arg( array( 'action' => 'sm_activity_csv', 'group' => $a['group'], 'from' => $a['from'], 'to' => $a['to'], 'q' => $a['q'], 'c' => $a['c'] ), admin_url( 'admin-post.php' ) ), 'sm_activity_csv' );
		echo '<a class="button" href="' . esc_url( $csv ) . '">' . esc_html__( 'Export (CSV)', 'sm-core' ) . '</a> <span class="description">' . esc_html( sprintf( /* translators: %d: count. */ _n( '%d entry', '%d entries', $total, 'sm-core' ), $total ) ) . '</span></p></form>';
		if ( $a['c'] && ( $co = Companies::get( $a['c'] ) ) ) {
			echo '<p>' . esc_html( sprintf( /* translators: %s: company. */ __( 'Only the activity of %s.', 'sm-core' ), $co->name ) ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=sm-audit' ) ) . '">' . esc_html__( 'Show all', 'sm-core' ) . '</a></p>';
		}
		echo '<table class="widefat striped sm-journal"><thead><tr><th>' . esc_html__( 'Date', 'sm-core' ) . '</th><th>' . esc_html__( 'Type', 'sm-core' ) . '</th><th>' . esc_html__( 'Activity', 'sm-core' ) . '</th><th>' . esc_html__( 'By', 'sm-core' ) . '</th><th>' . esc_html__( 'Company', 'sm-core' ) . '</th><th>IP</th></tr></thead><tbody>';
		if ( ! $rows ) {
			echo '<tr><td colspan="6">—</td></tr>';
		}
		foreach ( $rows as $row ) {
			$e    = AuditLog::entry( $row );
			$g    = Activity::group_of( $e->action );
			$u    = $e->user_id ? get_userdata( $e->user_id ) : null;
			$co   = $e->company_id ? Companies::get( $e->company_id ) : null;
			$link = Activity::link( $e );
			$text = esc_html( Activity::describe( $e ) );
			echo '<tr><td>' . esc_html( get_date_from_gmt( $e->created_at, 'Y-m-d H:i' ) ) . '</td><td><span class="smm-pill">' . esc_html( $groups[ $g ] ?? '—' ) . '</span></td>';
			echo '<td>' . ( $link ? '<a href="' . esc_url( $link ) . '">' . $text . '</a>' : $text ) . ' <code class="smm-desc">' . esc_html( $e->action ) . '</code></td>'; // phpcs:ignore WordPress.Security.EscapeOutput
			echo '<td>' . esc_html( $u ? $u->display_name . ' <' . $u->user_email . '>' : '—' ) . '</td>';
			echo '<td>' . ( $co ? '<a href="' . esc_url( admin_url( 'admin.php?page=sm-audit&c=' . (int) $co->id ) ) . '">' . esc_html( $co->name ) . '</a>' : '—' ) . '</td><td>' . esc_html( $e->ip ) . '</td></tr>';
		}
		echo '</tbody></table>';
		self::pager( $total, 50, $a['paged'], array( 'group' => $a['group'], 'from' => $a['from'], 'to' => $a['to'], 'q' => $a['q'], 'c' => $a['c'] ), 'sm-audit' );
		echo '</div>';
	}

	public static function export_csv() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You cannot change these settings.', 'sm-core' ) );
		}
		check_admin_referer( 'sm_activity_csv' );
		$a      = self::journal_args();
		$groups = self::group_labels();
		nocache_headers();
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="activity-' . gmdate( 'Y-m-d' ) . '.csv"' );
		$out = fopen( 'php://output', 'w' );
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- UTF-8 BOM for Excel.
		fputcsv( $out, array( __( 'Date', 'sm-core' ), __( 'Type', 'sm-core' ), __( 'Activity', 'sm-core' ), __( 'Action', 'sm-core' ), __( 'By', 'sm-core' ), __( 'E-mail', 'sm-core' ), __( 'Company', 'sm-core' ), 'IP' ) );
		for ( $page = 1; $page <= 100; $page++ ) {
			$rows = self::journal_query( $a, 500, $page );
			foreach ( $rows as $row ) {
				$e  = AuditLog::entry( $row );
				$u  = $e->user_id ? get_userdata( $e->user_id ) : null;
				$co = $e->company_id ? Companies::get( $e->company_id ) : null;
				fputcsv( $out, array( get_date_from_gmt( $e->created_at, 'Y-m-d H:i' ), $groups[ Activity::group_of( $e->action ) ] ?? '', Activity::describe( $e ), $e->action, $u ? $u->display_name : '', $u ? $u->user_email : '', $co ? $co->name : '', $e->ip ) );
			}
			if ( count( $rows ) < 500 ) {
				break;
			}
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}
}

<?php
/**
 * wp-admin: service pages by status (moderation when enabled) and settings.
 *
 * @package SM\Services
 */

namespace SM\Services\Admin;

use SM\Core\Accounts\Companies;
use SM\Core\Notifications\Notifications as Notify;
use SM\Services\Presenter;
use SM\Services\Services;
use SM\Services\Settings;

defined( 'ABSPATH' ) || exit;

final class Admin {

	const SLUG = 'sm-services';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 30 );
		add_action( 'admin_post_sms_moderate', array( __CLASS__, 'moderate' ) );
		add_action( 'admin_post_sms_settings', array( __CLASS__, 'save_settings' ) );
	}

	public static function menu() {
		add_submenu_page( 'sm-core', __( 'Services', 'sm-services' ), __( 'Services', 'sm-services' ), 'sm_moderate', self::SLUG, array( __CLASS__, 'page' ) );
	}

	private static function labels() {
		return array(
			'pending'   => __( 'Waiting for review', 'sm-services' ),
			'published' => __( 'Published', 'sm-services' ),
			'paused'    => __( 'Paused', 'sm-services' ),
			'rejected'  => __( 'Changes requested', 'sm-services' ),
		);
	}

	public static function page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab    = sanitize_key( $_GET['tab'] ?? 'list' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status = sanitize_key( $_GET['status'] ?? 'published' );
		$base   = admin_url( 'admin.php?page=' . self::SLUG );
		echo '<div class="wrap"><h1>' . esc_html__( 'Services', 'sm-services' ) . '</h1>';
		echo '<nav class="nav-tab-wrapper"><a class="nav-tab' . ( 'list' === $tab ? ' nav-tab-active' : '' ) . '" href="' . esc_url( $base ) . '">' . esc_html__( 'Service pages', 'sm-services' ) . '</a><a class="nav-tab' . ( 'settings' === $tab ? ' nav-tab-active' : '' ) . '" href="' . esc_url( $base . '&tab=settings' ) . '">' . esc_html__( 'Settings', 'sm-services' ) . '</a></nav>';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['done'] ) ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Saved.', 'sm-services' ) . '</p></div>';
		}
		if ( 'settings' === $tab ) {
			self::settings();
			echo '</div>';
			return;
		}
		$links = array();
		foreach ( self::labels() as $k => $l ) {
			$n       = Services::query( array( 'status' => $k, 'per_page' => 1 ) )['total'];
			$links[] = sprintf( '<li><a href="%s"%s>%s <span class="count">(%d)</span></a></li>', esc_url( $base . '&status=' . $k ), $k === $status ? ' class="current"' : '', esc_html( $l ), $n );
		}
		echo '<ul class="subsubsub">' . implode( ' | ', $links ) . '</ul><br class="clear">'; // phpcs:ignore WordPress.Security.EscapeOutput
		$q = Services::query( array( 'status' => isset( self::labels()[ $status ] ) ? $status : 'published', 'per_page' => 50 ) );
		if ( ! $q['items'] ) {
			echo '<p>' . esc_html__( 'Nothing here.', 'sm-services' ) . '</p></div>';
			return;
		}
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Service', 'sm-services' ) . '</th><th>' . esc_html__( 'Provider', 'sm-services' ) . '</th><th>' . esc_html__( 'Catalogue', 'sm-services' ) . '</th><th>' . esc_html__( 'Views', 'sm-services' ) . '</th><th>' . esc_html__( 'Decision', 'sm-services' ) . '</th></tr></thead><tbody>';
		foreach ( $q['items'] as $r ) {
			$c = Companies::get( $r->company_id );
			echo '<tr><td><strong><a href="' . esc_url( \SM\Services\Routes::link( $r->id ) ) . '" target="_blank">' . esc_html( Presenter::title_text( $r ) ) . '</a></strong><br><small>#' . (int) $r->id . '</small></td>';
			echo '<td>' . esc_html( $c ? $c->name : '—' ) . '</td><td>' . esc_html( \SM\Services\Catalogue::kind( $r->kind )['label']['fr'] ?? $r->kind ) . '</td><td>' . (int) $r->views . '</td><td>';
			if ( in_array( $r->status, array( 'pending', 'published' ), true ) ) {
				echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
				wp_nonce_field( 'sms_moderate' );
				echo '<input type="hidden" name="action" value="sms_moderate"><input type="hidden" name="id" value="' . (int) $r->id . '">';
				if ( 'pending' === $r->status ) {
					echo '<button class="button button-primary" name="decision" value="approve">' . esc_html__( 'Publish', 'sm-services' ) . '</button> ';
				}
				echo '<input name="reason" placeholder="' . esc_attr__( 'Reason (sent to the provider)', 'sm-services' ) . '" style="width:180px"> <button class="button" name="decision" value="reject">' . esc_html( 'published' === $r->status ? __( 'Unpublish', 'sm-services' ) : __( 'Request changes', 'sm-services' ) ) . '</button></form>';
			}
			echo '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	private static function settings() {
		$s      = Settings::all();
		$fields = array(
			'max_services'     => __( 'Service pages per company', 'sm-services' ),
			'max_photos'       => __( 'Photos per service page', 'sm-services' ),
			'photo_mb'         => __( 'Largest photo (MB)', 'sm-services' ),
			'max_files'        => __( 'Files per service request', 'sm-services' ),
			'file_mb'          => __( 'Largest file (MB)', 'sm-services' ),
			'requests_per_day' => __( 'Service requests a company may send per day', 'sm-services' ),
			'max_portfolio'    => __( 'References per company', 'sm-services' ),
			'max_price'        => __( 'Highest price accepted (DA)', 'sm-services' ),
		);
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="sms_settings">';
		wp_nonce_field( 'sms_settings' );
		echo '<table class="form-table"><tr><th>' . esc_html__( 'Moderation', 'sm-services' ) . '</th><td><label><input type="checkbox" name="moderation" value="1"' . checked( $s['moderation'], 1, false ) . '> ' . esc_html__( 'Check every new service page before it is published', 'sm-services' ) . '</label></td></tr>';
		foreach ( $fields as $k => $l ) {
			echo '<tr><th><label for="smq-' . esc_attr( $k ) . '">' . esc_html( $l ) . '</label></th><td><input id="smq-' . esc_attr( $k ) . '" name="' . esc_attr( $k ) . '" type="number" min="0" step="any" value="' . esc_attr( (string) $s[ $k ] ) . '" class="regular-text"></td></tr>';
		}
		echo '</table>';
		submit_button();
		echo '</form>';
	}

	public static function save_settings() {
		if ( ! current_user_can( 'sm_manage_settings' ) ) {
			wp_die( esc_html__( 'You cannot change these settings.', 'sm-services' ) );
		}
		check_admin_referer( 'sms_settings' );
		Settings::save( wp_unslash( $_POST ) );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&tab=settings&done=1' ) );
		exit;
	}

	public static function moderate() {
		if ( ! current_user_can( 'sm_moderate' ) ) {
			wp_die( esc_html__( 'You cannot moderate services.', 'sm-services' ) );
		}
		check_admin_referer( 'sms_moderate' );
		$r = Services::get( absint( $_POST['id'] ?? 0 ) );
		if ( $r ) {
			if ( 'approve' === sanitize_key( $_POST['decision'] ?? '' ) ) {
				Services::set_status( $r->id, 'published' );
				Notify::notify_company( $r->company_id, 'service.approved', array( 'service_id' => $r->id ) );
			} else {
				$reason = sanitize_text_field( wp_unslash( $_POST['reason'] ?? '' ) );
				Services::set_status( $r->id, 'rejected', $reason );
				Notify::notify_company( $r->company_id, 'service.rejected', array( 'service_id' => $r->id, 'reason' => $reason ) );
			}
		}
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&done=1' ) );
		exit;
	}
}

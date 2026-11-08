<?php
/**
 * wp-admin: requests for quotation by status (moderation when enabled) and settings.
 *
 * @package SM\Rfq
 */

namespace SM\Rfq\Admin;

use SM\Core\Accounts\Companies;
use SM\Core\Notifications\Notifications as Notify;
use SM\Rfq\Presenter;
use SM\Rfq\Rfqs;
use SM\Rfq\Settings;

defined( 'ABSPATH' ) || exit;

final class Admin {

	const SLUG = 'sm-rfq';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 30 );
		add_action( 'admin_post_smq_moderate', array( __CLASS__, 'moderate' ) );
		add_action( 'admin_post_smq_settings', array( __CLASS__, 'save_settings' ) );
	}

	public static function menu() {
		add_submenu_page( 'sm-core', __( 'Requests for quotation', 'sm-rfq' ), __( 'Requests for quotation', 'sm-rfq' ), 'sm_moderate', self::SLUG, array( __CLASS__, 'page' ) );
	}

	private static function labels() {
		return array(
			'pending'  => __( 'Waiting for review', 'sm-rfq' ),
			'open'     => __( 'Open', 'sm-rfq' ),
			'awarded'  => __( 'Awarded', 'sm-rfq' ),
			'expired'  => __( 'Deadline passed', 'sm-rfq' ),
			'closed'   => __( 'Closed', 'sm-rfq' ),
			'rejected' => __( 'Changes requested', 'sm-rfq' ),
		);
	}

	public static function page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab    = sanitize_key( $_GET['tab'] ?? 'list' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status = sanitize_key( $_GET['status'] ?? 'open' );
		$base   = admin_url( 'admin.php?page=' . self::SLUG );
		echo '<div class="wrap"><h1>' . esc_html__( 'Requests for quotation', 'sm-rfq' ) . '</h1>';
		echo '<nav class="nav-tab-wrapper"><a class="nav-tab' . ( 'list' === $tab ? ' nav-tab-active' : '' ) . '" href="' . esc_url( $base ) . '">' . esc_html__( 'Requests', 'sm-rfq' ) . '</a><a class="nav-tab' . ( 'settings' === $tab ? ' nav-tab-active' : '' ) . '" href="' . esc_url( $base . '&tab=settings' ) . '">' . esc_html__( 'Settings', 'sm-rfq' ) . '</a></nav>';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['done'] ) ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Saved.', 'sm-rfq' ) . '</p></div>';
		}
		if ( 'settings' === $tab ) {
			self::settings();
			echo '</div>';
			return;
		}
		$links = array();
		foreach ( self::labels() as $k => $l ) {
			$n       = Rfqs::query( array( 'status' => $k, 'per_page' => 1 ) )['total'];
			$links[] = sprintf( '<li><a href="%s"%s>%s <span class="count">(%d)</span></a></li>', esc_url( $base . '&status=' . $k ), $k === $status ? ' class="current"' : '', esc_html( $l ), $n );
		}
		echo '<ul class="subsubsub">' . implode( ' | ', $links ) . '</ul><br class="clear">'; // phpcs:ignore WordPress.Security.EscapeOutput
		$q = Rfqs::query( array( 'status' => isset( self::labels()[ $status ] ) ? $status : 'open', 'per_page' => 50, 'sort' => 'updated' ) );
		if ( ! $q['items'] ) {
			echo '<p>' . esc_html__( 'Nothing here.', 'sm-rfq' ) . '</p></div>';
			return;
		}
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Request', 'sm-rfq' ) . '</th><th>' . esc_html__( 'Buyer', 'sm-rfq' ) . '</th><th>' . esc_html__( 'Deadline', 'sm-rfq' ) . '</th><th>' . esc_html__( 'Offers', 'sm-rfq' ) . '</th><th>' . esc_html__( 'Decision', 'sm-rfq' ) . '</th></tr></thead><tbody>';
		foreach ( $q['items'] as $r ) {
			$c = Companies::get( $r->company_id );
			echo '<tr><td><strong><a href="' . esc_url( \SM\Rfq\Routes::link( $r->id ) ) . '" target="_blank">' . esc_html( Presenter::title_text( $r ) ) . '</a></strong><br><small>#' . (int) $r->id . ' · ' . esc_html( $r->designation ) . '</small></td>';
			echo '<td>' . esc_html( $c ? $c->name : '—' ) . '</td><td>' . esc_html( (string) $r->deadline ) . '</td><td>' . (int) $r->offers_count . '</td><td>';
			if ( in_array( $r->status, array( 'pending', 'open' ), true ) ) {
				echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
				wp_nonce_field( 'smq_moderate' );
				echo '<input type="hidden" name="action" value="smq_moderate"><input type="hidden" name="id" value="' . (int) $r->id . '">';
				if ( 'pending' === $r->status ) {
					echo '<button class="button button-primary" name="decision" value="approve">' . esc_html__( 'Publish', 'sm-rfq' ) . '</button> ';
				}
				echo '<input name="reason" placeholder="' . esc_attr__( 'Reason (sent to the buyer)', 'sm-rfq' ) . '" style="width:180px"> <button class="button" name="decision" value="reject">' . esc_html( 'open' === $r->status ? __( 'Unpublish', 'sm-rfq' ) : __( 'Request changes', 'sm-rfq' ) ) . '</button></form>';
			}
			echo '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	private static function settings() {
		$s      = Settings::all();
		$fields = array(
			'default_days' => __( 'Answer deadline proposed to buyers (days)', 'sm-rfq' ),
			'max_days'     => __( 'Latest answer deadline (days)', 'sm-rfq' ),
			'max_files'    => __( 'Files per request or offer', 'sm-rfq' ),
			'file_mb'      => __( 'Largest file (MB)', 'sm-rfq' ),
			'max_per_day'  => __( 'Requests a company may publish per day', 'sm-rfq' ),
			'min_price'    => __( 'Lowest price accepted (DA)', 'sm-rfq' ),
			'max_price'    => __( 'Highest price accepted (DA)', 'sm-rfq' ),
		);
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="smq_settings">';
		wp_nonce_field( 'smq_settings' );
		echo '<table class="form-table"><tr><th>' . esc_html__( 'Moderation', 'sm-rfq' ) . '</th><td><label><input type="checkbox" name="moderation" value="1"' . checked( $s['moderation'], 1, false ) . '> ' . esc_html__( 'Check every request before it is published', 'sm-rfq' ) . '</label></td></tr>';
		foreach ( $fields as $k => $l ) {
			echo '<tr><th><label for="smq-' . esc_attr( $k ) . '">' . esc_html( $l ) . '</label></th><td><input id="smq-' . esc_attr( $k ) . '" name="' . esc_attr( $k ) . '" type="number" min="0" step="any" value="' . esc_attr( (string) $s[ $k ] ) . '" class="regular-text"></td></tr>';
		}
		echo '</table>';
		submit_button();
		echo '</form>';
	}

	public static function save_settings() {
		if ( ! current_user_can( 'sm_manage_settings' ) ) {
			wp_die( esc_html__( 'You cannot change these settings.', 'sm-rfq' ) );
		}
		check_admin_referer( 'smq_settings' );
		Settings::save( wp_unslash( $_POST ) );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&tab=settings&done=1' ) );
		exit;
	}

	public static function moderate() {
		if ( ! current_user_can( 'sm_moderate' ) ) {
			wp_die( esc_html__( 'You cannot moderate requests.', 'sm-rfq' ) );
		}
		check_admin_referer( 'smq_moderate' );
		$r = Rfqs::get( absint( $_POST['id'] ?? 0 ) );
		if ( $r ) {
			if ( 'approve' === sanitize_key( $_POST['decision'] ?? '' ) ) {
				Rfqs::set_status( $r->id, 'open' );
				Notify::notify_company( $r->company_id, 'rfq.approved', array( 'rfq_id' => $r->id ) );
			} else {
				$reason = sanitize_text_field( wp_unslash( $_POST['reason'] ?? '' ) );
				Rfqs::set_status( $r->id, 'rejected', array( 'reject_reason' => $reason ) );
				Notify::notify_company( $r->company_id, 'rfq.rejected', array( 'rfq_id' => $r->id, 'reason' => $reason ) );
			}
		}
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&done=1' ) );
		exit;
	}
}

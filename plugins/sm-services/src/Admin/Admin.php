<?php
/**
 * wp-admin: service pages by status (moderation when enabled) and settings.
 *
 * @package SM\Services
 */

namespace SM\Services\Admin;

use SM\Core\Accounts\Companies;
use SM\Core\Content\Media;
use SM\Core\Content\Translations;
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
		add_action( 'admin_post_sms_admin_edit', array( __CLASS__, 'save_edit' ) );
	}

	public static function menu() {
		add_submenu_page( 'sm-core', __( 'Services', 'sm-services' ), __( 'Services', 'sm-services' ), 'sm_moderate', self::SLUG, array( __CLASS__, 'page' ) );
	}

	private static function labels() {
		return array(
			'pending'   => __( 'Waiting for review', 'sm-services' ),
			'revisions' => __( 'Changes to review', 'sm-services' ),
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
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$edit = absint( $_GET['edit'] ?? 0 );
		if ( $edit ) {
			self::edit_screen( $edit );
			echo '</div>';
			return;
		}
		$links = array();
		foreach ( self::labels() as $k => $l ) {
			$n       = Services::query( 'revisions' === $k ? array( 'status' => 'any', 'revision' => 1, 'per_page' => 1 ) : array( 'status' => $k, 'per_page' => 1 ) )['total'];
			$links[] = sprintf( '<li><a href="%s"%s>%s <span class="count">(%d)</span></a></li>', esc_url( $base . '&status=' . $k ), $k === $status ? ' class="current"' : '', esc_html( $l ), $n );
		}
		echo '<ul class="subsubsub">' . implode( ' | ', $links ) . '</ul><br class="clear">'; // phpcs:ignore WordPress.Security.EscapeOutput
		$status = isset( self::labels()[ $status ] ) ? $status : 'published';
		$q      = Services::query( 'revisions' === $status ? array( 'status' => 'any', 'revision' => 1, 'per_page' => 50 ) : array( 'status' => $status, 'per_page' => 50 ) );
		if ( ! $q['items'] ) {
			echo '<p>' . esc_html__( 'Nothing here.', 'sm-services' ) . '</p></div>';
			return;
		}
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Service', 'sm-services' ) . '</th><th>' . esc_html__( 'Provider', 'sm-services' ) . '</th><th>' . esc_html__( 'Catalogue', 'sm-services' ) . '</th><th>' . esc_html__( 'Views', 'sm-services' ) . '</th><th>' . esc_html__( 'Decision', 'sm-services' ) . '</th></tr></thead><tbody>';
		foreach ( $q['items'] as $r ) {
			$c = Companies::get( $r->company_id );
			echo '<tr><td><strong><a href="' . esc_url( \SM\Services\Routes::link( $r->id ) ) . '" target="_blank">' . esc_html( Presenter::title_text( $r ) ) . '</a></strong><br><small>#' . (int) $r->id . '</small></td>';
			echo '<td>' . esc_html( $c ? $c->name : '—' ) . '</td><td>' . esc_html( \SM\Services\Catalogue::kind( $r->kind )['label']['fr'] ?? $r->kind ) . '</td><td>' . (int) $r->views . '</td><td>';
			if ( 'revisions' === $status ) {
				echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
				wp_nonce_field( 'sms_moderate' );
				echo '<input type="hidden" name="action" value="sms_moderate"><input type="hidden" name="id" value="' . (int) $r->id . '">';
				echo '<button class="button button-primary" name="decision" value="approve_revision">' . esc_html__( 'Publish the changes', 'sm-services' ) . '</button> ';
				echo '<input name="reason" placeholder="' . esc_attr__( 'Reason (sent to the provider)', 'sm-services' ) . '" style="width:180px"> <button class="button" name="decision" value="reject_revision">' . esc_html__( 'Refuse the changes', 'sm-services' ) . '</button></form></td></tr>';
				continue;
			}
			echo '<p><a class="button button-small" href="' . esc_url( admin_url( 'admin.php?page=' . self::SLUG . '&edit=' . (int) $r->id ) ) . '">' . esc_html__( 'Edit text and image', 'sm-services' ) . '</a></p>';
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

	/** The platform edits the texts and the image of a service page (0.3.3), with a reason. */
	private static function edit_screen( $id ) {
		$r = Services::get( $id );
		if ( ! $r ) {
			echo '<p>' . esc_html__( 'Nothing here.', 'sm-services' ) . '</p>';
			return;
		}
		$t = Translations::get( 'service', $r->id );
		$c = Companies::get( $r->company_id );
		echo '<h2>' . esc_html( sprintf( /* translators: %s: service. */ __( 'Edit "%s"', 'sm-services' ), Presenter::title_text( $r ) ) ) . '</h2>';
		echo '<p>' . esc_html( sprintf( /* translators: %s: company. */ __( 'Provider: %s. The provider receives a notification and an e-mail with your reason and the list of changes.', 'sm-services' ), $c ? $c->name : '—' ) ) . '</p>';
		echo '<form method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="max-width:900px">';
		wp_nonce_field( 'sms_admin_edit_' . $r->id );
		echo '<input type="hidden" name="action" value="sms_admin_edit"><input type="hidden" name="id" value="' . (int) $r->id . '"><table class="form-table"><tbody>';
		$labels = array( 'description' => __( 'Description', 'sm-services' ), 'capabilities' => __( 'Capabilities', 'sm-services' ), 'equipment' => __( 'Equipment', 'sm-services' ) );
		foreach ( $labels as $k => $l ) {
			echo '<tr><th><label for="sms-ae-' . esc_attr( $k ) . '">' . esc_html( $l ) . '</label></th><td><textarea id="sms-ae-' . esc_attr( $k ) . '" class="large-text" rows="4" dir="auto" name="' . esc_attr( $k ) . '">' . esc_textarea( trim( implode( '', (array) ( $t[ $k ] ?? array() ) ) ) ) . '</textarea></td></tr>';
		}
		echo '<tr><th>' . esc_html__( 'Image', 'sm-services' ) . '</th><td>';
		foreach ( Media::get( 'service', $r->id, 'photo' ) as $aid ) {
			echo '<label style="display:inline-grid;gap:4px;justify-items:center;margin-inline-end:12px">' . wp_get_attachment_image( $aid, 'thumbnail' ) . '<span><input type="checkbox" name="remove_photo" value="1"> ' . esc_html__( 'Delete', 'sm-services' ) . '</span></label>';
		}
		echo '<p><label>' . esc_html__( 'Replace with', 'sm-services' ) . ' <input type="file" name="photo[]" accept="image/jpeg,image/png,image/webp"></label></p></td></tr>';
		echo '<tr><th><label for="sms-ae-reason">' . esc_html__( 'Reason (sent to the provider)', 'sm-services' ) . ' *</label></th><td><textarea id="sms-ae-reason" class="large-text" rows="3" name="reason" required></textarea></td></tr>';
		echo '</tbody></table>';
		submit_button( __( 'Save and inform the provider', 'sm-services' ), 'primary', 'submit', false );
		echo ' <a class="button" href="' . esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ) . '">' . esc_html__( 'Cancel', 'sm-services' ) . '</a></form>';
	}

	public static function save_edit() {
		$id = absint( $_POST['id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		check_admin_referer( 'sms_admin_edit_' . $id );
		if ( ! current_user_can( 'sm_moderate' ) ) {
			wp_die( '', 403 );
		}
		$r    = Services::get( $id );
		$back = admin_url( 'admin.php?page=' . self::SLUG . '&edit=' . $id );
		if ( ! $r ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG ) );
			exit;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$reason = trim( sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) ) );
		if ( '' === $reason ) {
			wp_safe_redirect( $back );
			exit;
		}
		$t       = Translations::get( 'service', $r->id );
		$texts   = $t;
		$changes = array();
		$names   = array( 'description' => __( 'description', 'sm-services' ), 'capabilities' => __( 'capabilities', 'sm-services' ), 'equipment' => __( 'equipment', 'sm-services' ) );
		foreach ( $names as $k => $label ) {
			$new = trim( sanitize_textarea_field( wp_unslash( $_POST[ $k ] ?? '' ) ) );
			if ( trim( implode( '', (array) ( $t[ $k ] ?? array() ) ) ) !== $new ) {
				$texts[ $k ] = preg_match_all( '/\p{Arabic}/u', $new ) > preg_match_all( '/\p{Latin}/u', $new ) ? array( 'ar' => $new, 'fr' => '' ) : array( 'fr' => $new, 'ar' => '' );
				$changes[]   = $label;
			}
		}
		$photos = Media::get( 'service', $r->id, 'photo' );
		if ( ! empty( $_POST['remove_photo'] ) ) {
			$photos    = array();
			$changes[] = __( 'image deleted', 'sm-services' );
		}
		// phpcs:enable
		if ( ! empty( $_FILES['photo']['name'][0] ) ) {
			$up = Media::upload( 'photo', Media::IMAGE_TYPES, 1, (int) Settings::get( 'photo_mb' ) * 1048576 );
			if ( $up['ids'] ) {
				$photos    = array( $up['ids'][0] );
				$changes[] = __( 'image replaced', 'sm-services' );
			}
		}
		if ( ! $changes ) {
			wp_safe_redirect( $back );
			exit;
		}
		Translations::set( 'service', $r->id, $texts );
		Media::set( 'service', $r->id, 'photo', $photos );
		\SM\Core\Audit\AuditLog::add( 'service.admin_edited', 'service', $r->id, array( 'reason' => $reason, 'changes' => $changes ), null, $r->company_id );
		Notify::notify_company( $r->company_id, 'service.admin_edited', array( 'service_id' => $r->id, 'reason' => $reason, 'changes' => implode( ', ', $changes ) ) );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&done=1' ) );
		exit;
	}

	public static function moderate() {
		if ( ! current_user_can( 'sm_moderate' ) ) {
			wp_die( esc_html__( 'You cannot moderate services.', 'sm-services' ) );
		}
		check_admin_referer( 'sms_moderate' );
		$r = Services::get( absint( $_POST['id'] ?? 0 ) );
		if ( $r ) {
			$decision = sanitize_key( $_POST['decision'] ?? '' );
			$reason   = sanitize_text_field( wp_unslash( $_POST['reason'] ?? '' ) );
			if ( 'approve_revision' === $decision ) {
				Services::approve_revision( $r->id );
				Notify::notify_company( $r->company_id, 'service.revision_approved', array( 'service_id' => $r->id ) );
				wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&status=revisions&done=1' ) );
				exit;
			}
			if ( 'reject_revision' === $decision ) {
				if ( '' === trim( $reason ) ) {
					wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&status=revisions' ) );
					exit;
				}
				Services::reject_revision( $r->id, $reason );
				Notify::notify_company( $r->company_id, 'service.revision_rejected', array( 'service_id' => $r->id, 'reason' => $reason ) );
				wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&status=revisions&done=1' ) );
				exit;
			}
			if ( 'approve' === $decision ) {
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

<?php
/**
 * wp-admin: limits of the building form (SM → Buildings). Categories, moderation
 * and listings are managed in SM → Products like the other markets.
 *
 * @package SM\Buildings
 */

namespace SM\Buildings\Admin;

use SM\Buildings\Settings;

defined( 'ABSPATH' ) || exit;

final class Admin {

	const SLUG = 'sm-buildings';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 30 );
		add_action( 'admin_post_smb_settings', array( __CLASS__, 'save' ) );
	}

	public static function menu() {
		add_submenu_page( 'sm-core', __( 'Buildings', 'sm-buildings' ), __( 'Buildings', 'sm-buildings' ), 'sm_manage_settings', self::SLUG, array( __CLASS__, 'page' ) );
	}

	public static function page() {
		$s      = Settings::all();
		$labels = array(
			'max_length_m' => __( 'Maximum length and width (m)', 'sm-buildings' ),
			'max_height_m' => __( 'Maximum eaves height (m)', 'sm-buildings' ),
			'max_portals'  => __( 'Maximum number of portal frames', 'sm-buildings' ),
			'max_lines'    => __( 'Maximum rows in the element inventory', 'sm-buildings' ),
		);
		echo '<div class="wrap"><h1>' . esc_html__( 'Steel buildings', 'sm-buildings' ) . '</h1>';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['saved'] ) ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'sm-buildings' ) . '</p></div>';
		}
		echo '<p>' . esc_html__( 'Categories, moderation and listings of buildings are managed in SM → Products, with the other markets. Here: the limits of the publication form.', 'sm-buildings' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="smb_settings">';
		wp_nonce_field( 'smb_settings' );
		echo '<table class="form-table">';
		foreach ( $labels as $k => $l ) {
			echo '<tr><th><label for="smb-' . esc_attr( $k ) . '">' . esc_html( $l ) . '</label></th><td><input id="smb-' . esc_attr( $k ) . '" name="' . esc_attr( $k ) . '" type="number" min="1" step="any" value="' . esc_attr( (string) $s[ $k ] ) . '" class="small-text"></td></tr>';
		}
		echo '</table>';
		submit_button();
		echo '</form></div>';
	}

	public static function save() {
		if ( ! current_user_can( 'sm_manage_settings' ) ) {
			wp_die( esc_html__( 'You cannot change these settings.', 'sm-buildings' ) );
		}
		check_admin_referer( 'smb_settings' );
		Settings::save( wp_unslash( $_POST ) );
		\SM\Core\Audit\AuditLog::add( 'buildings.settings', 'settings', 0, array() );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&saved=1' ) );
		exit;
	}
}

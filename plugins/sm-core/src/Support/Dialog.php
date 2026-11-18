<?php
/**
 * Centred messages (0.8.6): the dialog script and styles, for the site (the theme
 * enqueues the "sm-dialog" handle) and the ReuSteel pages of the administration.
 *
 * @package SM\Core
 */

namespace SM\Core\Support;

defined( 'ABSPATH' ) || exit;

final class Dialog {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register' ), 5 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin' ), 5 );
	}

	/** Words of the dialog, in the visitor's language. */
	public static function words( $admin_flash = false ) {
		return array(
			'ok'         => __( 'OK', 'sm-core' ),
			'cancel'     => __( 'Cancel', 'sm-core' ),
			'confirm'    => __( 'Confirm', 'sm-core' ),
			'understood' => __( 'Understood', 'sm-core' ),
			'complete'   => __( 'Complete', 'sm-core' ),
			'dismiss'    => __( 'Dismiss this notice.' ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- WordPress' own words.
			'adminFlash' => (bool) $admin_flash,
			// Test sites only (filter): questions answered "yes" and messages left as banners.
			'auto'       => (bool) apply_filters( 'sm_dialog_auto', false ),
		);
	}

	public static function register( $admin_flash = false ) {
		$v = SM_CORE_VERSION . '-' . (int) @filemtime( SM_CORE_DIR . '/assets/js/sm-dialog.js' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		wp_register_style( 'sm-dialog', SM_CORE_URL . 'assets/css/sm-dialog.css', array(), $v );
		wp_register_script( 'sm-dialog', SM_CORE_URL . 'assets/js/sm-dialog.js', array(), $v, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_localize_script( 'sm-dialog', 'smDialogI18n', self::words( true === $admin_flash ) );
	}

	/** On the ReuSteel pages of the administration: questions and notices in the centre. */
	public static function admin( $hook ) {
		if ( false === strpos( (string) $hook, 'sm-' ) && false === strpos( (string) $hook, '_sm_' ) ) {
			return;
		}
		self::register( true );
		wp_enqueue_style( 'sm-dialog' );
		wp_enqueue_script( 'sm-dialog' );
	}
}

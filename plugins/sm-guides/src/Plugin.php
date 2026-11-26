<?php
/**
 * Bootstrap.
 *
 * @package SM\Guides
 */

namespace SM\Guides;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/** @param \SM\Core\Modules $registry */
	public static function register( $registry ) {
		$ok = $registry->register(
			'sm-guides',
			array(
				'version'       => SM_GUIDES_VERSION,
				'requires_core' => '0.5.0',
				'label'         => 'SM Guides',
				'capabilities'  => array(),
			)
		);
		if ( ! $ok ) {
			return;
		}
		load_plugin_textdomain( 'sm-guides', false, dirname( plugin_basename( SM_GUIDES_FILE ) ) . '/languages' );
		add_action( 'sm_language_switched', array( __CLASS__, 'reload_textdomain' ), 10, 2 );
		Install\Installer::maybe_upgrade();
		Pages::init();
		Faq::init();
		if ( is_admin() ) {
			Admin\Editor::init();
			Admin\FaqEditor::init();
		}
	}

	public static function reload_textdomain( $code, $locale ) {
		unload_textdomain( 'sm-guides', true );
		$mo = SM_GUIDES_DIR . '/languages/sm-guides-' . $locale . '.mo';
		if ( is_readable( $mo ) ) {
			load_textdomain( 'sm-guides', $mo, $locale );
		}
	}
}

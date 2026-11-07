<?php
/**
 * Bootstrap.
 *
 * @package SM\Reuse
 */

namespace SM\Reuse;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/** @param \SM\Core\Modules $registry */
	public static function register( $registry ) {
		if ( ! defined( 'SM_PRODUCTS_VERSION' ) || version_compare( SM_PRODUCTS_VERSION, '0.2.0', '<' ) ) {
			return;
		}
		$ok = $registry->register(
			'sm-reuse',
			array(
				'version'       => SM_REUSE_VERSION,
				'requires_core' => '0.3.0',
				'label'         => 'SM Reuse',
				'capabilities'  => array( 'listing.reuse' ),
			)
		);
		if ( ! $ok ) {
			return;
		}
		load_plugin_textdomain( 'sm-reuse', false, dirname( plugin_basename( SM_REUSE_FILE ) ) . '/languages' );
		add_action( 'sm_language_switched', array( __CLASS__, 'reload_textdomain' ), 10, 2 );

		Install\Installer::maybe_upgrade();
		Market::init();
		Rules::init();
		Advice::init();
	}

	public static function reload_textdomain( $code, $locale ) {
		unload_textdomain( 'sm-reuse', true );
		$mo = SM_REUSE_DIR . '/languages/sm-reuse-' . $locale . '.mo';
		if ( is_readable( $mo ) ) {
			load_textdomain( 'sm-reuse', $mo, $locale );
		}
	}
}

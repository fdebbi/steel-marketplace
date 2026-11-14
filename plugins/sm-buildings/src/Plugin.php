<?php
/**
 * Bootstrap.
 *
 * @package SM\Buildings
 */

namespace SM\Buildings;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/** @param \SM\Core\Modules $registry */
	public static function register( $registry ) {
		if ( ! defined( 'SM_PRODUCTS_VERSION' ) || version_compare( SM_PRODUCTS_VERSION, '0.14.0', '<' ) ) {
			return;
		}
		$ok = $registry->register(
			'sm-buildings',
			array(
				'version'       => SM_BUILDINGS_VERSION,
				'requires_core' => '0.3.0',
				'label'         => 'SM Buildings',
				'capabilities'  => array( 'listing.buildings' ),
			)
		);
		if ( ! $ok ) {
			return;
		}
		load_plugin_textdomain( 'sm-buildings', false, dirname( plugin_basename( SM_BUILDINGS_FILE ) ) . '/languages' );
		add_action( 'sm_language_switched', array( __CLASS__, 'reload_textdomain' ), 10, 2 );

		Market::init();
		Spec::init();
		Rules::init();
		Proposal::init();
		Sale::init();
		Install\Installer::maybe_upgrade(); // After the market is registered: the 0.4.0 migration re-indexes listings.
		Presenter::init();
		if ( is_admin() ) {
			Admin\Admin::init();
		}
	}

	public static function reload_textdomain( $code, $locale ) {
		unload_textdomain( 'sm-buildings', true );
		$mo = SM_BUILDINGS_DIR . '/languages/sm-buildings-' . $locale . '.mo';
		if ( is_readable( $mo ) ) {
			load_textdomain( 'sm-buildings', $mo, $locale );
		}
	}
}

<?php
/**
 * Bootstrap.
 *
 * @package SM\Products
 */

namespace SM\Products;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/** @param \SM\Core\Modules $registry */
	public static function register( $registry ) {
		if ( ! defined( 'SM_WEIGHT_VERSION' ) ) {
			return;
		}
		$ok = $registry->register(
			'sm-products',
			array(
				'version'       => SM_PRODUCTS_VERSION,
				'requires_core' => '0.3.0',
				'label'         => 'SM Products',
				'capabilities'  => array( 'listing.products' ),
			)
		);
		if ( ! $ok ) {
			return;
		}
		load_plugin_textdomain( 'sm-products', false, dirname( plugin_basename( SM_PRODUCTS_FILE ) ) . '/languages' );
		add_action( 'sm_language_switched', array( __CLASS__, 'reload_textdomain' ), 10, 2 );

		Install\Installer::maybe_upgrade();
		Notifications::init();
		Activity::init();
		Form::init();
		Expiry::init();
		Visits::init();
		Theme\Bridge::init();
		Rest\Api::init();
		if ( is_admin() ) {
			Admin\Admin::init();
		}
	}

	public static function reload_textdomain( $code, $locale ) {
		unload_textdomain( 'sm-products', true );
		$mo = SM_PRODUCTS_DIR . '/languages/sm-products-' . $locale . '.mo';
		if ( is_readable( $mo ) ) {
			load_textdomain( 'sm-products', $mo, $locale );
		}
	}
}

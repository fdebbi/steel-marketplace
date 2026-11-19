<?php
/**
 * Bootstrap.
 *
 * @package SM\Stock
 */

namespace SM\Stock;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/** @param \SM\Core\Modules $registry */
	public static function register( $registry ) {
		if ( ! defined( 'SM_PRODUCTS_VERSION' ) || version_compare( SM_PRODUCTS_VERSION, '0.20.0', '<' ) ) {
			return;
		}
		$ok = $registry->register(
			'sm-stock',
			array(
				'version'       => SM_STOCK_VERSION,
				'requires_core' => '0.5.0',
				'label'         => 'SM Stock',
				'capabilities'  => array(),
			)
		);
		if ( ! $ok ) {
			return;
		}
		load_plugin_textdomain( 'sm-stock', false, dirname( plugin_basename( SM_STOCK_FILE ) ) . '/languages' );
		add_action( 'sm_language_switched', array( __CLASS__, 'reload_textdomain' ), 10, 2 );
		Install\Installer::maybe_upgrade();
		Moves::init();
		Limits::init();
		Prices::init();
		Actions::init();
		Cutting::init();
		Theme\Bridge::init();
		add_filter( 'sm_dashboard_view_slugs', static fn( $slugs ) => array_merge( $slugs, array( 'stock' => 'magasin', 'stock-reuse' => 'magasin-reemploi', 'stock-moves' => 'magasin-mouvements', 'stock-prices' => 'magasin-prix' ) ) );
		add_filter( 'sm_activity_labels', static fn( $l ) => $l + array( 'product.stock' => __( 'Item kept in the warehouse', 'sm-stock' ) ) );
	}

	public static function reload_textdomain( $code, $locale ) {
		unload_textdomain( 'sm-stock', true );
		$mo = SM_STOCK_DIR . '/languages/sm-stock-' . $locale . '.mo';
		if ( is_readable( $mo ) ) {
			load_textdomain( 'sm-stock', $mo, $locale );
		}
	}
}

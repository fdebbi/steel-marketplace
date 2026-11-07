<?php
/**
 * Plugin Name:       SM Products
 * Plugin URI:        https://github.com/fdebbi/steel-marketplace
 * Description:       New steel products market: publication by category with category-specific fields (profiles from the European catalogue, tubes, plates, bars, roofing, finishing parts), weights from SM Weight Engine, photos and technical sheets, moderation, public listings and seller dashboard. Requires SM Core and SM Weight Engine.
 * Version:           0.3.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Requires Plugins:  sm-core, sm-weight-engine
 * Author:            Steel Marketplace
 * License:           Proprietary
 * Text Domain:       sm-products
 * Domain Path:       /languages
 *
 * @package SM\Products
 */

defined( 'ABSPATH' ) || exit;

define( 'SM_PRODUCTS_VERSION', '0.3.0' );
define( 'SM_PRODUCTS_DB_VERSION', 3 );
define( 'SM_PRODUCTS_FILE', __FILE__ );
define( 'SM_PRODUCTS_DIR', __DIR__ );

spl_autoload_register(
	static function ( $class ) {
		$prefix = 'SM\\Products\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}
		$file = SM_PRODUCTS_DIR . '/src/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

register_activation_hook( __FILE__, array( 'SM\\Products\\Install\\Installer', 'activate' ) );
add_action( 'sm_register_modules', array( 'SM\\Products\\Plugin', 'register' ) );
add_action(
	'plugins_loaded',
	static function () {
		if ( ! defined( 'SM_CORE_VERSION' ) || ! defined( 'SM_WEIGHT_VERSION' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'SM Products needs the SM Core and SM Weight Engine plugins. Activate them first.', 'sm-products' ) . '</p></div>';
				}
			);
		}
	},
	20
);

<?php
/**
 * Plugin Name:       SM Stock
 * Plugin URI:        https://github.com/fdebbi/steel-marketplace
 * Description:       Warehouse of companies and professionals: every steel item they own, published on the market or kept in stock only (new and reused), with a reference and a location for each variant, quantity corrections, the history of movements (sales accepted on the platform are taken off automatically), prices changed for a whole type, printable labels with a QR code, and limits per plan. Requires SM Core and SM Products.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Requires Plugins:  sm-core, sm-products
 * Author:            ReuSteel
 * License:           Proprietary
 * Text Domain:       sm-stock
 * Domain Path:       /languages
 *
 * @package SM\Stock
 */

defined( 'ABSPATH' ) || exit;

define( 'SM_STOCK_VERSION', '0.1.0' );
define( 'SM_STOCK_DB_VERSION', 1 );
define( 'SM_STOCK_FILE', __FILE__ );
define( 'SM_STOCK_DIR', __DIR__ );

spl_autoload_register(
	static function ( $class ) {
		$prefix = 'SM\\Stock\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}
		$file = SM_STOCK_DIR . '/src/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

register_activation_hook( __FILE__, array( 'SM\\Stock\\Install\\Installer', 'activate' ) );
add_action( 'sm_register_modules', array( 'SM\\Stock\\Plugin', 'register' ), 35 );
add_action(
	'plugins_loaded',
	static function () {
		if ( ! defined( 'SM_CORE_VERSION' ) || ! defined( 'SM_PRODUCTS_VERSION' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'SM Stock needs the SM Core and SM Products plugins. Activate them first.', 'sm-stock' ) . '</p></div>';
				}
			);
		}
	},
	20
);

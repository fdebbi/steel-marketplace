<?php
/**
 * Plugin Name:       SM RFQ
 * Plugin URI:        https://github.com/fdebbi/steel-marketplace
 * Description:       Requests for quotation: buyers describe what they need (profile rows weighed by SM Weight Engine or free description, quantity, delivery place and date, drawings); suppliers and manufacturers answer with a price, a price per kg and a delivery time; the buyer compares the offers, contacts suppliers and accepts one. Notifications in French and Arabic. Requires SM Core, SM Weight Engine and SM Products.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Requires Plugins:  sm-core, sm-weight-engine, sm-products
 * Author:            ReuSteel
 * License:           Proprietary
 * Text Domain:       sm-rfq
 * Domain Path:       /languages
 *
 * @package SM\Rfq
 */

defined( 'ABSPATH' ) || exit;

define( 'SM_RFQ_VERSION', '0.1.0' );
define( 'SM_RFQ_DB_VERSION', 1 );
define( 'SM_RFQ_FILE', __FILE__ );
define( 'SM_RFQ_DIR', __DIR__ );

spl_autoload_register(
	static function ( $class ) {
		$prefix = 'SM\\Rfq\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}
		$file = SM_RFQ_DIR . '/src/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

register_activation_hook( __FILE__, array( 'SM\\Rfq\\Install\\Installer', 'activate' ) );
add_action( 'sm_register_modules', array( 'SM\\Rfq\\Plugin', 'register' ), 20 );
add_action(
	'plugins_loaded',
	static function () {
		if ( ! defined( 'SM_CORE_VERSION' ) || ! defined( 'SM_PRODUCTS_VERSION' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'SM RFQ needs the SM Core, SM Weight Engine and SM Products plugins. Activate them first.', 'sm-rfq' ) . '</p></div>';
				}
			);
		}
	},
	20
);

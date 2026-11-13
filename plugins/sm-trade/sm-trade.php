<?php
/**
 * Plugin Name:       SM Trade
 * Plugin URI:        https://github.com/fdebbi/steel-marketplace
 * Description:       Orders and offers between buyers and sellers: "Buy" at the listed price (whole lot or a quantity), "Make an offer" with counter-offers, acceptance by the seller, stock taken from the listing, contact details shared once accepted, history for the buyer, the seller and the administration (CSV export). Payment and delivery are settled directly between buyer and seller. Requires SM Core and SM Products 0.10.0.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Requires Plugins:  sm-core, sm-products
 * Author:            ReuSteel
 * License:           Proprietary
 * Text Domain:       sm-trade
 * Domain Path:       /languages
 *
 * @package SM\Trade
 */

defined( 'ABSPATH' ) || exit;

define( 'SM_TRADE_VERSION', '0.1.0' );
define( 'SM_TRADE_DB_VERSION', 1 );
define( 'SM_TRADE_FILE', __FILE__ );
define( 'SM_TRADE_DIR', __DIR__ );

spl_autoload_register(
	static function ( $class ) {
		$prefix = 'SM\\Trade\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}
		$file = SM_TRADE_DIR . '/src/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

register_activation_hook( __FILE__, array( 'SM\\Trade\\Install\\Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SM\\Trade\\Cron', 'unschedule' ) );
add_action( 'sm_register_modules', array( 'SM\\Trade\\Plugin', 'register' ), 26 );
add_action(
	'plugins_loaded',
	static function () {
		if ( ! defined( 'SM_CORE_VERSION' ) || ! defined( 'SM_PRODUCTS_VERSION' ) || version_compare( SM_PRODUCTS_VERSION, '0.10.0', '<' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'SM Trade needs SM Core and SM Products 0.10.0 or later. Activate or update them first.', 'sm-trade' ) . '</p></div>';
				}
			);
		}
	},
	20
);

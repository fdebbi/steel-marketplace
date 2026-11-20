<?php
/**
 * Plugin Name:       SM Insights
 * Plugin URI:        https://github.com/fdebbi/steel-marketplace
 * Description:       Business report of the steel market (Business plan): average asked prices per type of profile, new and reused, steel put on sale (new, reuse, buildings), the most searched and best-selling profiles (ranking only), what is in demand and little offered, figures per region, and a summary written by the AI from these figures. Only asked prices, weights on sale and counts of listings, searches and requests: no company name, no quantity or amount of sale. Requires SM Core and SM Products.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Requires Plugins:  sm-core, sm-products
 * Author:            ReuSteel
 * License:           Proprietary
 * Text Domain:       sm-insights
 * Domain Path:       /languages
 *
 * @package SM\Insights
 */

defined( 'ABSPATH' ) || exit;

define( 'SM_INSIGHTS_VERSION', '0.1.0' );
define( 'SM_INSIGHTS_DB_VERSION', 1 );
define( 'SM_INSIGHTS_FILE', __FILE__ );
define( 'SM_INSIGHTS_DIR', __DIR__ );

spl_autoload_register(
	static function ( $class ) {
		$prefix = 'SM\\Insights\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}
		$file = SM_INSIGHTS_DIR . '/src/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

register_activation_hook( __FILE__, array( 'SM\\Insights\\Install\\Installer', 'activate' ) );
add_action( 'sm_register_modules', array( 'SM\\Insights\\Plugin', 'register' ), 60 );
add_action(
	'plugins_loaded',
	static function () {
		if ( ! defined( 'SM_CORE_VERSION' ) || ! defined( 'SM_PRODUCTS_VERSION' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'SM Insights needs the SM Core and SM Products plugins. Activate them first.', 'sm-insights' ) . '</p></div>';
				}
			);
		}
	},
	20
);

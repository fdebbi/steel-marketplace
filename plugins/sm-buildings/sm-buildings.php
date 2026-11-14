<?php
/**
 * Plugin Name:       SM Buildings
 * Plugin URI:        https://github.com/fdebbi/steel-marketplace
  * Description:       Steel buildings market: new and used warehouses, agricultural and industrial hangars, workshops, livestock buildings and building kits, assembled or dismantled. Adds the "buildings" market to the SM Products listing engine: dimensions and area, structural system, roof and cladding, what the price includes, structural element inventory weighed by SM Weight Engine, price per kg and per m², indicative drawing. Requires SM Core, SM Weight Engine and SM Products 0.14.0.
 * Version:           0.4.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Requires Plugins:  sm-core, sm-weight-engine, sm-products
 * Author:            ReuSteel
 * License:           Proprietary
 * Text Domain:       sm-buildings
 * Domain Path:       /languages
 *
 * @package SM\Buildings
 */

defined( 'ABSPATH' ) || exit;

define( 'SM_BUILDINGS_VERSION', '0.4.0' );
define( 'SM_BUILDINGS_DB_VERSION', 2 );
define( 'SM_BUILDINGS_FILE', __FILE__ );
define( 'SM_BUILDINGS_DIR', __DIR__ );

spl_autoload_register(
	static function ( $class ) {
		$prefix = 'SM\\Buildings\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}
		$file = SM_BUILDINGS_DIR . '/src/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

// After sm-products (priority 10), whose tables and Markets class this plugin uses.
add_action( 'sm_register_modules', array( 'SM\\Buildings\\Plugin', 'register' ), 20 );
add_action(
	'plugins_loaded',
	static function () {
		if ( ! defined( 'SM_CORE_VERSION' ) || ! defined( 'SM_PRODUCTS_VERSION' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'SM Buildings needs the SM Core, SM Weight Engine and SM Products plugins. Activate them first.', 'sm-buildings' ) . '</p></div>';
				}
			);
		}
	},
	20
);

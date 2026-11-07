<?php
/**
 * Plugin Name:       SM Reuse
 * Plugin URI:        https://github.com/fdebbi/steel-marketplace
 * Description:       Steel reuse market: offcuts, dismantled profiles, used tubes, plate offcuts, mixed lots and scrap. Adds the "reuse" market to the SM Products listing engine (condition scale, origin, surface state, cutting and loading), seeds its categories and adds the engineer advice on reuse listings. Requires SM Core, SM Weight Engine and SM Products.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Requires Plugins:  sm-core, sm-weight-engine, sm-products
 * Author:            ReuSteel
 * License:           Proprietary
 * Text Domain:       sm-reuse
 * Domain Path:       /languages
 *
 * @package SM\Reuse
 */

defined( 'ABSPATH' ) || exit;

define( 'SM_REUSE_VERSION', '0.1.0' );
define( 'SM_REUSE_DB_VERSION', 1 );
define( 'SM_REUSE_FILE', __FILE__ );
define( 'SM_REUSE_DIR', __DIR__ );

spl_autoload_register(
	static function ( $class ) {
		$prefix = 'SM\\Reuse\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}
		$file = SM_REUSE_DIR . '/src/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

// After sm-products (priority 10), whose tables and Markets class this plugin uses.
add_action( 'sm_register_modules', array( 'SM\\Reuse\\Plugin', 'register' ), 20 );
add_action(
	'plugins_loaded',
	static function () {
		if ( ! defined( 'SM_CORE_VERSION' ) || ! defined( 'SM_PRODUCTS_VERSION' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'SM Reuse needs the SM Core, SM Weight Engine and SM Products plugins. Activate them first.', 'sm-reuse' ) . '</p></div>';
				}
			);
		}
	},
	20
);

<?php
/**
 * Plugin Name:       SM Engineering
 * Plugin URI:        https://github.com/fdebbi/steel-marketplace
 * Description:       Partner engineers and engineering missions. Engineers apply on their own page and are approved by the platform; every study or inspection request goes to the platform, which sends a quote to the client and gives the mission to an approved engineer as a subcontractor. Clients never deal with engineers directly. Requires SM Core.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Requires Plugins:  sm-core
 * Author:            ReuSteel
 * License:           Proprietary
 * Text Domain:       sm-engineering
 * Domain Path:       /languages
 *
 * @package SM\Engineering
 */

defined( 'ABSPATH' ) || exit;

define( 'SM_ENGINEERING_VERSION', '0.1.0' );
define( 'SM_ENGINEERING_DB_VERSION', 1 );
define( 'SM_ENGINEERING_FILE', __FILE__ );
define( 'SM_ENGINEERING_DIR', __DIR__ );

spl_autoload_register(
	static function ( $class ) {
		$prefix = 'SM\\Engineering\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}
		$file = SM_ENGINEERING_DIR . '/src/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

register_activation_hook( __FILE__, array( 'SM\\Engineering\\Install\\Installer', 'activate' ) );
add_action( 'sm_register_modules', array( 'SM\\Engineering\\Plugin', 'register' ), 20 );
add_action(
	'plugins_loaded',
	static function () {
		if ( ! defined( 'SM_CORE_VERSION' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'SM Engineering needs the SM Core plugin. Activate it first.', 'sm-engineering' ) . '</p></div>';
				}
			);
		}
	},
	20
);

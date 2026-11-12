<?php
/**
 * Plugin Name:       SM Services
 * Plugin URI:        https://github.com/fdebbi/steel-marketplace
 * Description:       Fabrication and site services of companies and professionals: service pages with photos, capabilities, equipment and coverage, service requests answered with a quote, portfolio of completed projects. Engineering studies are handled by SM Engineering. Requires SM Core.
 * Version:           0.3.6
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Requires Plugins:  sm-core
 * Author:            ReuSteel
 * License:           Proprietary
 * Text Domain:       sm-services
 * Domain Path:       /languages
 *
 * @package SM\Services
 */

defined( 'ABSPATH' ) || exit;

define( 'SM_SERVICES_VERSION', '0.3.6' );
define( 'SM_SERVICES_DB_VERSION', 4 );
define( 'SM_SERVICES_FILE', __FILE__ );
define( 'SM_SERVICES_DIR', __DIR__ );

spl_autoload_register(
	static function ( $class ) {
		$prefix = 'SM\\Services\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}
		$file = SM_SERVICES_DIR . '/src/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

register_activation_hook( __FILE__, array( 'SM\\Services\\Install\\Installer', 'activate' ) );
add_action( 'sm_register_modules', array( 'SM\\Services\\Plugin', 'register' ), 20 );
add_action(
	'plugins_loaded',
	static function () {
		if ( ! defined( 'SM_CORE_VERSION' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'SM Services needs the SM Core plugin. Activate it first.', 'sm-services' ) . '</p></div>';
				}
			);
		}
	},
	20
);

<?php
/**
 * Plugin Name:       SM Weight Engine
 * Plugin URI:        https://github.com/fdebbi/steel-marketplace
 * Description:       Steel section catalogue (IPE, HEA, HEB, HEM, IPN, UPN, UPE, angles, tubes, flats, bars, plates) with sourced and cross-checked reference data, exact cross-section drawings and theoretical weight calculations (bars <-> total weight). Requires SM Core.
 * Version:           0.2.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Requires Plugins:  sm-core
 * Author:            Steel Marketplace
 * License:           Proprietary
 * Text Domain:       sm-weight-engine
 * Domain Path:       /languages
 *
 * @package SM\WeightEngine
 */

defined( 'ABSPATH' ) || exit;

define( 'SM_WEIGHT_VERSION', '0.2.0' );
define( 'SM_WEIGHT_DB_VERSION', 1 );
/** Version of the bundled reference data (data/sections.php). Stored with every calculation. */
define( 'SM_WEIGHT_REF_VERSION', '2026.11.1' );
define( 'SM_WEIGHT_FILE', __FILE__ );
define( 'SM_WEIGHT_DIR', __DIR__ );

spl_autoload_register(
	static function ( $class ) {
		$prefix = 'SM\\WeightEngine\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}
		$file = SM_WEIGHT_DIR . '/src/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

require_once SM_WEIGHT_DIR . '/includes/functions.php';

register_activation_hook( __FILE__, array( 'SM\\WeightEngine\\Install\\Installer', 'activate' ) );

/*
 * Boot after sm-core (which registers modules on plugins_loaded, priority 5).
 * Without sm-core the plugin stays idle and shows a notice.
 */
add_action( 'sm_register_modules', array( 'SM\\WeightEngine\\Plugin', 'register' ) );
add_action(
	'plugins_loaded',
	static function () {
		if ( ! defined( 'SM_CORE_VERSION' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'SM Weight Engine needs the SM Core plugin. Activate SM Core first.', 'sm-weight-engine' ) . '</p></div>';
				}
			);
		}
	},
	20
);

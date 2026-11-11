<?php
/**
 * Plugin Name:       SM Core
 * Plugin URI:        https://github.com/fdebbi/steel-marketplace
 * Description:       Central foundation of Steel Marketplace: accounts and companies, business activities, permissions, verification, languages (/ar/), notifications, enquiries, e-mail alerts with editable templates, activity journal, contact form and the sm/v1 REST API. Every other sm-* plugin depends on it.
 * Version:           0.7.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Steel Marketplace
 * License:           Proprietary
 * Text Domain:       sm-core
 * Domain Path:       /languages
 *
 * @package SM\Core
 */

defined( 'ABSPATH' ) || exit;

define( 'SM_CORE_VERSION', '0.7.0' );
define( 'SM_CORE_DB_VERSION', 6 );
define( 'SM_CORE_FILE', __FILE__ );
define( 'SM_CORE_DIR', __DIR__ );
define( 'SM_CORE_URL', plugin_dir_url( __FILE__ ) );

/*
 * PSR-4 autoloader for SM\Core\ -> src/. Composer is optional: Hostinger shared
 * hosting does not always offer it, so the plugin ships its own loader.
 */
spl_autoload_register(
	static function ( $class ) {
		$prefix = 'SM\\Core\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}
		$file = SM_CORE_DIR . '/src/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

require_once SM_CORE_DIR . '/includes/functions.php';

// Language routing must run before WordPress parses the request.
SM\Core\I18n\Language::early_boot();

register_activation_hook( __FILE__, array( 'SM\\Core\\Install\\Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SM\\Core\\Install\\Installer', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'SM\\Core\\Plugin', 'boot' ), 5 );

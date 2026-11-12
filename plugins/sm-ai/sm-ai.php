<?php
/**
 * Plugin Name:       SM AI
 * Plugin URI:        https://github.com/fdebbi/steel-marketplace
 * Description:       AI assistance services, independent of the provider (Anthropic Claude, OpenAI-compatible APIs): listing assistant, steel profile recognition on photos, smart search, document analysis. Encrypted API keys, quotas, monthly budget, usage and cost log. Suggestions only: weights come from the weight engine, and AI never replaces engineering. Requires SM Core.
 * Version:           0.1.3
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Requires Plugins:  sm-core
 * Author:            ReuSteel
 * License:           Proprietary
 * Text Domain:       sm-ai
 * Domain Path:       /languages
 *
 * @package SM\Ai
 */

defined( 'ABSPATH' ) || exit;

define( 'SM_AI_VERSION', '0.1.3' );
define( 'SM_AI_DB_VERSION', 1 );
define( 'SM_AI_FILE', __FILE__ );
define( 'SM_AI_DIR', __DIR__ );

spl_autoload_register(
	static function ( $class ) {
		$prefix = 'SM\\Ai\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}
		$file = SM_AI_DIR . '/src/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

register_activation_hook( __FILE__, array( 'SM\\Ai\\Install\\Installer', 'activate' ) );
add_action( 'sm_register_modules', array( 'SM\\Ai\\Plugin', 'register' ), 30 );
add_action(
	'plugins_loaded',
	static function () {
		if ( ! defined( 'SM_CORE_VERSION' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'SM AI needs the SM Core plugin. Activate it first.', 'sm-ai' ) . '</p></div>';
				}
			);
		}
	},
	20
);

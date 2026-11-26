<?php
/**
 * Plugin Name:       SM Guides
 * Plugin URI:        https://github.com/fdebbi/steel-marketplace
 * Description:       Practical guides of ReuSteel in French, Arabic and English (buying reused steel, used steel buildings, ordering bars to cut less, selling offcuts): written in the administration, shown at /guides/ with a table of contents and links to the tools of the site. Frequently asked questions on how the site works, shown at /faq/ and at the bottom of the pages they concern. Methods and how the site works only: no market price, no statistics. Requires SM Core.
 * Version:           0.2.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Requires Plugins:  sm-core
 * Author:            ReuSteel
 * License:           Proprietary
 * Text Domain:       sm-guides
 * Domain Path:       /languages
 *
 * @package SM\Guides
 */

defined( 'ABSPATH' ) || exit;

define( 'SM_GUIDES_VERSION', '0.2.0' );
define( 'SM_GUIDES_SEED', 1 ); // Version of the guides written at installation (data/guides).
define( 'SM_GUIDES_FAQ_SEED', 1 ); // Version of the questions written at installation (data/faq), 0.2.0.
define( 'SM_GUIDES_FILE', __FILE__ );
define( 'SM_GUIDES_DIR', __DIR__ );

spl_autoload_register(
	static function ( $class ) {
		$prefix = 'SM\\Guides\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}
		$file = SM_GUIDES_DIR . '/src/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

register_activation_hook( __FILE__, array( 'SM\\Guides\\Install\\Installer', 'activate' ) );
add_action( 'init', array( 'SM\\Guides\\Guides', 'register_type' ), 5 );
add_action( 'init', array( 'SM\\Guides\\Faq', 'register_type' ), 5 );
add_action( 'sm_register_modules', array( 'SM\\Guides\\Plugin', 'register' ), 60 );
add_action(
	'plugins_loaded',
	static function () {
		if ( ! defined( 'SM_CORE_VERSION' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'SM Guides needs the SM Core plugin. Activate it first.', 'sm-guides' ) . '</p></div>';
				}
			);
		}
	},
	20
);

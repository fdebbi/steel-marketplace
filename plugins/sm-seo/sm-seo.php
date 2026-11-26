<?php
/**
 * Plugin Name:       SM SEO
 * Plugin URI:        https://github.com/fdebbi/steel-marketplace
 * Description:       Search engines and AI search: title and description of each page from the real data, canonical address, pages kept out of the index (account, search with filters, expired listings), XML sitemaps of the listings, companies, categories and services in French, Arabic and English, structured data (product and asked price, company, engineering services, frequently asked questions, breadcrumb), link previews, robots.txt and llms.txt. Never a review, a rating, a market price or a data source. Requires SM Core.
 * Version:           0.5.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Requires Plugins:  sm-core
 * Author:            ReuSteel
 * License:           Proprietary
 * Text Domain:       sm-seo
 * Domain Path:       /languages
 *
 * @package SM\Seo
 */

defined( 'ABSPATH' ) || exit;

define( 'SM_SEO_VERSION', '0.5.0' );
define( 'SM_SEO_FILE', __FILE__ );
define( 'SM_SEO_DIR', __DIR__ );

spl_autoload_register(
	static function ( $class ) {
		$prefix = 'SM\\Seo\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}
		$file = SM_SEO_DIR . '/src/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

add_action( 'sm_register_modules', array( 'SM\\Seo\\Plugin', 'register' ), 70 );
add_action(
	'plugins_loaded',
	static function () {
		if ( ! defined( 'SM_CORE_VERSION' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'SM SEO needs the SM Core plugin. Activate it first.', 'sm-seo' ) . '</p></div>';
				}
			);
		}
	},
	20
);

<?php
/**
 * Plugin Name:       SM Subscriptions
 * Plugin URI:        https://github.com/fdebbi/steel-marketplace
 * Description:       Plans of companies and professionals (limits on listings, tonnage, buildings, service pages, completed projects, quotation answers, AI credits, visible contacts, featured), AI credit packs and paid listing options. Payment outside the site (transfer, CCP, BaridiMob, cheque) with proof of payment, activated by the platform team, with an invoice. Founding members offer. Requires SM Core.
 * Version:           0.2.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Requires Plugins:  sm-core
 * Author:            ReuSteel
 * License:           Proprietary
 * Text Domain:       sm-subscriptions
 * Domain Path:       /languages
 *
 * @package SM\Subscriptions
 */

defined( 'ABSPATH' ) || exit;

define( 'SM_SUBSCRIPTIONS_VERSION', '0.2.0' );
define( 'SM_SUBSCRIPTIONS_DB_VERSION', 2 );
define( 'SM_SUBSCRIPTIONS_FILE', __FILE__ );
define( 'SM_SUBSCRIPTIONS_DIR', __DIR__ );

spl_autoload_register(
	static function ( $class ) {
		$prefix = 'SM\\Subscriptions\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}
		$file = SM_SUBSCRIPTIONS_DIR . '/src/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

register_activation_hook( __FILE__, array( 'SM\\Subscriptions\\Install\\Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SM\\Subscriptions\\Cron', 'unschedule' ) );
add_action( 'sm_register_modules', array( 'SM\\Subscriptions\\Plugin', 'register' ), 25 );
add_action(
	'plugins_loaded',
	static function () {
		if ( ! defined( 'SM_CORE_VERSION' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'SM Subscriptions needs the SM Core plugin. Activate it first.', 'sm-subscriptions' ) . '</p></div>';
				}
			);
		}
	},
	20
);

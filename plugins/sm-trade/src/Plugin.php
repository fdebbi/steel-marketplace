<?php
/**
 * Bootstrap.
 *
 * @package SM\Trade
 */

namespace SM\Trade;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/** @param \SM\Core\Modules $registry */
	public static function register( $registry ) {
		if ( ! defined( 'SM_PRODUCTS_VERSION' ) || version_compare( SM_PRODUCTS_VERSION, '0.10.0', '<' ) ) {
			return;
		}
		$ok = $registry->register(
			'sm-trade',
			array(
				'version'       => SM_TRADE_VERSION,
				'requires_core' => '0.8.1',
				'label'         => 'SM Trade',
				'capabilities'  => array(),
			)
		);
		if ( ! $ok ) {
			return;
		}
		load_plugin_textdomain( 'sm-trade', false, dirname( plugin_basename( SM_TRADE_FILE ) ) . '/languages' );
		add_action( 'sm_language_switched', array( __CLASS__, 'reload_textdomain' ), 10, 2 );

		Install\Installer::maybe_upgrade();
		Notifications::init();
		Form::init();
		Cron::init();
		Theme\Bridge::init();
		add_filter(
			'sm_dashboard_badges',
			static function ( $badges, $company_id ) {
				$w = Deals::waiting( (int) $company_id, get_current_user_id() );
				return array_merge( (array) $badges, array( 'sales' => $w['seller'], 'purchases' => $w['buyer'] ) );
			},
			10,
			2
		);
		add_filter( 'sm_dashboard_view_slugs', static fn( $slugs ) => array_merge( $slugs, array( 'purchases' => 'achats', 'sales' => 'ventes', 'deal' => 'transaction' ) ) );
		if ( is_admin() ) {
			Admin\Admin::init();
		}
	}

	public static function reload_textdomain( $code, $locale ) {
		unload_textdomain( 'sm-trade', true );
		$mo = SM_TRADE_DIR . '/languages/sm-trade-' . $locale . '.mo';
		if ( is_readable( $mo ) ) {
			load_textdomain( 'sm-trade', $mo, $locale );
		}
	}
}

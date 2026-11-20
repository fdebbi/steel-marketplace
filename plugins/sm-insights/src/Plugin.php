<?php
/**
 * Bootstrap.
 *
 * @package SM\Insights
 */

namespace SM\Insights;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/** @param \SM\Core\Modules $registry */
	public static function register( $registry ) {
		if ( ! defined( 'SM_PRODUCTS_VERSION' ) || version_compare( SM_PRODUCTS_VERSION, '0.22.0', '<' ) ) {
			return;
		}
		$ok = $registry->register(
			'sm-insights',
			array(
				'version'       => SM_INSIGHTS_VERSION,
				'requires_core' => '0.5.0',
				'label'         => 'SM Insights',
				'capabilities'  => array(),
			)
		);
		if ( ! $ok ) {
			return;
		}
		load_plugin_textdomain( 'sm-insights', false, dirname( plugin_basename( SM_INSIGHTS_FILE ) ) . '/languages' );
		add_action( 'sm_language_switched', array( __CLASS__, 'reload_textdomain' ), 10, 2 );
		Install\Installer::maybe_upgrade();
		Searches::init();
		Theme\Bridge::init();
		add_filter( 'sm_dashboard_view_slugs', static fn( $slugs ) => array_merge( $slugs, array( 'report' => 'rapport-business' ) ) );
	}

	public static function reload_textdomain( $code, $locale ) {
		unload_textdomain( 'sm-insights', true );
		$mo = SM_INSIGHTS_DIR . '/languages/sm-insights-' . $locale . '.mo';
		if ( is_readable( $mo ) ) {
			load_textdomain( 'sm-insights', $mo, $locale );
		}
	}
}

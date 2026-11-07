<?php
/**
 * Bootstrap: registers the module with sm-core and wires the services.
 *
 * @package SM\WeightEngine
 */

namespace SM\WeightEngine;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/** @param \SM\Core\Modules $registry */
	public static function register( $registry ) {
		$ok = $registry->register(
			'sm-weight-engine',
			array(
				'version'       => SM_WEIGHT_VERSION,
				'requires_core' => '0.2.0',
				'label'         => 'SM Weight Engine',
			)
		);
		if ( ! $ok ) {
			return;
		}
		self::boot();
	}

	private static function boot() {
		load_plugin_textdomain( 'sm-weight-engine', false, dirname( plugin_basename( SM_WEIGHT_FILE ) ) . '/languages' );
		add_action( 'sm_language_switched', array( __CLASS__, 'reload_textdomain' ), 10, 2 );

		Install\Installer::maybe_upgrade();
		\SM\Core\Modules::set_service( 'weight', new Service() );

		Rest\Api::init();
		Theme\Bridge::init();
		if ( is_admin() ) {
			Admin\Admin::init();
		}
	}

	/** Messages of REST and form requests follow the language sm-core switched to. */
	public static function reload_textdomain( $code, $locale ) {
		unload_textdomain( 'sm-weight-engine', true );
		$mo = SM_WEIGHT_DIR . '/languages/sm-weight-engine-' . $locale . '.mo';
		if ( is_readable( $mo ) ) {
			load_textdomain( 'sm-weight-engine', $mo, $locale );
		}
	}
}

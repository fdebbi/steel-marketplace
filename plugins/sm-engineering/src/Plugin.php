<?php
/**
 * Bootstrap.
 *
 * @package SM\Engineering
 */

namespace SM\Engineering;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/** @param \SM\Core\Modules $registry */
	public static function register( $registry ) {
		$ok = $registry->register(
			'sm-engineering',
			array(
				'version'       => SM_ENGINEERING_VERSION,
				'requires_core' => '0.6.0',
				'label'         => 'SM Engineering',
				'capabilities'  => array( 'mission.request', 'mission.work' ),
			)
		);
		if ( ! $ok ) {
			return;
		}
		load_plugin_textdomain( 'sm-engineering', false, dirname( plugin_basename( SM_ENGINEERING_FILE ) ) . '/languages' );
		add_action( 'sm_language_switched', array( __CLASS__, 'reload_textdomain' ), 10, 2 );

		Install\Installer::maybe_upgrade();
		Notifications::init();
		Activity::init();
		Apply::init();
		Form::init();
		Files::init();
		Payments::init();
		EmailCode::init();
		Changes::init();
		// Daily task (0.3.0): quote and payment reminders, unpaid advances.
		add_action( 'sm_engineering_daily', array( Missions::class, 'daily' ) );
		if ( ! wp_next_scheduled( 'sm_engineering_daily' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'sm_engineering_daily' );
		}
		add_filter(
			'sm_dashboard_view_slugs',
			static fn( $slugs ) => array_merge(
				$slugs,
				array(
					'studies'      => 'etudes',
					'missions'     => 'missions',
					'billing'      => 'facturation',
					'files'        => 'dossiers',
					'partner'      => 'profil-ingenieur',
				)
			)
		);
		Theme\Bridge::init();
		if ( is_admin() ) {
			Admin\Admin::init();
		}
	}

	public static function reload_textdomain( $code, $locale ) {
		unload_textdomain( 'sm-engineering', true );
		$mo = SM_ENGINEERING_DIR . '/languages/sm-engineering-' . $locale . '.mo';
		if ( is_readable( $mo ) ) {
			load_textdomain( 'sm-engineering', $mo, $locale );
		}
	}
}

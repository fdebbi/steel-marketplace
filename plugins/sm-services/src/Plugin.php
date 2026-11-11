<?php
/**
 * Bootstrap.
 *
 * @package SM\Services
 */

namespace SM\Services;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/** @param \SM\Core\Modules $registry */
	public static function register( $registry ) {
		$ok = $registry->register(
			'sm-services',
			array(
				'version'       => SM_SERVICES_VERSION,
				'requires_core' => '0.6.0',
				'label'         => 'SM Services',
				'capabilities'  => array( 'service.fabrication', 'service.request' ),
			)
		);
		if ( ! $ok ) {
			return;
		}
		load_plugin_textdomain( 'sm-services', false, dirname( plugin_basename( SM_SERVICES_FILE ) ) . '/languages' );
		add_action( 'sm_language_switched', array( __CLASS__, 'reload_textdomain' ), 10, 2 );

		Install\Installer::maybe_upgrade();
		Notifications::init();
		Activity::init();
		Form::init();
		Routes::init();
		add_filter(
			'sm_dashboard_view_slugs',
			static fn( $slugs ) => array_merge( $slugs, array( 'service-requests' => 'demandes-de-service', 'my-service-requests' => 'mes-demandes-de-service' ) )
		);
		Theme\Bridge::init();
		Rest\Api::init();
		add_filter( 'sm_term_lists', array( Catalogue::class, 'term_lists' ) );
		add_action( 'admin_post_sm_service_proposal', array( Catalogue::class, 'handle_proposal' ) );
		if ( is_admin() ) {
			Admin\Admin::init();
		}
	}

	public static function reload_textdomain( $code, $locale ) {
		unload_textdomain( 'sm-services', true );
		$mo = SM_SERVICES_DIR . '/languages/sm-services-' . $locale . '.mo';
		if ( is_readable( $mo ) ) {
			load_textdomain( 'sm-services', $mo, $locale );
		}
	}
}

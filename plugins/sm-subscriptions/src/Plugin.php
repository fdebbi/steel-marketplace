<?php
/**
 * Bootstrap.
 *
 * @package SM\Subscriptions
 */

namespace SM\Subscriptions;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/** @param \SM\Core\Modules $registry */
	public static function register( $registry ) {
		$ok = $registry->register(
			'sm-subscriptions',
			array(
				'version'       => SM_SUBSCRIPTIONS_VERSION,
				'requires_core' => '0.8.1',
				'label'         => 'SM Subscriptions',
				'capabilities'  => array(),
			)
		);
		if ( ! $ok ) {
			return;
		}
		load_plugin_textdomain( 'sm-subscriptions', false, dirname( plugin_basename( SM_SUBSCRIPTIONS_FILE ) ) . '/languages' );
		add_action( 'sm_language_switched', array( __CLASS__, 'reload_textdomain' ), 10, 2 );

		Install\Installer::maybe_upgrade();
		Limits::init();
		Founders::init();
		Notifications::init();
		Form::init();
		Proofs::init();
		Cron::init();
		Theme\Bridge::init();
		add_filter( 'sm_dashboard_view_slugs', static fn( $slugs ) => array_merge( $slugs, array( 'plan' => 'abonnement', 'invoice' => 'facture', 'order' => 'commande' ) ) );
		if ( is_admin() ) {
			Admin\Admin::init();
		}
	}

	public static function reload_textdomain( $code, $locale ) {
		unload_textdomain( 'sm-subscriptions', true );
		$mo = SM_SUBSCRIPTIONS_DIR . '/languages/sm-subscriptions-' . $locale . '.mo';
		if ( is_readable( $mo ) ) {
			load_textdomain( 'sm-subscriptions', $mo, $locale );
		}
	}
}

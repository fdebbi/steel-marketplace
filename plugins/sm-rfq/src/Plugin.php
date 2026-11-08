<?php
/**
 * Bootstrap.
 *
 * @package SM\Rfq
 */

namespace SM\Rfq;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/** @param \SM\Core\Modules $registry */
	public static function register( $registry ) {
		if ( ! defined( 'SM_PRODUCTS_VERSION' ) || version_compare( SM_PRODUCTS_VERSION, '0.3.0', '<' ) ) {
			return;
		}
		$ok = $registry->register(
			'sm-rfq',
			array(
				'version'       => SM_RFQ_VERSION,
				'requires_core' => '0.3.0',
				'label'         => 'SM RFQ',
				'capabilities'  => array( 'rfq.create', 'rfq.offer' ),
			)
		);
		if ( ! $ok ) {
			return;
		}
		load_plugin_textdomain( 'sm-rfq', false, dirname( plugin_basename( SM_RFQ_FILE ) ) . '/languages' );
		add_action( 'sm_language_switched', array( __CLASS__, 'reload_textdomain' ), 10, 2 );

		Install\Installer::maybe_upgrade();
		Notifications::init();
		Form::init();
		Expiry::init();
		Routes::init();
		Theme\Bridge::init();
		Rest\Api::init();
		if ( is_admin() ) {
			Admin\Admin::init();
		}
	}

	public static function reload_textdomain( $code, $locale ) {
		unload_textdomain( 'sm-rfq', true );
		$mo = SM_RFQ_DIR . '/languages/sm-rfq-' . $locale . '.mo';
		if ( is_readable( $mo ) ) {
			load_textdomain( 'sm-rfq', $mo, $locale );
		}
	}
}

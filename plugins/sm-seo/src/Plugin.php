<?php
/**
 * Bootstrap.
 *
 * @package SM\Seo
 */

namespace SM\Seo;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/** @param \SM\Core\Modules $registry */
	public static function register( $registry ) {
		$ok = $registry->register(
			'sm-seo',
			array(
				'version'       => SM_SEO_VERSION,
				'requires_core' => '0.5.0',
				'label'         => 'SM SEO',
				'capabilities'  => array(),
			)
		);
		if ( ! $ok ) {
			return;
		}
		load_plugin_textdomain( 'sm-seo', false, dirname( plugin_basename( SM_SEO_FILE ) ) . '/languages' );
		add_action( 'sm_language_switched', array( __CLASS__, 'reload_textdomain' ), 10, 2 );
		Head::init();
		Sitemaps::init();
		Robots::init();
		if ( is_admin() ) {
			Admin\Admin::init();
		}
	}

	public static function reload_textdomain( $code, $locale ) {
		unload_textdomain( 'sm-seo', true );
		$mo = SM_SEO_DIR . '/languages/sm-seo-' . $locale . '.mo';
		if ( is_readable( $mo ) ) {
			load_textdomain( 'sm-seo', $mo, $locale );
		}
	}
}

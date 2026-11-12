<?php
/**
 * Bootstrap.
 *
 * @package SM\Ai
 */

namespace SM\Ai;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/** @param \SM\Core\Modules $registry */
	public static function register( $registry ) {
		$ok = $registry->register(
			'sm-ai',
			array(
				'version'       => SM_AI_VERSION,
				'requires_core' => '0.5.0',
				'label'         => 'SM AI',
				'capabilities'  => array(),
			)
		);
		if ( ! $ok ) {
			return;
		}
		load_plugin_textdomain( 'sm-ai', false, dirname( plugin_basename( SM_AI_FILE ) ) . '/languages' );
		add_action( 'sm_language_switched', array( __CLASS__, 'reload_textdomain' ), 10, 2 );

		Install\Installer::maybe_upgrade();
		Form::init();
		add_filter( 'sm_dashboard_view_slugs', static fn( $slugs ) => array_merge( $slugs, array( 'ai' => 'assistant-ia', 'ai-credits' => 'credits-ia' ) ) );
		add_filter( 'sm_activity_labels', array( __CLASS__, 'labels' ) );
		add_filter( 'sm_activity_group_prefixes', static fn( $g ) => $g + array( 'ai' => array( 'ai.' ) ) );
		add_filter( 'sm_mail_event_groups', static fn( $g ) => $g + array( 'ai' => __( 'AI assistant', 'sm-ai' ) ) );
		Theme\Bridge::init();
		add_action( 'sm_core_daily', array( Usage::class, 'purge' ) );
		if ( is_admin() ) {
			Admin\Admin::init();
		}
	}

	public static function labels( array $labels ) {
		return $labels + array(
			'ai.settings' => __( 'AI settings changed', 'sm-ai' ),
			'ai.used'     => __( 'AI assistant used', 'sm-ai' ),
		);
	}

	public static function reload_textdomain( $code, $locale ) {
		unload_textdomain( 'sm-ai', true );
		$mo = SM_AI_DIR . '/languages/sm-ai-' . $locale . '.mo';
		if ( is_readable( $mo ) ) {
			load_textdomain( 'sm-ai', $mo, $locale );
		}
	}
}

<?php
/**
 * Versions of the other SM plugins that sm-subscriptions works with (0.1.1).
 *
 * sm-subscriptions calls functions added in Phase 10 to SM Core, SM Products,
 * SM Services, SM RFQ and SM AI. If one of them is active in an older version,
 * the call is skipped (the figure is left out of the page) instead of breaking
 * the page, and the administration shows which plugin to update.
 *
 * @package SM\Subscriptions
 */

namespace SM\Subscriptions;

defined( 'ABSPATH' ) || exit;

final class Compat {

	/** key => [ version constant, plugin name, version needed ]. */
	public static function requirements() {
		return array(
			'core'     => array( 'SM_CORE_VERSION', 'SM Core', '0.8.1' ),
			'products' => array( 'SM_PRODUCTS_VERSION', 'SM Products', '0.8.0' ),
			'services' => array( 'SM_SERVICES_VERSION', 'SM Services', '0.3.6' ),
			'rfq'      => array( 'SM_RFQ_VERSION', 'SM RFQ', '0.1.3' ),
			'ai'       => array( 'SM_AI_VERSION', 'SM AI', '0.1.3' ),
		);
	}

	/** True when the plugin is active in a version that has what sm-subscriptions needs. */
	public static function ready( $key ) {
		$r = self::requirements()[ $key ] ?? null;
		return $r && defined( $r[0] ) && version_compare( (string) constant( $r[0] ), $r[2], '>=' );
	}

	/** Active plugins in a version that is too old: list of { name, installed, needed }. */
	public static function outdated() {
		$out = array();
		foreach ( self::requirements() as $r ) {
			if ( defined( $r[0] ) && version_compare( (string) constant( $r[0] ), $r[2], '<' ) ) {
				$out[] = array( 'name' => $r[1], 'installed' => (string) constant( $r[0] ), 'needed' => $r[2] );
			}
		}
		return $out;
	}

	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'notice' ) );
	}

	public static function notice() {
		if ( ! current_user_can( 'activate_plugins' ) && ! current_user_can( 'sm_manage_settings' ) ) {
			return;
		}
		$old = self::outdated();
		if ( ! $old ) {
			return;
		}
		echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'SM Subscriptions: update these plugins.', 'sm-subscriptions' ) . '</strong> ' . esc_html__( 'Until then, the figures that depend on them are left out of the plan pages and the limits they control are not applied.', 'sm-subscriptions' ) . '</p><ul style="list-style:disc;margin-inline-start:20px">';
		foreach ( $old as $o ) {
			/* translators: 1: plugin name, 2: installed version, 3: version needed. */
			echo '<li>' . esc_html( sprintf( __( '%1$s: version %2$s installed, %3$s or later needed.', 'sm-subscriptions' ), $o['name'], $o['installed'], $o['needed'] ) ) . '</li>';
		}
		echo '</ul></div>';
	}
}

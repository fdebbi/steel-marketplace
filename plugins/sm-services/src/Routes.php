<?php
/**
 * Clean URL of a service page: /service/123/ (the theme's "service" page).
 *
 * @package SM\Services
 */

namespace SM\Services;

use SM\Core\Routing\Routes as CoreRoutes;

defined( 'ABSPATH' ) || exit;

final class Routes {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'rewrite' ), 11 );
		add_filter( 'sm_page_url', array( __CLASS__, 'url' ), 20, 3 );
		if ( get_option( 'sm_services_rewrite' ) !== SM_SERVICES_VERSION ) {
			add_action( 'init', array( __CLASS__, 'flush' ), 99 );
		}
	}

	public static function rewrite() {
		$id = CoreRoutes::page_id( 'service' );
		if ( $id ) {
			add_rewrite_rule( '^' . preg_quote( get_page_uri( $id ), '#' ) . '/([0-9]+)/?$', 'index.php?page_id=' . $id . '&sm_id=$matches[1]', 'top' );
		}
	}

	public static function flush() {
		flush_rewrite_rules( false );
		update_option( 'sm_services_rewrite', SM_SERVICES_VERSION, false );
	}

	/** Address of a service page (forms, notifications, admin). */
	public static function link( $id, array $args = array() ) {
		return self::url( CoreRoutes::url( 'service', $args ), 'service', array_merge( $args, array( 'id' => (int) $id ) ) );
	}

	public static function url( $url, $key, $args ) {
		if ( 'service' !== $key || empty( $args['id'] ) ) {
			return $url;
		}
		$id   = absint( $args['id'] );
		$page = CoreRoutes::page_id( 'service' );
		if ( ! $page ) {
			return $url;
		}
		unset( $args['id'] );
		$base = get_option( 'permalink_structure' ) ? trailingslashit( get_permalink( $page ) ) . $id . '/' : add_query_arg( 'id', $id, get_permalink( $page ) );
		return $args ? add_query_arg( $args, $base ) : $base;
	}
}

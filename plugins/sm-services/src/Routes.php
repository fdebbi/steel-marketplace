<?php
/**
 * Clean URL of a service page: /service/decoupe-plasma-oran-123/ (0.4.0; the name
 * is made once from the service and the wilaya of the company, and never changes;
 * /service/123/ and ?id=123 are sent there with a 301).
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
		add_action( 'template_redirect', array( __CLASS__, 'canonical' ), 1 );
		if ( get_option( 'sm_services_rewrite' ) !== SM_SERVICES_VERSION ) {
			add_action( 'init', array( __CLASS__, 'flush' ), 99 );
		}
	}

	public static function rewrite() {
		$id = CoreRoutes::page_id( 'service' );
		if ( $id ) {
			add_rewrite_rule( '^' . preg_quote( get_page_uri( $id ), '#' ) . '/(?:[a-z0-9-]*-)?([0-9]+)/?$', 'index.php?page_id=' . $id . '&sm_id=$matches[1]', 'top' );
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
		$slug = self::slug( $id );
		$base = get_option( 'permalink_structure' ) ? trailingslashit( get_permalink( $page ) ) . ( '' !== $slug ? $slug . '-' : '' ) . $id . '/' : add_query_arg( 'id', $id, get_permalink( $page ) );
		return $args ? add_query_arg( $args, $base ) : $base;
	}

	/** Name of a service in its address, made once (French name of the service + wilaya of the company). */
	public static function slug( $id ) {
		static $cache = array();
		$id = (int) $id;
		if ( isset( $cache[ $id ] ) ) {
			return $cache[ $id ];
		}
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT id, slug FROM ' . Services::table() . ' WHERE id = %d', $id ) ); // phpcs:ignore WordPress.DB
		if ( ! $row ) {
			return $cache[ $id ] = '';
		}
		$slug = (string) $row->slug;
		if ( '' === $slug && class_exists( '\\SM\\Core\\Listings\\Index' ) ) {
			$s = Services::get( $id );
			if ( $s && 'published' === $s->status ) { // Named at its first publication.
				$title   = Presenter::title( $s );
				$company = \SM\Core\Accounts\Companies::get( $s->company_id );
				$wilaya  = $company && $company->wilaya ? (string) \SM\Core\Accounts\Wilayas::name( $company->wilaya, 'fr' ) : '';
				$slug    = \SM\Core\Listings\Index::make_slug( trim( (string) ( $title['fr'] ?? '' ) . ' ' . $wilaya ) );
				if ( '' !== $slug ) {
					$wpdb->update( Services::table(), array( 'slug' => $slug ), array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				}
			}
		}
		return $cache[ $id ] = $slug;
	}

	/** One address per service page: old addresses are sent to the readable one (301). */
	public static function canonical() {
		$page = CoreRoutes::page_id( 'service' );
		if ( ! $page || 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) || ! get_option( 'permalink_structure' ) || ! is_page() || (int) get_queried_object_id() !== $page ) {
			return;
		}
		$id = absint( get_query_var( 'sm_id' ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$by_arg = ! $id && isset( $_GET['id'] );
		if ( $by_arg ) {
			$id = absint( wp_unslash( $_GET['id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		if ( ! $id || ! Services::get( $id ) ) {
			return;
		}
		$slug   = self::slug( $id );
		$wanted = ( '' !== $slug ? $slug . '-' : '' ) . $id;
		$asked  = basename( untrailingslashit( (string) ( $GLOBALS['wp']->request ?? '' ) ) );
		if ( ! $by_arg && $asked === $wanted ) {
			return;
		}
		$args = array();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		foreach ( (array) $_GET as $k => $v ) {
			if ( 'id' !== $k && is_scalar( $v ) ) {
				$args[ sanitize_key( $k ) ] = sanitize_text_field( wp_unslash( (string) $v ) );
			}
		}
		wp_safe_redirect( self::link( $id, $args ), 301 );
		exit;
	}
}

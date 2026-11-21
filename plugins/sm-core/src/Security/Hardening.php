<?php
/**
 * Hardening of the public site (0.9.0).
 *
 *  - Security headers on the pages: nosniff, same-origin framing only, referrer
 *    sent without the path to other sites, browser features limited (camera and
 *    position for the site itself only, for the photos and the map). Filter
 *    sm_core_security_headers (array; empty = none, e.g. when the host sends them).
 *  - The user names are not given away: the REST list of users is closed to those
 *    who cannot edit content, and ?author= / /author/ pages answer « not found ».
 *  - XML-RPC is off (not used by the platform; a door for password guessing).
 *    Filter sm_core_xmlrpc to turn it back on.
 *  - The WordPress version is not printed in the pages.
 *
 * @package SM\Core
 */

namespace SM\Core\Security;

defined( 'ABSPATH' ) || exit;

final class Hardening {

	public static function init() {
		add_action( 'send_headers', array( __CLASS__, 'headers' ) );
		add_filter( 'rest_endpoints', array( __CLASS__, 'rest_endpoints' ) );
		add_action( 'template_redirect', array( __CLASS__, 'author_pages' ), 0 );
		add_filter( 'xmlrpc_enabled', static fn() => (bool) apply_filters( 'sm_core_xmlrpc', false ) );
		add_filter( 'wp_headers', array( __CLASS__, 'no_pingback' ) );
		remove_action( 'wp_head', 'wp_generator' );
		add_filter( 'the_generator', '__return_empty_string' );
	}

	public static function header_list() {
		return (array) apply_filters(
			'sm_core_security_headers',
			array(
				'X-Content-Type-Options' => 'nosniff',
				'X-Frame-Options'        => 'SAMEORIGIN',
				'Referrer-Policy'        => 'strict-origin-when-cross-origin',
				'Permissions-Policy'     => 'camera=(self), geolocation=(self), microphone=(), payment=(), usb=()',
			)
		);
	}

	public static function headers() {
		if ( is_admin() || headers_sent() ) {
			return;
		}
		foreach ( self::header_list() as $name => $value ) {
			if ( '' !== (string) $value ) {
				header( $name . ': ' . $value );
			}
		}
	}

	/** The list of users and a user's profile: only for the members who edit content. */
	public static function rest_endpoints( $endpoints ) {
		if ( current_user_can( 'edit_posts' ) ) {
			return $endpoints;
		}
		foreach ( array_keys( $endpoints ) as $route ) {
			if ( 0 === strpos( $route, '/wp/v2/users' ) ) {
				unset( $endpoints[ $route ] );
			}
		}
		return $endpoints;
	}

	/** ?author=1 and /author/name/ would give the user names: « not found » instead. */
	public static function author_pages() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ( is_author() || isset( $_GET['author'] ) ) && ! current_user_can( 'edit_posts' ) ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
		}
	}

	public static function no_pingback( $headers ) {
		unset( $headers['X-Pingback'] );
		return $headers;
	}
}

<?php
/**
 * Page-cache safety (Hostinger LiteSpeed / CDN and similar).
 *
 * Until sm-core (Phase 2) moves language and listings to path-based URLs
 * (/ar/..., /annonce/302/), several pages depend on query arguments
 * (?lang=, ?id=, ?view=, filters) or on the sm_lang cookie. A page cache that
 * ignores query strings would serve the first cached variant to everyone, so:
 *
 * 1. Any front-end request with a query string, and every account page, is
 *    marked as non-cacheable for WordPress cache plugins, LiteSpeed and CDNs.
 * 2. A visitor whose saved language differs from the URL is redirected to the
 *    explicit ?lang= URL, so the content of a URL never depends on a cookie.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

/** Whether the current request must bypass page caches. */
function sm_theme_is_uncacheable_request() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only check.
	if ( ! empty( $_GET ) ) {
		return true;
	}
	$private = array( 'templates/dashboard.php', 'templates/auth-login.php', 'templates/auth-register.php', 'templates/auth-lost.php' );
	foreach ( $private as $template ) {
		if ( is_page_template( $template ) ) {
			return true;
		}
	}
	return is_user_logged_in();
}

/** Send no-cache signals understood by WordPress cache plugins, LiteSpeed and CDNs. */
function sm_theme_cache_control() {
	if ( ! sm_theme_is_uncacheable_request() ) {
		return;
	}
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true ); // WP Super Cache, W3TC, LiteSpeed Cache plugin.
	}
	do_action( 'litespeed_control_set_nocache', 'sm-marketplace: query-dependent page' );
	if ( ! headers_sent() ) {
		nocache_headers();
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private', true );
		header( 'X-LiteSpeed-Cache-Control: no-cache', true );
		header( 'CDN-Cache-Control: no-store', true );
	}
}
add_action( 'template_redirect', 'sm_theme_cache_control', 0 );

/**
 * A visitor who chose Arabic and opens a plain URL (no ?lang) is sent to the
 * ?lang=ar version, instead of receiving Arabic content on a French URL.
 */
function sm_theme_language_redirect() {
	if ( function_exists( 'sm_current_language' ) || is_admin() || 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
		return;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['lang'] ) || empty( $_COOKIE['sm_lang'] ) ) {
		return;
	}
	$lang = sm_theme_current_language();
	if ( sm_theme_default_language() === $lang ) {
		return;
	}
	nocache_headers();
	wp_safe_redirect( add_query_arg( 'lang', $lang, sm_theme_current_url() ), 302 );
	exit;
}
add_action( 'template_redirect', 'sm_theme_language_redirect', 1 );

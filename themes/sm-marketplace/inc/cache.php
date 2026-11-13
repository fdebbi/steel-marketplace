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
 * 3. Signed-in members (0.30.1): a page kept for visitors must never be shown
 *    to a member (he would see "Sign in" in the menu). Two protections:
 *    - LiteSpeed is asked to keep separate versions by sign-in cookie;
 *    - the page carries the state it was made in (data-sm-user); a small
 *      cookie readable by the page (sm_in) says whether the browser is signed
 *      in; when they differ, main.js asks sm_user_nav for the right menu.
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
	// Pages with a form (0.28.1): never kept by a cache, so the security code of the form stays valid.
	$private = array( 'templates/dashboard.php', 'templates/auth-login.php', 'templates/auth-register.php', 'templates/auth-lost.php', 'templates/engineer-apply.php', 'templates/mission-request.php', 'templates/contact.php' );
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
		// Cacheable page: one version for visitors, never reused for a signed-in member.
		if ( ! is_admin() && ! wp_doing_ajax() && ! headers_sent() && defined( 'LOGGED_IN_COOKIE' ) ) {
			header( 'X-LiteSpeed-Vary: cookie=' . LOGGED_IN_COOKIE, false );
		}
		return;
	}
	sm_theme_user_hint_sync();
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

/*
 * ---- Signed-in hint (0.30.1) ---------------------------------------------
 * sm_in=1 is a cookie the page can read (the WordPress session cookies cannot
 * be read by scripts). It carries no identity and gives no access: it only
 * says "this browser is signed in", so a cached page can check itself.
 */

/** Write or clear the hint cookie. */
function sm_theme_user_hint( $on, $expire = 0 ) {
	if ( headers_sent() ) {
		return;
	}
	$args = array(
		'expires'  => $on ? (int) $expire : time() - YEAR_IN_SECONDS,
		'path'     => COOKIEPATH ? COOKIEPATH : '/',
		'domain'   => COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
		'secure'   => is_ssl(),
		'httponly' => false,
		'samesite' => 'Lax',
	);
	setcookie( 'sm_in', $on ? '1' : '', $args );
	if ( $on ) {
		$_COOKIE['sm_in'] = '1';
	} else {
		unset( $_COOKIE['sm_in'] );
	}
}

/** Same lifetime as the WordPress session. */
function sm_theme_user_hint_login( $cookie, $expire ) {
	sm_theme_user_hint( true, $expire );
}
add_action( 'set_logged_in_cookie', 'sm_theme_user_hint_login', 10, 2 );

function sm_theme_user_hint_logout() {
	sm_theme_user_hint( false );
}
add_action( 'clear_auth_cookie', 'sm_theme_user_hint_logout' );

/** On a page that is never cached: put the hint back in line with the real session. */
function sm_theme_user_hint_sync() {
	$has = ! empty( $_COOKIE['sm_in'] );
	if ( is_user_logged_in() && ! $has ) {
		sm_theme_user_hint( true, time() + 14 * DAY_IN_SECONDS ); // Sessions opened before 0.30.1.
	} elseif ( ! is_user_logged_in() && $has ) {
		sm_theme_user_hint( false );
	}
}

/**
 * Links made during an admin-ajax request carry no language prefix (/ar/…):
 * put it back, so the corrected menu keeps the visitor in his language.
 */
function sm_theme_localize_links( $html, $lang ) {
	if ( ! $lang || ! class_exists( '\\SM\\Core\\I18n\\Language' ) || \SM\Core\I18n\Language::default_language() === $lang ) {
		return $html;
	}
	$home = untrailingslashit( home_url() );
	return preg_replace_callback(
		'#href="(' . preg_quote( $home, '#' ) . '/[^"]*)"#',
		static function ( $m ) use ( $home, $lang ) {
			$url = html_entity_decode( $m[1], ENT_QUOTES );
			if ( preg_match( '#^' . preg_quote( $home, '#' ) . '/wp-#', $url ) ) {
				return $m[0]; // wp-login.php (sign out), wp-admin.
			}
			return 'href="' . esc_url( \SM\Core\I18n\Language::url_for( $url, $lang ) ) . '"';
		},
		$html
	);
}

/**
 * admin-ajax.php?action=sm_user_nav: the header actions and the foot of the
 * mobile menu for the person who asks (member or visitor). Never cached.
 */
function sm_theme_ajax_user_nav() {
	// phpcs:ignore WordPress.Security.NonceVerification -- read-only, returns only the caller's own menu.
	$lang = isset( $_POST['sm_lang'] ) ? sanitize_key( wp_unslash( $_POST['sm_lang'] ) ) : '';
	if ( $lang && class_exists( '\\SM\\Core\\I18n\\Language' ) ) {
		\SM\Core\I18n\Language::force( $lang );
	}
	sm_theme_user_hint_sync();
	nocache_headers();
	header( 'X-LiteSpeed-Cache-Control: no-cache', true );
	ob_start();
	get_template_part( 'template-parts/header-actions' );
	$head = ob_get_clean();
	ob_start();
	get_template_part( 'template-parts/drawer-foot' );
	$drawer = ob_get_clean();
	wp_send_json_success(
		array(
			'in'     => is_user_logged_in(),
			'head'   => sm_theme_localize_links( $head, $lang ),
			'drawer' => sm_theme_localize_links( $drawer, $lang ),
		)
	);
}
add_action( 'wp_ajax_sm_user_nav', 'sm_theme_ajax_user_nav' );
add_action( 'wp_ajax_nopriv_sm_user_nav', 'sm_theme_ajax_user_nav' );

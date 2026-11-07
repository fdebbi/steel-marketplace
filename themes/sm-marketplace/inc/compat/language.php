<?php
/**
 * Temporary front-end language layer.
 *
 * Phase 1 only: sm-core (Phase 2) will own language detection, the /ar/ and /fr/
 * URL prefixes and the saved user preference. When sm-core defines
 * sm_current_language(), this file steps aside and only reads from it.
 *
 * Detection order here: ?lang= query arg -> sm_lang cookie -> default (French).
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

/**
 * Supported languages. Filterable so sm-core (or a future English pack) can extend it.
 *
 * @return array<string,array{locale:string,label:string,short:string,dir:string}>
 */
function sm_theme_languages() {
	return apply_filters(
		'sm_languages',
		array(
			'fr' => array( 'locale' => 'fr_FR', 'label' => 'Français', 'short' => 'FR', 'dir' => 'ltr' ),
			'ar' => array( 'locale' => 'ar', 'label' => 'العربية', 'short' => 'ع', 'dir' => 'rtl' ),
		)
	);
}

/** Default language (decision 1 of Phase 0: French). */
function sm_theme_default_language() {
	return apply_filters( 'sm_default_language', 'fr' );
}

/**
 * Current language code (fr|ar|...).
 */
function sm_theme_current_language() {
	static $lang = null;

	if ( function_exists( 'sm_current_language' ) ) {
		return sm_current_language();
	}
	if ( null !== $lang ) {
		return $lang;
	}

	$languages = sm_theme_languages();
	$candidate = '';

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only language preference.
	if ( isset( $_GET['lang'] ) ) {
		$candidate = sanitize_key( wp_unslash( $_GET['lang'] ) );
	} elseif ( isset( $_COOKIE['sm_lang'] ) ) {
		$candidate = sanitize_key( wp_unslash( $_COOKIE['sm_lang'] ) );
	}

	$lang = isset( $languages[ $candidate ] ) ? $candidate : sm_theme_default_language();
	return $lang;
}

/** Text direction of the current language. */
function sm_theme_dir() {
	$languages = sm_theme_languages();
	$lang      = sm_theme_current_language();
	return $languages[ $lang ]['dir'] ?? 'ltr';
}

/** URL of the current page in another language. */
function sm_theme_language_url( $code ) {
	if ( function_exists( 'sm_language_url' ) ) {
		return sm_language_url( $code );
	}
	return add_query_arg( 'lang', $code, sm_theme_current_url() );
}

/** Absolute URL of the current request (works in sub-directory installs too). */
function sm_theme_current_url() {
	$host    = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : wp_parse_url( home_url(), PHP_URL_HOST );
	$request = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- escaped below.
	return esc_url_raw( ( is_ssl() ? 'https://' : 'http://' ) . $host . $request );
}

/**
 * Apply the language to the front end when sm-core is not active.
 */
function sm_theme_apply_language() {
	if ( function_exists( 'sm_current_language' ) || is_admin() || wp_doing_cron() ) {
		return;
	}

	$languages = sm_theme_languages();
	$lang      = sm_theme_current_language();
	$locale    = $languages[ $lang ]['locale'];

	// Remember an explicit choice for one year.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['lang'] ) && ! headers_sent() ) {
		setcookie( 'sm_lang', $lang, time() + YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), false );
	}

	add_filter(
		'locale',
		static function () use ( $locale ) {
			return $locale;
		},
		99
	);

	// Reload core strings for the new locale (if the core language pack is installed).
	unload_textdomain( 'default' );
	load_default_textdomain( $locale );

	// Direction does not depend on the core language pack being installed.
	if ( isset( $GLOBALS['wp_locale'] ) ) {
		$GLOBALS['wp_locale']->text_direction = $languages[ $lang ]['dir'];
	}
}
add_action( 'after_setup_theme', 'sm_theme_apply_language', 1 );

/**
 * Keep links inside the site in the current language while the ?lang= fallback is used.
 * Only non-default languages carry the argument; the cookie covers the rest.
 */
function sm_theme_localize_link( $url ) {
	if ( function_exists( 'sm_current_language' ) || is_admin() ) {
		return $url;
	}
	$lang = sm_theme_current_language();
	if ( sm_theme_default_language() === $lang || false === strpos( $url, home_url() ) ) {
		return $url;
	}
	return add_query_arg( 'lang', $lang, $url );
}
add_filter( 'page_link', 'sm_theme_localize_link' );
add_filter( 'home_url', 'sm_theme_localize_home_url', 10, 2 );

/** Same as above for home_url() without recursion. */
function sm_theme_localize_home_url( $url, $path ) {
	static $busy = false;
	if ( $busy || is_admin() || function_exists( 'sm_current_language' ) || doing_action( 'after_setup_theme' ) ) {
		return $url;
	}
	$busy = true;
	$lang = sm_theme_current_language();
	$busy = false;
	if ( sm_theme_default_language() === $lang || false !== strpos( $path, 'wp-' ) ) {
		return $url;
	}
	return add_query_arg( 'lang', $lang, $url );
}

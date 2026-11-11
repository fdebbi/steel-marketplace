<?php
/**
 * Language routing.
 *
 * - The default language (French) has no prefix: /produits/.
 * - Other languages use a path prefix: /ar/produits/. The prefix is removed from
 *   REQUEST_URI before WordPress parses the request, so every page, rewrite rule
 *   and template works unchanged in every language.
 * - ?lang=xx (language switcher, legacy theme links) stores the choice in a
 *   cookie and in the user's profile, then redirects (301) to the clean URL.
 * - An unprefixed URL requested by a visitor whose saved language is not the
 *   default is redirected (302, not cached) to the prefixed URL.
 * - Emails and notifications are rendered in the recipient's language.
 *
 * A URL therefore always maps to exactly one language, which keeps page caches
 * (Hostinger LiteSpeed, CDN) correct.
 *
 * @package SM\Core
 */

namespace SM\Core\I18n;

defined( 'ABSPATH' ) || exit;

final class Language {

	const COOKIE = 'sm_lang';
	const META   = 'sm_locale';

	/** @var string|null Language detected from the URL prefix (or default). */
	private static $current;

	/** @var bool Whether the request carried a language prefix. */
	private static $prefixed = false;

	/** @var string Original REQUEST_URI before prefix removal. */
	private static $original_uri = '';

	/** Supported languages: code => locale, label, dir. */
	public static function languages() {
		return apply_filters(
			'sm_languages',
			array(
				'fr' => array( 'locale' => 'fr_FR', 'label' => 'Français', 'short' => 'FR', 'dir' => 'ltr' ),
				'ar' => array( 'locale' => 'ar', 'label' => 'العربية', 'short' => 'ع', 'dir' => 'rtl' ),
			)
		);
	}

	public static function default_language() {
		$settings = get_option( 'sm_core_settings', array() );
		$code     = $settings['default_language'] ?? 'fr';
		return isset( self::languages()[ $code ] ) ? $code : 'fr';
	}

	/** Set the language for the rest of this request (form handlers, REST). */
	public static function force( $code ) {
		if ( ! isset( self::languages()[ $code ] ) ) {
			return;
		}
		self::$current = $code;
		if ( did_action( 'init' ) ) {
			switch_to_locale( self::locale( $code ) );
			self::reload_textdomains();
		}
	}

	/** Current language. */
	public static function current() {
		if ( null === self::$current ) {
			self::$current = self::default_language();
		}
		return self::$current;
	}

	public static function dir( $code = null ) {
		$langs = self::languages();
		return $langs[ $code ?? self::current() ]['dir'] ?? 'ltr';
	}

	public static function locale( $code = null ) {
		$langs = self::languages();
		return $langs[ $code ?? self::current() ]['locale'] ?? 'fr_FR';
	}

	/** Code from a WordPress locale (ar -> ar, fr_FR -> fr). */
	public static function code_from_locale( $locale ) {
		foreach ( self::languages() as $code => $lang ) {
			if ( $lang['locale'] === $locale ) {
				return $code;
			}
		}
		return self::default_language();
	}

	/** Path of the WordPress home URL, e.g. "/" or "/site/". */
	private static function home_path() {
		$path = wp_parse_url( get_option( 'home' ), PHP_URL_PATH );
		return trailingslashit( $path ? $path : '/' );
	}

	/** Whether this request is a front-end page request we should route. */
	private static function is_routable_request() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return false;
		}
		$uri = $_SERVER['REQUEST_URI'] ?? '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		return ! preg_match( '#/(wp-admin|wp-login\.php|wp-json|wp-content|wp-includes|xmlrpc\.php|wp-cron\.php)#', $uri );
	}

	/**
	 * Called from the main plugin file, before plugins_loaded: detect and strip
	 * the language prefix so WordPress parses the unprefixed path.
	 */
	public static function early_boot() {
		self::$current = self::default_language();
		if ( ! self::is_routable_request() ) {
			// REST requests may pass ?lang= or the cookie for localized responses.
			if ( isset( $_GET['lang'] ) && isset( self::languages()[ sanitize_key( wp_unslash( $_GET['lang'] ) ) ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				self::$current = sanitize_key( wp_unslash( $_GET['lang'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			}
			return;
		}

		$uri       = $_SERVER['REQUEST_URI'] ?? '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$home_path = self::home_path();
		$relative  = substr( $uri, strlen( $home_path ) );
		self::$original_uri = $uri;

		foreach ( self::languages() as $code => $lang ) {
			if ( $code === self::default_language() ) {
				continue;
			}
			if ( preg_match( '#^' . preg_quote( $code, '#' ) . '(/|\?|$)#', (string) $relative ) ) {
				self::$current  = $code;
				self::$prefixed = true;
				$rest           = substr( $relative, strlen( $code ) );
				$rest           = ltrim( $rest, '/' );
				$_SERVER['REQUEST_URI'] = $home_path . $rest;
				break;
			}
		}

		add_filter( 'locale', array( __CLASS__, 'filter_locale' ), 1 );
	}

	public static function filter_locale( $locale ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $locale;
		}
		return self::locale();
	}

	public static function init() {
		add_action( 'after_setup_theme', array( __CLASS__, 'set_direction' ), 0 );
		add_action( 'template_redirect', array( __CLASS__, 'handle_redirects' ), 0 );
		add_filter( 'home_url', array( __CLASS__, 'filter_home_url' ), 10, 4 );
		add_filter( 'redirect_canonical', array( __CLASS__, 'filter_redirect_canonical' ) );
		add_filter( 'language_attributes', array( __CLASS__, 'language_attributes' ), 20 );
		add_action( 'wp_login', array( __CLASS__, 'on_login' ), 10, 2 );
	}

	/** Text direction must follow the language even without the WordPress language pack. */
	public static function set_direction() {
		if ( isset( $GLOBALS['wp_locale'] ) && ! is_admin() ) {
			$GLOBALS['wp_locale']->text_direction = self::dir();
		}
	}

	public static function language_attributes( $output ) {
		if ( is_admin() ) {
			return $output;
		}
		return sprintf( 'lang="%s" dir="%s"', esc_attr( str_replace( '_', '-', self::locale() ) ), esc_attr( self::dir() ) );
	}

	/** Saved preference: user meta, then cookie. Empty string when none. */
	public static function saved_preference() {
		$langs = self::languages();
		if ( is_user_logged_in() ) {
			$meta = get_user_meta( get_current_user_id(), self::META, true );
			if ( $meta && isset( $langs[ $meta ] ) ) {
				return $meta;
			}
		}
		$cookie = isset( $_COOKIE[ self::COOKIE ] ) ? sanitize_key( wp_unslash( $_COOKIE[ self::COOKIE ] ) ) : '';
		return isset( $langs[ $cookie ] ) ? $cookie : '';
	}

	/** Store a language choice (cookie + user meta). */
	public static function remember( $code ) {
		if ( ! isset( self::languages()[ $code ] ) ) {
			return;
		}
		if ( ! headers_sent() ) {
			setcookie( self::COOKIE, $code, time() + YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), false );
		}
		$_COOKIE[ self::COOKIE ] = $code;
		if ( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), self::META, $code );
		}
	}

	/** ?lang= switch and preference redirects. */
	public static function handle_redirects() {
		if ( 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
			return;
		}
		$langs = self::languages();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['lang'] ) ) {
			$code = sanitize_key( wp_unslash( $_GET['lang'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $langs[ $code ] ) ) {
				self::remember( $code );
				nocache_headers();
				wp_safe_redirect( self::url_for( remove_query_arg( 'lang', self::current_unprefixed_url() ), $code ), 301 );
				exit;
			}
		}

		if ( ! self::$prefixed ) {
			$pref = self::saved_preference();
			if ( $pref && $pref !== self::default_language() ) {
				nocache_headers();
				wp_safe_redirect( self::url_for( self::current_unprefixed_url(), $pref ), 302 );
				exit;
			}
		}
	}

	/** Absolute URL of the current request without language prefix. */
	public static function current_unprefixed_url() {
		$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : wp_parse_url( home_url(), PHP_URL_HOST );
		$uri  = $_SERVER['REQUEST_URI'] ?? '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- prefix already stripped; escaped below.
		return esc_url_raw( ( is_ssl() ? 'https://' : 'http://' ) . $host . $uri );
	}

	/**
	 * Rewrite an absolute front-end URL of this site into a language.
	 * Works on unprefixed or prefixed input.
	 */
	public static function url_for( $url, $code ) {
		$home      = untrailingslashit( get_option( 'home' ) );
		$home_path = self::home_path();
		$parts     = wp_parse_url( $url );
		$home_host = wp_parse_url( $home, PHP_URL_HOST );
		if ( empty( $parts['host'] ) || $parts['host'] !== $home_host ) {
			return $url;
		}
		$path = $parts['path'] ?? '/';
		$rel  = ltrim( substr( $path, strlen( $home_path ) - 1 ), '/' );
		// Remove any existing prefix.
		foreach ( array_keys( self::languages() ) as $c ) {
			if ( $c !== self::default_language() && preg_match( '#^' . preg_quote( $c, '#' ) . '(/|$)#', $rel ) ) {
				$rel = ltrim( substr( $rel, strlen( $c ) ), '/' );
			}
		}
		if ( preg_match( '#^(wp-|xmlrpc)#', $rel ) ) {
			return $url;
		}
		$prefix = $code === self::default_language() ? '' : $code . '/';
		$new    = ( $parts['scheme'] ?? 'https' ) . '://' . $parts['host'] . ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' ) . $home_path . $prefix . $rel;
		if ( ! empty( $parts['query'] ) ) {
			$new .= '?' . $parts['query'];
		}
		if ( ! empty( $parts['fragment'] ) ) {
			$new .= '#' . $parts['fragment'];
		}
		return $new;
	}

	/**
	 * Language switcher target: same page in $code, through ?lang= so the
	 * choice is remembered before the redirect.
	 */
	public static function switch_url( $code ) {
		return add_query_arg( 'lang', $code, self::url_for( self::current_unprefixed_url(), self::default_language() ) );
	}

	/** Prefix every front-end home_url() in the current language. */
	public static function filter_home_url( $url, $path, $scheme, $blog_id ) {
		if ( self::current() === self::default_language() || is_admin() || 'rest' === $scheme ) {
			return $url;
		}
		if ( $path && preg_match( '#^/?(wp-|xmlrpc|ar/)#', $path ) ) {
			return $url;
		}
		if ( doing_filter( 'rest_url' ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return $url;
		}
		return self::url_for( $url, self::current() );
	}

	/** Canonical redirects compare against the stripped URI: skip them for prefixed requests. */
	public static function filter_redirect_canonical( $redirect ) {
		return self::$prefixed ? false : $redirect;
	}

	/** Apply a logged-in user's saved language preference to the cookie. */
	public static function on_login( $login, $user ) {
		$pref = get_user_meta( $user->ID, self::META, true );
		if ( $pref && isset( self::languages()[ $pref ] ) && ! headers_sent() ) {
			setcookie( self::COOKIE, $pref, time() + YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), false );
		} elseif ( ! $pref ) {
			update_user_meta( $user->ID, self::META, self::current() );
		}
	}

	/** Language of a user (for emails). */
	public static function user_language( $user_id ) {
		$pref = get_user_meta( $user_id, self::META, true );
		return $pref && isset( self::languages()[ $pref ] ) ? $pref : self::default_language();
	}

	/**
	 * Run $callback with WordPress switched to $code's locale (emails, notifications).
	 * Reloads the sm-core and theme text domains for that locale.
	 */
	public static function with_language( $code, callable $callback ) {
		$previous = self::$current;
		self::$current = $code;
		$switched = switch_to_locale( self::locale( $code ) );
		self::reload_textdomains();
		try {
			return $callback();
		} finally {
			if ( $switched ) {
				restore_previous_locale();
			}
			self::$current = $previous;
			// WordPress keeps translations per locale: when the request's language
			// differs from the restored WordPress locale (admin-post.php, REST),
			// switch to it again so the rest of the request stays translated.
			if ( null !== $previous && self::locale( $previous ) !== determine_locale() && did_action( 'init' ) ) {
				switch_to_locale( self::locale( $previous ) );
			}
			self::reload_textdomains();
		}
	}

	/**
	 * Load sm-core and theme translations for the current language with explicit
	 * file paths: in admin-post.php and REST requests WordPress would otherwise
	 * pick the administrator's locale.
	 */
	private static function reload_textdomains() {
		$locale = self::locale();
		unload_textdomain( 'sm-core', true );
		$core = SM_CORE_DIR . '/languages/sm-core-' . $locale . '.mo';
		if ( is_readable( $core ) ) {
			load_textdomain( 'sm-core', $core, $locale );
		}
		$theme = get_template_directory() . '/languages/' . $locale . '.mo';
		unload_textdomain( 'sm-marketplace', true );
		if ( is_readable( $theme ) ) {
			load_textdomain( 'sm-marketplace', $theme, $locale );
		}
		do_action( 'sm_language_switched', self::$current, $locale );
	}
}

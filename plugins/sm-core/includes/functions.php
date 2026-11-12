<?php
/**
 * Public helper functions of sm-core (stable API for the theme and other plugins).
 *
 * @package SM\Core
 */

defined( 'ABSPATH' ) || exit;

use SM\Core\Accounts\Companies;
use SM\Core\I18n\Language;
use SM\Core\Permissions\Permissions;

/** Current language code (fr, ar…). */
function sm_current_language() {
	return Language::current();
}

/** URL of the current page in another language (the switcher target). */
function sm_language_url( $code ) {
	return Language::switch_url( $code );
}

/** Default language code. */
function sm_default_language() {
	return Language::default_language();
}

/**
 * Whether a company may perform an action.
 *
 * @param string   $cap        Capability, e.g. listing.products, rfq.offer, buy.
 * @param int|null $company_id Company ID; null = company of the current user.
 * @param array    $context    Extra context passed to filters (listing, quantity…).
 */
function sm_can( $cap, $company_id = null, array $context = array() ) {
	return true === Permissions::check( $cap, $company_id, $context );
}

/** Same as sm_can() but returns true or a WP_Error explaining the refusal. */
function sm_can_explain( $cap, $company_id = null, array $context = array() ) {
	return Permissions::check( $cap, $company_id, $context );
}

/** Company row of the current user (object) or null. */
function sm_current_company() {
	return Companies::for_user( get_current_user_id() );
}

/** Fire a domain event: do_action( 'sm_event', $name, $payload ) and "sm_event_{$name}". */
function sm_event( $name, array $payload = array() ) {
	do_action( 'sm_event', $name, $payload );
	do_action( 'sm_event_' . str_replace( '.', '_', $name ), $payload );
}

/** Table name helper: sm_table( 'companies' ) -> wp_sm_companies. */
function sm_table( $name ) {
	global $wpdb;
	return $wpdb->prefix . 'sm_' . $name;
}

/** Core setting with default. */
function sm_setting( $key, $default = null ) {
	$settings = get_option( 'sm_core_settings', array() );
	return array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
}

/** Logical URL of a platform page (filtered by sm-core routes). */
function sm_url( $key, array $args = array() ) {
	return SM\Core\Routing\Routes::url( $key, $args );
}

/**
 * Route argument for the theme templates, from clean URLs or the query string.
 *
 * @param string $key id (listing), c (company slug) or view (dashboard view).
 */
function sm_route_arg( $key ) {
	if ( 'view' === $key ) {
		return SM\Core\Routing\Routes::current_view();
	}
	$var = get_query_var( 'sm_' . $key );
	if ( '' !== $var && null !== $var ) {
		return sanitize_text_field( $var );
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : '';
}

/** Public name of the platform (set in the theme Customizer, "ReuSteel" by default). */
function sm_brand_name() {
	$name = trim( (string) get_theme_mod( 'sm_brand_name', '' ) );
	return '' !== $name ? $name : 'ReuSteel';
}

/**
 * English text of a French platform value (0.8.0), from data/en-content.php and
 * the `sm_en_dictionary` filter. Returns the French text when it has no entry.
 */
function sm_en( $fr ) {
	static $dict = null;
	if ( null === $dict ) {
		$dict = (array) apply_filters( 'sm_en_dictionary', require SM_CORE_DIR . '/data/en-content.php' );
	}
	$fr = (string) $fr;
	return $dict[ $fr ] ?? $fr;
}

/**
 * Text of a multilingual value ({fr, ar, en?}) in a language: its own text, then
 * (English) the English of its French text, then the first text it has.
 */
function sm_pick( $values, $lang = null ) {
	if ( ! is_array( $values ) ) {
		return (string) $values;
	}
	$lang = $lang ?? \SM\Core\I18n\Language::current();
	if ( '' !== trim( (string) ( $values[ $lang ] ?? '' ) ) ) {
		return (string) $values[ $lang ];
	}
	if ( 'en' === $lang && '' !== trim( (string) ( $values['fr'] ?? '' ) ) ) {
		return sm_en( $values['fr'] );
	}
	foreach ( $values as $text ) {
		if ( '' !== trim( (string) $text ) ) {
			return (string) $text;
		}
	}
	return '';
}

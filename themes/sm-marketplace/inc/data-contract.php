<?php
/**
 * Read-only data contract between the theme and the sm-* plugins.
 *
 * Templates never query the database. They call the functions below, which ask the
 * plugins through filters. A plugin answers by returning data in the documented shape
 * (see docs/DATA-CONTRACT.md). When nothing answers and demo mode is on, the demo
 * provider supplies realistic sample content so every page can be reviewed.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

/** Whether demo content may be shown. */
function sm_theme_demo_enabled() {
	return (bool) apply_filters( 'sm_theme_demo_enabled', get_theme_mod( 'sm_demo_mode', true ) );
}

/**
 * Ask plugins for data, fall back to demo data.
 *
 * @param string $resource listings|listing|companies|company|services|categories|wilayas|account|stats.
 * @param array  $args     Query arguments.
 * @return mixed
 */
function sm_theme_data( $resource, $args = array() ) {
	$result = apply_filters( "sm_theme_data_{$resource}", null, $args );
	if ( null !== $result ) {
		return $result;
	}
	if ( sm_theme_demo_enabled() && class_exists( 'SM_Theme_Demo_Data' ) ) {
		return SM_Theme_Demo_Data::get( $resource, $args );
	}
	return in_array( $resource, array( 'listing', 'company', 'account' ), true ) ? null : array();
}

/**
 * Listings of one or several markets.
 *
 * @param array $args market, category, condition, wilaya, q, featured, company, per_page, page, sort.
 * @return array{items:array,total:int,pages:int}
 */
function sm_theme_listings( $args = array() ) {
	$result = sm_theme_data( 'listings', wp_parse_args( $args, array( 'per_page' => 12, 'page' => 1 ) ) );
	return wp_parse_args( (array) $result, array( 'items' => array(), 'total' => 0, 'pages' => 0 ) );
}

function sm_theme_listing( $id ) {
	return sm_theme_data( 'listing', array( 'id' => $id ) );
}

function sm_theme_companies( $args = array() ) {
	return (array) sm_theme_data( 'companies', $args );
}

function sm_theme_company( $slug ) {
	return sm_theme_data( 'company', array( 'slug' => $slug ) );
}

function sm_theme_services( $args = array() ) {
	return (array) sm_theme_data( 'services', $args );
}

function sm_theme_categories( $market ) {
	return (array) sm_theme_data( 'categories', array( 'market' => $market ) );
}

function sm_theme_wilayas() {
	return (array) sm_theme_data( 'wilayas' );
}

/** The signed-in company account as seen by the dashboard (class, activities, plan, usage). */
function sm_theme_account( $args = array() ) {
	return sm_theme_data( 'account', $args );
}

function sm_theme_stats() {
	return (array) sm_theme_data( 'stats' );
}

/** Read a sanitised query-string filter for archive pages. */
function sm_theme_query_arg( $key, $default = '' ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filters.
	return isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : $default;
}

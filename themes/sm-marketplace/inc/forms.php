<?php
/**
 * Form and route helpers. The theme only renders: processing, validation and
 * security (nonces, rate limits) belong to sm-core, which plugs in through the
 * sm_form_action / sm_form_fields / sm_theme_notices / sm_form_old hooks.
 *
 * Without sm-core, forms stay in demo mode (no submission).
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

/** Whether the core plugin is active. */
function sm_theme_core_active() {
	return function_exists( 'sm_route_arg' );
}

/** Route argument: listing id, company slug (c) or dashboard view. */
function sm_theme_route( $key, $default = '' ) {
	if ( sm_theme_core_active() ) {
		$value = sm_route_arg( $key );
		return '' === $value || null === $value ? $default : $value;
	}
	return sm_theme_query_arg( $key, $default );
}

/** Capability check through sm-core; true in demo mode. */
function sm_theme_can( $cap ) {
	return function_exists( 'sm_can' ) ? sm_can( $cap ) : true;
}

/** Explanation of a refusal, or ''. */
function sm_theme_cannot_reason( $cap ) {
	if ( ! function_exists( 'sm_can_explain' ) ) {
		return '';
	}
	$result = sm_can_explain( $cap );
	return is_wp_error( $result ) ? $result->get_error_message() : '';
}

/**
 * Print the opening <form> tag for a core form.
 *
 * @param string $form  Form id (login, register, enquiry, company_save…).
 * @param array  $attrs Extra attributes (class, enctype…).
 */
function sm_theme_form_open( $form, array $attrs = array() ) {
	$action = apply_filters( 'sm_form_action', '#', $form );
	$attrs  = array_merge( array( 'class' => 'sm-form', 'method' => 'post', 'action' => $action ), $attrs );
	if ( '#' === $action ) {
		$attrs['data-sm-demo-form'] = '';
	}
	echo '<form';
	foreach ( $attrs as $name => $value ) {
		echo ' ' . esc_attr( $name ) . ( '' === $value ? '' : '="' . ( 'action' === $name ? esc_url( $value ) : esc_attr( $value ) ) . '"' );
	}
	echo ' novalidate>';
	do_action( 'sm_form_fields', $form );
}

/** Notices for the current request: list of [type, message, field]. */
function sm_theme_notice_list() {
	static $list = null;
	if ( null === $list ) {
		$list = (array) apply_filters( 'sm_theme_notices', array() );
	}
	return $list;
}

/** Print notices (all, or only general ones when $general_only). */
function sm_theme_notices( $general_only = false ) {
	$items = sm_theme_notice_list();
	if ( ! $items ) {
		return;
	}
	$errors  = array_filter( $items, static fn( $n ) => 'error' === $n['type'] && ( ! $general_only || '' === $n['field'] ) );
	$success = array_filter( $items, static fn( $n ) => 'success' === $n['type'] );
	if ( $errors ) {
		echo '<div class="sm-notice sm-notice--error" role="alert">' . sm_get_icon( 'alert' ) . '<div>'; // phpcs:ignore WordPress.Security.EscapeOutput
		if ( count( $errors ) > 1 || $general_only ) {
			echo '<ul>';
			foreach ( $errors as $n ) {
				echo '<li>' . esc_html( $n['message'] ) . '</li>';
			}
			echo '</ul>';
		} else {
			echo '<p>' . esc_html( reset( $errors )['message'] ) . '</p>';
		}
		echo '</div></div>';
	}
	foreach ( $success as $n ) {
		echo '<div class="sm-notice sm-notice--success" role="status">' . sm_get_icon( 'check' ) . '<p>' . esc_html( $n['message'] ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/** Error message of a field, or ''. */
function sm_theme_field_error( $field ) {
	foreach ( sm_theme_notice_list() as $n ) {
		if ( 'error' === $n['type'] && $field === $n['field'] ) {
			return $n['message'];
		}
	}
	return '';
}

/** Print the field error (with id for aria-describedby). */
function sm_theme_error( $field ) {
	$message = sm_theme_field_error( $field );
	if ( $message ) {
		printf( '<p class="sm-field-error" id="err-%s">%s</p>', esc_attr( $field ), esc_html( $message ) );
	}
}

/** aria attributes for an input with a possible error. */
function sm_theme_aria( $field ) {
	if ( sm_theme_field_error( $field ) ) {
		printf( ' aria-invalid="true" aria-describedby="err-%s"', esc_attr( $field ) );
	}
}

/** All previously submitted values (after a failed submission), or an empty array. */
function sm_theme_old_all() {
	return (array) apply_filters( 'sm_form_old_all', array() );
}

/** Previously submitted value (after a failed submission) or $default. */
function sm_theme_old( $field, $default = '' ) {
	return apply_filters( 'sm_form_old', $default, $field, $default );
}

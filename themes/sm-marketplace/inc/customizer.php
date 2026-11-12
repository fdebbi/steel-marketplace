<?php
/**
 * Brand settings editable from wp-admin (Appearance -> Customize -> SM Brand).
 *
 * Decision 8 of Phase 0: the brand name and logo come later, so the primary colours
 * are not hardcoded. Every colour used by the theme derives from these tokens via
 * CSS custom properties and color-mix(), so changing one colour here re-themes the site.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

/**
 * Brand colour tokens and their defaults (Phase 0 palette).
 *
 * @return array<string,array{label:string,default:string,var:string,description:string}>
 */
function sm_theme_color_tokens() {
	return array(
		'primary'    => array(
			'label'       => __( 'Primary colour', 'sm-marketplace' ),
			'description' => __( 'Header, headings, dark surfaces.', 'sm-marketplace' ),
			'default'     => '#111820',
			'var'         => '--sm-primary',
		),
		'accent'     => array(
			'label'       => __( 'Accent colour', 'sm-marketplace' ),
			'description' => __( 'Main buttons, price per kg, verified badges.', 'sm-marketplace' ),
			'default'     => '#06CE77',
			'var'         => '--sm-accent',
		),
		'secondary'  => array(
			'label'       => __( 'Secondary colour', 'sm-marketplace' ),
			'description' => __( 'Secondary text, icons and borders.', 'sm-marketplace' ),
			'default'     => '#5B6670',
			'var'         => '--sm-secondary',
		),
		'background' => array(
			'label'       => __( 'Page background', 'sm-marketplace' ),
			'description' => __( 'Light background behind cards.', 'sm-marketplace' ),
			'default'     => '#F5F7F8',
			'var'         => '--sm-bg',
		),
	);
}

/** Current value of a colour token. */
function sm_theme_color( $key ) {
	$tokens = sm_theme_color_tokens();
	$value  = get_theme_mod( 'sm_color_' . $key, $tokens[ $key ]['default'] );
	$value  = sanitize_hex_color( $value );
	return $value ? $value : $tokens[ $key ]['default'];
}

function sm_theme_customize_register( WP_Customize_Manager $wp_customize ) {
	$wp_customize->add_section(
		'sm_brand',
		array(
			'title'       => __( 'SM Brand', 'sm-marketplace' ),
			'description' => __( 'Brand colours of the platform. Every component follows these four colours, so they can be matched to the final logo without code changes.', 'sm-marketplace' ),
			'priority'    => 25,
		)
	);

	foreach ( sm_theme_color_tokens() as $key => $token ) {
		$wp_customize->add_setting(
			'sm_color_' . $key,
			array(
				'default'           => $token['default'],
				'sanitize_callback' => 'sanitize_hex_color',
				'transport'         => 'postMessage',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				'sm_color_' . $key,
				array(
					'label'       => $token['label'],
					'description' => $token['description'],
					'section'     => 'sm_brand',
				)
			)
		);
	}

	$wp_customize->add_setting(
		'sm_brand_name',
		array(
			'default'           => 'ReuSteel',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		)
	);
	$wp_customize->add_control(
		'sm_brand_name',
		array(
			'label'       => __( 'Brand name (shown when no logo is set)', 'sm-marketplace' ),
			'section'     => 'sm_brand',
			'type'        => 'text',
		)
	);

	$wp_customize->add_setting(
		'sm_demo_mode',
		array(
			'default'           => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
		)
	);
	$wp_customize->add_control(
		'sm_demo_mode',
		array(
			'label'       => __( 'Show demo listings when no marketplace plugin provides data', 'sm-marketplace' ),
			'section'     => 'sm_brand',
			'type'        => 'checkbox',
		)
	);
}
add_action( 'customize_register', 'sm_theme_customize_register' );

/** Brand tokens as CSS variables, attached after the main stylesheet so they win. */
function sm_theme_brand_css() {
	$css = ':root{';
	foreach ( sm_theme_color_tokens() as $key => $token ) {
		$css .= $token['var'] . ':' . sm_theme_color( $key ) . ';';
	}
	return $css . '--sm-on-accent:' . sm_theme_on_color( sm_theme_color( 'accent' ) ) . ';}';
}

/** Relative luminance of a #rrggbb colour (WCAG). */
function sm_theme_luminance( $hex ) {
	$hex = ltrim( (string) $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	$lum = 0;
	foreach ( array( 0.2126, 0.7152, 0.0722 ) as $i => $w ) {
		$c    = hexdec( substr( $hex, $i * 2, 2 ) ) / 255;
		$lum += $w * ( $c <= 0.03928 ? $c / 12.92 : ( ( $c + 0.055 ) / 1.055 ) ** 2.4 );
	}
	return $lum;
}

/** Text colour with the better contrast on a background: near-black or white. */
function sm_theme_on_color( $hex ) {
	$l = sm_theme_luminance( $hex );
	return ( $l + 0.05 ) / 0.0535 >= 1.05 / ( $l + 0.05 ) ? '#080B0E' : '#FFFFFF';
}

/**
 * 0.18.0: the brand moves to the ReuSteel palette. Colours still equal to the
 * old defaults (never chosen by the administrator) are dropped so the new
 * defaults apply; colours chosen on purpose are kept.
 */
function sm_theme_migrate_palette() {
	if ( get_option( 'sm_theme_palette' ) === '0.18' ) {
		return;
	}
	$old = array( 'primary' => '#172b3a', 'accent' => '#16a36a', 'secondary' => '#64748b', 'background' => '#f1f5f9' );
	foreach ( $old as $key => $hex ) {
		if ( strtolower( (string) get_theme_mod( 'sm_color_' . $key, '' ) ) === $hex ) {
			remove_theme_mod( 'sm_color_' . $key );
		}
	}
	update_option( 'sm_theme_palette', '0.18', false );
}
add_action( 'after_setup_theme', 'sm_theme_migrate_palette' );

function sm_theme_enqueue_brand_css() {
	wp_add_inline_style( 'sm-main', sm_theme_brand_css() );
}
add_action( 'wp_enqueue_scripts', 'sm_theme_enqueue_brand_css', 30 );

/** Live preview of colour changes in the Customizer. */
function sm_theme_customize_preview_js() {
	$vars = array();
	foreach ( sm_theme_color_tokens() as $key => $token ) {
		$vars[ 'sm_color_' . $key ] = $token['var'];
	}
	wp_enqueue_script( 'sm-customize-preview', SM_THEME_URI . '/assets/js/customize-preview.js', array( 'customize-preview' ), SM_THEME_VERSION, true );
	wp_add_inline_script( 'sm-customize-preview', 'window.smColorVars=' . wp_json_encode( $vars ) . ';', 'before' );
}
add_action( 'customize_preview_init', 'sm_theme_customize_preview_js' );

/** Brand name used in header/footer when no custom logo is uploaded. */
function sm_theme_brand_name() {
	$name = trim( (string) get_theme_mod( 'sm_brand_name', 'ReuSteel' ) );
	return '' === $name ? 'ReuSteel' : $name;
}

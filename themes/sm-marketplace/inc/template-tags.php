<?php
/**
 * Presentation helpers. No business rules here: formatting and markup only.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

/**
 * Echo an inline SVG icon from the sprite.
 *
 * Directional icons (chevrons, arrows) carry .sm-icon--dir so CSS mirrors them in RTL.
 */
function sm_icon( $name, $class = '', $label = '' ) {
	$directional = array( 'chevron-end', 'chevron-start', 'arrow-end' );
	$classes     = trim( 'sm-icon ' . ( in_array( $name, $directional, true ) ? 'sm-icon--dir ' : '' ) . $class );
	$a11y        = $label ? 'role="img" aria-label="' . esc_attr( $label ) . '"' : 'aria-hidden="true" focusable="false"';
	printf( '<svg class="%1$s" %2$s><use href="#i-%3$s"></use></svg>', esc_attr( $classes ), $a11y, esc_attr( $name ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/** Return the icon markup instead of printing it. */
function sm_get_icon( $name, $class = '', $label = '' ) {
	ob_start();
	sm_icon( $name, $class, $label );
	return ob_get_clean();
}

/**
 * Render a component from template-parts/components/{name}.php with $args.
 * Plugins and child themes can override a component through the sm_component_path filter.
 */
function sm_component( $name, $args = array() ) {
	$path = apply_filters( 'sm_component_path', '', $name, $args );
	if ( $path && file_exists( $path ) ) {
		load_template( $path, false, $args );
		return;
	}
	get_template_part( 'template-parts/components/' . $name, null, $args );
}

/**
 * Pick the current language value of a multilingual field.
 *
 * @param array|string $field Either a plain string or array( 'fr' => ..., 'ar' => ... ).
 * @return array{text:string,lang:string,fallback:bool}
 */
function sm_field( $field ) {
	if ( ! is_array( $field ) ) {
		return array( 'text' => (string) $field, 'lang' => '', 'fallback' => false );
	}
	$lang = sm_theme_current_language();
	if ( ! empty( $field[ $lang ] ) ) {
		return array( 'text' => $field[ $lang ], 'lang' => $lang, 'fallback' => false );
	}
	foreach ( $field as $code => $text ) {
		if ( '' !== $text ) {
			return array( 'text' => $text, 'lang' => $code, 'fallback' => true );
		}
	}
	return array( 'text' => '', 'lang' => '', 'fallback' => false );
}

/** Shortcut: translated text of a multilingual field. */
function sm_t( $field ) {
	return sm_field( $field )['text'];
}

/**
 * Echo a multilingual field. When it falls back to another language, the text keeps
 * its own lang/dir attributes so Arabic inside French (or the reverse) renders correctly.
 */
function sm_e( $field, $tag = 'span' ) {
	$f = sm_field( $field );
	if ( $f['fallback'] ) {
		$languages = sm_theme_languages();
		$dir       = $languages[ $f['lang'] ]['dir'] ?? 'auto';
		printf( '<%1$s lang="%2$s" dir="%3$s">%4$s</%1$s>', tag_escape( $tag ), esc_attr( $f['lang'] ), esc_attr( $dir ), esc_html( $f['text'] ) );
		return;
	}
	echo esc_html( $f['text'] );
}

/**
 * Number formatting with Western digits in every language (decision 2 of Phase 0).
 * French style: narrow no-break space as thousands separator, comma as decimal mark.
 */
function sm_number( $value, $decimals = 0 ) {
	return number_format( (float) $value, $decimals, ',', "\u{202F}" );
}

/** Currency suffix for DZD in the current language. */
function sm_currency_label() {
	return 'ar' === sm_theme_current_language() ? 'دج' : 'DA';
}

/** Money: "1 234 567 DA" / "1 234 567 دج". */
function sm_money( $value, $decimals = 0 ) {
	return sm_number( $value, $decimals ) . "\u{00A0}" . sm_currency_label();
}

/**
 * Price per unit as one bidi-safe block: "245 DA/kg" in French, "245 دج/kg" in Arabic.
 * Returns HTML (escaped). $small wraps the unit in <small>.
 */
function sm_per_unit( $value, $unit = 'kg', $small = false, $decimals = 0 ) {
	$dir  = 'rtl' === sm_theme_dir() ? 'rtl' : 'ltr';
	$text = sm_currency_label() . '/' . $unit;
	$u    = '<bdi dir="' . $dir . '" class="sm-unit">' . esc_html( $text ) . '</bdi>';
	return '<bdi class="sm-perunit">' . esc_html( sm_number( $value, $decimals ) ) . "\u{00A0}" . ( $small ? '<small>' . $u . '</small>' : $u ) . '</bdi>';
}

/** Short unit of a price ("kg", "m²", "ml", "pièce"). */
function sm_price_unit_label( $unit ) {
	$labels = array(
		'kg'   => 'kg',
		'm2'   => 'm²',
		'ml'   => 'ml',
		'unit' => __( 'piece', 'sm-marketplace' ),
	);
	return $labels[ $unit ] ?? 'kg';
}

/** Main price of a listing: price per kg, or per its own unit (m², piece…). */
function sm_listing_price( array $item, $small = true ) {
	$unit = $item['price_unit'] ?? 'kg';
	if ( 'kg' !== $unit && ! empty( $item['price'] ) ) {
		return sm_per_unit( $item['price'], sm_price_unit_label( $unit ), $small );
	}
	return sm_per_unit( $item['price_per_kg'], 'kg', $small );
}

/** Allowed markup for pre-formatted technical values (spec tables). */
function sm_kses_tech( $html ) {
	return wp_kses(
		$html,
		array(
			'bdi'    => array( 'dir' => true, 'class' => true ),
			'small'  => array(),
			'strong' => array(),
		)
	);
}

/** Technical value kept left-to-right inside any sentence (IPE 200, 12 m, S275JR). */
function sm_tech( $text ) {
	return '<bdi dir="ltr" class="sm-tech">' . esc_html( $text ) . '</bdi>';
}

/** Weight with unit, e.g. "3 230 kg". */
function sm_kg( $value, $decimals = 0 ) {
	return sm_tech( sm_number( $value, $decimals ) . "\u{00A0}kg" );
}

/**
 * Logical page URLs. Plugins replace them via the sm_page_url filter once they own
 * real routes; the demo installer maps them to WordPress pages meanwhile.
 */
function sm_page_url( $key, $args = array() ) {
	$url = apply_filters( 'sm_page_url', '', $key, $args );
	if ( ! $url ) {
		$map     = get_option( 'sm_theme_pages', array() );
		$page_id = $map[ $key ] ?? 0;
		$url     = $page_id ? get_permalink( $page_id ) : home_url( '/' );
	}
	return $args ? add_query_arg( $args, $url ) : $url;
}

/** Labels of reuse condition levels (the five classes of the brief). */
function sm_condition_labels() {
	return array(
		'new_surplus' => __( 'New unused surplus', 'sm-marketplace' ),
		'excellent'   => __( 'Excellent used condition', 'sm-marketplace' ),
		'good'        => __( 'Good used condition', 'sm-marketplace' ),
		'inspection'  => __( 'Requires inspection or treatment', 'sm-marketplace' ),
		'scrap'       => __( 'Scrap / recycling only', 'sm-marketplace' ),
	);
}

/** Position of a condition on the 5-step scale (1 = best). */
function sm_condition_level( $code ) {
	$keys = array_keys( sm_condition_labels() );
	$pos  = array_search( $code, $keys, true );
	return false === $pos ? 0 : $pos + 1;
}

/** Label of a weight source. */
function sm_weight_source_label( $source ) {
	return 'actual' === $source ? __( 'Actual weight', 'sm-marketplace' ) : __( 'Theoretical weight', 'sm-marketplace' );
}

/** Market labels. */
function sm_market_labels() {
	return array(
		'products'  => __( 'Steel products', 'sm-marketplace' ),
		'reuse'     => __( 'Steel reuse', 'sm-marketplace' ),
		'buildings' => __( 'Steel buildings', 'sm-marketplace' ),
		'services'  => __( 'Fabrication & installation', 'sm-marketplace' ),
		'engineers' => __( 'Engineering', 'sm-marketplace' ),
	);
}

/** Activity labels. */
function sm_activity_labels() {
	return array(
		'manufacturer'  => __( 'Manufacturer', 'sm-marketplace' ),
		'supplier'      => __( 'Supplier / distributor', 'sm-marketplace' ),
		'installer'     => __( 'Installation company', 'sm-marketplace' ),
		'project_owner' => __( 'Project owner', 'sm-marketplace' ),
		'reuse_dealer'  => __( 'Second-hand dealer / recycler', 'sm-marketplace' ),
		'fabrication'   => __( 'Fabrication services', 'sm-marketplace' ),
		'engineer'      => __( 'Engineer / engineering office', 'sm-marketplace' ),
	);
}

/** Verification level labels. */
function sm_verification_label( $level ) {
	$labels = array(
		0 => __( 'Not verified', 'sm-marketplace' ),
		1 => __( 'Contact verified', 'sm-marketplace' ),
		2 => __( 'Verified business', 'sm-marketplace' ),
		3 => __( 'Verified engineer', 'sm-marketplace' ),
	);
	return $labels[ (int) $level ] ?? $labels[0];
}

/** Relative date ("3 days ago"), translated by the theme itself. */
function sm_ago( $date ) {
	$diff = max( 0, time() - strtotime( $date ) );
	if ( $diff < HOUR_IN_SECONDS ) {
		return __( 'just now', 'sm-marketplace' );
	}
	if ( $diff < DAY_IN_SECONDS ) {
		$n = (int) floor( $diff / HOUR_IN_SECONDS );
		/* translators: %s: number of hours. */
		return sprintf( _n( '%s hour ago', '%s hours ago', $n, 'sm-marketplace' ), $n );
	}
	$n = (int) floor( $diff / DAY_IN_SECONDS );
	/* translators: %s: number of days. */
	return sprintf( _n( '%s day ago', '%s days ago', $n, 'sm-marketplace' ), $n );
}

/** Brand block (logo or name). */
function sm_brand() {
	echo '<a class="sm-brand" href="' . esc_url( home_url( '/' ) ) . '" rel="home">';
	if ( has_custom_logo() ) {
		$logo_id = get_theme_mod( 'custom_logo' );
		echo wp_get_attachment_image( $logo_id, 'full', false, array( 'class' => 'sm-brand__logo', 'alt' => esc_attr( sm_theme_brand_name() ) ) );
	} else {
		echo '<span class="sm-brand__mark" aria-hidden="true"><svg viewBox="0 0 32 32"><path d="M5 7h22v4H18.5v10H27v4H5v-4h8.5V11H5z" fill="currentColor"/></svg></span>';
		echo '<span class="sm-brand__name">' . esc_html( sm_theme_brand_name() ) . '</span>';
	}
	echo '</a>';
}

/** Breadcrumb from an array of [label, url]. The last item is the current page. */
function sm_breadcrumb( array $items ) {
	echo '<nav class="sm-breadcrumb" aria-label="' . esc_attr__( 'Breadcrumb', 'sm-marketplace' ) . '"><ol>';
	echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'sm-marketplace' ) . '</a></li>';
	$last = count( $items ) - 1;
	foreach ( $items as $i => $item ) {
		if ( $i === $last || empty( $item[1] ) ) {
			echo '<li aria-current="page">' . esc_html( $item[0] ) . '</li>';
		} else {
			echo '<li><a href="' . esc_url( $item[1] ) . '">' . esc_html( $item[0] ) . '</a></li>';
		}
	}
	echo '</ol></nav>';
}

/** Image URL for a listing (demo images are SVG illustrations). */
function sm_image_url( $image ) {
	if ( is_numeric( $image ) ) {
		return wp_get_attachment_image_url( (int) $image, 'sm-card' );
	}
	if ( is_string( $image ) && 0 === strpos( $image, 'demo:' ) ) {
		return SM_THEME_URI . '/assets/demo/' . substr( $image, 5 ) . '.svg';
	}
	return is_string( $image ) ? $image : '';
}

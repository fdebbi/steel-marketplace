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
		'lot'  => __( 'lot', 'sm-marketplace' ),
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
	if ( 'new' === $code ) {
		return 1; // Buildings: new.
	}
	$keys = array_keys( sm_condition_labels() );
	$pos  = array_search( $code, $keys, true );
	return false === $pos ? 0 : $pos + 1;
}

/** Label of a weight source. */
function sm_weight_source_label( $source ) {
	$labels = array(
		'actual'    => __( 'Actual weight', 'sm-marketplace' ),
		'estimated' => __( 'Estimated weight', 'sm-marketplace' ),
	);
	return $labels[ $source ] ?? __( 'Theoretical weight', 'sm-marketplace' );
}

/** Short label of a weight source, for cards. */
function sm_weight_source_short( $source ) {
	$labels = array(
		'actual'    => __( 'actual', 'sm-marketplace' ),
		'estimated' => __( 'estimated', 'sm-marketplace' ),
	);
	return $labels[ $source ] ?? __( 'theoretical', 'sm-marketplace' );
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

/**
 * Account classes with their types (companies) and trades (professionals), as
 * set by the administrator in SM Core. Fallback for the demo without plugins.
 *
 * @return array{classes: array, labels: array}
 */
function sm_account_types() {
	static $types = null;
	if ( null !== $types ) {
		return $types;
	}
	$data = sm_theme_data( 'account_types' );
	if ( is_array( $data ) && ! empty( $data['classes'] ) ) {
		$types = $data;
		return $types;
	}
	$company = array(
		'construction' => __( 'Steel construction contractor', 'sm-marketplace' ),
		'manufacturer' => __( 'Manufacturing', 'sm-marketplace' ),
		'supplier'     => __( 'Supply and distribution', 'sm-marketplace' ),
		'recycler'     => __( 'Recycling and scrap', 'sm-marketplace' ),
		'dismantler'   => __( 'Dismantling and second-hand resale', 'sm-marketplace' ),
	);
	$trades  = array(
		'trade_fabricator'  => __( 'Structural steel fabricator', 'sm-marketplace' ),
		'trade_erector'     => __( 'Steel structure erector', 'sm-marketplace' ),
		'trade_roofing'     => __( 'Roofing and cladding installer', 'sm-marketplace' ),
		'trade_welder'      => __( 'Welder', 'sm-marketplace' ),
		'trade_boilermaker' => __( 'Boilermaker', 'sm-marketplace' ),
		'trade_metalworker' => __( 'Metalworker (locksmith)', 'sm-marketplace' ),
		'trade_lifting'     => __( 'Lifting and handling', 'sm-marketplace' ),
		'trade_painting'    => __( 'Sandblasting and painting', 'sm-marketplace' ),
	);
	$types = array(
		'classes' => array(
			'company'      => array( 'label' => __( 'Company', 'sm-marketplace' ), 'public' => true, 'chooses' => true, 'activities' => $company ),
			'professional' => array( 'label' => __( 'Professional', 'sm-marketplace' ), 'public' => true, 'chooses' => true, 'activities' => $trades ),
			'client'       => array( 'label' => __( 'Client', 'sm-marketplace' ), 'public' => false, 'chooses' => false, 'activities' => array() ),
			'engineer'     => array( 'label' => __( 'Partner engineer', 'sm-marketplace' ), 'public' => false, 'chooses' => false, 'activities' => array() ),
		),
		'labels'  => $company + $trades + array( 'client' => __( 'Client', 'sm-marketplace' ), 'engineer' => __( 'Partner engineer', 'sm-marketplace' ) ),
	);
	return $types;
}

/** Account class of an account or company array ('company', 'professional', 'client', 'engineer'); maps the demo's former names. */
function sm_account_class( $data ) {
	$class = (string) ( $data['class'] ?? 'company' );
	$map   = array( 'commercial' => 'company', 'engineering' => 'engineer' );
	return $map[ $class ] ?? $class;
}

/** Label of an account class. */
function sm_class_label( $class ) {
	$types = sm_account_types();
	return $types['classes'][ $class ]['label'] ?? $class;
}

/** Labels of the types and trades (code => label), in the current language. */
function sm_activity_labels() {
	return sm_account_types()['labels'];
}

/**
 * Avatar of an account: its logo / photo, or its initials.
 *
 * @param array  $company Company (theme shape: logo, initials, class).
 * @param string $size    sm (32), md (48, default), lg (88).
 */
function sm_avatar( $company, $size = 'md' ) {
	$class = sm_account_class( $company );
	$logo  = (string) ( $company['logo'] ?? ( $company['photo'] ?? '' ) );
	$cls   = 'sm-avatar sm-avatar--' . $class . ( 'md' !== $size ? ' sm-avatar--' . $size : '' ) . ( $logo ? ' sm-avatar--img' : '' );
	if ( $logo ) {
		printf( '<span class="%s" aria-hidden="true"><img src="%s" alt="" loading="lazy" decoding="async"></span>', esc_attr( $cls ), esc_url( $logo ) );
		return;
	}
	printf( '<span class="%s" aria-hidden="true">%s</span>', esc_attr( $cls ), esc_html( (string) ( $company['initials'] ?? 'SM' ) ) );
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
		// "ReuSteel" -> "Reu" + accented "Steel" (any CamelCase name is split the same way).
		$name = sm_theme_brand_name();
		if ( preg_match( '/^(\p{Lu}\p{Ll}+)(\p{Lu}.*)$/u', $name, $m ) ) {
			echo '<span class="sm-brand__name">' . esc_html( $m[1] ) . '<span class="sm-brand__accent">' . esc_html( $m[2] ) . '</span></span>';
		} else {
			echo '<span class="sm-brand__name">' . esc_html( $name ) . '</span>';
		}
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

/** Labels of the quantity units of a request for quotation. */
function sm_rfq_unit_label( $unit, $qty = 2 ) {
	$labels = array(
		'pieces' => _n( 'piece', 'pieces', max( 1, (int) ceil( (float) $qty ) ), 'sm-marketplace' ),
		'kg'     => 'kg',
		't'      => 't',
		'm'      => 'm',
		'm2'     => 'm²',
		'lot'    => __( 'lot', 'sm-marketplace' ),
	);
	return $labels[ $unit ] ?? $unit;
}

/** Status labels of a request for quotation. */
function sm_rfq_status_labels() {
	return array(
		'pending'  => __( 'Waiting for review', 'sm-marketplace' ),
		'open'     => __( 'Open for offers', 'sm-marketplace' ),
		'awarded'  => __( 'Awarded', 'sm-marketplace' ),
		'closed'   => __( 'Closed', 'sm-marketplace' ),
		'expired'  => __( 'Deadline passed', 'sm-marketplace' ),
		'rejected' => __( 'Changes requested', 'sm-marketplace' ),
	);
}

/** Status labels of an offer. */
function sm_offer_status_labels() {
	return array(
		'submitted'   => __( 'Sent', 'sm-marketplace' ),
		'shortlisted' => __( 'Shortlisted', 'sm-marketplace' ),
		'accepted'    => __( 'Accepted', 'sm-marketplace' ),
		'declined'    => __( 'Not retained', 'sm-marketplace' ),
		'withdrawn'   => __( 'Withdrawn', 'sm-marketplace' ),
	);
}

/** Date as "12 nov. 2026" in the current language. */
function sm_date( $ymd ) {
	if ( ! $ymd || ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})/', (string) $ymd, $m ) ) {
		return '';
	}
	$months = array(
		'fr' => array( 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.' ),
		'ar' => array( 'جانفي', 'فيفري', 'مارس', 'أفريل', 'ماي', 'جوان', 'جويلية', 'أوت', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر' ),
	);
	$lang = 'ar' === sm_theme_current_language() ? 'ar' : 'fr';
	return (int) $m[3] . "\u{00A0}" . $months[ $lang ][ (int) $m[2] - 1 ] . "\u{00A0}" . $m[1];
}

/** Public number of the request being viewed on the RFQ page (0 = the board). */
function sm_theme_current_rfq_id() {
	$id = get_query_var( 'sm_id' );
	return absint( $id ? $id : sm_theme_query_arg( 'id', 0 ) );
}

/** Units of an indicative service price. */
function sm_service_unit_label( $unit ) {
	$labels = array(
		'project' => __( 'project', 'sm-marketplace' ),
		'kg'      => 'kg',
		't'       => 't',
		'm2'      => 'm²',
		'ml'      => 'ml',
		'piece'   => __( 'piece', 'sm-marketplace' ),
		'hour'    => __( 'hour', 'sm-marketplace' ),
		'day'     => __( 'day', 'sm-marketplace' ),
	);
	return $labels[ $unit ] ?? $unit;
}

/** "On quotation" or "From 180 DA/kg" (safe HTML). */
function sm_service_price( array $service ) {
	if ( 'from' !== ( $service['price_mode'] ?? '' ) || empty( $service['price_from'] ) ) {
		return esc_html__( 'On quotation', 'sm-marketplace' );
	}
	/* translators: %s: price with unit. */
	return sprintf( esc_html__( 'From %s', 'sm-marketplace' ), sm_per_unit( $service['price_from'], sm_service_unit_label( $service['price_unit'] ) ) );
}

/** Status labels of a service request. */
function sm_service_request_labels() {
	return array(
		'new'               => __( 'New', 'sm-marketplace' ),
		'quoted'            => __( 'Quote sent', 'sm-marketplace' ),
		'accepted'          => __( 'Accepted', 'sm-marketplace' ),
		'declined'          => __( 'Quote declined', 'sm-marketplace' ),
		'declined_provider' => __( 'Declined by the provider', 'sm-marketplace' ),
		'cancelled'         => __( 'Cancelled', 'sm-marketplace' ),
	);
}

/** Lines of a free text as a list ("one per line" fields). */
function sm_lines( $text ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\n/', (string) $text ) ) ) );
}

/** Status pill of a mission (tone from SM Engineering: action, progress, review, done, off). */
function sm_mission_status( array $mission ) {
	$tones = array( 'action' => 'warn', 'progress' => 'info', 'review' => 'info', 'done' => 'ok', 'off' => 'muted' );
	printf( '<span class="sm-status sm-status--%s">%s</span>', esc_attr( $tones[ $mission['tone'] ?? 'off' ] ?? 'muted' ), esc_html( (string) ( $mission['label'] ?? '' ) ) );
}

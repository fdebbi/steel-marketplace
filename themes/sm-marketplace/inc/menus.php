<?php
/**
 * Header navigation: marketplace mega menu built from the categories that the
 * marketplace plugins expose (demo categories meanwhile), plus optional WP menus.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

/** Top-level navigation entries. */
function sm_theme_nav_items() {
	$items = array(
		array(
			'key'   => 'products',
			'label' => __( 'Steel products', 'sm-marketplace' ),
			'icon'  => 'layers',
			'url'   => sm_page_url( 'products' ),
			'desc'  => __( 'New profiles, tubes, plates and accessories, priced per kg.', 'sm-marketplace' ),
			'mega'  => 'products',
		),
		array(
			'key'   => 'reuse',
			'label' => __( 'Steel reuse', 'sm-marketplace' ),
			'icon'  => 'recycle',
			'url'   => sm_page_url( 'reuse' ),
			'desc'  => __( 'Offcuts, surplus, used profiles and scrap.', 'sm-marketplace' ),
			'mega'  => 'reuse',
		),
		array(
			'key'   => 'buildings',
			'label' => __( 'Steel buildings', 'sm-marketplace' ),
			'icon'  => 'warehouse',
			'url'   => sm_page_url( 'buildings' ),
			'desc'  => __( 'New and used warehouses, assembled or dismantled.', 'sm-marketplace' ),
			'mega'  => 'buildings',
		),
		array(
			'key'   => 'services',
			'label' => __( 'Services', 'sm-marketplace' ),
			'icon'  => 'wrench',
			'url'   => sm_page_url( 'services' ),
			'mega'  => 'services',
		),
		array(
			'key'   => 'rfq',
			'label' => __( 'Requests for quotation', 'sm-marketplace' ),
			'icon'  => 'rfq',
			'url'   => sm_page_url( 'rfq' ),
		),
	);
	return apply_filters( 'sm_theme_nav_items', $items );
}

/** Mega menu panel for one market. */
function sm_theme_mega_panel( $item ) {
	echo '<div class="sm-mega" id="sm-mega-' . esc_attr( $item['key'] ) . '" hidden>';
	echo '<div class="sm-container sm-mega__inner">';

	if ( 'services' === $item['mega'] ) {
		$columns = array(
			array( 'services', 'wrench', __( 'Fabrication & installation', 'sm-marketplace' ), __( 'Cutting, welding, assembly, installation and dismantling.', 'sm-marketplace' ) ),
			array( 'engineers', 'hard-hat', __( 'Engineering', 'sm-marketplace' ), __( 'Structural design, calculations and inspection of used structures.', 'sm-marketplace' ) ),
			array( 'companies', 'company', __( 'Company directory', 'sm-marketplace' ), __( 'Manufacturers, suppliers, installers and recyclers.', 'sm-marketplace' ) ),
			array( 'professionals', 'user', __( 'Professionals directory', 'sm-marketplace' ), __( 'Welders, steel erectors, boilermakers and other trades.', 'sm-marketplace' ) ),
		);
		echo '<ul class="sm-mega__cards">';
		foreach ( $columns as list( $key, $icon, $label, $desc ) ) {
			echo '<li><a class="sm-mega__card" href="' . esc_url( sm_page_url( $key ) ) . '">' . sm_get_icon( $icon ) . '<span><strong>' . esc_html( $label ) . '</strong><small>' . esc_html( $desc ) . '</small></span></a></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</ul>';
	} else {
		echo '<div class="sm-mega__intro">' . sm_get_icon( $item['icon'], 'sm-mega__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<p class="sm-mega__title">' . esc_html( $item['label'] ) . '</p>';
		echo '<p class="sm-mega__desc">' . esc_html( $item['desc'] ) . '</p>';
		echo '<a class="sm-link" href="' . esc_url( $item['url'] ) . '">' . esc_html__( 'View all listings', 'sm-marketplace' ) . sm_get_icon( 'arrow-end' ) . '</a></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<ul class="sm-mega__links">';
		foreach ( sm_theme_categories( $item['mega'] ) as $slug => $label ) {
			echo '<li><a href="' . esc_url( add_query_arg( 'category', $slug, $item['url'] ) ) . '">' . esc_html( sm_t( $label ) ) . '</a></li>';
		}
		echo '</ul>';
	}

	echo '</div></div>';
}

/** Language switcher (header and mobile drawer). */
function sm_theme_language_switcher( $variant = 'header' ) {
	$current = sm_theme_current_language();
	echo '<div class="sm-lang sm-lang--' . esc_attr( $variant ) . '" role="group" aria-label="' . esc_attr__( 'Language', 'sm-marketplace' ) . '">';
	foreach ( sm_theme_languages() as $code => $language ) {
		$is_current = $code === $current;
		printf(
			'<a href="%1$s" lang="%2$s" hreflang="%2$s" class="sm-lang__item%3$s"%4$s>%5$s</a>',
			esc_url( sm_theme_language_url( $code ) ),
			esc_attr( $code ),
			$is_current ? ' is-current' : '',
			$is_current ? ' aria-current="true"' : '',
			esc_html( $language['label'] )
		);
	}
	echo '</div>';
}

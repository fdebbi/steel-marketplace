<?php
/**
 * Creates the pages the theme templates need and maps logical keys to them.
 *
 * Runs on theme activation, and can be re-run from Appearance -> SM Pages.
 * Only creates pages that are missing: it never deletes or overwrites content.
 * When the marketplace plugins provide their own routes (sm_page_url filter),
 * these pages simply stop being used.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

/** Logical page key => [ slug, French title, template file ]. */
function sm_theme_page_definitions() {
	return array(
		'home'        => array( 'accueil', 'Accueil', 'templates/front-page-template.php' ),
		'search'      => array( 'recherche', 'Recherche', 'templates/marketplace-search.php' ),
		'products'    => array( 'produits', 'Produits sidérurgiques', 'templates/marketplace-products.php' ),
		'reuse'       => array( 'reemploi', 'Réemploi de l’acier', 'templates/marketplace-reuse.php' ),
		'buildings'   => array( 'batiments', 'Bâtiments métalliques', 'templates/marketplace-buildings.php' ),
		'services'    => array( 'services', 'Fabrication et montage', 'templates/marketplace-services.php' ),
		'engineers'   => array( 'ingenierie', 'Ingénierie', 'templates/marketplace-engineers.php' ),
		'listing'     => array( 'annonce', 'Annonce', 'templates/single-listing.php' ),
		'companies'   => array( 'entreprises', 'Entreprises', 'templates/companies.php' ),
		'company'     => array( 'entreprise', 'Entreprise', 'templates/company-profile.php' ),
		'rfq'         => array( 'demandes-de-devis', 'Demandes de devis', 'templates/rfq-board.php' ),
		'price-index' => array( 'indice-prix', 'Indice des prix', 'templates/price-index.php' ),
		'weight'      => array( 'calcul-poids', 'Calculateur de poids', 'templates/weight-calculator.php' ),
		'pricing'     => array( 'tarifs', 'Tarifs', 'templates/pricing.php' ),
		'login'       => array( 'connexion', 'Connexion', 'templates/auth-login.php' ),
		'register'    => array( 'inscription', 'Inscription', 'templates/auth-register.php' ),
		'lost'        => array( 'mot-de-passe-oublie', 'Mot de passe oublié', 'templates/auth-lost.php' ),
		'account'     => array( 'compte', 'Mon compte', 'templates/dashboard.php' ),
		'styleguide'  => array( 'styleguide', 'Styleguide', 'templates/styleguide.php' ),
		'contact'     => array( 'contact', 'Contact', '' ),
	);
}

/** Create missing pages and store the key => ID map. */
function sm_theme_install_pages() {
	$map = get_option( 'sm_theme_pages', array() );

	foreach ( sm_theme_page_definitions() as $key => list( $slug, $title, $template ) ) {
		$existing = ! empty( $map[ $key ] ) ? get_post( $map[ $key ] ) : get_page_by_path( $slug );
		if ( $existing && 'trash' !== $existing->post_status ) {
			$page_id = $existing->ID;
		} else {
			$page_id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => $title,
					'post_name'    => $slug,
					'post_content' => '',
				)
			);
		}
		if ( $page_id && ! is_wp_error( $page_id ) ) {
			if ( $template ) {
				update_post_meta( $page_id, '_wp_page_template', $template );
			}
			$map[ $key ] = (int) $page_id;
		}
	}

	update_option( 'sm_theme_pages', $map, false );
	update_option( 'sm_theme_pages_version', SM_THEME_VERSION, false );

	if ( ! empty( $map['home'] ) && 'page' !== get_option( 'show_on_front' ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $map['home'] );
	}
}
add_action( 'after_switch_theme', 'sm_theme_install_pages' );

/** After a theme update, create the pages that new versions need (never overwrites). */
function sm_theme_maybe_install_pages() {
	if ( get_option( 'sm_theme_pages_version' ) !== SM_THEME_VERSION && current_user_can( 'edit_pages' ) ) {
		sm_theme_rename_brand();
		sm_theme_install_pages();
	}
}

/**
 * 0.6.0: the platform is now called ReuSteel. The old default name is
 * replaced; a name chosen by the administrator is kept.
 */
function sm_theme_rename_brand() {
	if ( 'Steel Marketplace' === get_theme_mod( 'sm_brand_name' ) ) {
		set_theme_mod( 'sm_brand_name', 'ReuSteel' );
	}
	if ( 'Steel Marketplace' === get_option( 'blogname' ) ) {
		update_option( 'blogname', 'ReuSteel' );
	}
}
add_action( 'admin_init', 'sm_theme_maybe_install_pages' );

/** Small admin screen to re-run the installer. */
function sm_theme_pages_admin_menu() {
	add_theme_page(
		__( 'SM Pages', 'sm-marketplace' ),
		__( 'SM Pages', 'sm-marketplace' ),
		'edit_theme_options',
		'sm-pages',
		'sm_theme_pages_admin_screen'
	);
}
add_action( 'admin_menu', 'sm_theme_pages_admin_menu' );

function sm_theme_pages_admin_screen() {
	if ( isset( $_POST['sm_install_pages'] ) && check_admin_referer( 'sm_install_pages' ) && current_user_can( 'edit_theme_options' ) ) {
		sm_theme_install_pages();
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Missing pages were created.', 'sm-marketplace' ) . '</p></div>';
	}
	$map = get_option( 'sm_theme_pages', array() );
	echo '<div class="wrap"><h1>' . esc_html__( 'SM Pages', 'sm-marketplace' ) . '</h1>';
	echo '<p>' . esc_html__( 'Pages used by the theme templates. Existing pages are never overwritten.', 'sm-marketplace' ) . '</p>';
	echo '<table class="widefat striped"><thead><tr><th>Key</th><th>Page</th></tr></thead><tbody>';
	foreach ( sm_theme_page_definitions() as $key => $def ) {
		$id = $map[ $key ] ?? 0;
		echo '<tr><td><code>' . esc_html( $key ) . '</code></td><td>' . ( $id ? '<a href="' . esc_url( get_edit_post_link( $id ) ) . '">' . esc_html( get_the_title( $id ) ) . '</a>' : '—' ) . '</td></tr>';
	}
	echo '</tbody></table><form method="post" style="margin-top:1em">';
	wp_nonce_field( 'sm_install_pages' );
	submit_button( __( 'Create missing pages', 'sm-marketplace' ), 'primary', 'sm_install_pages' );
	echo '</form></div>';
}

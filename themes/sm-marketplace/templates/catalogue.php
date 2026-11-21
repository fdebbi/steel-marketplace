<?php
/**
 * Template Name: SM — Catalogue of sections
 *
 * Public pages of the catalogue (0.59.0, sm-weight-engine 0.8.0):
 *   /catalogue/          the families
 *   /catalogue/ipe/      a family: table of its sizes
 *   /catalogue/ipe-200/  a size: weight per metre, bars, drawing, calculator,
 *                        listings in progress
 * Data: sm_theme_data( 'catalogue', { slug } ) — values of the weight engine only.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$sm_slug = sanitize_title( (string) ( get_query_var( 'sm_cat' ) ? get_query_var( 'sm_cat' ) : sm_theme_query_arg( 'sm_cat', '' ) ) );
$sm_cat  = sm_theme_data( 'catalogue', array( 'slug' => $sm_slug ) );
get_header();
if ( ! is_array( $sm_cat ) ) {
	status_header( 404 );
	echo '<div class="sm-container sm-section">';
	sm_component(
		'empty-state',
		array(
			'icon'         => 'ruler',
			'title'        => __( 'This section is not in the catalogue', 'sm-marketplace' ),
			'text'         => __( 'Choose a family of sections to see the sizes, their weight and their dimensions.', 'sm-marketplace' ),
			'action_label' => __( 'See the catalogue', 'sm-marketplace' ),
			'action_url'   => sm_page_url( 'catalogue' ),
		)
	);
	echo '</div>';
	get_footer();
	return;
}
get_template_part( 'template-parts/catalogue/' . $sm_cat['kind'], null, array( 'data' => $sm_cat ) );
get_footer();

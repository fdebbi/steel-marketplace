<?php
/**
 * Template Name: SM — Guides
 *
 * Practical guides (0.61.0, sm-guides 0.1.0):
 *   /guides/          the list of the guides
 *   /guides/{name}/   a guide: summary, table of contents, text, linked tools
 * Data: sm_theme_data( 'guides', { slug } ) — written in the administration (SM → Guides).
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$sm_slug  = sanitize_title( (string) ( get_query_var( 'sm_guide' ) ? get_query_var( 'sm_guide' ) : sm_theme_query_arg( 'sm_guide', '' ) ) );
$sm_guide = sm_theme_data( 'guides', array( 'slug' => $sm_slug ) );
get_header();
if ( ! is_array( $sm_guide ) || empty( $sm_guide['kind'] ) ) {
	if ( '' !== $sm_slug ) {
		status_header( 404 );
	}
	echo '<div class="sm-container sm-section">';
	sm_component(
		'empty-state',
		array(
			'icon'         => 'file',
			'title'        => '' !== $sm_slug ? __( 'This guide does not exist', 'sm-marketplace' ) : __( 'No guide for the moment', 'sm-marketplace' ),
			'text'         => __( 'The guides explain how to buy, sell and order steel on ReuSteel.', 'sm-marketplace' ),
			'action_label' => '' !== $sm_slug ? __( 'See the guides', 'sm-marketplace' ) : __( 'Back to the home page', 'sm-marketplace' ),
			'action_url'   => '' !== $sm_slug ? sm_page_url( 'guides' ) : home_url( '/' ),
		)
	);
	echo '</div>';
	get_footer();
	return;
}
get_template_part( 'template-parts/guides/' . ( 'guide' === $sm_guide['kind'] ? 'guide' : 'index' ), null, array( 'data' => $sm_guide ) );
get_footer();

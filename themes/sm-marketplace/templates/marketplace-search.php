<?php
/**
 * Template Name: SM — Search
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
get_header();
get_template_part(
	'template-parts/marketplace-archive',
	null,
	array(
		'market' => 'search',
		'title'  => __( 'Search results', 'sm-marketplace' ),
		'intro'  => __( 'Results across steel products, reuse and buildings.', 'sm-marketplace' ),
	)
);
get_footer();

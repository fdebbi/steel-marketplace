<?php
/**
 * Template Name: SM — Steel reuse
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
get_header();
get_template_part(
	'template-parts/marketplace-archive',
	null,
	array(
		'market' => 'reuse',
		'title'  => __( 'Steel reuse', 'sm-marketplace' ),
		'intro'  => __( 'Offcuts, site surplus, used profiles and scrap: search by profile, length, quantity and condition.', 'sm-marketplace' ),
	)
);
get_footer();

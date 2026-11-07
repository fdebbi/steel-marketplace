<?php
/**
 * Template Name: SM — Steel products
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
get_header();
get_template_part(
	'template-parts/marketplace-archive',
	null,
	array(
		'market' => 'products',
		'title'  => __( 'Steel products', 'sm-marketplace' ),
		'intro'  => __( 'New beams, channels, tubes, plates and accessories from manufacturers and distributors, priced per kilogram.', 'sm-marketplace' ),
	)
);
get_footer();

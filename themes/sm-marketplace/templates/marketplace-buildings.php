<?php
/**
 * Template Name: SM — Steel buildings
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
get_header();
get_template_part(
	'template-parts/marketplace-archive',
	null,
	array(
		'market' => 'buildings',
		'title'  => __( 'Steel buildings', 'sm-marketplace' ),
		'intro'  => __( 'New warehouse kits and used buildings, sold assembled or dismantled with their full element inventory.', 'sm-marketplace' ),
	)
);
get_footer();

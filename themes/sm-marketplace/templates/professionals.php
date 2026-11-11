<?php
/**
 * Template Name: SM — Professionals directory
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
get_header();
get_template_part( 'template-parts/directory', null, array( 'class' => 'professional' ) );
get_footer();

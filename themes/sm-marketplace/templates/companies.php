<?php
/**
 * Template Name: SM — Company directory
 *
 * Companies only (0.15.0); professionals have their own directory.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
// The old "?class=professional" links now open the professionals directory.
if ( 'professional' === sm_theme_query_arg( 'class' ) && ! headers_sent() ) {
	wp_safe_redirect( sm_page_url( 'professionals' ), 301 );
	exit;
}
get_header();
get_template_part( 'template-parts/directory', null, array( 'class' => 'company' ) );
get_footer();

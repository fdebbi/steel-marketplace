<?php
/**
 * Editorial pages (about, terms, contact...).
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	?>
	<section class="sm-pagehead"><div class="sm-container"><?php sm_breadcrumb( array( array( get_the_title(), '' ) ) ); ?><h1 class="sm-pagehead__title"><?php the_title(); ?></h1></div></section>
	<div class="sm-container sm-section"><div class="sm-prose sm-panel"><?php the_content(); ?></div></div>
	<?php
endwhile;
get_footer();

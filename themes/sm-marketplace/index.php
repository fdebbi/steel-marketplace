<?php
/**
 * Fallback template (blog posts, archives not owned by a plugin).
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<div class="sm-container sm-section sm-prose">
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<article <?php post_class( 'sm-panel' ); ?>>
				<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
				<?php the_excerpt(); ?>
			</article>
		<?php endwhile; ?>
		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<?php sm_component( 'empty-state', array( 'title' => __( 'Nothing here yet', 'sm-marketplace' ) ) ); ?>
	<?php endif; ?>
</div>
<?php
get_footer();

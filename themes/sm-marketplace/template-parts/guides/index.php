<?php
/**
 * The list of the guides (0.61.0).
 *
 * @var array $args { data: { items } }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$d = $args['data'];
?>
<section class="sm-pagehead">
	<div class="sm-container">
		<?php sm_breadcrumb( array( array( __( 'Guides', 'sm-marketplace' ), '' ) ) ); ?>
		<h1 class="sm-pagehead__title"><?php esc_html_e( 'Steel guides', 'sm-marketplace' ); ?></h1>
		<p class="sm-pagehead__intro"><?php esc_html_e( 'Practical guides to buy, sell and order steel in Algeria: what to check, how to read a listing, how to cut less. Each guide links to the tools of the site.', 'sm-marketplace' ); ?></p>
	</div>
</section>
<div class="sm-container sm-section">
	<?php if ( empty( $d['items'] ) ) : ?>
		<?php
		sm_component(
			'empty-state',
			array(
				'icon'  => 'file',
				'title' => __( 'No guide for the moment', 'sm-marketplace' ),
				'text'  => __( 'The guides explain how to buy, sell and order steel on ReuSteel.', 'sm-marketplace' ),
			)
		);
		?>
	<?php else : ?>
		<div class="sm-guidegrid">
			<?php foreach ( $d['items'] as $g ) : ?>
				<?php get_template_part( 'template-parts/guides/card', null, array( 'guide' => $g ) ); ?>
			<?php endforeach; ?>
		</div>
		<p class="sm-guide-note"><?php esc_html_e( 'The guides describe how ReuSteel works and practical methods. They give no market price and no statistics.', 'sm-marketplace' ); ?></p>
	<?php endif; ?>
</div>

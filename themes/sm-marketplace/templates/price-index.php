<?php
/**
 * Template Name: SM — Price index
 *
 * Layout for the steel price index (sm-analytics, Phase 10). The charts and tables are
 * injected through the 'price-index.body' slot; until then an explanatory state is shown.
 * The price curve of the home page is shown at the top (same source, 0.23.0).
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<section class="sm-pagehead">
	<div class="sm-container">
		<?php sm_breadcrumb( array( array( __( 'Price index', 'sm-marketplace' ), '' ) ) ); ?>
		<h1 class="sm-pagehead__title"><?php esc_html_e( 'Steel price index', 'sm-marketplace' ); ?></h1>
		<p class="sm-pagehead__intro"><?php esc_html_e( 'Average advertised prices per kg by profile, condition, wilaya and period.', 'sm-marketplace' ); ?></p>
	</div>
</section>
<div class="sm-container sm-section">
	<?php sm_component( 'hero-trend', array( 'variant' => 'page' ) ); ?>
	<p class="sm-callout sm-callout--warn"><?php sm_icon( 'alert' ); ?><span><?php esc_html_e( 'These are asking prices published by sellers on the platform, not official market prices and not completed transaction prices.', 'sm-marketplace' ); ?></span></p>
	<?php
	ob_start();
	do_action( 'sm_slot', 'price-index.body' );
	$body = ob_get_clean();
	if ( $body ) {
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by sm-analytics.
	} else {
		sm_component(
			'empty-state',
			array(
				'icon'  => 'chart',
				'title' => __( 'The index will appear once enough listings are published', 'sm-marketplace' ),
				'text'  => __( 'Charts by profile, condition and wilaya are provided by the analytics module.', 'sm-marketplace' ),
			)
		);
	}
	?>
</div>
<?php
get_footer();

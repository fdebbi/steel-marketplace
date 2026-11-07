<?php
/**
 * Template Name: SM — Styleguide
 *
 * Component catalogue for design review in Arabic and French. Visible to
 * administrators only (or anyone while demo mode is on in a local environment).
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

if ( ! current_user_can( 'edit_theme_options' ) && ! ( sm_theme_demo_enabled() && 'local' === wp_get_environment_type() ) ) {
	wp_safe_redirect( home_url( '/' ) );
	exit;
}
get_header();
$demo = sm_theme_listings( array( 'per_page' => 3 ) )['items'];
?>
<section class="sm-pagehead"><div class="sm-container"><h1 class="sm-pagehead__title"><?php esc_html_e( 'Styleguide', 'sm-marketplace' ); ?></h1><p class="sm-pagehead__intro"><?php esc_html_e( 'Every component in the current language. Switch language in the header to review RTL and LTR.', 'sm-marketplace' ); ?></p></div></section>
<div class="sm-container sm-section sm-styleguide">
	<section class="sm-panel">
		<h2 class="sm-panel__title"><?php esc_html_e( 'Brand colours', 'sm-marketplace' ); ?></h2>
		<ul class="sm-swatches">
			<?php foreach ( sm_theme_color_tokens() as $key => $token ) : ?>
				<li><span style="background:var(<?php echo esc_attr( $token['var'] ); ?>)"></span><strong><?php echo esc_html( $token['label'] ); ?></strong><code><?php echo esc_html( sm_theme_color( $key ) ); ?></code></li>
			<?php endforeach; ?>
		</ul>
		<p class="sm-help"><?php esc_html_e( 'Editable in Appearance → Customize → SM Brand.', 'sm-marketplace' ); ?></p>
	</section>
	<section class="sm-panel">
		<h2 class="sm-panel__title"><?php esc_html_e( 'Buttons', 'sm-marketplace' ); ?></h2>
		<div class="sm-row-wrap">
			<a class="sm-btn sm-btn--accent" href="#"><?php sm_icon( 'plus' ); ?><?php esc_html_e( 'Publish', 'sm-marketplace' ); ?></a>
			<a class="sm-btn sm-btn--primary" href="#"><?php esc_html_e( 'Continue', 'sm-marketplace' ); ?><?php sm_icon( 'chevron-end' ); ?></a>
			<a class="sm-btn sm-btn--primary-outline" href="#"><?php esc_html_e( 'Request a quotation', 'sm-marketplace' ); ?></a>
			<a class="sm-btn sm-btn--ghost" href="#"><?php esc_html_e( 'Reset', 'sm-marketplace' ); ?></a>
		</div>
	</section>
	<section class="sm-panel">
		<h2 class="sm-panel__title"><?php esc_html_e( 'Condition scale', 'sm-marketplace' ); ?></h2>
		<div class="sm-row-wrap"><?php foreach ( array_keys( sm_condition_labels() ) as $c ) : ?><?php sm_component( 'condition-badge', array( 'condition' => $c ) ); ?><?php endforeach; ?></div>
	</section>
	<section class="sm-panel">
		<h2 class="sm-panel__title"><?php esc_html_e( 'Status and chips', 'sm-marketplace' ); ?></h2>
		<div class="sm-row-wrap">
			<span class="sm-status sm-status--ok"><?php esc_html_e( 'Published', 'sm-marketplace' ); ?></span>
			<span class="sm-status sm-status--warn"><?php esc_html_e( 'Quote sent', 'sm-marketplace' ); ?></span>
			<span class="sm-status sm-status--info"><?php esc_html_e( 'New', 'sm-marketplace' ); ?></span>
			<span class="sm-chip sm-chip--tech"><?php echo sm_tech( 'HEB 240' ); // phpcs:ignore ?></span>
			<span class="sm-weight-src sm-weight-src--actual"><?php esc_html_e( 'actual', 'sm-marketplace' ); ?></span>
			<span class="sm-weight-src sm-weight-src--theoretical"><?php esc_html_e( 'theoretical', 'sm-marketplace' ); ?></span>
		</div>
	</section>
	<section class="sm-section">
		<h2><?php esc_html_e( 'Listing cards', 'sm-marketplace' ); ?></h2>
		<div class="sm-grid sm-grid--grid sm-grid--3"><?php foreach ( $demo as $item ) : ?><?php sm_component( 'card-listing', array( 'item' => $item ) ); ?><?php endforeach; ?></div>
	</section>
</div>
<?php
get_footer();

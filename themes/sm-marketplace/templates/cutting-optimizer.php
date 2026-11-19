<?php
/**
 * Template Name: SM — Cutting optimiser
 *
 * Cutting optimiser (0.46.0, sm-weight-engine 0.4.0): the pieces to cut,
 * profile by profile, the commercial bars and the offcuts in stock; the plan
 * that buys the fewest bars, drawn bar by bar, with the offcuts in kg and %.
 * A plain GET form (the calculation can be shared as a link); the calculation
 * is done by sm-weight-engine, never here. assets/js/cutting.js fills the size
 * lists, adds rows and reads a list pasted from Excel.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only GET form.
$sm_raw = wp_unslash( $_GET ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
// phpcs:enable
$sm_secs = sm_theme_data( 'cutting_sections' );

get_header();
?>
<section class="sm-pagehead">
	<div class="sm-container">
		<?php sm_breadcrumb( array( array( __( 'Cutting optimiser', 'sm-marketplace' ), '' ) ) ); ?>
		<h1 class="sm-pagehead__title smc-title"><?php esc_html_e( 'Cutting optimiser', 'sm-marketplace' ); ?> <span class="smc-free"><?php esc_html_e( 'Free · exact calculation', 'sm-marketplace' ); ?></span></h1>
		<p class="sm-pagehead__intro"><?php esc_html_e( 'Give your pieces profile by profile: the optimiser finds the cutting that leaves the fewest offcuts, bar by bar, and tells you how many bars to buy. Weights come from the catalogue of the platform.', 'sm-marketplace' ); ?></p>
	</div>
</section>
<div class="sm-container sm-section">
	<?php
	if ( ! is_array( $sm_secs ) || ! $sm_secs ) {
		sm_component( 'empty-state', array( 'icon' => 'ruler', 'title' => __( 'The cutting optimiser is not available yet.', 'sm-marketplace' ) ) );
	} else {
		$sm_in  = (array) sm_theme_data( 'cutting_input', $sm_raw );
		$sm_run = ! empty( $sm_in['lines'] );
		$sm_res = $sm_run ? sm_theme_data( 'cutting', $sm_raw ) : null;
		sm_component( 'cutting-optimizer', array( 'sections' => $sm_secs, 'input' => $sm_in, 'result' => $sm_res, 'raw' => $sm_raw ) );
	}
	?>
</div>
<?php
get_footer();

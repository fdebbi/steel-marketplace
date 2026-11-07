<?php
/**
 * Template Name: SM — Weight calculator
 *
 * Theoretical weight of steel sections, tubes, bars and plates (sm-weight-engine).
 * The choices travel in the URL (?family=IPE&section=7&bars=10), so a
 * calculation can be bookmarked or shared. ?smw_partial=1 returns only the
 * calculator, for the live refresh done by assets/js/weight.js.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only GET form.
$sm_state = array();
foreach ( array( 'family', 'section', 'mode', 'length', 'length_custom', 'width', 'plate_length', 'bars', 'total_kg' ) as $sm_key ) {
	if ( isset( $_GET[ $sm_key ] ) && '' !== $_GET[ $sm_key ] ) {
		$sm_state[ $sm_key ] = sanitize_text_field( wp_unslash( $_GET[ $sm_key ] ) );
	}
}
if ( isset( $_GET['dims'] ) && is_array( $_GET['dims'] ) ) {
	$sm_state['dims'] = array_map( 'sanitize_text_field', wp_unslash( $_GET['dims'] ) );
}
$sm_partial = ! empty( $_GET['smw_partial'] );
// phpcs:enable

if ( $sm_partial ) {
	sm_component( 'weight-calculator', array( 'state' => $sm_state ) );
	return;
}

get_header();
?>
<section class="sm-pagehead">
	<div class="sm-container">
		<?php sm_breadcrumb( array( array( __( 'Weight calculator', 'sm-marketplace' ), '' ) ) ); ?>
		<h1 class="sm-pagehead__title"><?php esc_html_e( 'Steel weight calculator', 'sm-marketplace' ); ?></h1>
		<p class="sm-pagehead__intro"><?php esc_html_e( 'Choose a section, its size and the quantity: the weight is calculated from the European standard tables. You can also start from a total weight to know how many bars it makes.', 'sm-marketplace' ); ?></p>
	</div>
</section>
<div class="sm-container sm-section">
	<?php sm_component( 'weight-calculator', array( 'state' => $sm_state ) ); ?>
</div>
<?php
get_footer();

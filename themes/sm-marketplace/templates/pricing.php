<?php
/**
 * Template Name: SM — Plans
 *
 * Plan cards. Prices and limits are never hardcoded: they come from sm-subscriptions
 * through the sm_theme_data_plans filter. Without it, the cards show the plan structure
 * and say that prices are set by the administrator.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
get_header();
$plans = sm_theme_data( 'plans' );
if ( ! $plans ) {
	$plans = array(
		array( 'name' => __( 'Free', 'sm-marketplace' ), 'price' => null, 'features' => array( __( 'Buy and contact sellers', 'sm-marketplace' ), __( 'Limited number of listings', 'sm-marketplace' ), __( 'Requests for quotation', 'sm-marketplace' ) ) ),
		array( 'name' => __( 'Business', 'sm-marketplace' ), 'price' => null, 'highlight' => true, 'features' => array( __( 'More listings and featured slots', 'sm-marketplace' ), __( 'Company page with verified badge', 'sm-marketplace' ), __( 'Listing statistics', 'sm-marketplace' ), __( 'Monthly AI credits', 'sm-marketplace' ) ) ),
		array( 'name' => __( 'Pro', 'sm-marketplace' ), 'price' => null, 'features' => array( __( 'Highest limits', 'sm-marketplace' ), __( 'Premium visibility', 'sm-marketplace' ), __( 'Priority on quotation requests', 'sm-marketplace' ), __( 'Extended AI credits', 'sm-marketplace' ) ) ),
	);
}
?>
<section class="sm-pagehead">
	<div class="sm-container">
		<?php sm_breadcrumb( array( array( __( 'Plans', 'sm-marketplace' ), '' ) ) ); ?>
		<h1 class="sm-pagehead__title"><?php esc_html_e( 'Plans', 'sm-marketplace' ); ?></h1>
		<p class="sm-pagehead__intro"><?php esc_html_e( 'Start free. Upgrade when you need more listings, visibility and AI credits. Payment by bank transfer, CCP or BaridiMob, activated by our team.', 'sm-marketplace' ); ?></p>
	</div>
</section>
<div class="sm-container sm-section">
	<div class="sm-plans">
		<?php foreach ( $plans as $plan ) : ?>
			<article class="sm-plan<?php echo ! empty( $plan['highlight'] ) ? ' sm-plan--highlight' : ''; ?>">
				<h2 class="sm-plan__name"><?php echo esc_html( $plan['name'] ); ?></h2>
				<p class="sm-plan__price"><?php echo null === $plan['price'] ? esc_html__( 'Price set by the administrator', 'sm-marketplace' ) : esc_html( sm_money( $plan['price'] ) ); ?></p>
				<ul class="sm-checklist"><?php foreach ( $plan['features'] as $f ) : ?><li><?php sm_icon( 'check' ); ?><?php echo esc_html( $f ); ?></li><?php endforeach; ?></ul>
				<a class="sm-btn <?php echo ! empty( $plan['highlight'] ) ? 'sm-btn--accent' : 'sm-btn--primary-outline'; ?> sm-btn--block" href="<?php echo esc_url( sm_page_url( 'register' ) ); ?>"><?php esc_html_e( 'Choose', 'sm-marketplace' ); ?></a>
			</article>
		<?php endforeach; ?>
	</div>
</div>
<?php
get_footer();

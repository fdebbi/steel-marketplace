<?php
/**
 * Template Name: SM — Services
 *
 * Fabrication, installation and dismantling services (sm-services, catalogue "fabrication").
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
get_header();
$set = (array) sm_theme_data( 'services_settings' );
$cta = ! empty( $set['kinds']['fabrication']['allowed'] ) ? sm_page_url( 'account', array( 'view' => 'services', 'new' => 'fabrication' ) ) : sm_page_url( 'register' );
?>
<section class="sm-pagehead sm-pagehead--services">
	<div class="sm-container">
		<?php sm_breadcrumb( array( array( __( 'Fabrication & installation', 'sm-marketplace' ), '' ) ) ); ?>
		<div class="sm-pagehead__row">
			<div>
				<h1 class="sm-pagehead__title"><?php esc_html_e( 'Fabrication & installation', 'sm-marketplace' ); ?></h1>
				<p class="sm-pagehead__intro"><?php esc_html_e( 'Workshops and site teams for cutting, welding, assembly, installation and dismantling of steel structures.', 'sm-marketplace' ); ?></p>
			</div>
			<a class="sm-btn sm-btn--accent" href="<?php echo esc_url( $cta ); ?>"><?php sm_icon( 'plus' ); ?><?php esc_html_e( 'Offer my services', 'sm-marketplace' ); ?></a>
		</div>
	</div>
</section>
<div class="sm-container sm-section sms-board">
	<?php sm_component( 'services-board', array( 'kind' => 'fabrication', 'page' => 'services' ) ); ?>
</div>
<?php
get_footer();

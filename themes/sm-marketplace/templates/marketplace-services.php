<?php
/**
 * Template Name: SM — Services
 *
 * Fabrication, installation and dismantling services (sm-fabrication, Phase 8).
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
get_header();
$kinds = array(
	__( 'Cutting', 'sm-marketplace' ), __( 'Drilling', 'sm-marketplace' ), __( 'Welding', 'sm-marketplace' ), __( 'Structural fabrication', 'sm-marketplace' ),
	__( 'Sandblasting', 'sm-marketplace' ), __( 'Painting', 'sm-marketplace' ), __( 'Galvanizing', 'sm-marketplace' ), __( 'Installation', 'sm-marketplace' ),
	__( 'Dismantling', 'sm-marketplace' ), __( 'Transport coordination', 'sm-marketplace' ),
);
?>
<section class="sm-pagehead">
	<div class="sm-container">
		<?php sm_breadcrumb( array( array( __( 'Fabrication & installation', 'sm-marketplace' ), '' ) ) ); ?>
		<div class="sm-pagehead__row">
			<div>
				<h1 class="sm-pagehead__title"><?php esc_html_e( 'Fabrication & installation', 'sm-marketplace' ); ?></h1>
				<p class="sm-pagehead__intro"><?php esc_html_e( 'Workshops and site teams for cutting, welding, assembly, installation and dismantling of steel structures.', 'sm-marketplace' ); ?></p>
			</div>
		</div>
		<ul class="sm-catpills">
			<?php foreach ( $kinds as $kind ) : ?><li><a href="#"><?php echo esc_html( $kind ); ?></a></li><?php endforeach; ?>
		</ul>
	</div>
</section>
<div class="sm-container sm-section">
	<div class="sm-grid sm-grid--grid sm-grid--3">
		<?php foreach ( sm_theme_services( array( 'kind' => 'fabrication' ) ) as $service ) : ?>
			<?php sm_component( 'card-service', array( 'service' => $service ) ); ?>
		<?php endforeach; ?>
	</div>
</div>
<?php
get_footer();

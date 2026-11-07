<?php
/**
 * Template Name: SM — Company directory
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<section class="sm-pagehead">
	<div class="sm-container">
		<?php sm_breadcrumb( array( array( __( 'Company directory', 'sm-marketplace' ), '' ) ) ); ?>
		<h1 class="sm-pagehead__title"><?php esc_html_e( 'Company directory', 'sm-marketplace' ); ?></h1>
		<p class="sm-pagehead__intro"><?php esc_html_e( 'Manufacturers, distributors, installers, recyclers, project owners and engineering offices.', 'sm-marketplace' ); ?></p>
		<ul class="sm-catpills">
			<li><a href="#" aria-current="true"><?php esc_html_e( 'All', 'sm-marketplace' ); ?></a></li>
			<?php foreach ( sm_activity_labels() as $label ) : ?><li><a href="#"><?php echo esc_html( $label ); ?></a></li><?php endforeach; ?>
		</ul>
	</div>
</section>
<div class="sm-container sm-section">
	<div class="sm-grid sm-grid--companies">
		<?php foreach ( sm_theme_companies() as $company ) : ?>
			<?php sm_component( 'card-company', array( 'company' => $company ) ); ?>
		<?php endforeach; ?>
	</div>
</div>
<?php
get_footer();

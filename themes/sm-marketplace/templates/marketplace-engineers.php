<?php
/**
 * Template Name: SM — Engineering
 *
 * Engineering services (sm-services, catalogue "engineering") and engineering offices.
 * Kept apart from commercial listings: no price per kg, no stock.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
get_header();
$engineers = sm_theme_companies( array( 'class' => 'engineering' ) );
$set       = (array) sm_theme_data( 'services_settings' );
$cta       = ! empty( $set['kinds']['engineering']['allowed'] ) ? sm_page_url( 'account', array( 'view' => 'services', 'new' => 'engineering' ) ) : sm_page_url( 'register', array( 'type' => 'engineering' ) );
?>
<section class="sm-pagehead sm-pagehead--engineers">
	<div class="sm-container">
		<?php sm_breadcrumb( array( array( __( 'Engineering', 'sm-marketplace' ), '' ) ) ); ?>
		<div class="sm-pagehead__row">
			<div>
				<h1 class="sm-pagehead__title"><?php esc_html_e( 'Engineering services', 'sm-marketplace' ); ?></h1>
				<p class="sm-pagehead__intro"><?php esc_html_e( 'Verified engineers and engineering offices for steel design, calculation notes, drawings and inspection of structures before reuse.', 'sm-marketplace' ); ?></p>
			</div>
			<a class="sm-btn sm-btn--primary" href="<?php echo esc_url( $cta ); ?>"><?php sm_icon( 'hard-hat' ); ?><?php echo esc_html( ! empty( $set['kinds']['engineering']['allowed'] ) ? __( 'Publish an engineering service', 'sm-marketplace' ) : __( 'Join as an engineer', 'sm-marketplace' ) ); ?></a>
		</div>
	</div>
</section>
<div class="sm-container sm-section sms-board">
	<?php sm_component( 'services-board', array( 'kind' => 'engineering', 'page' => 'engineers' ) ); ?>
	<?php if ( $engineers ) : ?>
		<div class="sm-section__head sm-section__head--spaced"><h2><?php esc_html_e( 'Engineering offices', 'sm-marketplace' ); ?></h2></div>
		<div class="sm-grid sm-grid--companies">
			<?php foreach ( $engineers as $company ) : ?>
				<?php sm_component( 'card-company', array( 'company' => $company ) ); ?>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	<p class="sm-callout"><?php sm_icon( 'info' ); ?><span><?php esc_html_e( 'AI tools on this platform never replace a structural calculation or an engineer’s signature.', 'sm-marketplace' ); ?></span></p>
</div>
<?php
get_footer();

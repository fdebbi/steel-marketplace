<?php
/**
 * Template Name: SM — Engineering
 *
 * Engineering services marketplace (sm-engineer-services, Phase 8).
 * Kept visually separate from commercial listings: no price per kg, no stock.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
get_header();
$engineers = sm_theme_companies( array( 'class' => 'engineering' ) );
$kinds     = array(
	__( 'Structural steel design', 'sm-marketplace' ), __( 'Structural calculations', 'sm-marketplace' ), __( 'Connection design', 'sm-marketplace' ),
	__( 'Shop drawings', 'sm-marketplace' ), __( 'Technical inspection', 'sm-marketplace' ), __( 'Used structure assessment', 'sm-marketplace' ),
);
?>
<section class="sm-pagehead sm-pagehead--engineers">
	<div class="sm-container">
		<?php sm_breadcrumb( array( array( __( 'Engineering', 'sm-marketplace' ), '' ) ) ); ?>
		<div class="sm-pagehead__row">
			<div>
				<h1 class="sm-pagehead__title"><?php esc_html_e( 'Engineering services', 'sm-marketplace' ); ?></h1>
				<p class="sm-pagehead__intro"><?php esc_html_e( 'Verified engineers and engineering offices for steel design, calculation notes, drawings and inspection of structures before reuse.', 'sm-marketplace' ); ?></p>
			</div>
			<a class="sm-btn sm-btn--primary" href="<?php echo esc_url( sm_page_url( 'register', array( 'type' => 'engineering' ) ) ); ?>"><?php sm_icon( 'hard-hat' ); ?><?php esc_html_e( 'Join as an engineer', 'sm-marketplace' ); ?></a>
		</div>
		<ul class="sm-catpills">
			<?php foreach ( $kinds as $kind ) : ?><li><a href="#"><?php echo esc_html( $kind ); ?></a></li><?php endforeach; ?>
		</ul>
	</div>
</section>
<div class="sm-container sm-section">
	<div class="sm-grid sm-grid--grid sm-grid--3">
		<?php foreach ( sm_theme_services( array( 'kind' => 'engineering' ) ) as $service ) : ?>
			<?php sm_component( 'card-service', array( 'service' => $service ) ); ?>
		<?php endforeach; ?>
	</div>
	<div class="sm-section__head sm-section__head--spaced"><h2><?php esc_html_e( 'Engineering offices', 'sm-marketplace' ); ?></h2></div>
	<div class="sm-grid sm-grid--companies">
		<?php foreach ( $engineers as $company ) : ?>
			<?php sm_component( 'card-company', array( 'company' => $company ) ); ?>
		<?php endforeach; ?>
	</div>
	<p class="sm-callout"><?php sm_icon( 'info' ); ?><span><?php esc_html_e( 'AI tools on this platform never replace a structural calculation or an engineer’s signature.', 'sm-marketplace' ); ?></span></p>
</div>
<?php
get_footer();

<?php
/**
 * Template Name: SM — Engineering
 *
 * Engineering by the platform (0.13.0, sm-engineering): every study or
 * inspection request goes to the platform, which sends a quote and gives the
 * mission to one of its approved partner engineers. The list of partner
 * engineers never shows contact details; "Ask for a study" sends the request
 * to the platform with the chosen engineer as a preference.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
get_header();
$specialty = sanitize_key( sm_theme_query_arg( 'specialty' ) );
$wilaya    = preg_replace( '/\D/', '', (string) sm_theme_query_arg( 'wilaya' ) );
$q         = sanitize_text_field( (string) sm_theme_query_arg( 'q' ) );
$data      = sm_theme_data( 'engineers', array( 'specialty' => $specialty, 'wilaya' => $wilaya, 'q' => $q ) );
$request   = is_array( $data ) ? $data['request_url'] : sm_page_url( 'mission-request' );
$steps     = array(
	array( 'file', __( 'You describe your project', 'sm-marketplace' ), __( 'Type of study, plans, photos, wilaya.', 'sm-marketplace' ) ),
	array( 'rfq', __( 'The platform sends you a quote', 'sm-marketplace' ), __( 'One price, one time, one contact: the platform.', 'sm-marketplace' ) ),
	array( 'hard-hat', __( 'A partner engineer carries out the study', 'sm-marketplace' ), __( 'Chosen for your project among the verified engineers.', 'sm-marketplace' ) ),
	array( 'check', __( 'The platform delivers it', 'sm-marketplace' ), __( 'Checked documents, in your account.', 'sm-marketplace' ) ),
);
?>
<section class="sm-pagehead sm-pagehead--engineers">
	<div class="sm-container">
		<?php sm_breadcrumb( array( array( __( 'Engineering', 'sm-marketplace' ), '' ) ) ); ?>
		<div class="sm-pagehead__row">
			<div>
				<h1 class="sm-pagehead__title"><?php esc_html_e( 'Engineering studies and inspections', 'sm-marketplace' ); ?></h1>
				<p class="sm-pagehead__intro"><?php esc_html_e( 'Steel design, calculation notes, connections, shop drawings, inspection of used structures: ask the platform, which works with verified partner engineers.', 'sm-marketplace' ); ?></p>
			</div>
			<a class="sm-btn sm-btn--accent sm-btn--lg" href="<?php echo esc_url( $request ); ?>"><?php sm_icon( 'hard-hat' ); ?><?php esc_html_e( 'Ask for a study', 'sm-marketplace' ); ?></a>
		</div>
	</div>
</section>
<div class="sm-container sm-section">
	<ol class="sme-how" role="list">
		<?php foreach ( $steps as $i => list( $icon, $title, $text ) ) : ?>
			<li><span class="sme-how__num"><?php echo (int) $i + 1; ?></span><?php sm_icon( $icon ); ?><strong><?php echo esc_html( $title ); ?></strong><small><?php echo esc_html( $text ); ?></small></li>
		<?php endforeach; ?>
	</ol>

	<?php if ( is_array( $data ) && $data['enabled'] ) : ?>
		<div class="sm-section__head sm-section__head--spaced">
			<h2><?php esc_html_e( 'Partner engineers of the platform', 'sm-marketplace' ); ?></h2>
			<?php if ( $data['open'] ) : ?><a class="sm-link" href="<?php echo esc_url( $data['apply_url'] ); ?>"><?php esc_html_e( 'Become a partner engineer', 'sm-marketplace' ); ?><?php sm_icon( 'arrow-end' ); ?></a><?php endif; ?>
		</div>
		<form class="sme-filters" method="get" action="<?php echo esc_url( sm_page_url( 'engineers' ) ); ?>">
			<div class="sm-field">
				<label for="sme-spec"><?php esc_html_e( 'Specialty', 'sm-marketplace' ); ?></label>
				<select id="sme-spec" name="specialty">
					<option value=""><?php esc_html_e( 'All', 'sm-marketplace' ); ?></option>
					<?php foreach ( $data['types'] as $code => $label ) : ?><option value="<?php echo esc_attr( $code ); ?>" <?php selected( $specialty, $code ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
				</select>
			</div>
			<div class="sm-field">
				<label for="sme-wilaya"><?php esc_html_e( 'Wilaya', 'sm-marketplace' ); ?></label>
				<select id="sme-wilaya" name="wilaya">
					<option value=""><?php esc_html_e( 'All Algeria', 'sm-marketplace' ); ?></option>
					<?php foreach ( sm_theme_wilayas() as $code => $name ) : ?><option value="<?php echo esc_attr( $code ); ?>" <?php selected( $wilaya, (string) $code ); ?>><?php echo esc_html( $code . ' — ' . sm_t( $name ) ); ?></option><?php endforeach; ?>
				</select>
			</div>
			<div class="sm-field">
				<label for="sme-q"><?php esc_html_e( 'Search', 'sm-marketplace' ); ?></label>
				<input id="sme-q" name="q" type="search" dir="auto" value="<?php echo esc_attr( $q ); ?>" placeholder="<?php esc_attr_e( 'e.g. hangar, Eurocode, expertise', 'sm-marketplace' ); ?>">
			</div>
			<button class="sm-btn sm-btn--primary" type="submit"><?php sm_icon( 'filter' ); ?><?php esc_html_e( 'Filter', 'sm-marketplace' ); ?></button>
		</form>
		<?php if ( $data['items'] ) : ?>
			<div class="sme-grid">
				<?php foreach ( $data['items'] as $engineer ) : ?>
					<?php sm_component( 'card-engineer', array( 'engineer' => $engineer ) ); ?>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<?php sm_component( 'empty-state', array( 'icon' => 'hard-hat', 'title' => ( $specialty || $wilaya || $q ) ? __( 'No partner engineer matches these filters', 'sm-marketplace' ) : __( 'The list of partner engineers opens soon', 'sm-marketplace' ), 'text' => __( 'You can already send your request: the platform finds the right engineer.', 'sm-marketplace' ), 'action_label' => __( 'Ask for a study', 'sm-marketplace' ), 'action_url' => $request ) ); ?>
		<?php endif; ?>
	<?php endif; ?>
	<p class="sm-callout"><?php sm_icon( 'info' ); ?><span><?php esc_html_e( 'You deal only with the platform: quote, follow-up and delivery. AI tools on this platform never replace a structural calculation or an engineer’s signature.', 'sm-marketplace' ); ?></span></p>
</div>
<?php
get_footer();

<?php
/**
 * Template Name: SM — Engineering
 *
 * Engineering by the platform (sm-engineering): every study or inspection
 * request goes to the platform, which sends a quote and gives the mission to one
 * of its approved partner engineers.
 *
 * 0.27.0: the page shows the engineering services (title, image and prestations
 * set in wp-admin → SM → Engineering settings); each card opens the request form
 * with the service chosen. The list of partner engineers is no longer shown,
 * unless the administrator turns it back on ("Also show the list of partner
 * engineers under the services").
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
	array( 'file', __( 'You describe your project', 'sm-marketplace' ), __( 'Service, prestations, plans, photos, wilaya.', 'sm-marketplace' ) ),
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
				<p class="sm-pagehead__intro"><?php esc_html_e( 'Studies, calculation notes, drawings, inspections and site follow-up of steel structures: ask the platform, which works with verified partner engineers.', 'sm-marketplace' ); ?></p>
			</div>
			<a class="sm-btn sm-btn--accent sm-btn--lg" href="<?php echo esc_url( $request ); ?>"><?php sm_icon( 'hard-hat' ); ?><?php esc_html_e( 'Ask for a study', 'sm-marketplace' ); ?></a>
		</div>
	</div>
</section>
<div class="sm-container sm-section">
	<?php if ( is_array( $data ) && ! empty( $data['services'] ) ) : ?>
		<div class="sm-section__head">
			<h2><?php esc_html_e( 'Our engineering services', 'sm-marketplace' ); ?></h2>
		</div>
		<ul class="sme-services" role="list">
			<?php foreach ( $data['services'] as $sv ) : ?>
				<li class="sme-service">
					<div class="sme-service__media">
						<img src="<?php echo esc_url( '' !== $sv['image'] ? $sv['image'] : sm_image_url( 'demo:' . $sv['demo'] ) ); ?>" alt="<?php echo esc_attr( $sv['alt'] ); ?>" loading="lazy" decoding="async">
					</div>
					<div class="sme-service__body">
						<h3 class="sme-service__title"><?php echo esc_html( $sv['title'] ); ?></h3>
						<?php if ( $sv['items'] ) : ?>
							<ul class="sme-service__items">
								<?php foreach ( $sv['items'] as $item ) : ?><li><?php sm_icon( 'check' ); ?><span><?php echo esc_html( $item ); ?></span></li><?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<a class="sm-btn sm-btn--primary sme-service__cta" href="<?php echo esc_url( $sv['url'] ); ?>"><?php esc_html_e( 'Ask for this service', 'sm-marketplace' ); ?><?php sm_icon( 'arrow-end' ); ?></a>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<div class="sm-section__head sm-section__head--spaced">
		<h2><?php esc_html_e( 'How it works', 'sm-marketplace' ); ?></h2>
	</div>
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
				<label for="sme-spec"><?php esc_html_e( 'Service', 'sm-marketplace' ); ?></label>
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
	<?php if ( is_array( $data ) && $data['open'] && ! $data['enabled'] ) : ?>
		<p class="sme-partner"><?php esc_html_e( 'Are you a structural engineer?', 'sm-marketplace' ); ?> <a class="sm-link" href="<?php echo esc_url( $data['apply_url'] ); ?>"><?php esc_html_e( 'Become a partner engineer', 'sm-marketplace' ); ?></a></p>
	<?php endif; ?>
</div>
<?php
get_footer();

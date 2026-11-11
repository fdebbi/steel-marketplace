<?php
/**
 * Template Name: SM — Company profile
 *
 * Public page of a company or a professional (?c=slug): listings, services,
 * completed projects, presentation. Professionals also show their title,
 * qualifications and experience. Clients and partner engineers have no public
 * page (0.13.0).
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$company = sm_theme_company( sm_theme_route( 'c', sm_theme_core_active() ? '' : 'acier-hodna' ) );
get_header();

if ( ! $company ) {
	echo '<div class="sm-container sm-section">';
	sm_component( 'empty-state', array( 'icon' => 'company', 'title' => __( 'Company not found', 'sm-marketplace' ), 'action_label' => __( 'Company directory', 'sm-marketplace' ), 'action_url' => sm_page_url( 'companies' ) ) );
	echo '</div>';
	get_footer();
	return;
}

$is_pro     = 'professional' === sm_account_class( $company );
$wilayas    = sm_theme_wilayas();
$activities = sm_activity_labels();
$listings   = sm_theme_listings( array( 'company' => $company['slug'], 'per_page' => 24 ) )['items'];
$services   = sm_theme_services( array( 'company' => $company['slug'] ) );
$tabs       = array();
foreach ( array( 'products', 'reuse', 'buildings' ) as $m ) {
	$count = count( array_filter( $listings, static fn( $l ) => $l['market'] === $m ) );
	if ( $count ) {
		$tabs[ $m ] = array( sm_market_labels()[ $m ], $count );
	}
}
if ( $services ) {
	$tabs['services'] = array( __( 'Services', 'sm-marketplace' ), count( $services ) );
}
$references = array_filter( (array) sm_theme_data( 'portfolio', array( 'company' => $company['slug'] ) ), 'is_array' );
if ( $references ) {
	$tabs['references'] = array( __( 'Completed projects', 'sm-marketplace' ), count( $references ) );
}
$tabs['about'] = array( __( 'About', 'sm-marketplace' ), 0 );
?>
<section class="sm-company-head<?php echo $is_pro ? ' sm-company-head--pro' : ''; ?>">
	<div class="sm-container">
		<?php sm_breadcrumb( array( array( __( 'Company directory', 'sm-marketplace' ), sm_page_url( 'companies' ) ), array( sm_t( $company['name'] ), '' ) ) ); ?>
		<div class="sm-company-head__row">
			<?php sm_avatar( $company, 'xl' ); ?>
			<div class="sm-company-head__id">
				<h1><?php sm_e( $company['name'] ); ?></h1>
				<?php if ( ! empty( $company['title'] ) ) : ?><p class="sm-company-head__title"><?php sm_e( $company['title'] ); ?></p><?php endif; ?>
				<p class="sm-company-head__meta">
					<span><?php sm_icon( 'map-pin' ); ?><?php echo esc_html( sm_t( $wilayas[ $company['wilaya'] ] ?? '' ) ); ?></span>
					<span class="sm-seller__badge<?php echo $company['verification'] >= 2 ? ' is-verified' : ''; ?>"><?php sm_icon( $company['verification'] >= 2 ? 'verified' : 'shield' ); ?><?php echo esc_html( sm_verification_label( $company['verification'] ) ); ?></span>
					<?php if ( ! empty( $company['experience'] ) ) : ?>
						<span><?php sm_icon( 'hard-hat' ); ?><?php echo esc_html( sprintf( /* translators: %d: years. */ _n( '%d year of experience', '%d years of experience', (int) $company['experience'], 'sm-marketplace' ), (int) $company['experience'] ) ); ?></span>
					<?php endif; ?>
					<span>
						<?php
						/* translators: %s: year. */
						echo esc_html( sprintf( __( 'Active since %s', 'sm-marketplace' ), $company['since'] ) );
						?>
					</span>
				</p>
				<ul class="sm-tags">
					<li class="sm-tags__class"><?php echo esc_html( sm_class_label( sm_account_class( $company ) ) ); ?></li>
					<?php foreach ( $company['activities'] as $a ) : ?><li><?php echo esc_html( $activities[ $a ] ?? $a ); ?></li><?php endforeach; ?>
				</ul>
			</div>
			<div class="sm-company-head__actions">
				<a class="sm-btn sm-btn--accent" href="#sm-contact"><?php sm_icon( 'message' ); ?><?php echo esc_html( $is_pro ? __( 'Request a service', 'sm-marketplace' ) : __( 'Contact', 'sm-marketplace' ) ); ?></a>
<?php if ( ! empty( $company['phone'] ) ) : ?>
				<a class="sm-btn sm-btn--ghost-light" href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $company['phone'] ) ); ?>"><?php sm_icon( 'phone' ); ?><bdi dir="ltr"><?php echo esc_html( $company['phone'] ); ?></bdi></a>
				<?php endif; ?>
			</div>
		</div>
		<nav class="sm-tabs" aria-label="<?php esc_attr_e( 'Company sections', 'sm-marketplace' ); ?>">
			<?php foreach ( $tabs as $key => list( $label, $count ) ) : ?>
				<a href="#tab-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?><?php if ( $count ) : ?><span class="sm-count"><?php echo esc_html( $count ); ?></span><?php endif; ?></a>
			<?php endforeach; ?>
		</nav>
	</div>
</section>

<div class="sm-container sm-section sm-company-body">
	<?php foreach ( array( 'products', 'reuse', 'buildings' ) as $m ) : ?>
		<?php if ( isset( $tabs[ $m ] ) ) : ?>
			<section id="tab-<?php echo esc_attr( $m ); ?>" class="sm-section">
				<div class="sm-section__head"><h2><?php echo esc_html( $tabs[ $m ][0] ); ?></h2></div>
				<div class="sm-grid sm-grid--grid sm-grid--3">
					<?php foreach ( array_filter( $listings, static fn( $l ) => $l['market'] === $m ) as $item ) : ?>
						<?php sm_component( 'card-listing', array( 'item' => $item ) ); ?>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>
	<?php endforeach; ?>

	<?php if ( $services ) : ?>
		<section id="tab-services" class="sm-section">
			<div class="sm-section__head"><h2><?php esc_html_e( 'Services', 'sm-marketplace' ); ?></h2></div>
			<div class="sm-grid sm-grid--grid sm-grid--3">
				<?php foreach ( $services as $service ) : ?><?php sm_component( 'card-service', array( 'service' => $service ) ); ?><?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $references ) : ?>
		<section id="tab-references" class="sm-section">
			<div class="sm-section__head"><h2><?php esc_html_e( 'Completed projects', 'sm-marketplace' ); ?></h2></div>
			<?php sm_component( 'portfolio-grid', array( 'items' => $references ) ); ?>
		</section>
	<?php endif; ?>

	<section id="tab-about" class="sm-section sm-about">
		<div class="sm-panel">
			<h2 class="sm-panel__title"><?php esc_html_e( 'About', 'sm-marketplace' ); ?></h2>
			<?php if ( sm_t( $company['about'] ) ) : ?>
				<p><?php sm_e( $company['about'] ); ?></p>
			<?php else : ?>
				<p class="sm-muted"><?php esc_html_e( 'This company has not written its presentation yet.', 'sm-marketplace' ); ?></p>
			<?php endif; ?>
		</div>
		<?php if ( $is_pro ) : ?>
			<?php $quals = array_filter( (array) ( $company['qualifications'] ?? array() ) ); ?>
			<?php $specs = array_filter( (array) ( $company['specialities'] ?? array() ) ); ?>
			<?php if ( $quals || $specs ) : ?>
				<div class="sm-panel">
					<?php if ( $quals ) : ?>
						<h2 class="sm-panel__title"><?php esc_html_e( 'Qualifications', 'sm-marketplace' ); ?></h2>
						<ul class="sm-checklist"><?php foreach ( $quals as $q ) : ?><li><?php sm_icon( 'verified' ); ?><?php sm_e( $q ); ?></li><?php endforeach; ?></ul>
					<?php endif; ?>
					<?php if ( $specs ) : ?>
						<h3 class="sm-panel__subtitle"><?php esc_html_e( 'Specialisations', 'sm-marketplace' ); ?></h3>
						<ul class="sm-tags"><?php foreach ( $specs as $sp ) : ?><li><?php sm_e( $sp ); ?></li><?php endforeach; ?></ul>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		<?php else : ?>
			<div class="sm-panel">
				<h2 class="sm-panel__title"><?php esc_html_e( 'Company details', 'sm-marketplace' ); ?></h2>
				<dl class="sm-spec">
					<div><dt><?php esc_html_e( 'Wilaya', 'sm-marketplace' ); ?></dt><dd><?php echo esc_html( sm_t( $wilayas[ $company['wilaya'] ] ?? '' ) ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Commercial register', 'sm-marketplace' ); ?></dt><dd><?php echo esc_html( $company['verification'] >= 2 ? __( 'Checked by the platform', 'sm-marketplace' ) : __( 'Not yet checked', 'sm-marketplace' ) ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Phone', 'sm-marketplace' ); ?></dt><dd><bdi dir="ltr"><?php echo esc_html( $company['phone'] ); ?></bdi></dd></div>
				</dl>
			</div>
		<?php endif; ?>
		<?php if ( ! empty( $company['location'] ) ) : ?>
			<div class="sm-panel">
				<h2 class="sm-panel__title"><?php esc_html_e( 'Location', 'sm-marketplace' ); ?></h2>
				<?php
				sm_component(
					'location-map',
					array(
						'mode'  => 'view',
						'lat'   => $company['location']['lat'],
						'lng'   => $company['location']['lng'],
						'label' => sm_t( $company['name'] ),
					)
				);
				?>
			</div>
		<?php endif; ?>
	</section>
	<?php
	sm_component(
		'enquiry-form',
		array(
			'kind'    => $is_pro ? 'service' : 'listing',
			'module'  => 'company',
			'company' => $company['slug'],
			'subject' => $is_pro ? __( 'Service request', 'sm-marketplace' ) : __( 'Contact from the company page', 'sm-marketplace' ),
			'title'   => $is_pro ? __( 'Contact this professional', 'sm-marketplace' ) : __( 'Contact this company', 'sm-marketplace' ),
			'id'      => 'sm-contact',
		)
	);
	?>
</div>
<?php
get_footer();

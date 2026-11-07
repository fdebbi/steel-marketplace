<?php
/**
 * Template Name: SM — Company profile
 *
 * Commercial company or engineering office (?c=slug). Engineering profiles show
 * qualifications and services only: no commercial tabs.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$company = sm_theme_company( sm_theme_query_arg( 'c', 'acier-hodna' ) );
get_header();

if ( ! $company ) {
	echo '<div class="sm-container sm-section">';
	sm_component( 'empty-state', array( 'icon' => 'company', 'title' => __( 'Company not found', 'sm-marketplace' ), 'action_label' => __( 'Company directory', 'sm-marketplace' ), 'action_url' => sm_page_url( 'companies' ) ) );
	echo '</div>';
	get_footer();
	return;
}

$is_eng     = 'engineering' === $company['class'];
$wilayas    = sm_theme_wilayas();
$activities = sm_activity_labels();
$listings   = $is_eng ? array() : sm_theme_listings( array( 'company' => $company['slug'], 'per_page' => 24 ) )['items'];
$services   = sm_theme_services( array( 'company' => $company['slug'] ) );
$tabs       = array();
if ( ! $is_eng ) {
	foreach ( array( 'products', 'reuse', 'buildings' ) as $m ) {
		$count = count( array_filter( $listings, static fn( $l ) => $l['market'] === $m ) );
		if ( $count ) {
			$tabs[ $m ] = array( sm_market_labels()[ $m ], $count );
		}
	}
}
if ( $services ) {
	$tabs['services'] = array( __( 'Services', 'sm-marketplace' ), count( $services ) );
}
$tabs['about'] = array( __( 'About', 'sm-marketplace' ), 0 );
?>
<section class="sm-company-head<?php echo $is_eng ? ' sm-company-head--eng' : ''; ?>">
	<div class="sm-container">
		<?php sm_breadcrumb( array( array( $is_eng ? __( 'Engineering', 'sm-marketplace' ) : __( 'Company directory', 'sm-marketplace' ), sm_page_url( $is_eng ? 'engineers' : 'companies' ) ), array( sm_t( $company['name'] ), '' ) ) ); ?>
		<div class="sm-company-head__row">
			<div class="sm-avatar sm-avatar--xl sm-avatar--<?php echo esc_attr( $company['class'] ); ?>" aria-hidden="true"><?php echo esc_html( $company['initials'] ); ?></div>
			<div class="sm-company-head__id">
				<h1><?php sm_e( $company['name'] ); ?></h1>
				<?php if ( ! empty( $company['title'] ) ) : ?><p class="sm-company-head__title"><?php sm_e( $company['title'] ); ?></p><?php endif; ?>
				<p class="sm-company-head__meta">
					<span><?php sm_icon( 'map-pin' ); ?><?php echo esc_html( sm_t( $wilayas[ $company['wilaya'] ] ?? '' ) ); ?></span>
					<span class="sm-seller__badge<?php echo $company['verification'] >= 2 ? ' is-verified' : ''; ?>"><?php sm_icon( $company['verification'] >= 2 ? 'verified' : 'shield' ); ?><?php echo esc_html( sm_verification_label( $company['verification'] ) ); ?></span>
					<span>
						<?php
						/* translators: %s: year. */
						echo esc_html( sprintf( __( 'Active since %s', 'sm-marketplace' ), $company['since'] ) );
						?>
					</span>
				</p>
				<ul class="sm-tags">
					<?php foreach ( $company['activities'] as $a ) : ?><li><?php echo esc_html( $activities[ $a ] ?? $a ); ?></li><?php endforeach; ?>
				</ul>
			</div>
			<div class="sm-company-head__actions">
				<a class="sm-btn sm-btn--accent" href="#sm-contact"><?php sm_icon( 'message' ); ?><?php echo esc_html( $is_eng ? __( 'Request a service', 'sm-marketplace' ) : __( 'Contact', 'sm-marketplace' ) ); ?></a>
				<a class="sm-btn sm-btn--ghost-light" href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $company['phone'] ) ); ?>"><?php sm_icon( 'phone' ); ?><bdi dir="ltr"><?php echo esc_html( $company['phone'] ); ?></bdi></a>
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

	<section id="tab-about" class="sm-section sm-about">
		<div class="sm-panel">
			<h2 class="sm-panel__title"><?php esc_html_e( 'About', 'sm-marketplace' ); ?></h2>
			<p><?php sm_e( $company['about'] ); ?></p>
		</div>
		<?php if ( $is_eng ) : ?>
			<div class="sm-panel">
				<h2 class="sm-panel__title"><?php esc_html_e( 'Qualifications', 'sm-marketplace' ); ?></h2>
				<ul class="sm-checklist"><?php foreach ( $company['qualifications'] as $q ) : ?><li><?php sm_icon( 'verified' ); ?><?php sm_e( $q ); ?></li><?php endforeach; ?></ul>
				<h3 class="sm-panel__subtitle"><?php esc_html_e( 'Specialisations', 'sm-marketplace' ); ?></h3>
				<ul class="sm-tags"><?php foreach ( $company['specialities'] as $s ) : ?><li><?php sm_e( $s ); ?></li><?php endforeach; ?></ul>
			</div>
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
	</section>
	<span id="sm-contact"></span>
</div>
<?php
get_footer();

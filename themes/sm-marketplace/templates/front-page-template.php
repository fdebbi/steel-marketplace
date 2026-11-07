<?php
/**
 * Template Name: SM — Home
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
get_header();

$stats    = sm_theme_stats();
$featured = sm_theme_listings( array( 'market' => array( 'products', 'buildings' ), 'featured' => true, 'per_page' => 4 ) )['items'];
$reuse    = sm_theme_listings( array( 'market' => 'reuse', 'per_page' => 4 ) )['items'];
$services = sm_theme_services();
$companies = array_slice( sm_theme_companies(), 0, 4 );
?>
<section class="sm-hero">
	<div class="sm-container sm-hero__inner">
		<div class="sm-hero__copy">
			<p class="sm-eyebrow"><?php esc_html_e( 'Steel · Reuse · Buildings · Services', 'sm-marketplace' ); ?></p>
			<h1 class="sm-hero__title"><?php esc_html_e( 'Buy and sell steel by the kilogram, from beams to complete warehouses', 'sm-marketplace' ); ?></h1>
			<p class="sm-hero__lead"><?php esc_html_e( 'Compare prices per kg, find offcuts and used structures near you, and work with verified suppliers, fabricators and engineers.', 'sm-marketplace' ); ?></p>

			<form class="sm-hero__search" role="search" method="get" action="<?php echo esc_url( sm_page_url( 'search' ) ); ?>">
				<?php if ( 'ar' === sm_theme_current_language() ) : ?><input type="hidden" name="lang" value="ar"><?php endif; ?>
				<div class="sm-hero__field sm-hero__field--q">
					<label for="hero-q"><?php esc_html_e( 'Profile or product', 'sm-marketplace' ); ?></label>
					<input id="hero-q" name="q" type="search" placeholder="IPE 200, HEA 240…" dir="auto">
				</div>
				<div class="sm-hero__field">
					<label for="hero-market"><?php esc_html_e( 'Market', 'sm-marketplace' ); ?></label>
					<select id="hero-market" name="market">
						<option value=""><?php esc_html_e( 'All markets', 'sm-marketplace' ); ?></option>
						<?php foreach ( array_slice( sm_market_labels(), 0, 3, true ) as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="sm-hero__field">
					<label for="hero-wilaya"><?php esc_html_e( 'Wilaya', 'sm-marketplace' ); ?></label>
					<select id="hero-wilaya" name="wilaya">
						<option value=""><?php esc_html_e( 'All wilayas', 'sm-marketplace' ); ?></option>
						<?php foreach ( sm_theme_wilayas() as $code => $name ) : ?>
							<option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( sm_t( $name ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<button class="sm-btn sm-btn--accent sm-btn--lg" type="submit"><?php sm_icon( 'search' ); ?><?php esc_html_e( 'Search', 'sm-marketplace' ); ?></button>
			</form>

			<ul class="sm-hero__stats">
				<li><strong><?php echo esc_html( sm_number( $stats['listings'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'active listings', 'sm-marketplace' ); ?></span></li>
				<li><strong><?php echo esc_html( sm_number( $stats['companies'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'companies', 'sm-marketplace' ); ?></span></li>
				<li><strong><?php echo sm_tech( sm_number( ( $stats['reuse_kg'] ?? 0 ) / 1000 ) . "\u{00A0}t" ); // phpcs:ignore ?></strong><span><?php esc_html_e( 'of steel offered for reuse', 'sm-marketplace' ); ?></span></li>
			</ul>
		</div>
		<div class="sm-hero__visual" aria-hidden="true">
			<img src="<?php echo esc_url( SM_THEME_URI . '/assets/demo/hero.svg' ); ?>" alt="" width="560" height="480">
		</div>
	</div>
</section>

<section class="sm-container sm-section">
	<ul class="sm-markets">
		<?php
		$markets = array(
			array( 'products', 'layers', __( 'Steel products', 'sm-marketplace' ), __( 'Beams, channels, tubes, plates and bolts, new and certified.', 'sm-marketplace' ) ),
			array( 'reuse', 'recycle', __( 'Steel reuse', 'sm-marketplace' ), __( 'Offcuts and surplus at a lower price per kg, graded on 5 levels.', 'sm-marketplace' ) ),
			array( 'buildings', 'warehouse', __( 'Steel buildings', 'sm-marketplace' ), __( 'Warehouses and hangars, assembled or dismantled with inventory.', 'sm-marketplace' ) ),
			array( 'services', 'wrench', __( 'Fabrication & installation', 'sm-marketplace' ), __( 'Cutting, welding, assembly, installation and dismantling.', 'sm-marketplace' ) ),
			array( 'engineers', 'hard-hat', __( 'Engineering', 'sm-marketplace' ), __( 'Design, calculation notes and inspection by verified engineers.', 'sm-marketplace' ) ),
		);
		foreach ( $markets as list( $key, $icon, $label, $desc ) ) :
			?>
			<li>
				<a class="sm-market sm-market--<?php echo esc_attr( $key ); ?>" href="<?php echo esc_url( sm_page_url( $key ) ); ?>">
					<span class="sm-market__icon"><?php sm_icon( $icon ); ?></span>
					<span class="sm-market__label"><?php echo esc_html( $label ); ?></span>
					<span class="sm-market__desc"><?php echo esc_html( $desc ); ?></span>
					<?php sm_icon( 'arrow-end', 'sm-market__arrow' ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</section>

<section class="sm-container sm-section">
	<div class="sm-section__head">
		<h2><?php esc_html_e( 'Featured listings', 'sm-marketplace' ); ?></h2>
		<a class="sm-link" href="<?php echo esc_url( sm_page_url( 'search' ) ); ?>"><?php esc_html_e( 'See all', 'sm-marketplace' ); ?><?php sm_icon( 'arrow-end' ); ?></a>
	</div>
	<div class="sm-grid sm-grid--grid sm-grid--4">
		<?php foreach ( $featured as $item ) : ?>
			<?php sm_component( 'card-listing', array( 'item' => $item ) ); ?>
		<?php endforeach; ?>
	</div>
</section>

<section class="sm-band sm-band--reuse">
	<div class="sm-container sm-section">
		<div class="sm-section__head">
			<div>
				<h2><?php esc_html_e( 'Latest reuse lots', 'sm-marketplace' ); ?></h2>
				<p><?php esc_html_e( 'Every lot shows its condition, its weight source and an automatic total price.', 'sm-marketplace' ); ?></p>
			</div>
			<a class="sm-link" href="<?php echo esc_url( sm_page_url( 'reuse' ) ); ?>"><?php esc_html_e( 'Browse reuse', 'sm-marketplace' ); ?><?php sm_icon( 'arrow-end' ); ?></a>
		</div>
		<div class="sm-grid sm-grid--grid sm-grid--4">
			<?php foreach ( $reuse as $item ) : ?>
				<?php sm_component( 'card-listing', array( 'item' => $item ) ); ?>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="sm-container sm-section">
	<div class="sm-split">
		<div class="sm-split__card sm-split__card--rfq">
			<?php sm_icon( 'rfq', 'sm-split__icon' ); ?>
			<h2><?php esc_html_e( 'Publish one request, receive several offers', 'sm-marketplace' ); ?></h2>
			<p><?php esc_html_e( 'Describe profiles, quantities and delivery wilaya. Suppliers answer with a price per kg and a delivery time you can compare side by side.', 'sm-marketplace' ); ?></p>
			<a class="sm-btn sm-btn--accent" href="<?php echo esc_url( sm_page_url( 'rfq' ) ); ?>"><?php esc_html_e( 'Request a quotation', 'sm-marketplace' ); ?></a>
		</div>
		<div class="sm-split__card sm-split__card--index">
			<?php sm_icon( 'chart', 'sm-split__icon' ); ?>
			<h2><?php esc_html_e( 'Steel price index', 'sm-marketplace' ); ?></h2>
			<p><?php esc_html_e( 'Average advertised prices per kg by profile, condition and wilaya, updated from live listings. Asking prices, not official market prices.', 'sm-marketplace' ); ?></p>
			<a class="sm-btn sm-btn--ghost-light" href="<?php echo esc_url( sm_page_url( 'price-index' ) ); ?>"><?php esc_html_e( 'Open the index', 'sm-marketplace' ); ?></a>
		</div>
	</div>
</section>

<section class="sm-container sm-section">
	<div class="sm-section__head">
		<h2><?php esc_html_e( 'Fabricators and engineers', 'sm-marketplace' ); ?></h2>
		<a class="sm-link" href="<?php echo esc_url( sm_page_url( 'services' ) ); ?>"><?php esc_html_e( 'All services', 'sm-marketplace' ); ?><?php sm_icon( 'arrow-end' ); ?></a>
	</div>
	<div class="sm-grid sm-grid--grid sm-grid--4">
		<?php foreach ( $services as $service ) : ?>
			<?php sm_component( 'card-service', array( 'service' => $service ) ); ?>
		<?php endforeach; ?>
	</div>
</section>

<section class="sm-container sm-section">
	<div class="sm-section__head">
		<h2><?php esc_html_e( 'Verified companies', 'sm-marketplace' ); ?></h2>
		<a class="sm-link" href="<?php echo esc_url( sm_page_url( 'companies' ) ); ?>"><?php esc_html_e( 'Company directory', 'sm-marketplace' ); ?><?php sm_icon( 'arrow-end' ); ?></a>
	</div>
	<div class="sm-grid sm-grid--companies">
		<?php foreach ( $companies as $company ) : ?>
			<?php sm_component( 'card-company', array( 'company' => $company ) ); ?>
		<?php endforeach; ?>
	</div>
</section>

<section class="sm-cta">
	<div class="sm-container sm-cta__inner">
		<div>
			<h2><?php esc_html_e( 'Sell your stock, surplus or used building', 'sm-marketplace' ); ?></h2>
			<p><?php esc_html_e( 'One business account to buy and sell. Engineers get a dedicated services account.', 'sm-marketplace' ); ?></p>
		</div>
		<div class="sm-cta__actions">
			<a class="sm-btn sm-btn--accent sm-btn--lg" href="<?php echo esc_url( sm_page_url( 'register' ) ); ?>"><?php esc_html_e( 'Create a free account', 'sm-marketplace' ); ?></a>
			<a class="sm-btn sm-btn--ghost-light sm-btn--lg" href="<?php echo esc_url( sm_page_url( 'pricing' ) ); ?>"><?php esc_html_e( 'See the plans', 'sm-marketplace' ); ?></a>
		</div>
	</div>
</section>
<?php
get_footer();

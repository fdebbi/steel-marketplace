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
?>
<div class="sm-home">
<section class="sm-hero sm-hero--galaxy">
	<div class="sm-hero__stars" aria-hidden="true"></div>
	<div class="sm-container sm-hero__inner">
		<div class="sm-hero__copy">
			<p class="sm-hero__pill"><span aria-hidden="true"></span><?php esc_html_e( 'The industrial steel marketplace in Algeria', 'sm-marketplace' ); ?></p>
			<h1 class="sm-hero__title"><?php esc_html_e( 'The future of steel commerce', 'sm-marketplace' ); ?> <span class="sm-hero__glow"><?php esc_html_e( 'starts here.', 'sm-marketplace' ); ?></span></h1>
			<p class="sm-hero__lead"><?php esc_html_e( 'Buy, sell, reuse and recycle steel. Verified companies, weights calculated automatically, partner engineers.', 'sm-marketplace' ); ?></p>
			<div class="sm-hero__ctas">
				<a class="sm-btn sm-btn--accent sm-btn--lg sm-hero__cta" href="<?php echo esc_url( sm_page_url( 'search' ) ); ?>"><?php esc_html_e( 'Explore the marketplace', 'sm-marketplace' ); ?><?php sm_icon( 'arrow-end' ); ?></a>
				<?php if ( is_user_logged_in() ) : ?>
					<a class="sm-btn sm-btn--glass sm-btn--lg" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'publish' ) ) ); ?>"><?php esc_html_e( 'Publish a listing', 'sm-marketplace' ); ?></a>
				<?php else : ?>
					<a class="sm-btn sm-btn--glass sm-btn--lg" href="<?php echo esc_url( sm_page_url( 'register' ) ); ?>"><?php echo esc_html( sprintf( /* translators: %s: platform name. */ __( 'Join %s', 'sm-marketplace' ), sm_theme_brand_name() ) ); ?></a>
				<?php endif; ?>
			</div>

			<form class="sm-hero__search" role="search" method="get" action="<?php echo esc_url( sm_page_url( 'search' ) ); ?>">
				<?php if ( 'fr' !== sm_theme_current_language() ) : ?><input type="hidden" name="lang" value="<?php echo esc_attr( sm_theme_current_language() ); ?>"><?php endif; ?>
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
				<button class="sm-btn sm-btn--accent sm-btn--lg" type="submit"><?php sm_icon( 'search' ); ?><span><?php esc_html_e( 'Search', 'sm-marketplace' ); ?></span></button>
			</form>

			<ul class="sm-hero__stats">
				<li><strong><?php echo esc_html( sm_number( $stats['listings'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'active listings', 'sm-marketplace' ); ?></span></li>
				<li><strong><?php echo esc_html( sm_number( $stats['companies'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'companies', 'sm-marketplace' ); ?></span></li>
				<li><strong><?php echo sm_tech( sm_number( ( $stats['reuse_kg'] ?? 0 ) / 1000 ) . "\u{00A0}t" ); // phpcs:ignore ?></strong><span><?php esc_html_e( 'of steel offered for reuse', 'sm-marketplace' ); ?></span></li>
			</ul>
		</div>
		<?php sm_component( 'hero-steel' ); ?>
	</div>
</section>

<section class="sm-home__band sm-home__band--markets">
	<div class="sm-container sm-section">
		<p class="sm-kicker"><?php esc_html_e( 'Markets', 'sm-marketplace' ); ?></p>
		<h2 class="sm-home__h2"><?php esc_html_e( 'All the steel, in one place', 'sm-marketplace' ); ?></h2>
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
	</div>
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

<section class="sm-home__band sm-home__band--circular">
	<div class="sm-container sm-section sm-circular">
		<div class="sm-circular__copy">
			<p class="sm-kicker"><?php esc_html_e( 'Circular economy', 'sm-marketplace' ); ?></p>
			<h2 class="sm-home__h2"><?php esc_html_e( 'Dismantled steel still has a life', 'sm-marketplace' ); ?></h2>
			<p><?php esc_html_e( 'Every reuse lot shows its condition, its origin and its inventory. Buyers are invited to have the structure checked by a partner engineer.', 'sm-marketplace' ); ?></p>
		</div>
		<ol class="sm-circular__steps">
			<li><span class="sm-circular__n">01</span><strong><?php esc_html_e( 'Dismantle', 'sm-marketplace' ); ?></strong><span><?php esc_html_e( 'Inventory by profile, weight calculated', 'sm-marketplace' ); ?></span></li>
			<li><span class="sm-circular__n">02</span><strong><?php esc_html_e( 'Check', 'sm-marketplace' ); ?></strong><span><?php esc_html_e( 'Five condition classes, engineer advice', 'sm-marketplace' ); ?></span></li>
			<li><span class="sm-circular__n">03</span><strong><?php esc_html_e( 'Resell', 'sm-marketplace' ); ?></strong><span><?php esc_html_e( 'By the piece, by the kg or the whole lot', 'sm-marketplace' ); ?></span></li>
		</ol>
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

<?php
$smf_rows = array(
	'company'      => array( 'companies', __( 'Featured companies', 'sm-marketplace' ), __( 'Latest companies', 'sm-marketplace' ), __( 'Manufacturers, distributors, recyclers and steel construction companies.', 'sm-marketplace' ), __( 'See all companies', 'sm-marketplace' ) ),
	'professional' => array( 'professionals', __( 'Featured professionals', 'sm-marketplace' ), __( 'Latest professionals', 'sm-marketplace' ), __( 'Welders, steel erectors, boilermakers and the other steel trades.', 'sm-marketplace' ), __( 'See all professionals', 'sm-marketplace' ) ),
);
foreach ( $smf_rows as $smf_class => list( $smf_page, $smf_title, $smf_latest, $smf_intro, $smf_all ) ) :
	$smf_items = (array) sm_theme_data( 'featured_companies', array( 'class' => $smf_class, 'limit' => 4 ) );
	if ( ! $smf_items ) {
		continue;
	}
	$smf_has = (bool) array_filter( $smf_items, static fn( $c ) => ! empty( $c['featured'] ) );
	?>
<section class="sm-container sm-section smf-section smf-section--<?php echo esc_attr( $smf_class ); ?>" aria-labelledby="smf-<?php echo esc_attr( $smf_class ); ?>">
	<div class="smf-head">
		<div>
			<p class="smf-eyebrow"><?php sm_icon( 'star' ); ?><?php echo esc_html( $smf_has ? __( 'Featured', 'sm-marketplace' ) : __( 'New on the platform', 'sm-marketplace' ) ); ?></p>
			<h2 id="smf-<?php echo esc_attr( $smf_class ); ?>"><?php echo esc_html( $smf_has ? $smf_title : $smf_latest ); ?></h2>
			<p><?php echo esc_html( $smf_intro ); ?></p>
		</div>
		<a class="sm-btn sm-btn--primary-outline" href="<?php echo esc_url( sm_page_url( $smf_page ) ); ?>"><?php echo esc_html( $smf_all ); ?><?php sm_icon( 'arrow-end' ); ?></a>
	</div>
	<div class="smf-grid">
		<?php foreach ( $smf_items as $company ) : ?>
			<?php sm_component( 'card-featured', array( 'company' => $company ) ); ?>
		<?php endforeach; ?>
	</div>
</section>
<?php endforeach; ?>

<section class="sm-home__band sm-home__band--why">
	<div class="sm-container sm-section">
		<h2 class="sm-home__h2"><?php echo esc_html( sprintf( /* translators: %s: platform name. */ __( 'Why %s?', 'sm-marketplace' ), sm_theme_brand_name() ) ); ?></h2>
		<ul class="sm-why">
			<li><strong><?php esc_html_e( 'Weight calculated', 'sm-marketplace' ); ?></strong><span><?php esc_html_e( 'European section tables: the weight of every lot is calculated, never guessed by hand.', 'sm-marketplace' ); ?></span></li>
			<li><strong><?php esc_html_e( 'Verified accounts', 'sm-marketplace' ); ?></strong><span><?php esc_html_e( 'Every account is verified before it receives its badge.', 'sm-marketplace' ); ?></span></li>
			<li><strong><?php esc_html_e( 'Partner engineers', 'sm-marketplace' ); ?></strong><span><?php esc_html_e( 'Studies, calculation notes and inspections given by the platform.', 'sm-marketplace' ); ?></span></li>
			<li><strong><?php esc_html_e( 'French, Arabic, English', 'sm-marketplace' ); ?></strong><span><?php esc_html_e( 'The whole platform and its e-mails in your language.', 'sm-marketplace' ); ?></span></li>
		</ul>
	</div>
</section>

<?php if ( ! is_user_logged_in() ) : ?>
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
<?php endif; ?>
</div>
<?php
get_footer();

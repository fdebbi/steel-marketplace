<?php
/**
 * Template Name: SM — Listing detail
 *
 * Detail page of a product, reuse item or building (?id=).
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$item = sm_theme_listing( sm_theme_route( 'id' ) );
if ( ! $item ) {
	status_header( 404 );
}
get_header();

if ( ! $item ) {
	echo '<div class="sm-container sm-section">';
	sm_component( 'empty-state', array( 'icon' => 'alert', 'title' => __( 'This listing is no longer available', 'sm-marketplace' ), 'action_label' => __( 'Back to the marketplace', 'sm-marketplace' ), 'action_url' => sm_page_url( 'search' ) ) );
	echo '</div>';
	get_footer();
	return;
}

$market   = $item['market'];
$labels   = sm_market_labels();
$cats     = sm_theme_categories( $market );
$company  = sm_theme_company( $item['company'] );
$wilayas  = sm_theme_wilayas();
$is_build = 'buildings' === $market;

// Specifications shown as label/value rows (stacked cards on mobile).
$specs = array();
if ( $is_build ) {
	$specs[ __( 'State', 'sm-marketplace' ) ]             = 'assembled' === $item['mode'] ? __( 'Assembled', 'sm-marketplace' ) : __( 'Dismantled', 'sm-marketplace' );
	$specs[ __( 'Length × width × height', 'sm-marketplace' ) ] = sm_tech( sm_number( $item['length'] ) . ' × ' . sm_number( $item['width'] ) . ' × ' . sm_number( $item['height'], 1 ) . ' m' );
	$specs[ __( 'Total area', 'sm-marketplace' ) ]        = sm_tech( sm_number( $item['area'] ) . ' m²' );
	$specs[ __( 'Number of portals', 'sm-marketplace' ) ] = sm_tech( (string) $item['portals'] );
	$specs[ __( 'Structural system', 'sm-marketplace' ) ] = esc_html( sm_t( $item['system'] ) );
	$specs[ __( 'Roof design', 'sm-marketplace' ) ]       = esc_html( sm_t( $item['roof'] ) );
	$specs[ __( 'Roof covering', 'sm-marketplace' ) ]     = esc_html( sm_t( $item['roof_cover'] ) );
	$specs[ __( 'Side cladding', 'sm-marketplace' ) ]     = esc_html( sm_t( $item['cladding'] ) );
} else {
	$specs[ __( 'Profile designation', 'sm-marketplace' ) ] = sm_tech( $item['designation'] );
	$specs[ __( 'Steel grade', 'sm-marketplace' ) ]         = sm_tech( $item['grade'] );
	if ( ! empty( $item['specs']['standard'] ) ) {
		$specs[ __( 'Standard', 'sm-marketplace' ) ] = sm_tech( $item['specs']['standard'] );
	}
	if ( ! empty( $item['length_m'] ) ) {
		$specs[ __( 'Length', 'sm-marketplace' ) ] = sm_tech( sm_number( $item['length_m'], fmod( $item['length_m'], 1 ) ? 1 : 0 ) . ' m' );
	}
	$specs[ __( 'Number of pieces', 'sm-marketplace' ) ] = sm_tech( (string) $item['quantity'] );
	if ( ! empty( $item['specs']['finish'] ) ) {
		$specs[ __( 'Finish', 'sm-marketplace' ) ] = esc_html( sm_t( $item['specs']['finish'] ) );
	}
}
$specs[ __( 'Weight source', 'sm-marketplace' ) ] = esc_html( sm_weight_source_label( $item['weight_source'] ) );
$specs[ __( 'Location', 'sm-marketplace' ) ]      = esc_html( sm_t( $wilayas[ $item['wilaya'] ] ?? '' ) );

$similar = sm_theme_listings( array( 'market' => $market, 'per_page' => 4 ) )['items'];
$similar = array_values( array_filter( $similar, static fn( $s ) => $s['id'] !== $item['id'] ) );
?>
<div class="sm-container sm-detail">
	<?php sm_breadcrumb( array( array( $labels[ $market ], sm_page_url( $market ) ), array( sm_t( $cats[ $item['category'] ] ?? '' ), sm_page_url( $market, array( 'category' => $item['category'] ) ) ), array( sm_t( $item['title'] ), '' ) ) ); ?>

	<div class="sm-detail__top">
		<div class="sm-gallery" data-sm-gallery>
			<figure class="sm-gallery__main">
				<img src="<?php echo esc_url( sm_image_url( $item['image'] ) ); ?>" alt="<?php echo esc_attr( sm_t( $item['title'] ) ); ?>" width="1280" height="960" data-sm-gallery-main>
			</figure>
			<div class="sm-gallery__thumbs">
				<?php foreach ( array( $item['image'], 'demo:detail-section', 'demo:detail-stack' ) as $n => $img ) : ?>
					<button type="button" class="sm-gallery__thumb" aria-pressed="<?php echo 0 === $n ? 'true' : 'false'; ?>" data-src="<?php echo esc_url( sm_image_url( $img ) ); ?>">
						<img src="<?php echo esc_url( sm_image_url( $img ) ); ?>" alt="" width="160" height="120" loading="lazy">
						<span class="screen-reader-text">
							<?php
							/* translators: %d: photo number. */
							echo esc_html( sprintf( __( 'Photo %d', 'sm-marketplace' ), $n + 1 ) );
							?>
						</span>
					</button>
				<?php endforeach; ?>
			</div>
		</div>

		<aside class="sm-detail__aside">
			<div class="sm-card__meta">
				<?php if ( 'reuse' === $market ) : ?>
					<?php sm_component( 'condition-badge', array( 'condition' => $item['condition'] ) ); ?>
				<?php elseif ( $is_build ) : ?>
					<span class="sm-chip"><?php echo esc_html( 'assembled' === $item['mode'] ? __( 'Assembled', 'sm-marketplace' ) : __( 'Dismantled', 'sm-marketplace' ) ); ?></span>
				<?php else : ?>
					<span class="sm-chip sm-chip--tech"><?php echo sm_tech( $item['designation'] ); // phpcs:ignore ?></span>
				<?php endif; ?>
				<span class="sm-chip sm-chip--quiet"><?php echo esc_html( sm_ago( $item['date'] ) ); ?></span>
			</div>
			<h1 class="sm-detail__title"><?php sm_e( $item['title'] ); ?></h1>
			<p class="sm-card__place"><?php sm_icon( 'map-pin' ); ?><?php echo esc_html( sm_t( $wilayas[ $item['wilaya'] ] ?? '' ) ); ?></p>

			<?php sm_component( 'price-block', array( 'item' => $item ) ); ?>

			<div class="sm-detail__actions">
				<a class="sm-btn sm-btn--accent sm-btn--block" href="#sm-enquiry"><?php sm_icon( 'message' ); ?><?php esc_html_e( 'Contact the seller', 'sm-marketplace' ); ?></a>
				<a class="sm-btn sm-btn--primary-outline sm-btn--block" href="<?php echo esc_url( sm_page_url( 'rfq', array( 'from' => $item['id'] ) ) ); ?>"><?php sm_icon( 'rfq' ); ?><?php esc_html_e( 'Request a quotation', 'sm-marketplace' ); ?></a>
				<div class="sm-detail__mini">
					<button class="sm-btn sm-btn--ghost" type="button" aria-pressed="false" data-sm-save="<?php echo esc_attr( $item['id'] ); ?>"><?php sm_icon( 'heart' ); ?><?php esc_html_e( 'Save', 'sm-marketplace' ); ?></button>
					<button class="sm-btn sm-btn--ghost" type="button" data-sm-share><?php sm_icon( 'share' ); ?><?php esc_html_e( 'Share', 'sm-marketplace' ); ?></button>
				</div>
			</div>
			<?php do_action( 'sm_slot', 'listing.aside', $item ); ?>

			<?php if ( $company ) : ?>
				<div class="sm-seller">
					<div class="sm-avatar sm-avatar--<?php echo esc_attr( $company['class'] ); ?>" aria-hidden="true"><?php echo esc_html( $company['initials'] ); ?></div>
					<div>
						<p class="sm-seller__name"><a href="<?php echo esc_url( sm_page_url( 'company', array( 'c' => $company['slug'] ) ) ); ?>"><?php sm_e( $company['name'] ); ?></a></p>
						<p class="sm-seller__badge<?php echo $company['verification'] >= 2 ? ' is-verified' : ''; ?>"><?php sm_icon( $company['verification'] >= 2 ? 'verified' : 'shield' ); ?><?php echo esc_html( sm_verification_label( $company['verification'] ) ); ?></p>
						<p class="sm-seller__since">
							<?php
							/* translators: %s: year. */
							echo esc_html( sprintf( __( 'Active since %s', 'sm-marketplace' ), $company['since'] ) );
							?>
						</p>
					</div>
				</div>
			<?php endif; ?>
		</aside>
	</div>

	<div class="sm-detail__body">
		<section class="sm-panel">
			<h2 class="sm-panel__title"><?php esc_html_e( 'Technical specifications', 'sm-marketplace' ); ?></h2>
			<dl class="sm-spec">
				<?php foreach ( $specs as $label => $value ) : ?>
					<div><dt><?php echo esc_html( $label ); ?></dt><dd><?php echo sm_kses_tech( $value ); // phpcs:ignore ?></dd></div>
				<?php endforeach; ?>
			</dl>
		</section>

		<?php if ( $is_build ) : ?>
			<section class="sm-panel">
				<h2 class="sm-panel__title"><?php esc_html_e( 'What the price includes', 'sm-marketplace' ); ?></h2>
				<ul class="sm-includes">
					<?php
					$inc_labels = array(
						'dismantling' => __( 'Dismantling', 'sm-marketplace' ),
						'transport'   => __( 'Transport', 'sm-marketplace' ),
						'reinstall'   => __( 'Reinstallation', 'sm-marketplace' ),
						'foundations' => __( 'Foundations and accessories', 'sm-marketplace' ),
					);
					foreach ( $inc_labels as $key => $label ) :
						$yes = ! empty( $item['includes'][ $key ] );
						?>
						<li class="<?php echo $yes ? 'is-yes' : 'is-no'; ?>"><?php sm_icon( $yes ? 'check' : 'close' ); ?><span><?php echo esc_html( $label ); ?></span><span class="screen-reader-text"><?php echo esc_html( $yes ? __( 'included', 'sm-marketplace' ) : __( 'not included', 'sm-marketplace' ) ); ?></span></li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $item['elements'] ) ) : ?>
			<section class="sm-panel sm-panel--wide">
				<h2 class="sm-panel__title"><?php esc_html_e( 'Structural element inventory', 'sm-marketplace' ); ?></h2>
				<div class="sm-table-wrap" tabindex="0" role="region" aria-label="<?php esc_attr_e( 'Structural element inventory', 'sm-marketplace' ); ?>">
					<table class="sm-table sm-table--stack">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'Element', 'sm-marketplace' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Profile', 'sm-marketplace' ); ?></th>
								<th scope="col" class="num"><?php esc_html_e( 'Quantity', 'sm-marketplace' ); ?></th>
								<th scope="col" class="num"><?php esc_html_e( 'Length (m)', 'sm-marketplace' ); ?></th>
								<th scope="col" class="num"><?php esc_html_e( 'Unit weight (kg/m)', 'sm-marketplace' ); ?></th>
								<th scope="col" class="num"><?php esc_html_e( 'Total weight (kg)', 'sm-marketplace' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $item['elements'] as $el ) : ?>
								<tr>
									<th scope="row" data-label="<?php esc_attr_e( 'Element', 'sm-marketplace' ); ?>"><?php sm_e( $el['type'] ); ?></th>
									<td data-label="<?php esc_attr_e( 'Profile', 'sm-marketplace' ); ?>"><?php echo sm_tech( $el['designation'] ); // phpcs:ignore ?></td>
									<td class="num" data-label="<?php esc_attr_e( 'Quantity', 'sm-marketplace' ); ?>"><?php echo esc_html( $el['qty'] ); ?></td>
									<td class="num" data-label="<?php esc_attr_e( 'Length (m)', 'sm-marketplace' ); ?>"><?php echo esc_html( sm_number( $el['length'], 1 ) ); ?></td>
									<td class="num" data-label="<?php esc_attr_e( 'Unit weight (kg/m)', 'sm-marketplace' ); ?>"><?php echo esc_html( sm_number( $el['unit_weight'], 1 ) ); ?></td>
									<td class="num" data-label="<?php esc_attr_e( 'Total weight (kg)', 'sm-marketplace' ); ?>"><?php echo esc_html( sm_number( $el['total'] ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
						<tfoot>
							<tr>
								<th scope="row" colspan="5"><?php esc_html_e( 'Total steel weight', 'sm-marketplace' ); ?></th>
								<td class="num" data-label="<?php esc_attr_e( 'Total steel weight', 'sm-marketplace' ); ?>"><strong><?php echo esc_html( sm_number( $item['weight_kg'] ) ); ?></strong></td>
							</tr>
						</tfoot>
					</table>
				</div>
				<p class="sm-muted"><?php esc_html_e( 'Unit weights come from the platform’s standard section tables. The theoretical weight excludes bolts, plates and welds.', 'sm-marketplace' ); ?></p>
			</section>
		<?php endif; ?>

		<section class="sm-panel">
			<h2 class="sm-panel__title"><?php esc_html_e( 'Description', 'sm-marketplace' ); ?></h2>
			<?php $desc = sm_field( $item['description'] ); ?>
			<p<?php echo $desc['fallback'] ? ' lang="' . esc_attr( $desc['lang'] ) . '" dir="auto"' : ''; ?>><?php echo esc_html( $desc['text'] ); ?></p>
			<?php if ( $desc['fallback'] ) : ?><p class="sm-muted"><?php esc_html_e( 'Shown in the language the seller wrote it in.', 'sm-marketplace' ); ?></p><?php endif; ?>
			<?php if ( 'reuse' === $market || $is_build ) : ?>
				<p class="sm-callout"><?php sm_icon( 'hard-hat' ); ?><span><?php esc_html_e( 'Planning a structural reuse? Have the elements checked by a verified engineer before installation.', 'sm-marketplace' ); ?> <a href="<?php echo esc_url( sm_page_url( 'engineers' ) ); ?>"><?php esc_html_e( 'Find an engineer', 'sm-marketplace' ); ?></a></span></p>
			<?php endif; ?>
		</section>

		<?php
		sm_component(
			'enquiry-form',
			array(
				'kind'      => 'listing',
				'module'    => $market,
				'entity_id' => $item['id'],
				'company'   => $item['company'],
				'subject'   => sm_t( $item['title'] ),
			)
		);
		?>
	</div>

	<?php if ( $similar ) : ?>
		<section class="sm-section">
			<div class="sm-section__head"><h2><?php esc_html_e( 'Similar listings', 'sm-marketplace' ); ?></h2></div>
			<div class="sm-grid sm-grid--grid sm-grid--4">
				<?php foreach ( array_slice( $similar, 0, 4 ) as $s ) : ?>
					<?php sm_component( 'card-listing', array( 'item' => $s ) ); ?>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>
</div>
<div class="sm-stickybar" aria-hidden="false">
	<div><strong><?php echo $is_build && 'assembled' === $item['mode'] ? esc_html( sm_money( $item['total_price'] ) ) : sm_per_unit( $item['price_per_kg'] ); // phpcs:ignore ?></strong></div>
	<a class="sm-btn sm-btn--accent" href="#sm-enquiry"><?php sm_icon( 'message' ); ?><?php esc_html_e( 'Contact', 'sm-marketplace' ); ?></a>
</div>
<?php
get_footer();

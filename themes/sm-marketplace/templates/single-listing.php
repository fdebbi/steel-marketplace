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
} else {
	// Link previews (WhatsApp, Facebook): title, and the first photo or the PNG drawing of the section.
	add_action(
		'wp_head',
		static function () use ( $item ) {
			$img = ! empty( $item['photos'] ) ? sm_image_url( $item['photos'][0] ) : (string) ( $item['share_image'] ?? '' );
			printf( '<meta property="og:type" content="product">' . "\n" . '<meta property="og:title" content="%s">' . "\n", esc_attr( sm_t( $item['title'] ) ) );
			if ( $img ) {
				printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $img ) );
			}
		},
		5
	);
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
	$specs[ __( 'Length × width × height', 'sm-marketplace' ) ] = sm_tech( sm_number( $item['length'] ) . ' × ' . sm_number( $item['width'] ) . ' × ' . sm_number( $item['height'], fmod( (float) $item['height'], 1 ) ? 1 : 0 ) . ' m' );
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
if ( ! empty( $item['spec_rows'] ) ) { // Rows prepared by the listing plugin for its category.
	$specs = array();
	foreach ( $item['spec_rows'] as $row ) {
		$specs[ $row[0] ] = $row[1];
	}
}
if ( in_array( $item['weight_source'], array( 'actual', 'theoretical', 'estimated', 'partial' ), true ) ) {
	$specs[ __( 'Weight source', 'sm-marketplace' ) ] = esc_html( sm_weight_source_label( $item['weight_source'] ) );
}
$sm_place = implode( ', ', array_filter( array( (string) ( $item['commune'] ?? '' ), sm_t( $wilayas[ $item['wilaya'] ] ?? '' ) ) ) );
$specs[ __( 'Location', 'sm-marketplace' ) ]      = esc_html( $sm_place );

$similar = sm_theme_listings( array( 'market' => $market, 'per_page' => 4 ) )['items'];
$similar = array_values( array_filter( $similar, static fn( $s ) => $s['id'] !== $item['id'] ) );
?>
<div class="sm-container sm-detail">
	<?php if ( ! empty( $item['unavailable'] ) || ( isset( $item['status'] ) && 'published' !== $item['status'] ) ) : ?>
		<div class="sm-notice sm-notice--warn"><?php sm_icon( 'info' ); ?><p>
			<?php
			$sm_status_text = array(
				'sold'     => __( 'This product has been sold.', 'sm-marketplace' ),
				'expired'  => __( 'This listing has expired.', 'sm-marketplace' ),
				'pending'  => __( 'Preview: this product is waiting for review and is not visible to buyers yet.', 'sm-marketplace' ),
				'draft'    => __( 'Preview of a draft: not visible to buyers.', 'sm-marketplace' ),
				'rejected' => __( 'Preview: changes were requested; not visible to buyers.', 'sm-marketplace' ),
			);
			echo esc_html( $sm_status_text[ $item['status'] ] ?? '' );
			?>
		</p></div>
	<?php endif; ?>
	<?php sm_breadcrumb( array( array( $labels[ $market ], sm_page_url( $market ) ), array( sm_t( $cats[ $item['category'] ] ?? '' ), sm_page_url( $market, array( 'category' => $item['category'] ) ) ), array( sm_t( $item['title'] ), '' ) ) ); ?>

	<?php if ( $is_build && ! empty( $item['building'] ) ) : ?>
		<?php // 0.33.0: page of a building (sm-buildings 0.4.0): price of the whole building first, geometry, inventory on demand. ?>
		<?php sm_component( 'listing-building', array( 'item' => $item, 'company' => $company, 'specs' => $specs, 'place' => $sm_place ) ); ?>
	<?php else : ?>
	<div class="sm-detail__top">
		<?php sm_component( 'listing-gallery', array( 'item' => $item ) ); ?>

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

			<?php
			// Buy / make an offer (sm-trade, 0.24.0).
			$trade     = sm_theme_data( 'trade_box', array( 'id' => $item['id'] ) );
			$can_trade = isset( $trade['state'] ) && ! empty( $trade['can_buy'] ) && 'unavailable' !== $trade['state'];
			?>
			<div class="sm-detail__actions">
				<?php if ( $can_trade && 'own' !== $trade['state'] ) : ?>
					<a class="sm-btn sm-btn--accent sm-btn--block" href="#sm-buy" data-sm-buy="order"><?php sm_icon( 'cart' ); ?><?php esc_html_e( 'Buy', 'sm-marketplace' ); ?></a>
					<a class="sm-btn sm-btn--primary-outline sm-btn--block" href="#sm-buy" data-sm-buy="offer"><?php sm_icon( 'tag' ); ?><?php esc_html_e( 'Make an offer', 'sm-marketplace' ); ?></a>
					<a class="sm-btn sm-btn--ghost sm-btn--block" href="#sm-enquiry"><?php sm_icon( 'message' ); ?><?php esc_html_e( 'Contact the seller', 'sm-marketplace' ); ?></a>
				<?php else : ?>
					<a class="sm-btn sm-btn--accent sm-btn--block" href="#sm-enquiry"><?php sm_icon( 'message' ); ?><?php esc_html_e( 'Contact the seller', 'sm-marketplace' ); ?></a>
				<?php endif; ?>
				<a class="sm-btn sm-btn--primary-outline sm-btn--block" href="<?php echo esc_url( sm_page_url( 'rfq', array( 'from' => $item['id'] ) ) ); ?>"><?php sm_icon( 'rfq' ); ?><?php esc_html_e( 'Request a quotation', 'sm-marketplace' ); ?></a>
				<div class="sm-detail__mini">
					<button class="sm-btn sm-btn--ghost" type="button" aria-pressed="false" data-sm-save="<?php echo esc_attr( $item['id'] ); ?>"><?php sm_icon( 'heart' ); ?><?php esc_html_e( 'Save', 'sm-marketplace' ); ?></button>
					<button class="sm-btn sm-btn--ghost" type="button" data-sm-share><?php sm_icon( 'share' ); ?><?php esc_html_e( 'Share', 'sm-marketplace' ); ?></button>
				</div>
			</div>
			<?php if ( $can_trade && 'own' !== $trade['state'] ) : ?>
				<?php sm_component( 'buy-box', array( 'item' => $item, 'box' => $trade ) ); ?>
				<script>(function(p){if(!p)return;var keep=location.hash==='#sm-buy'||p.querySelector('.sm-notice--error,.sm-notice--info,.sm-field__error,[aria-invalid="true"]');if(!keep){p.hidden=true;}})(document.currentScript.previousElementSibling);</script>
			<?php endif; ?>
			<?php do_action( 'sm_slot', 'listing.aside', $item ); ?>

			<?php sm_component( 'listing-seller', array( 'item' => $item, 'company' => $company ) ); ?>
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
			<?php if ( ! empty( $item['drawing'] ) ) : ?>
				<figure class="smp-drawing<?php echo $is_build ? ' smp-drawing--wide' : ''; ?>">
					<div dir="ltr"><?php echo $item['drawing']; // phpcs:ignore WordPress.Security.EscapeOutput -- SVG built from numbers by the listing plugin. ?></div>
					<?php if ( ! empty( $item['drawing_note'] ) ) : ?><figcaption><?php sm_icon( 'info' ); ?><span><?php echo esc_html( $item['drawing_note'] ); ?></span></figcaption><?php endif; ?>
				</figure>
			<?php endif; ?>
		</section>
		<?php if ( ! empty( $item['sheets'] ) ) : ?>
			<section class="sm-panel">
				<h2 class="sm-panel__title"><?php esc_html_e( 'Technical documents', 'sm-marketplace' ); ?></h2>
				<ul class="smp-docs" role="list">
					<?php foreach ( $item['sheets'] as $doc ) : ?>
						<li><a href="<?php echo esc_url( $doc['url'] ); ?>" target="_blank" rel="noopener" download><?php sm_icon( 'file' ); ?><span><?php echo esc_html( $doc['name'] ); ?></span><small>PDF<?php echo $doc['size'] ? ' · ' . esc_html( size_format( $doc['size'], 1 ) ) : ''; ?></small></a></li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

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
				<?php $inv_title = 'reuse' === $market ? __( 'Lot inventory', 'sm-marketplace' ) : __( 'Structural element inventory', 'sm-marketplace' ); ?>
				<h2 class="sm-panel__title"><?php echo esc_html( $inv_title ); ?></h2>
				<?php sm_component( 'listing-inventory', array( 'item' => $item, 'title' => $inv_title ) ); ?>
				<p class="sm-muted"><?php esc_html_e( 'Unit weights come from the platform’s standard section tables. The theoretical weight excludes bolts, plates and welds.', 'sm-marketplace' ); ?></p>
			</section>
		<?php endif; ?>

		<?php $desc = sm_field( $item['description'] ); ?>
		<section class="sm-panel">
			<h2 class="sm-panel__title"><?php esc_html_e( 'Description', 'sm-marketplace' ); ?></h2>
			<?php if ( '' === trim( (string) $desc['text'] ) ) : ?>
				<p class="sm-muted"><?php esc_html_e( 'No description. Ask the seller for details with the form below.', 'sm-marketplace' ); ?></p>
			<?php else : ?>
			<p class="sm-prewrap"<?php echo $desc['fallback'] ? ' lang="' . esc_attr( $desc['lang'] ) . '" dir="auto"' : ''; ?>><?php echo esc_html( $desc['text'] ); ?></p>
			<?php endif; ?>
			<?php if ( $desc['fallback'] ) : ?><p class="sm-muted"><?php esc_html_e( 'Shown in the language the seller wrote it in.', 'sm-marketplace' ); ?></p><?php endif; ?>
			<?php if ( ! empty( $item['advice'] ) ) : ?>
				<?php $adv = $item['advice']; ?>
				<div class="sm-advice sm-advice--<?php echo esc_attr( 'warning' === ( $adv['level'] ?? '' ) ? 'warning' : 'info' ); ?>" role="note">
					<?php sm_icon( empty( $adv['link_page'] ) ? 'recycle' : 'hard-hat', 'sm-advice__icon' ); ?>
					<div>
						<p class="sm-advice__title"><?php echo esc_html( $adv['title'] ); ?></p>
						<p><?php echo esc_html( $adv['text'] ); ?></p>
						<?php if ( ! empty( $adv['link_page'] ) ) : ?>
							<a class="sm-btn sm-btn--ghost sm-btn--sm" href="<?php echo esc_url( sm_page_url( $adv['link_page'] ) ); ?>"><?php echo esc_html( $adv['link_label'] ); ?><?php sm_icon( 'chevron-end' ); ?></a>
						<?php endif; ?>
					</div>
				</div>
			<?php elseif ( 'reuse' === $market || $is_build ) : ?>
				<p class="sm-callout"><?php sm_icon( 'hard-hat' ); ?><span><?php esc_html_e( 'Planning a structural reuse? Have the elements checked by a verified engineer before installation.', 'sm-marketplace' ); ?> <a href="<?php echo esc_url( sm_page_url( 'engineers' ) ); ?>"><?php esc_html_e( 'Find an engineer', 'sm-marketplace' ); ?></a></span></p>
			<?php endif; ?>
		</section>

		<?php sm_component( 'listing-place', array( 'item' => $item, 'place' => $sm_place ) ); ?>

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
	<?php endif; ?>

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
	<div><strong><?php echo $is_build && ! empty( $item['building_sale']['lot_total'] ) ? esc_html( sm_money( $item['building_sale']['lot_total'] ) ) : ( $is_build && 'assembled' === $item['mode'] ? esc_html( sm_money( $item['total_price'] ) ) : sm_listing_price( $item, false ) ); // phpcs:ignore ?></strong></div>
	<a class="sm-btn sm-btn--accent" href="#sm-enquiry"><?php sm_icon( 'message' ); ?><?php esc_html_e( 'Contact', 'sm-marketplace' ); ?></a>
</div>
<?php
get_footer();

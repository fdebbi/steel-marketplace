<?php
/**
 * Page of a product or reuse listing (0.34.0), built like the building page.
 *
 * Right column: state, title, place, price (per kg first, a bar, what is
 * available, the whole lot), actions, seller, location and visit, inspection
 * request (reuse). Left column: gallery (the section sheet is a slide), the lot
 * in four figures with the other characteristics on demand, inventory of a
 * reuse lot (on demand), documents, description, message to the seller.
 * On a phone: photos, title, price and actions first.
 *
 * @var array $args { item: array, company: array|null, specs: array label => value, place: string }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$item    = (array) $args['item'];
$company = $args['company'] ?? null;
$specs   = (array) ( $args['specs'] ?? array() );
$place   = (string) ( $args['place'] ?? '' );
$market  = $item['market'];
$insp    = $item['inspection'] ?? null;

// Key figures of the lot: designation, grade, bars × length (or pieces), weight; else the first rows of the specification.
$figures = array();
$els     = (array) ( $item['elements'] ?? array() );
$plate   = 'PLATE' === ( $item['family'] ?? '' );
$bars    = ! $els && ! $plate && 'bars' === ( $item['qty_mode'] ?? '' ) && (int) $item['quantity'] > 0 && (float) $item['length_m'] > 0;
$weight  = static fn( $w ) => sm_tech( $w >= 1000 ? sm_number( $w / 1000, 2 ) . ' t' : sm_number( $w ) . ' kg' );
if ( $els ) {
	// A lot of several elements (reuse): kinds, pieces and weight; the inventory below gives the detail.
	$figures[] = array( sm_tech( sm_number( count( $els ) ) ), __( 'Kinds of elements', 'sm-marketplace' ) );
	$figures[] = array( sm_tech( sm_number( array_sum( array_map( static fn( $e ) => (int) ( $e['qty'] ?? 0 ), $els ) ) ) ), __( 'Pieces', 'sm-marketplace' ) );
	if ( (float) $item['weight_kg'] > 0 ) {
		$figures[] = array( $weight( (float) $item['weight_kg'] ), __( 'Total weight', 'sm-marketplace' ) . ' · ' . sm_weight_source_short( $item['weight_source'] ) );
	}
} elseif ( '' !== (string) $item['designation'] ) {
	$figures[] = array( sm_tech( $item['designation'] ), __( 'Designation', 'sm-marketplace' ) );
	if ( '' !== (string) $item['grade'] ) {
		$figures[] = array( sm_tech( $item['grade'] ), __( 'Steel grade', 'sm-marketplace' ) );
	}
	if ( $bars ) {
		$figures[] = array( sm_tech( sm_number( (int) $item['quantity'] ) . ' × ' . sm_number( $item['length_m'], fmod( (float) $item['length_m'], 1 ) ? 1 : 0 ) . ' m' ), __( 'Bars × length', 'sm-marketplace' ) );
	} elseif ( (float) $item['quantity'] > 0 ) {
		$figures[] = array( sm_tech( ( 'weight' === ( $item['qty_mode'] ?? '' ) ? '≈ ' : '' ) . sm_number( $item['quantity'], fmod( (float) $item['quantity'], 1 ) ? 1 : 0 ) ), $plate ? __( 'Sheets', 'sm-marketplace' ) : __( 'Pieces', 'sm-marketplace' ) );
	}
	if ( (float) $item['weight_kg'] > 0 ) {
		$figures[] = array( $weight( (float) $item['weight_kg'] ), __( 'Total weight', 'sm-marketplace' ) . ' · ' . sm_weight_source_short( $item['weight_source'] ) );
	}
} else {
	foreach ( array_slice( $specs, 0, 4, true ) as $label => $value ) {
		$figures[] = array( $value, $label );
	}
}

// Buy / make an offer (sm-trade).
$trade     = sm_theme_data( 'trade_box', array( 'id' => $item['id'] ) );
$can_trade = isset( $trade['state'] ) && ! empty( $trade['can_buy'] ) && 'unavailable' !== $trade['state'] && 'own' !== $trade['state'];
$has_lot   = $can_trade && ! empty( $trade['lot'] );
$has_det   = $can_trade && ! empty( $trade['detail'] );
?>
<div class="smb-page smb-page--product">
	<div class="smb-page__col smb-page__col--main">
		<div class="smb-page__gallery"><?php sm_component( 'listing-gallery', array( 'item' => $item ) ); ?></div>

		<section class="sm-panel smb-geo">
			<div class="smb-head">
				<h2 class="sm-panel__title"><?php esc_html_e( 'The lot for sale', 'sm-marketplace' ); ?></h2>
				<?php if ( ! empty( $item['category_label'] ) ) : ?><span class="sm-chip sm-chip--quiet"><?php echo esc_html( sm_t( $item['category_label'] ) ); ?></span><?php endif; ?>
			</div>
			<?php if ( $figures ) : ?>
				<dl class="smb-figures">
					<?php foreach ( $figures as $f ) : ?>
						<div><dd><?php echo sm_kses_tech( $f[0] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd><dt><?php echo esc_html( $f[1] ); ?></dt></div>
					<?php endforeach; ?>
				</dl>
			<?php endif; ?>
			<details class="smb-more">
				<summary><span><?php esc_html_e( 'All characteristics', 'sm-marketplace' ); ?> <small><?php esc_html_e( 'standard, dimensions, weight per metre, weight source…', 'sm-marketplace' ); ?></small></span><?php sm_icon( 'chevron-down', 'smb-more__chev' ); ?></summary>
				<div class="smb-more__body">
					<dl class="sm-spec">
						<?php foreach ( $specs as $label => $value ) : ?>
							<div><dt><?php echo esc_html( $label ); ?></dt><dd><?php echo sm_kses_tech( $value ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd></div>
						<?php endforeach; ?>
					</dl>
					<?php if ( ! empty( $item['drawing'] ) ) : ?>
						<figure class="smp-drawing">
							<div dir="ltr"><?php echo $item['drawing']; // phpcs:ignore WordPress.Security.EscapeOutput -- SVG built from numbers by the listing plugin. ?></div>
							<?php if ( ! empty( $item['drawing_note'] ) ) : ?><figcaption><?php sm_icon( 'info' ); ?><span><?php echo esc_html( $item['drawing_note'] ); ?></span></figcaption><?php endif; ?>
						</figure>
					<?php endif; ?>
				</div>
			</details>
		</section>

		<?php if ( ! empty( $item['elements'] ) ) : ?>
			<?php
			$inv_title = __( 'Lot inventory', 'sm-marketplace' );
			$pieces    = array_sum( array_map( static fn( $e ) => (int) ( $e['qty'] ?? 0 ), (array) $item['elements'] ) );
			?>
			<section class="sm-panel smb-stock" id="smb-inventory">
				<div class="smb-head">
					<div>
						<h2 class="sm-panel__title"><?php echo esc_html( $inv_title ); ?></h2>
						<p class="sm-muted smb-stock__count">
							<?php
							/* translators: 1: number of kinds of elements, 2: number of pieces. */
							echo esc_html( sprintf( __( '%1$s kinds of elements · %2$s pieces', 'sm-marketplace' ), sm_number( count( (array) $item['elements'] ) ), sm_number( $pieces ) ) );
							?>
						</p>
					</div>
					<?php if ( (float) $item['weight_kg'] > 0 ) : ?><span class="smb-pill"><?php echo sm_kg( $item['weight_kg'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php endif; ?>
				</div>
				<details class="smb-more">
					<summary><span><?php esc_html_e( 'See the detail of the inventory', 'sm-marketplace' ); ?></span><?php sm_icon( 'chevron-down', 'smb-more__chev' ); ?></summary>
					<div class="smb-more__body">
						<?php sm_component( 'listing-inventory', array( 'item' => $item, 'title' => $inv_title ) ); ?>
						<p class="sm-muted"><?php esc_html_e( 'Unit weights come from the platform’s standard section tables. The theoretical weight excludes bolts, plates and welds.', 'sm-marketplace' ); ?></p>
					</div>
				</details>
			</section>
		<?php endif; ?>

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

		<?php $desc = sm_field( $item['description'] ); ?>
		<section class="sm-panel">
			<h2 class="sm-panel__title"><?php esc_html_e( 'Description', 'sm-marketplace' ); ?></h2>
			<?php if ( '' === trim( (string) $desc['text'] ) ) : ?>
				<p class="sm-muted"><?php esc_html_e( 'No description. Ask the seller for details with the form below.', 'sm-marketplace' ); ?></p>
			<?php else : ?>
				<p class="sm-prewrap"<?php echo $desc['fallback'] ? ' lang="' . esc_attr( $desc['lang'] ) . '" dir="auto"' : ''; ?>><?php echo esc_html( $desc['text'] ); ?></p>
			<?php endif; ?>
			<?php if ( $desc['fallback'] ) : ?><p class="sm-muted"><?php esc_html_e( 'Shown in the language the seller wrote it in.', 'sm-marketplace' ); ?></p><?php endif; ?>
			<?php if ( ! $insp && ! empty( $item['advice'] ) && empty( $item['advice']['link_page'] ) ) : ?>
				<?php $adv = $item['advice']; // Scrap: recycling only. ?>
				<div class="sm-advice sm-advice--info" role="note">
					<?php sm_icon( 'recycle', 'sm-advice__icon' ); ?>
					<div><p class="sm-advice__title"><?php echo esc_html( $adv['title'] ); ?></p><p><?php echo esc_html( $adv['text'] ); ?></p></div>
				</div>
			<?php endif; ?>
		</section>

		<div class="smb-page__enquiry">
			<?php
			sm_component(
				'enquiry-form',
				array(
					'kind'      => 'listing',
					'module'    => $market,
					'entity_id' => $item['id'],
					'company'   => $item['company'],
					'subject'   => sm_t( $item['title'] ),
					'qty_label' => $bars ? __( 'Quantity needed (bars)', 'sm-marketplace' ) : '',
				)
			);
			?>
		</div>
	</div>

	<aside class="smb-page__col smb-page__col--side">
		<div class="smb-page__headline">
			<div class="sm-card__meta">
				<?php if ( 'reuse' === $market ) : ?>
					<?php sm_component( 'condition-badge', array( 'condition' => $item['condition'] ) ); ?>
				<?php else : ?>
					<span class="sm-chip sm-chip--tech"><?php esc_html_e( 'Brand new', 'sm-marketplace' ); ?></span>
				<?php endif; ?>
				<span class="sm-chip sm-chip--quiet"><?php echo esc_html( sm_ago( $item['date'] ) ); ?></span>
			</div>
			<h1 class="sm-detail__title"><?php sm_e( $item['title'] ); ?></h1>
			<p class="smb-loc"><?php sm_icon( 'map-pin' ); ?><span><?php echo esc_html( $place ); ?></span><a href="#sm-place"><?php esc_html_e( 'See the location', 'sm-marketplace' ); ?></a></p>
		</div>

		<div class="smb-page__price"><?php sm_component( 'price-block', array( 'item' => $item, 'page' => true ) ); ?></div>

		<div class="smb-page__actions">
			<div class="smb-actions">
				<?php if ( $can_trade ) : ?>
					<a class="sm-btn sm-btn--accent sm-btn--block" href="#sm-buy" data-sm-buy="order" data-sm-buy-mode="<?php echo $has_det ? 'detail' : 'lot'; ?>"><?php sm_icon( 'cart' ); ?><?php echo esc_html( $has_det ? __( 'Buy', 'sm-marketplace' ) : __( 'Buy the whole lot', 'sm-marketplace' ) ); ?></a>
					<div class="smb-actions__two">
						<?php if ( $has_lot && $has_det ) : ?>
							<a class="sm-btn sm-btn--primary-outline" href="#sm-buy" data-sm-buy="order" data-sm-buy-mode="lot"><?php sm_icon( 'layers' ); ?><?php esc_html_e( 'Buy the whole lot', 'sm-marketplace' ); ?></a>
						<?php endif; ?>
						<a class="sm-btn sm-btn--primary-outline" href="#sm-buy" data-sm-buy="offer"><?php sm_icon( 'tag' ); ?><?php esc_html_e( 'Make an offer', 'sm-marketplace' ); ?></a>
					</div>
				<?php else : ?>
					<a class="sm-btn sm-btn--accent sm-btn--block" href="#sm-enquiry"><?php sm_icon( 'message' ); ?><?php esc_html_e( 'Contact the seller', 'sm-marketplace' ); ?></a>
				<?php endif; ?>
				<div class="smb-actions__mini">
					<?php if ( $can_trade ) : ?><a class="sm-btn sm-btn--ghost sm-btn--sm" href="#sm-enquiry"><?php sm_icon( 'message' ); ?><?php esc_html_e( 'Get in touch', 'sm-marketplace' ); ?></a><?php endif; ?>
					<a class="sm-btn sm-btn--ghost sm-btn--sm" href="<?php echo esc_url( sm_page_url( 'rfq', array( 'from' => $item['id'] ) ) ); ?>"><?php sm_icon( 'rfq' ); ?><?php esc_html_e( 'Quotation', 'sm-marketplace' ); ?></a>
					<button class="sm-btn sm-btn--ghost sm-btn--sm" type="button" aria-pressed="false" data-sm-save="<?php echo esc_attr( $item['id'] ); ?>"><?php sm_icon( 'heart' ); ?><?php esc_html_e( 'Save', 'sm-marketplace' ); ?></button>
					<button class="sm-btn sm-btn--ghost sm-btn--sm" type="button" data-sm-share><?php sm_icon( 'share' ); ?><?php esc_html_e( 'Share', 'sm-marketplace' ); ?></button>
				</div>
			</div>
			<?php if ( $can_trade ) : ?>
				<?php sm_component( 'buy-box', array( 'item' => $item, 'box' => $trade ) ); ?>
				<script>(function(p){if(!p)return;var keep=location.hash==='#sm-buy'||p.querySelector('.sm-notice--error,.sm-notice--info,.sm-field__error,[aria-invalid="true"]');if(!keep){p.hidden=true;}})(document.currentScript.previousElementSibling);</script>
			<?php endif; ?>
			<?php do_action( 'sm_slot', 'listing.aside', $item ); ?>
		</div>

		<div class="smb-page__seller sm-panel">
			<?php sm_component( 'listing-seller', array( 'item' => $item, 'company' => $company ) ); ?>
		</div>

		<div class="smb-page__place">
			<?php sm_component( 'listing-place', array( 'item' => $item, 'place' => $place, 'area' => true ) ); ?>
		</div>

		<?php if ( $insp ) : ?>
			<section class="smb-insp smb-insp--<?php echo esc_attr( 'warning' === ( $insp['level'] ?? '' ) ? 'warning' : 'info' ); ?>" role="note">
				<?php sm_icon( 'hard-hat', 'smb-insp__icon' ); ?>
				<div>
					<h2 class="smb-insp__title"><?php echo esc_html( $insp['title'] ); ?></h2>
					<p><?php echo esc_html( $insp['text'] ); ?></p>
				</div>
				<a class="sm-btn sm-btn--info sm-btn--block" href="<?php echo esc_url( sm_page_url( 'mission-request', array( 'type' => $insp['type'], 'listing' => $item['id'] ) ) ); ?>"><?php echo esc_html( $insp['label'] ); ?><?php sm_icon( 'chevron-end' ); ?></a>
			</section>
		<?php endif; ?>
	</aside>
</div>

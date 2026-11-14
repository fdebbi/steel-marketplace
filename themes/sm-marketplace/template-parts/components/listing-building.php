<?php
/**
 * Page of a building listing (0.33.0, data from sm-buildings 0.4.0).
 *
 * Right column: title, place, price of the whole building (the price per kg is
 * its equivalent), actions, seller, location and visit, inspection request.
 * Left column: photos, geometry (key figures and drawing; the other
 * characteristics open on demand), inventory (opens on demand), what the price
 * includes, documents, description, message to the seller.
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
$b       = (array) $item['building'];
$sale    = (array) ( $item['building_sale'] ?? array() );
$insp    = $b['inspection'] ?? null;
$market  = $item['market'];

// Buy / make an offer (sm-trade).
$trade     = sm_theme_data( 'trade_box', array( 'id' => $item['id'] ) );
$can_trade = isset( $trade['state'] ) && ! empty( $trade['can_buy'] ) && 'unavailable' !== $trade['state'] && 'own' !== $trade['state'];
$has_lot   = $can_trade && ! empty( $trade['lot'] );
$has_el    = $can_trade && ! empty( $trade['detail']['lines'] );
$el_kg     = (float) ( $item['elements_kg'] ?? 0 );
?>
<div class="smb-page">
	<div class="smb-page__col smb-page__col--main">
		<div class="smb-page__gallery"><?php sm_component( 'listing-gallery', array( 'item' => $item ) ); ?></div>

		<section class="sm-panel smb-geo">
			<div class="smb-head">
				<h2 class="sm-panel__title"><?php esc_html_e( 'Geometric characteristics', 'sm-marketplace' ); ?></h2>
				<?php if ( ! empty( $b['system'] ) ) : ?><span class="sm-chip sm-chip--quiet"><?php echo esc_html( sm_t( $b['system'] ) ); ?></span><?php endif; ?>
			</div>
			<dl class="smb-figures">
				<?php foreach ( (array) $b['figures'] as $f ) : ?>
					<div><dd><?php echo sm_kses_tech( $f[0] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd><dt><?php echo esc_html( $f[1] ); ?></dt></div>
				<?php endforeach; ?>
			</dl>
			<?php if ( ! empty( $item['drawing'] ) ) : ?>
				<figure class="smp-drawing smp-drawing--wide">
					<div dir="ltr"><?php echo $item['drawing']; // phpcs:ignore WordPress.Security.EscapeOutput -- SVG built from numbers by the listing plugin. ?></div>
					<?php if ( ! empty( $item['drawing_note'] ) ) : ?><figcaption><?php sm_icon( 'info' ); ?><span><?php echo esc_html( $item['drawing_note'] ); ?></span></figcaption><?php endif; ?>
				</figure>
			<?php endif; ?>
			<details class="smb-more">
				<summary><span><?php esc_html_e( 'All characteristics', 'sm-marketplace' ); ?> <small><?php esc_html_e( 'condition, roof, covering, cladding…', 'sm-marketplace' ); ?></small></span><?php sm_icon( 'chevron-down', 'smb-more__chev' ); ?></summary>
				<dl class="sm-spec smb-more__body">
					<?php foreach ( $specs as $label => $value ) : ?>
						<div><dt><?php echo esc_html( $label ); ?></dt><dd><?php echo sm_kses_tech( $value ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd></div>
					<?php endforeach; ?>
				</dl>
			</details>
		</section>

		<?php if ( ! empty( $item['elements'] ) ) : ?>
			<?php $inv_title = __( 'Structural element inventory', 'sm-marketplace' ); ?>
			<section class="sm-panel smb-stock" id="smb-inventory">
				<div class="smb-head">
					<div>
						<h2 class="sm-panel__title"><?php echo esc_html( $inv_title ); ?></h2>
						<p class="sm-muted smb-stock__count">
							<?php
							echo esc_html(
								sprintf(
									/* translators: 1: number of kinds of elements, 2: number of pieces. */
									__( '%1$s kinds of elements · %2$s pieces', 'sm-marketplace' ),
									sm_number( (int) $b['kinds'] ),
									sm_number( (int) $b['pieces'] )
								)
							);
							?>
						</p>
					</div>
					<span class="smb-pill"><?php echo sm_kg( $el_kg > 0 ? $el_kg : $item['weight_kg'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
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

		<section class="sm-panel">
			<h2 class="sm-panel__title"><?php esc_html_e( 'What the price includes', 'sm-marketplace' ); ?></h2>
			<ul class="smb-inc" role="list">
				<li class="is-yes"><?php sm_icon( 'check' ); ?><span><?php esc_html_e( 'Steel structure', 'sm-marketplace' ); ?></span></li>
				<?php
				$inc_labels = array(
					'dismantling' => array( __( 'Dismantling included', 'sm-marketplace' ), __( 'Dismantling: not included', 'sm-marketplace' ) ),
					'transport'   => array( __( 'Transport included', 'sm-marketplace' ), __( 'Transport: not included', 'sm-marketplace' ) ),
					'reinstall'   => array( __( 'Reinstallation included', 'sm-marketplace' ), __( 'Reinstallation: not included', 'sm-marketplace' ) ),
					'foundations' => array( __( 'Foundations and accessories included', 'sm-marketplace' ), __( 'Foundations and accessories: not included', 'sm-marketplace' ) ),
				);
				// Included first, then what is not; written in full, nothing struck through.
				foreach ( array( true, false ) as $want ) :
					foreach ( $inc_labels as $key => $label ) :
						$yes = ! empty( $item['includes'][ $key ] );
						if ( $yes !== $want || ( 'dismantling' === $key && 'assembled' !== $item['mode'] ) ) {
							continue;
						}
						?>
						<li class="<?php echo $yes ? 'is-yes' : 'is-no'; ?>"><?php if ( $yes ) : ?><?php sm_icon( 'check' ); ?><?php else : ?><span class="smb-inc__dash" aria-hidden="true">–</span><?php endif; ?><span><?php echo esc_html( $label[ $yes ? 0 : 1 ] ); ?></span></li>
						<?php
					endforeach;
				endforeach;
				?>
			</ul>
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

		<?php $desc = sm_field( $item['description'] ); ?>
		<section class="sm-panel">
			<h2 class="sm-panel__title"><?php esc_html_e( 'Description', 'sm-marketplace' ); ?></h2>
			<?php if ( '' === trim( (string) $desc['text'] ) ) : ?>
				<p class="sm-muted"><?php esc_html_e( 'No description. Ask the seller for details with the form below.', 'sm-marketplace' ); ?></p>
			<?php else : ?>
				<p class="sm-prewrap"<?php echo $desc['fallback'] ? ' lang="' . esc_attr( $desc['lang'] ) . '" dir="auto"' : ''; ?>><?php echo esc_html( $desc['text'] ); ?></p>
			<?php endif; ?>
			<?php if ( $desc['fallback'] ) : ?><p class="sm-muted"><?php esc_html_e( 'Shown in the language the seller wrote it in.', 'sm-marketplace' ); ?></p><?php endif; ?>
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
				)
			);
			?>
		</div>
	</div>

	<aside class="smb-page__col smb-page__col--side">
		<div class="smb-page__headline">
			<div class="sm-card__meta">
				<span class="sm-chip"><?php echo esc_html( 'assembled' === $item['mode'] ? __( 'Assembled', 'sm-marketplace' ) : __( 'Dismantled', 'sm-marketplace' ) ); ?></span>
				<?php if ( ! empty( $b['condition'] ) ) : ?><span class="sm-chip sm-chip--quiet"><?php echo esc_html( sm_t( $b['condition'] ) ); ?></span><?php endif; ?>
				<span class="sm-chip sm-chip--quiet"><?php echo esc_html( sm_ago( $item['date'] ) ); ?></span>
			</div>
			<h1 class="sm-detail__title"><?php sm_e( $item['title'] ); ?></h1>
			<p class="smb-loc"><?php sm_icon( 'map-pin' ); ?><span><?php echo esc_html( $place ); ?></span><a href="#sm-place"><?php esc_html_e( 'See the location', 'sm-marketplace' ); ?></a></p>
		</div>

		<div class="smb-page__price"><?php sm_component( 'price-block', array( 'item' => $item ) ); ?></div>

		<div class="smb-page__actions">
			<div class="smb-actions">
				<?php if ( $can_trade ) : ?>
					<?php if ( $has_lot ) : ?>
						<a class="sm-btn sm-btn--accent sm-btn--block" href="#sm-buy" data-sm-buy="order" data-sm-buy-mode="lot"><?php sm_icon( 'cart' ); ?><?php esc_html_e( 'Buy the building', 'sm-marketplace' ); ?></a>
					<?php endif; ?>
					<div class="smb-actions__two">
						<?php if ( $has_el ) : ?>
							<a class="sm-btn <?php echo $has_lot ? 'sm-btn--primary-outline' : 'sm-btn--accent'; ?>" href="#sm-buy" data-sm-buy="order" data-sm-buy-mode="detail"><?php sm_icon( 'layers' ); ?><?php esc_html_e( 'Buy elements', 'sm-marketplace' ); ?></a>
						<?php endif; ?>
						<a class="sm-btn sm-btn--primary-outline" href="#sm-buy" data-sm-buy="offer"><?php sm_icon( 'tag' ); ?><?php esc_html_e( 'Make an offer', 'sm-marketplace' ); ?></a>
					</div>
				<?php else : ?>
					<a class="sm-btn sm-btn--accent sm-btn--block" href="#sm-enquiry"><?php sm_icon( 'message' ); ?><?php esc_html_e( 'Contact the seller', 'sm-marketplace' ); ?></a>
				<?php endif; ?>
				<div class="smb-actions__mini">
					<?php if ( $can_trade ) : ?><a class="sm-btn sm-btn--ghost sm-btn--sm" href="#sm-enquiry"><?php sm_icon( 'message' ); ?><?php esc_html_e( 'Contact', 'sm-marketplace' ); ?></a><?php endif; ?>
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

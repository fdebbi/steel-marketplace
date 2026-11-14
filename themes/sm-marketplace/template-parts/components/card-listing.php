<?php
/**
 * Listing card for products, reuse items and buildings.
 *
 * @var array $args { item: array, layout?: 'grid'|'list' }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$item    = $args['item'] ?? array();
if ( ! $item ) {
	return;
}
$company = sm_theme_company( $item['company'] ?? '' );
$wilayas = sm_theme_wilayas();
$url     = sm_page_url( 'listing', array( 'id' => $item['id'] ) );
$market  = $item['market'];
?>
<article class="sm-card sm-card--listing sm-card--<?php echo esc_attr( $market ); ?>">
	<a class="sm-card__media<?php echo ! empty( $item['image_drawing'] ) ? ' is-drawing' : ''; ?>" href="<?php echo esc_url( $url ); ?>" tabindex="-1" aria-hidden="true">
		<img src="<?php echo esc_url( sm_image_url( $item['image'] ?? '' ) ); ?>" alt="" loading="lazy" decoding="async" width="640" height="480">
		<?php if ( count( (array) ( $item['photos'] ?? array() ) ) > 1 ) : ?>
			<span class="sm-card__more" dir="ltr"><?php sm_icon( 'camera' ); ?><?php echo esc_html( count( $item['photos'] ) ); ?></span>
		<?php endif; ?>
		<span class="sm-card__chips">
			<?php if ( ! empty( $item['featured'] ) || ! empty( $item['is_featured'] ) ) : ?>
				<span class="sm-chip sm-chip--featured"><?php sm_icon( 'star' ); ?><?php esc_html_e( 'Featured', 'sm-marketplace' ); ?></span>
			<?php endif; ?>
			<?php if ( ! empty( $item['is_urgent'] ) ) : ?>
				<span class="sm-chip sm-chip--urgent"><?php sm_icon( 'alert' ); ?><?php esc_html_e( 'Urgent', 'sm-marketplace' ); ?></span>
			<?php endif; ?>
		</span>
	</a>
	<button class="sm-card__save" type="button" aria-pressed="false" data-sm-save="<?php echo esc_attr( $item['id'] ); ?>">
		<?php sm_icon( 'heart' ); ?><span class="screen-reader-text"><?php esc_html_e( 'Save listing', 'sm-marketplace' ); ?></span>
	</button>

	<div class="sm-card__body">
		<div class="sm-card__meta">
			<?php if ( 'buildings' === $market ) : ?>
				<span class="sm-chip"><?php echo esc_html( 'assembled' === $item['mode'] ? __( 'Assembled', 'sm-marketplace' ) : __( 'Dismantled', 'sm-marketplace' ) ); ?></span>
			<?php elseif ( 'reuse' === $market && ! empty( $item['condition'] ) ) : ?>
				<?php sm_component( 'condition-badge', array( 'condition' => $item['condition'], 'compact' => true ) ); ?>
			<?php else : ?>
				<span class="sm-chip sm-chip--tech"><?php echo sm_tech( $item['designation'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<?php if ( ! empty( $item['grade'] ) ) : ?><span class="sm-chip sm-chip--quiet"><?php echo sm_tech( $item['grade'] ); // phpcs:ignore ?></span><?php endif; ?>
			<?php endif; ?>
		</div>

		<h3 class="sm-card__title"><a href="<?php echo esc_url( $url ); ?>"><?php sm_e( $item['title'] ); ?><?php if ( ! empty( $item['is_urgent'] ) ) : ?><span class="screen-reader-text"> (<?php esc_html_e( 'Urgent', 'sm-marketplace' ); ?>)</span><?php endif; ?></a></h3>

		<ul class="sm-card__facts">
			<?php if ( 'buildings' === $market ) : ?>
				<li><?php sm_icon( 'ruler' ); ?><?php echo sm_tech( sm_number( $item['length'] ) . ' × ' . sm_number( $item['width'] ) . ' × ' . sm_number( $item['height'], fmod( (float) $item['height'], 1 ) ? 1 : 0 ) . ' m' ); // phpcs:ignore ?></li>
				<li><?php sm_icon( 'grid' ); ?><?php echo sm_tech( sm_number( $item['area'] ) . ' m²' ); // phpcs:ignore ?></li>
			<?php else : ?>
				<?php if ( 'lines' === ( $item['kind'] ?? '' ) && ! empty( $item['quantity'] ) ) : ?>
					<li><?php sm_icon( 'ruler' ); ?><?php echo sm_tech( sprintf( /* translators: 1: number of pieces, 2: longest length in metres. */ __( '%1$s pcs · up to %2$s m', 'sm-marketplace' ), sm_number( $item['quantity'] ), sm_number( $item['length_m'], fmod( $item['length_m'], 1 ) ? 1 : 0 ) ) ); // phpcs:ignore ?></li>
				<?php elseif ( ! empty( $item['length_m'] ) && 'weight' !== ( $item['kind'] ?? '' ) ) : ?>
					<li><?php sm_icon( 'ruler' ); ?><?php echo sm_tech( sm_number( $item['quantity'], fmod( (float) $item['quantity'], 1 ) ? 1 : 0 ) . ' × ' . sm_number( $item['length_m'], fmod( $item['length_m'], 1 ) ? 1 : 0 ) . ' m' ); // phpcs:ignore ?></li>
				<?php endif; ?>
			<?php endif; ?>
			<?php if ( ! empty( $item['weight_kg'] ) ) : ?>
				<li><?php sm_icon( 'weight' ); ?><?php echo sm_kg( $item['weight_kg'] ); // phpcs:ignore ?> <span class="sm-weight-src sm-weight-src--<?php echo esc_attr( $item['weight_source'] ); ?>"><?php echo esc_html( sm_weight_source_short( $item['weight_source'] ) ); ?></span></li>
			<?php endif; ?>
		</ul>

		<div class="sm-card__price">
			<?php if ( 'buildings' === $market && isset( $item['building_sale'] ) ) : ?>
				<?php $bs = (array) $item['building_sale']; // 0.33.0: the whole-building price first, its equivalent per kg below. ?>
				<?php if ( $bs['lot_total'] > 0 ) : ?>
					<strong class="sm-price-main"><?php echo esc_html( sm_money( $bs['lot_total'] ) ); ?></strong>
					<span class="sm-price-sub"><?php if ( $bs['per_kg'] > 0 ) : ?><?php echo sm_per_unit( $bs['per_kg'], 'kg', false, $bs['per_kg'] - floor( $bs['per_kg'] ) > 0 ? 2 : 0 ); // phpcs:ignore ?><?php endif; ?><?php if ( ! empty( $bs['by_element'] ) ) : ?> · <?php esc_html_e( 'also by element', 'sm-marketplace' ); ?><?php endif; ?></span>
				<?php else : ?>
					<strong class="sm-price-main"><?php echo sm_per_unit( $bs['per_kg'], 'kg', true, $bs['per_kg'] - floor( $bs['per_kg'] ) > 0 ? 2 : 0 ); // phpcs:ignore ?></strong>
					<span class="sm-price-sub"><?php esc_html_e( 'Sold by element', 'sm-marketplace' ); ?></span>
				<?php endif; ?>
			<?php elseif ( 'buildings' === $market && 'assembled' === $item['mode'] ) : ?>
				<strong class="sm-price-main"><?php echo esc_html( sm_money( $item['total_price'] ) ); ?></strong>
				<span class="sm-price-sub"><?php echo sm_per_unit( $item['price_per_kg'] ); // phpcs:ignore ?> · <?php echo sm_per_unit( $item['price_per_m2'], 'm²' ); // phpcs:ignore ?></span>
			<?php elseif ( 'lot' === ( $item['sale_mode'] ?? '' ) ) : ?>
				<strong class="sm-price-main"><?php echo esc_html( sm_money( $item['total_price'] ) ); ?></strong>
				<span class="sm-price-sub"><?php esc_html_e( 'Whole lot only', 'sm-marketplace' ); ?><?php if ( $item['price_per_kg'] > 0 ) : ?> · <?php echo sm_per_unit( $item['price_per_kg'] ); // phpcs:ignore ?><?php endif; ?></span>
			<?php else : ?>
				<strong class="sm-price-main"><?php echo sm_listing_price( $item ); // phpcs:ignore ?></strong>
				<span class="sm-price-sub"><?php esc_html_e( 'Total', 'sm-marketplace' ); ?> <?php echo esc_html( sm_money( $item['total_price'] ) ); ?></span>
				<?php if ( 'both' === ( $item['sale_mode'] ?? '' ) && ! empty( $item['lot_price'] ) ) : ?>
					<span class="sm-price-lot"><?php echo esc_html( sprintf( /* translators: %s: price. */ __( 'or %s for the whole lot', 'sm-marketplace' ), sm_money( $item['lot_price'] ) ) ); ?></span>
				<?php endif; ?>
			<?php endif; ?>
		</div>

		<footer class="sm-card__foot">
			<span class="sm-card__place"><?php sm_icon( 'map-pin' ); ?><?php echo esc_html( sm_t( $wilayas[ $item['wilaya'] ] ?? '' ) ); ?></span>
			<?php if ( $company ) : ?>
				<a class="sm-card__seller" href="<?php echo esc_url( sm_page_url( 'company', array( 'c' => $company['slug'] ) ) ); ?>">
					<?php echo esc_html( sm_t( $company['name'] ) ); ?>
					<?php if ( $company['verification'] >= 2 ) : ?><?php sm_icon( 'verified', 'sm-verified', sm_verification_label( $company['verification'], $company['class'] ?? '' ) ); ?><?php endif; ?>
				</a>
			<?php elseif ( isset( $item['seller_public'] ) && ! $item['seller_public'] ) : ?>
				<span class="sm-card__seller sm-card__seller--private"><?php esc_html_e( 'Private seller', 'sm-marketplace' ); ?></span>
			<?php endif; ?>
		</footer>
	</div>
</article>

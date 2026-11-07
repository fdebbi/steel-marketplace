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
	<a class="sm-card__media" href="<?php echo esc_url( $url ); ?>" tabindex="-1" aria-hidden="true">
		<img src="<?php echo esc_url( sm_image_url( $item['image'] ?? '' ) ); ?>" alt="" loading="lazy" decoding="async" width="640" height="480">
		<?php if ( ! empty( $item['featured'] ) ) : ?>
			<span class="sm-chip sm-chip--featured"><?php sm_icon( 'star' ); ?><?php esc_html_e( 'Featured', 'sm-marketplace' ); ?></span>
		<?php endif; ?>
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

		<h3 class="sm-card__title"><a href="<?php echo esc_url( $url ); ?>"><?php sm_e( $item['title'] ); ?></a></h3>

		<ul class="sm-card__facts">
			<?php if ( 'buildings' === $market ) : ?>
				<li><?php sm_icon( 'ruler' ); ?><?php echo sm_tech( sm_number( $item['length'] ) . ' × ' . sm_number( $item['width'] ) . ' × ' . sm_number( $item['height'], 1 ) . ' m' ); // phpcs:ignore ?></li>
				<li><?php sm_icon( 'grid' ); ?><?php echo sm_tech( sm_number( $item['area'] ) . ' m²' ); // phpcs:ignore ?></li>
			<?php else : ?>
				<?php if ( ! empty( $item['length_m'] ) ) : ?>
					<li><?php sm_icon( 'ruler' ); ?><?php echo sm_tech( $item['quantity'] . ' × ' . sm_number( $item['length_m'], fmod( $item['length_m'], 1 ) ? 1 : 0 ) . ' m' ); // phpcs:ignore ?></li>
				<?php endif; ?>
			<?php endif; ?>
			<li><?php sm_icon( 'weight' ); ?><?php echo sm_kg( $item['weight_kg'] ); // phpcs:ignore ?> <span class="sm-weight-src sm-weight-src--<?php echo esc_attr( $item['weight_source'] ); ?>"><?php echo esc_html( 'actual' === $item['weight_source'] ? __( 'actual', 'sm-marketplace' ) : __( 'theoretical', 'sm-marketplace' ) ); ?></span></li>
		</ul>

		<div class="sm-card__price">
			<?php if ( 'buildings' === $market && 'assembled' === $item['mode'] ) : ?>
				<strong class="sm-price-main"><?php echo esc_html( sm_money( $item['total_price'] ) ); ?></strong>
				<span class="sm-price-sub"><?php echo sm_per_unit( $item['price_per_kg'] ); // phpcs:ignore ?> · <?php echo sm_per_unit( $item['price_per_m2'], 'm²' ); // phpcs:ignore ?></span>
			<?php else : ?>
				<strong class="sm-price-main"><?php echo sm_per_unit( $item['price_per_kg'], 'kg', true ); // phpcs:ignore ?></strong>
				<span class="sm-price-sub"><?php esc_html_e( 'Total', 'sm-marketplace' ); ?> <?php echo esc_html( sm_money( $item['total_price'] ) ); ?></span>
			<?php endif; ?>
		</div>

		<footer class="sm-card__foot">
			<span class="sm-card__place"><?php sm_icon( 'map-pin' ); ?><?php echo esc_html( sm_t( $wilayas[ $item['wilaya'] ] ?? '' ) ); ?></span>
			<?php if ( $company ) : ?>
				<a class="sm-card__seller" href="<?php echo esc_url( sm_page_url( 'company', array( 'c' => $company['slug'] ) ) ); ?>">
					<?php echo esc_html( sm_t( $company['name'] ) ); ?>
					<?php if ( $company['verification'] >= 2 ) : ?><?php sm_icon( 'verified', 'sm-verified', sm_verification_label( $company['verification'] ) ); ?><?php endif; ?>
				</a>
			<?php endif; ?>
		</footer>
	</div>
</article>

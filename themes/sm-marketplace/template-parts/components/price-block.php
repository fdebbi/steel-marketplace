<?php
/**
 * Price block of a detail page: price per kg, total, weight and its source.
 * Values are displayed as provided by the listing plugin (computed by sm-weight-engine).
 *
 * @var array $args { item: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$item      = $args['item'];
$assembled = 'buildings' === $item['market'] && 'assembled' === ( $item['mode'] ?? '' );
?>
<section class="sm-priceblock" aria-label="<?php esc_attr_e( 'Price', 'sm-marketplace' ); ?>">
	<?php if ( $assembled ) : ?>
		<p class="sm-priceblock__label"><?php esc_html_e( 'Asking price', 'sm-marketplace' ); ?></p>
		<p class="sm-priceblock__main"><?php echo esc_html( sm_money( $item['total_price'] ) ); ?></p>
		<dl class="sm-priceblock__rows">
			<div><dt><?php esc_html_e( 'Equivalent price per kg', 'sm-marketplace' ); ?></dt><dd><?php echo sm_per_unit( $item['price_per_kg'] ); // phpcs:ignore ?></dd></div>
			<div><dt><?php esc_html_e( 'Price per m²', 'sm-marketplace' ); ?></dt><dd><?php echo sm_per_unit( $item['price_per_m2'], 'm²' ); // phpcs:ignore ?></dd></div>
			<div><dt><?php esc_html_e( 'Estimated steel weight', 'sm-marketplace' ); ?></dt><dd><?php echo sm_kg( $item['weight_kg'] ); // phpcs:ignore ?></dd></div>
		</dl>
	<?php else : ?>
		<p class="sm-priceblock__label"><?php esc_html_e( 'Price per kilogram', 'sm-marketplace' ); ?></p>
		<p class="sm-priceblock__main"><?php echo sm_per_unit( $item['price_per_kg'], 'kg', true ); // phpcs:ignore ?></p>
		<dl class="sm-priceblock__rows">
			<div><dt><?php esc_html_e( 'Total weight', 'sm-marketplace' ); ?></dt><dd><?php echo sm_kg( $item['weight_kg'] ); // phpcs:ignore ?></dd></div>
			<div class="sm-priceblock__total"><dt><?php esc_html_e( 'Total price', 'sm-marketplace' ); ?></dt><dd><?php echo esc_html( sm_money( $item['total_price'] ) ); ?></dd></div>
		</dl>
	<?php endif; ?>

	<p class="sm-weight-note sm-weight-note--<?php echo esc_attr( $item['weight_source'] ); ?>">
		<?php sm_icon( 'actual' === $item['weight_source'] ? 'scale' : 'ruler' ); ?>
		<span>
			<strong><?php echo esc_html( sm_weight_source_label( $item['weight_source'] ) ); ?></strong>
			<?php echo esc_html( 'actual' === $item['weight_source'] ? __( 'Weighed by the seller.', 'sm-marketplace' ) : __( 'Calculated from standard section tables.', 'sm-marketplace' ) ); ?>
		</span>
	</p>
	<?php if ( ! empty( $item['demo'] ) ) : ?>
		<p class="sm-demo-note"><?php sm_icon( 'info' ); ?><?php esc_html_e( 'Demo listing: values are illustrative and will be computed by the weight engine.', 'sm-marketplace' ); ?></p>
	<?php endif; ?>
</section>

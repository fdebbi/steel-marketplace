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
		<?php
		$per_kg = 'kg' === ( $item['price_unit'] ?? 'kg' );
		$is_lot = 'lot' === ( $item['price_unit'] ?? '' );
		?>
		<p class="sm-priceblock__label"><?php echo esc_html( $per_kg ? __( 'Price per kilogram', 'sm-marketplace' ) : ( $is_lot ? __( 'Price for the whole lot', 'sm-marketplace' ) : __( 'Unit price', 'sm-marketplace' ) ) ); ?><?php if ( $is_lot && 'lot' === ( $item['sale_mode'] ?? '' ) ) : ?> · <?php esc_html_e( 'sold as a whole lot only', 'sm-marketplace' ); ?><?php endif; ?></p>
		<p class="sm-priceblock__main"><?php echo $is_lot ? esc_html( sm_money( $item['price'] ) ) : sm_listing_price( $item ); // phpcs:ignore ?></p>
		<dl class="sm-priceblock__rows">
			<?php if ( ! empty( $item['weight_kg'] ) ) : ?>
				<div><dt><?php esc_html_e( 'Total weight', 'sm-marketplace' ); ?></dt><dd><?php echo sm_kg( $item['weight_kg'] ); // phpcs:ignore ?></dd></div>
			<?php endif; ?>
			<?php if ( ! $per_kg && ! empty( $item['price_per_kg'] ) ) : ?>
				<div><dt><?php esc_html_e( 'Equivalent price per kg', 'sm-marketplace' ); ?></dt><dd><?php echo sm_per_unit( $item['price_per_kg'] ); // phpcs:ignore ?></dd></div>
			<?php endif; ?>
			<?php if ( ! $is_lot ) : ?>
				<div class="sm-priceblock__total"><dt><?php echo esc_html( 'both' === ( $item['sale_mode'] ?? '' ) ? __( 'Total at the detail price', 'sm-marketplace' ) : __( 'Total price', 'sm-marketplace' ) ); ?></dt><dd><?php echo esc_html( sm_money( $item['total_price'] ) ); ?></dd></div>
			<?php endif; ?>
		</dl>
		<?php if ( 'both' === ( $item['sale_mode'] ?? '' ) && ! empty( $item['lot_price'] ) ) : ?>
			<p class="sm-priceblock__lot"><?php esc_html_e( 'Or the whole lot', 'sm-marketplace' ); ?> <strong><?php echo esc_html( sm_money( $item['lot_price'] ) ); ?></strong><?php if ( ! empty( $item['lot_per_kg'] ) ) : ?> <small><?php echo sm_per_unit( $item['lot_per_kg'] ); // phpcs:ignore ?></small><?php endif; ?></p>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ( in_array( $item['weight_source'], array( 'actual', 'theoretical', 'estimated' ), true ) ) : ?>
	<?php
	$notes = array(
		'actual'      => __( 'Weighed by the seller.', 'sm-marketplace' ),
		'theoretical' => __( 'Calculated from standard section tables.', 'sm-marketplace' ),
		'estimated'   => __( 'Estimated by the seller, not weighed.', 'sm-marketplace' ),
	);
	?>
	<p class="sm-weight-note sm-weight-note--<?php echo esc_attr( $item['weight_source'] ); ?>">
		<?php sm_icon( 'theoretical' === $item['weight_source'] ? 'ruler' : 'scale' ); ?>
		<span>
			<strong><?php echo esc_html( sm_weight_source_label( $item['weight_source'] ) ); ?></strong>
			<?php echo esc_html( $notes[ $item['weight_source'] ] ); ?>
		</span>
	</p>
	<?php endif; ?>
	<?php if ( ! empty( $item['demo'] ) ) : ?>
		<p class="sm-demo-note"><?php sm_icon( 'info' ); ?><?php esc_html_e( 'Demo listing: values are illustrative and will be computed by the weight engine.', 'sm-marketplace' ); ?></p>
	<?php endif; ?>
</section>

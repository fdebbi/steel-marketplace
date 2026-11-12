<?php
/**
 * Home hero price curve (0.19.0): average asking price per kg of new steel
 * sections, week by week, computed by sm-products from the platform's listings
 * (resource "price_trend"). Nothing is shown without enough listings.
 *
 * The line draws itself, then the latest average appears; both are still when
 * the visitor asks for reduced motion.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$trend = sm_theme_data( 'price_trend' );
if ( ! is_array( $trend ) || empty( $trend['points'] ) || count( $trend['points'] ) < 2 ) {
	return;
}

$points = array_values( $trend['points'] );
$values = array_map( static fn( $p ) => (float) $p['value'], $points );
$min    = min( $values );
$max    = max( $values );
$span   = $max - $min > 0 ? $max - $min : 1;
$w      = 320;
$h      = 84;
$pad    = 10;
$coords = array();
foreach ( $values as $i => $v ) {
	$x        = $pad + ( $w - 2 * $pad ) * $i / ( count( $values ) - 1 );
	$y        = $h - $pad - ( $h - 2 * $pad ) * ( $v - $min ) / $span;
	$coords[] = array( round( $x, 1 ), round( $y, 1 ) );
}
$line = 'M' . implode( ' L', array_map( static fn( $c ) => $c[0] . ' ' . $c[1], $coords ) );
$area = $line . ' L' . end( $coords )[0] . ' ' . $h . ' L' . $coords[0][0] . ' ' . $h . ' Z';
$last = end( $coords );

$change = (float) ( $trend['change'] ?? 0 );
$weeks  = count( $points );
$label  = sprintf(
	/* translators: 1: average price per kg, 2: number of weeks shown. */
	__( 'Average asking price of new steel sections: %1$s DA per kg, curve over %2$d weeks.', 'sm-marketplace' ),
	sm_number( $trend['last'] ),
	$weeks
);
?>
<a class="sm-trend" href="<?php echo esc_url( sm_page_url( 'price-index' ) ); ?>" aria-label="<?php echo esc_attr( $label ); ?>">
	<span class="sm-trend__head">
		<span class="sm-trend__title"><?php esc_html_e( 'Average price of new sections', 'sm-marketplace' ); ?></span>
		<?php if ( abs( $change ) >= 0.1 ) : ?>
			<span class="sm-trend__change sm-trend__change--<?php echo $change > 0 ? 'up' : 'down'; ?>" dir="ltr"><?php echo esc_html( ( $change > 0 ? '+' : '−' ) . sm_number( abs( $change ), 1 ) . ' %' ); ?></span>
		<?php endif; ?>
	</span>
	<svg class="sm-trend__chart" viewBox="0 0 <?php echo (int) $w; ?> <?php echo (int) $h; ?>" preserveAspectRatio="none" aria-hidden="true" focusable="false" dir="ltr">
		<defs>
			<linearGradient id="sm-trend-fill" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#06CE77" stop-opacity=".28"/><stop offset="1" stop-color="#06CE77" stop-opacity="0"/></linearGradient>
		</defs>
		<path class="sm-trend__area" d="<?php echo esc_attr( $area ); ?>" fill="url(#sm-trend-fill)"/>
		<path class="sm-trend__line" d="<?php echo esc_attr( $line ); ?>" pathLength="1"/>
		<circle class="sm-trend__dot" cx="<?php echo esc_attr( $last[0] ); ?>" cy="<?php echo esc_attr( $last[1] ); ?>" r="4"/>
	</svg>
	<span class="sm-trend__value"><strong><?php echo sm_per_unit( $trend['last'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped by sm_per_unit(). ?></strong>
		<?php /* translators: %d: number of weeks. */ ?>
		<span><?php echo esc_html( sprintf( _n( 'average, last %d week', 'averages, last %d weeks', $weeks, 'sm-marketplace' ), $weeks ) ); ?></span>
	</span>
	<?php if ( ! empty( $trend['reuse'] ) ) : ?>
		<span class="sm-trend__reuse"><?php esc_html_e( 'Reused sections', 'sm-marketplace' ); ?> · <?php echo sm_per_unit( $trend['reuse'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped by sm_per_unit(). ?> <span><?php esc_html_e( '(last 30 days)', 'sm-marketplace' ); ?></span></span>
	<?php endif; ?>
	<span class="sm-trend__note"><?php esc_html_e( 'Prices asked by sellers on the platform, not an official price.', 'sm-marketplace' ); ?></span>
</a>

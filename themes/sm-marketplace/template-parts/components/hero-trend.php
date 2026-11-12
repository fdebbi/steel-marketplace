<?php
/**
 * Steel price curve (0.23.0), under the picture of the home page and at the top
 * of the price index page.
 *
 * Data: resource "price_trend" from sm-products (PriceTrend::summary()):
 *   mode auto   = average asking price per kg of new hot-rolled sections, week by week;
 *   mode manual = reference prices entered by the administrator, with their date;
 *   mode none   = no price yet.
 * Nothing is invented: with one price only, the past is drawn as a thin dashed
 * guide (a decoration, not data) ending on the price of the week; with no price
 * the card says that the price is coming.
 *
 * Animation: the line draws itself from left to right when the chart enters
 * the screen, the points appear one after the other, then the price card. The
 * chart box has a fixed ratio and the card is placed inside it, so nothing
 * moves on the page. Everything is still with "reduce motion" or without JS.
 *
 * @var array $args { variant: hero|page }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$variant = ( $args['variant'] ?? 'hero' ) === 'page' ? 'page' : 'hero';
$trend   = sm_theme_data( 'price_trend' );
if ( ! is_array( $trend ) || ! isset( $trend['mode'] ) ) {
	// sm-products missing or older than 0.9.0: show nothing rather than a broken chart.
	if ( ! is_array( $trend ) || empty( $trend['points'] ) ) {
		return;
	}
	$trend = array_merge( $trend, array( 'mode' => 'auto', 'last_date' => end( $trend['points'] )['start'] ?? '' ) );
}

$mode   = in_array( $trend['mode'], array( 'auto', 'manual' ), true ) ? $trend['mode'] : 'none';
$points = array_values( (array) $trend['points'] );
$values = array();
foreach ( $points as $p ) {
	$values[] = (float) $p['value'];
}
$n = count( $values );

// Geometry (SVG units). The top of the box is kept free for the price card.
$w      = 400;
$h      = 190;
$left   = 14;
$right  = $w - 16;
$top    = 104;
$bottom = 164;
$mid    = ( $top + $bottom ) / 2;
$xy     = array();
if ( $n >= 2 ) {
	$min  = min( $values );
	$max  = max( $values );
	$span = $max - $min;
	foreach ( $values as $i => $v ) {
		$x    = $left + ( $right - $left ) * $i / ( $n - 1 );
		$y    = $span > 0 ? $bottom - ( $bottom - $top ) * ( $v - $min ) / $span : $mid;
		$xy[] = array( $x, $y );
	}
} elseif ( 1 === $n ) {
	$xy[] = array( $right, $mid );
}

/**
 * Smooth path through the points that never overshoots them (monotone cubic,
 * Fritsch–Carlson): no invented peak or dip between two weeks.
 */
$path = '';
if ( $n >= 2 ) {
	$d = array();
	for ( $k = 0; $k < $n - 1; $k++ ) {
		$d[ $k ] = ( $xy[ $k + 1 ][1] - $xy[ $k ][1] ) / ( $xy[ $k + 1 ][0] - $xy[ $k ][0] );
	}
	$m        = array();
	$m[0]     = $d[0];
	$m[ $n - 1 ] = $d[ $n - 2 ];
	for ( $k = 1; $k < $n - 1; $k++ ) {
		$m[ $k ] = ( $d[ $k - 1 ] * $d[ $k ] <= 0 ) ? 0 : ( $d[ $k - 1 ] + $d[ $k ] ) / 2;
	}
	for ( $k = 0; $k < $n - 1; $k++ ) {
		if ( 0.0 === (float) $d[ $k ] ) {
			$m[ $k ]     = 0;
			$m[ $k + 1 ] = 0;
			continue;
		}
		$a = $m[ $k ] / $d[ $k ];
		$b = $m[ $k + 1 ] / $d[ $k ];
		$s = $a * $a + $b * $b;
		if ( $s > 9 ) {
			$t           = 3 / sqrt( $s );
			$m[ $k ]     = $t * $a * $d[ $k ];
			$m[ $k + 1 ] = $t * $b * $d[ $k ];
		}
	}
	$f    = static fn( $v ) => rtrim( rtrim( number_format( $v, 1, '.', '' ), '0' ), '.' );
	$path = 'M' . $f( $xy[0][0] ) . ' ' . $f( $xy[0][1] );
	for ( $k = 0; $k < $n - 1; $k++ ) {
		$hx    = ( $xy[ $k + 1 ][0] - $xy[ $k ][0] ) / 3;
		$path .= ' C' . $f( $xy[ $k ][0] + $hx ) . ' ' . $f( $xy[ $k ][1] + $m[ $k ] * $hx ) . ' ' . $f( $xy[ $k + 1 ][0] - $hx ) . ' ' . $f( $xy[ $k + 1 ][1] - $m[ $k + 1 ] * $hx ) . ' ' . $f( $xy[ $k + 1 ][0] ) . ' ' . $f( $xy[ $k + 1 ][1] );
	}
	$area = $path . ' L' . $f( $right ) . ' ' . $h . ' L' . $f( $left ) . ' ' . $h . ' Z';
}
$end   = $xy ? end( $xy ) : array( $right, $mid );
$guide = 'M' . $left . ' ' . round( $end[1], 1 ) . ' L' . round( $end[0], 1 ) . ' ' . round( $end[1], 1 );

// Texts.
$last     = null !== $trend['last'] ? (float) $trend['last'] : null;
$decimals = ( null !== $last && abs( $last - round( $last ) ) > 0.001 ) ? 2 : 0;
$change   = $n > 1 ? (float) ( $trend['change'] ?? 0 ) : 0.0;
$title    = 'manual' === $mode ? __( 'ReuSteel reference price', 'sm-marketplace' ) : ( 'auto' === $mode ? __( 'Average asking price', 'sm-marketplace' ) : __( 'Price of steel', 'sm-marketplace' ) );
$tag      = 'manual' === $mode ? __( 'Reference price', 'sm-marketplace' ) : ( 'auto' === $mode ? __( 'Current average', 'sm-marketplace' ) : __( 'Price of the week', 'sm-marketplace' ) );
if ( 'auto' === $mode ) {
	/* translators: %s: date of the Monday of the week. */
	$when = sprintf( __( 'Week of %s', 'sm-marketplace' ), sm_date( $trend['last_date'] ) );
} elseif ( 'manual' === $mode ) {
	/* translators: %s: date of the reference price. */
	$when = sprintf( __( 'Checked on %s', 'sm-marketplace' ), sm_date( $trend['last_date'] ) );
} else {
	$when = '';
}
if ( 'auto' === $mode ) {
	$note = __( 'Average of the prices asked by sellers on the platform, not an official price.', 'sm-marketplace' );
} elseif ( 'manual' === $mode ) {
	$note = '' !== (string) $trend['source']
		/* translators: %s: where the administrator checked the price. */
		? sprintf( __( 'Price checked by the ReuSteel team (%s), not an official price.', 'sm-marketplace' ), $trend['source'] )
		: __( 'Price checked by the ReuSteel team, not an official price.', 'sm-marketplace' );
} else {
	$note = __( 'The price of the week will be shown here.', 'sm-marketplace' );
}
if ( null === $last ) {
	$label = __( 'Price of new steel sections: coming soon.', 'sm-marketplace' );
} else {
	/* translators: 1: kind of price, 2: price per kg, 3: date. */
	$label = sprintf( __( '%1$s of new steel sections: %2$s DA per kg, %3$s.', 'sm-marketplace' ), $title, sm_number( $last, $decimals ), $when );
}
$is_link = 'hero' === $variant;
$tagname = $is_link ? 'a' : 'figure';
$uid     = 'smt' . wp_unique_id();
?>
<<?php echo esc_html( $tagname ); ?> class="sm-trend sm-trend--<?php echo esc_attr( $variant ); ?> sm-trend--<?php echo esc_attr( $mode ); ?>"<?php echo $is_link ? ' href="' . esc_url( sm_page_url( 'price-index' ) ) . '"' : ''; ?> aria-label="<?php echo esc_attr( $label ); ?>">
	<span class="sm-trend__head">
		<span class="sm-trend__title"><?php echo esc_html( $title ); ?><small><?php esc_html_e( 'New sections · IPE, HEA, UPN…', 'sm-marketplace' ); ?></small></span>
		<?php if ( abs( $change ) >= 0.1 ) : ?>
			<span class="sm-trend__change sm-trend__change--<?php echo $change > 0 ? 'up' : 'down'; ?>" dir="ltr"><?php echo esc_html( ( $change > 0 ? '+' : '−' ) . sm_number( abs( $change ), 1 ) . ' %' ); ?></span>
		<?php endif; ?>
	</span>
	<span class="sm-trend__plot" dir="ltr" aria-hidden="true">
		<svg class="sm-trend__chart" viewBox="0 0 <?php echo (int) $w; ?> <?php echo (int) $h; ?>" preserveAspectRatio="none" focusable="false">
			<defs>
				<linearGradient id="<?php echo esc_attr( $uid ); ?>-fill" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#06CE77" stop-opacity=".26"/><stop offset="1" stop-color="#06CE77" stop-opacity="0"/></linearGradient>
				<linearGradient id="<?php echo esc_attr( $uid ); ?>-stroke" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#5FE3A6" stop-opacity=".55"/><stop offset=".6" stop-color="#06CE77"/><stop offset="1" stop-color="#9BF5CC"/></linearGradient>
			</defs>
			<?php foreach ( array( $top, $mid, $bottom ) as $gy ) : ?>
				<line class="sm-trend__grid" x1="0" x2="<?php echo (int) $w; ?>" y1="<?php echo esc_attr( $gy ); ?>" y2="<?php echo esc_attr( $gy ); ?>"/>
			<?php endforeach; ?>
			<?php if ( $n >= 2 ) : ?>
				<path class="sm-trend__area" d="<?php echo esc_attr( $area ); ?>" fill="url(#<?php echo esc_attr( $uid ); ?>-fill)"/>
				<path class="sm-trend__glow" d="<?php echo esc_attr( $path ); ?>" pathLength="1"/>
				<path class="sm-trend__line" d="<?php echo esc_attr( $path ); ?>" pathLength="1" stroke="url(#<?php echo esc_attr( $uid ); ?>-stroke)"/>
			<?php else : ?>
				<path class="sm-trend__guide" d="<?php echo esc_attr( $guide ); ?>"/>
			<?php endif; ?>
		</svg>
		<?php foreach ( $xy as $i => $c ) : ?>
			<i class="sm-trend__pt<?php echo $i === $n - 1 ? ' sm-trend__pt--last' : ''; ?>" style="--x:<?php echo esc_attr( round( $c[0] / $w * 100, 2 ) ); ?>%;--y:<?php echo esc_attr( round( $c[1] / $h * 100, 2 ) ); ?>%;--i:<?php echo (int) $i; ?>;--n:<?php echo (int) max( 1, $n ); ?>"></i>
		<?php endforeach; ?>
		<?php if ( ! $xy ) : ?>
			<i class="sm-trend__pt sm-trend__pt--ghost" style="--x:<?php echo esc_attr( round( $end[0] / $w * 100, 2 ) ); ?>%;--y:<?php echo esc_attr( round( $end[1] / $h * 100, 2 ) ); ?>%;--i:0;--n:1"></i>
		<?php endif; ?>
		<span class="sm-trend__tag" dir="<?php echo esc_attr( sm_theme_dir() ); ?>" style="--x:<?php echo esc_attr( round( $end[0] / $w * 100, 2 ) ); ?>%;--y:<?php echo esc_attr( round( $end[1] / $h * 100, 2 ) ); ?>%">
			<span class="sm-trend__taglabel"><?php echo esc_html( $tag ); ?></span>
			<?php if ( null !== $last ) : ?>
				<strong class="sm-trend__price"><?php echo sm_per_unit( $last, 'kg', true, $decimals ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped by sm_per_unit(). ?></strong>
				<span class="sm-trend__when"><?php echo esc_html( $when ); ?></span>
			<?php else : ?>
				<strong class="sm-trend__price sm-trend__price--soon"><?php esc_html_e( 'Price coming soon', 'sm-marketplace' ); ?></strong>
			<?php endif; ?>
		</span>
	</span>
	<span class="sm-trend__foot">
		<?php if ( 1 === $n ) : ?>
			<span class="sm-trend__build"><?php esc_html_e( 'The curve builds up with every new price.', 'sm-marketplace' ); ?></span>
		<?php endif; ?>
		<?php if ( 'auto' === $mode && ! empty( $trend['reuse'] ) ) : ?>
			<span class="sm-trend__reuse"><?php esc_html_e( 'Reused sections', 'sm-marketplace' ); ?> · <?php echo sm_per_unit( $trend['reuse'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped by sm_per_unit(). ?> <span><?php esc_html_e( '(last 30 days)', 'sm-marketplace' ); ?></span></span>
		<?php endif; ?>
		<span class="sm-trend__note"><?php echo esc_html( $note ); ?></span>
	</span>
</<?php echo esc_html( $tagname ); ?>>
<script>(function(e){if(!e||!('IntersectionObserver' in window)||(window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches))return;e.setAttribute('data-anim','wait');var o=new IntersectionObserver(function(l){if(l[0].isIntersecting){e.setAttribute('data-anim','go');o.disconnect();}},{threshold:.35});o.observe(e);})(document.currentScript&&document.currentScript.previousElementSibling);</script>

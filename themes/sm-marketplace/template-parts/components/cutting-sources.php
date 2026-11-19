<?php
/**
 * Where the bars of a cutting plan come from (0.47.0): listings of the
 * marketplace (seller, condition, price shown, what to take) and the new bars
 * to buy (request for quotation). Data: cutting (sm-weight-engine 0.5.0).
 *
 * @var array $args { result: array, rfq: string (URL of the pre-filled request) }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$res   = $args['result'];
$conds = array( 'new' => __( 'New', 'sm-marketplace' ) ) + sm_condition_labels();
$wil   = sm_theme_wilayas();
$len   = static fn( $mm ) => sm_number( $mm / 1000, 2 );
$items = array();
$buy   = array();
foreach ( $res['profiles'] as $p ) {
	foreach ( $p['market'] as $m ) {
		$items[] = array( $p, $m );
	}
	if ( $p['new_bars'] > 0 ) {
		$pieces = array();
		foreach ( $p['bars'] as $b ) {
			if ( 'new' === $b['source'] ) {
				foreach ( $b['pieces'] as $pc ) {
					$pieces[ $pc ] = ( $pieces[ $pc ] ?? 0 ) + $b['count'];
				}
			}
		}
		krsort( $pieces );
		$buy[] = array( $p, $pieces );
	}
}
?>
<?php if ( $items ) : ?>
	<section class="smcut-src is-mk">
		<h3><span class="smcut-dot"></span><?php esc_html_e( 'ReuSteel marketplace', 'sm-marketplace' ); ?></h3>
		<?php foreach ( $items as list( $p, $m ) ) : ?>
			<div class="smcut-item">
				<div>
					<?php foreach ( $m['take_pieces'] as $pl => $pn ) : ?>
						<p class="smcut-item__big"><?php echo esc_html( $pn . ' × ' . $p['designation'] . ' · L = ' . $len( $pl ) . ' m' ); ?></p>
					<?php endforeach; ?>
					<p>
						<?php echo esc_html( sprintf( /* translators: %s: title of the listing. */ __( 'Listing « %s »', 'sm-marketplace' ), $m['title'] ) ); ?>
						<?php if ( $m['company'] ) : ?> · <b><?php echo esc_html( $m['company'] ); ?></b><?php if ( $m['verified'] ) : ?> <span class="smcut-ver"><?php esc_html_e( '(verified)', 'sm-marketplace' ); ?></span><?php endif; ?><?php endif; ?>
						<?php if ( isset( $wil[ $m['wilaya'] ] ) ) : ?> · <?php echo esc_html( sm_t( $wil[ $m['wilaya'] ] ) ); ?><?php endif; ?>
					</p>
					<p class="smcut-meta">
						<span><?php echo esc_html( $conds[ $m['condition'] ] ?? $m['condition'] ); ?><?php echo $m['grade'] ? esc_html( ' · ' . $m['grade'] ) : ''; ?></span>
						<?php if ( $m['price_per_kg'] > 0 && ! $m['lot'] ) : ?><span><?php echo sm_per_unit( $m['price_per_kg'], 'kg', false, 0 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php endif; ?>
						<span><?php echo esc_html( implode( ' + ', array_map( static fn( $l, $n ) => $n . ' × ' . $len( $l ) . ' m', array_keys( $m['take_bars'] ), $m['take_bars'] ) ) . ' · ' . sm_number( $m['take_kg'], 1 ) . ' kg' ); ?></span>
						<?php if ( $m['cost'] > 0 ) : ?><span><?php echo esc_html( ( $m['lot'] ? __( 'whole lot', 'sm-marketplace' ) . ' ' : '≈ ' ) . sm_money( $m['cost'] ) ); ?></span><?php endif; ?>
					</p>
					<?php if ( $m['lot'] ) : ?>
						<p class="smcut-lotnote"><?php echo esc_html( $m['lot_rest'] > 0 ? sprintf( /* translators: %d: bars of the lot not needed. */ _n( 'Sold as a whole lot only: you also get %d bar you do not need.', 'Sold as a whole lot only: you also get %d bars you do not need.', $m['lot_rest'], 'sm-marketplace' ), $m['lot_rest'] ) : __( 'Sold as a whole lot only.', 'sm-marketplace' ) ); ?></p>
					<?php endif; ?>
				</div>
				<div class="smcut-item__acts">
					<a class="sm-btn sm-btn--accent" href="<?php echo esc_url( $m['url'] ); ?>"><?php esc_html_e( 'Buy / Make an offer', 'sm-marketplace' ); ?></a>
					<a class="sm-btn sm-btn--ghost" href="<?php echo esc_url( $m['url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'See the listing', 'sm-marketplace' ); ?></a>
				</div>
			</div>
		<?php endforeach; ?>
	</section>
<?php endif; ?>
<?php if ( $buy ) : ?>
	<section class="smcut-src is-by">
		<h3><span class="smcut-dot"></span><?php esc_html_e( 'To buy (new bars)', 'sm-marketplace' ); ?></h3>
		<?php foreach ( $buy as $i => list( $p, $pieces ) ) : ?>
			<div class="smcut-item">
				<div>
					<p class="smcut-item__big"><?php echo esc_html( $p['new_bars'] . ' × ' . $p['designation'] . ' · L = ' . sm_number( $res['settings']['bar'] / 1000, 0 ) . ' m' ); ?></p>
					<p><?php echo esc_html( sprintf( /* translators: %s: pieces, e.g. "12 × 3,95 m". */ __( 'To cut %s', 'sm-marketplace' ), implode( ', ', array_map( static fn( $l, $n ) => $n . ' × ' . $len( $l ) . ' m', array_keys( $pieces ), $pieces ) ) ) ); ?></p>
					<p class="smcut-meta"><span><?php echo esc_html( sm_number( $p['bought_kg'], 1 ) . ' kg' ); ?></span></p>
				</div>
				<?php if ( 0 === $i ) : ?>
					<div class="smcut-item__acts"><a class="sm-btn sm-btn--accent" href="<?php echo esc_url( $args['rfq'] ); ?>"><?php esc_html_e( 'Make a request for quotation', 'sm-marketplace' ); ?></a></div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</section>
<?php endif; ?>

<?php
/**
 * Where the bars of a cutting plan come from (0.47.0): listings of the
 * marketplace (seller, condition, price shown, what to take) and the new bars
 * to buy (request for quotation). Data: cutting (sm-weight-engine 0.5.0).
 *
 * 0.49.0: « My warehouse » first (references, bars taken, « Take these bars out
 * of the warehouse »), and the reusable offcuts to add to the reuse warehouse.
 *
 * @var array $args { result: array, rfq: string (URL of the pre-filled request), warehouse: bool }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$res   = $args['result'];
$conds = array( 'new' => __( 'New', 'sm-marketplace' ) ) + sm_condition_labels();
$wil   = sm_theme_wilayas();
$len   = static fn( $mm ) => sm_number( $mm / 1000, 2 );
$items = array();
$buy   = array();
$mine  = array();
$offc  = array();
foreach ( $res['profiles'] as $p ) {
	foreach ( $p['mine'] ?? array() as $m ) {
		$mine[] = array( $p, $m );
	}
	foreach ( $p['offcuts'] ?? array() as $o ) {
		$offc[] = $o;
	}
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
$take = array_sum( array_map( static fn( $x ) => $x[1]['take_count'], $mine ) );
?>
<?php if ( $mine ) : ?>
	<section class="smcut-src is-st">
		<h3><span class="smcut-dot"></span><?php esc_html_e( 'My warehouse', 'sm-marketplace' ); ?></h3>
		<?php foreach ( $mine as list( $p, $m ) ) : ?>
			<div class="smcut-item">
				<div>
					<p class="smcut-item__big"><?php echo esc_html( implode( ' + ', array_map( static fn( $l, $n ) => $n . ' × ' . $p['designation'] . ' · L = ' . sm_number( $l / 1000, 2 ) . ' m', array_keys( $m['take_bars'] ), $m['take_bars'] ) ) ); ?><?php if ( $m['ref'] ) : ?> <span class="smcut-ref" dir="ltr"><?php echo esc_html( $m['ref'] ); ?></span><?php endif; ?></p>
					<p>
						<?php echo esc_html( ( 'reuse' === $m['market'] ? __( 'Reuse', 'sm-marketplace' ) : __( 'New steel', 'sm-marketplace' ) ) . ( 'reuse' === $m['market'] && isset( $conds[ $m['condition'] ] ) ? ' · ' . $conds[ $m['condition'] ] : '' ) . ( $m['grade'] ? ' · ' . $m['grade'] : '' ) . ( $m['location'] ? ' · ' . $m['location'] : '' ) ); ?>
						· <?php echo esc_html( $m['published'] ? __( 'published on the market', 'sm-marketplace' ) : __( 'in stock only', 'sm-marketplace' ) ); ?>
					</p>
					<p class="smcut-meta">
						<span><?php echo esc_html( sprintf( /* translators: 1: bars taken, 2: bars of the item. */ __( '%1$d bars of %2$d', 'sm-marketplace' ), $m['take_count'], $m['lines'][0]['qty'] ) ); ?></span>
						<span><?php echo esc_html( sm_number( $m['take_kg'], 1 ) . ' kg' ); ?></span>
						<span><?php echo esc_html( sprintf( /* translators: %s: pieces, e.g. "2 × 5,40 m". */ __( 'pieces: %s', 'sm-marketplace' ), implode( ', ', array_map( static fn( $l, $n ) => $n . ' × ' . $len( $l ) . ' m', array_keys( $m['take_pieces'] ), $m['take_pieces'] ) ) ) ); ?></span>
					</p>
				</div>
				<div class="smcut-item__acts"><a class="sm-btn sm-btn--ghost" href="<?php echo esc_url( $m['url'] ); ?>"><?php esc_html_e( 'See in the warehouse', 'sm-marketplace' ); ?></a></div>
			</div>
		<?php endforeach; ?>
		<?php sm_theme_form_open( 'stock_cut_out', array( 'class' => 'smcut-out' ) ); ?>
			<?php foreach ( $mine as list( $p, $m ) ) : ?><input type="hidden" name="take[<?php echo esc_attr( $m['key'] ); ?>]" value="<?php echo esc_attr( $m['take_count'] ); ?>"><?php endforeach; ?>
			<p><?php esc_html_e( 'When the cutting is done, take these bars out of the warehouse: the movement « Cutting » is noted with the reference. Nothing is taken out before.', 'sm-marketplace' ); ?></p>
			<button class="sm-btn sm-btn--accent" type="submit" data-sm-confirm="<?php echo esc_attr( sprintf( /* translators: %d: bars. */ _n( 'Take %d bar out of your warehouse? The cutting is done.', 'Take %d bars out of your warehouse? The cutting is done.', $take, 'sm-marketplace' ), $take ) ); ?>"><?php echo esc_html( sprintf( /* translators: %d: bars. */ _n( 'Take %d bar out of the warehouse', 'Take %d bars out of the warehouse', $take, 'sm-marketplace' ), $take ) ); ?></button>
		</form>
	</section>
<?php endif; ?>
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
<?php if ( $offc && ! empty( $args['warehouse'] ) ) : ?>
	<section class="smcut-src is-off">
		<h3><?php echo esc_html( sprintf( /* translators: %s: length in m. */ __( 'Reusable offcuts (≥ %s m)', 'sm-marketplace' ), sm_number( $res['settings']['reuse'] / 1000, 2 ) ) ); ?></h3>
		<?php sm_theme_form_open( 'stock_add_offcuts', array( 'class' => 'sm-form smcut-offform' ) ); ?>
			<?php foreach ( $offc as $i => $o ) : ?>
				<label class="smcut-off">
					<input type="checkbox" name="off[<?php echo esc_attr( $i ); ?>][on]" value="1" checked>
					<span><b><?php echo esc_html( $o['designation'] . ' · ' . $len( $o['length'] ) . ' m' ); ?></b> · <?php echo esc_html( 'mine' === $o['source'] && $o['ref'] ? sprintf( /* translators: %s: reference. */ __( 'from %s', 'sm-marketplace' ), $o['ref'] ) : ( 'market' === $o['source'] ? __( 'bars of the marketplace', 'sm-marketplace' ) : ( 'stock' === $o['source'] ? __( 'offcuts at hand', 'sm-marketplace' ) : __( 'new bars bought', 'sm-marketplace' ) ) ) ); ?></span>
					<span>× <?php echo esc_html( $o['count'] ); ?></span>
					<span><?php echo esc_html( sm_number( $o['kg'], 1 ) . ' kg' ); ?></span>
					<?php foreach ( array( 'section' => $o['section_id'], 'length' => $o['length'], 'count' => $o['count'], 'condition' => in_array( $o['source'], array( 'mine', 'market' ), true ) ? $o['condition'] : '', 'grade' => $o['grade'] ) as $fk => $fv ) : ?>
						<input type="hidden" name="off[<?php echo esc_attr( $i ); ?>][<?php echo esc_attr( $fk ); ?>]" value="<?php echo esc_attr( $fv ); ?>">
					<?php endforeach; ?>
				</label>
			<?php endforeach; ?>
			<div class="smcut-offform__go">
				<label><span><?php esc_html_e( 'Location', 'sm-marketplace' ); ?></span><input name="location" maxlength="120" placeholder="<?php esc_attr_e( 'e.g. Hangar 2 – offcuts', 'sm-marketplace' ); ?>"></label>
				<button class="sm-btn sm-btn--accent" type="submit"><?php esc_html_e( 'Add to my reuse warehouse', 'sm-marketplace' ); ?></button>
			</div>
			<p class="sm-muted"><?php esc_html_e( 'Kept in stock only, with automatic references. Condition and grade of the bar they come from (« Good » for bought bars); you can change them in the warehouse.', 'sm-marketplace' ); ?></p>
		</form>
	</section>
<?php endif; ?>

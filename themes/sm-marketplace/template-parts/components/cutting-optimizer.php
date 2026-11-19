<?php
/**
 * Cutting optimiser: form (bars, pieces, offcuts in stock) and plan (0.46.0).
 * Data: cutting_sections, cutting_input, cutting (sm-weight-engine 0.4.0).
 *
 * @var array $args { sections: array, input: array, result: array|null, raw: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$secs  = (array) $args['sections'];
$in    = (array) $args['input'];
$res   = $args['result'];
$lang  = sm_theme_current_language();
$fam_of = array();
foreach ( $secs as $code => $f ) {
	foreach ( $f['sections'] as $s ) {
		$fam_of[ $s[0] ] = $code;
	}
}
$m      = static fn( $mm ) => $mm ? number_format( $mm / 1000, 2, ',', '' ) : '';
$bar    = (int) ( $in['bar'] ?? 12000 );
$lens   = array( 6000, 12000 );
$custom = ! in_array( $bar, $lens, true );
$page   = sm_page_url( 'cutting' );
$qs     = static function () use ( $in, $bar ) {
	$q = array( 'bar' => $bar / 1000, 'kerf' => $in['kerf'], 'reuse' => $in['reuse'] / 1000 );
	foreach ( array( 'lines' => 'rows', 'stock' => 'stock' ) as $k => $name ) {
		foreach ( array_values( (array) ( $in[ $k ] ?? array() ) ) as $i => $l ) {
			$q[ $name ][ $i ] = array( 'section' => $l['section'], 'length' => $l['length'] / 1000, 'qty' => $l['qty'] );
		}
	}
	return $q;
};
$errs = array();
if ( is_array( $res ) ) {
	foreach ( $res['errors'] as $e ) {
		$errs[ $e['kind'] ][ $e['row'] ] = $e['message'];
	}
}

/* One row of the form: family, size, length, number. */
$row = static function ( $name, $i, $l, $count_label ) use ( $secs, $fam_of, $m, $lang, $errs ) {
	$sid  = (int) ( $l['section'] ?? 0 );
	$fam  = $sid && isset( $fam_of[ $sid ] ) ? $fam_of[ $sid ] : (string) ( $l['family'] ?? '' );
	$kind = 'rows' === $name ? 'rows' : 'stock';
	$err  = $errs[ $kind ][ $i ] ?? '';
	?>
	<tr class="smcut-row<?php echo $err ? ' has-error' : ''; ?>" data-smcut-row>
		<td>
			<select name="<?php echo esc_attr( $name . '[' . $i . '][family]' ); ?>" data-smcut-family aria-label="<?php esc_attr_e( 'Profile', 'sm-marketplace' ); ?>">
				<option value=""><?php esc_html_e( 'Profile…', 'sm-marketplace' ); ?></option>
				<?php foreach ( $secs as $code => $f ) : ?><option value="<?php echo esc_attr( $code ); ?>" <?php selected( $fam, $code ); ?>><?php echo esc_html( $code ); ?></option><?php endforeach; ?>
			</select>
		</td>
		<td>
			<select name="<?php echo esc_attr( $name . '[' . $i . '][section]' ); ?>" data-smcut-section aria-label="<?php esc_attr_e( 'Size', 'sm-marketplace' ); ?>">
				<option value=""><?php esc_html_e( 'Size…', 'sm-marketplace' ); ?></option>
				<?php if ( $fam && isset( $secs[ $fam ] ) ) : ?>
					<?php foreach ( $secs[ $fam ]['sections'] as $s ) : ?><option value="<?php echo esc_attr( $s[0] ); ?>" <?php selected( $sid, $s[0] ); ?>><?php echo esc_html( $s[1] ); ?></option><?php endforeach; ?>
				<?php endif; ?>
			</select>
		</td>
		<td><span class="smcut-unit"><input name="<?php echo esc_attr( $name . '[' . $i . '][length]' ); ?>" inputmode="decimal" value="<?php echo esc_attr( $m( $l['length'] ?? 0 ) ); ?>" placeholder="5,10" aria-label="<?php esc_attr_e( 'Length', 'sm-marketplace' ); ?>"><i>m</i></span></td>
		<td><input name="<?php echo esc_attr( $name . '[' . $i . '][qty]' ); ?>" inputmode="numeric" value="<?php echo esc_attr( ! empty( $l['qty'] ) ? (int) $l['qty'] : '' ); ?>" aria-label="<?php echo esc_attr( $count_label ); ?>"></td>
	</tr>
	<?php if ( $err ) : ?><tr class="smcut-row-error"><td colspan="4"><?php echo esc_html( $err ); ?></td></tr><?php endif; ?>
	<?php
};
$lines = array_values( (array) ( $in['lines'] ?? array() ) );
$stock = array_values( (array) ( $in['stock'] ?? array() ) );
wp_enqueue_script( 'sm-cutting', SM_THEME_URI . '/assets/js/cutting.js', array(), sm_theme_asset_version( 'assets/js/cutting.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
$js = array(
	'sections' => array_map( static fn( $f ) => array_map( static fn( $s ) => array( $s[0], $s[1] ), $f['sections'] ), $secs ),
	'i18n'     => array(
		'size'      => __( 'Size…', 'sm-marketplace' ),
		'pasteHelp' => __( 'One line per piece: profile, length in m, number. For example: IPE 200 ; 5,10 ; 4', 'sm-marketplace' ),
		'pasteOk'   => __( 'Add these pieces', 'sm-marketplace' ),
		/* translators: %d: lines not understood. */
		'pasteBad'  => __( '%d line(s) not understood were left out.', 'sm-marketplace' ),
		'copied'    => __( 'Link copied.', 'sm-marketplace' ),
	),
);
?>
<div class="smcut" data-smcut="<?php echo esc_attr( wp_json_encode( $js ) ); ?>">
	<form class="sm-form smcut-form" method="get" action="<?php echo esc_url( $page ); ?>">
		<?php if ( 'fr' !== $lang ) : ?><input type="hidden" name="lang" value="<?php echo esc_attr( $lang ); ?>"><?php endif; ?>
		<section class="sm-panel">
			<h2 class="sm-panel__title"><?php esc_html_e( '1. Commercial bars', 'sm-marketplace' ); ?></h2>
			<fieldset class="smcut-bars">
				<legend><?php esc_html_e( 'Length of the bars', 'sm-marketplace' ); ?></legend>
				<div class="smcut-seg">
					<?php foreach ( $lens as $len ) : ?>
						<label><input type="radio" name="bar" value="<?php echo esc_attr( $len / 1000 ); ?>" <?php checked( $bar, $len ); ?>><span><?php echo esc_html( ( $len / 1000 ) . ' m' ); ?></span></label>
					<?php endforeach; ?>
					<label><input type="radio" name="bar" value="" <?php checked( $custom ); ?> data-smcut-custom><span><?php esc_html_e( 'Other…', 'sm-marketplace' ); ?></span></label>
				</div>
				<span class="smcut-unit smcut-custom"><input name="bar_custom" inputmode="decimal" value="<?php echo esc_attr( $custom ? $m( $bar ) : '' ); ?>" placeholder="10,00" aria-label="<?php esc_attr_e( 'Other length of bar', 'sm-marketplace' ); ?>"><i>m</i></span>
			</fieldset>
			<div class="smcut-2">
				<div class="sm-field"><label for="smcut-kerf"><?php esc_html_e( 'Saw kerf', 'sm-marketplace' ); ?></label><span class="smcut-unit"><input id="smcut-kerf" name="kerf" inputmode="numeric" value="<?php echo esc_attr( (int) $in['kerf'] ); ?>"><i>mm</i></span></div>
				<div class="sm-field"><label for="smcut-reuse"><?php esc_html_e( 'Offcut kept from', 'sm-marketplace' ); ?></label><span class="smcut-unit"><input id="smcut-reuse" name="reuse" inputmode="decimal" value="<?php echo esc_attr( $m( $in['reuse'] ) ); ?>"><i>m</i></span></div>
			</div>

			<h2 class="sm-panel__title smcut-h"><?php esc_html_e( '2. Pieces to cut', 'sm-marketplace' ); ?></h2>
			<table class="smcut-table" data-smcut-table="rows">
				<thead><tr><th><?php esc_html_e( 'Profile', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Size', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Length', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Qty', 'sm-marketplace' ); ?></th></tr></thead>
				<tbody>
					<?php
					$n = max( count( $lines ) + 1, 4 );
					for ( $i = 0; $i < $n; $i++ ) {
						$row( 'rows', $i, $lines[ $i ] ?? array(), __( 'Number of pieces', 'sm-marketplace' ) );
					}
					?>
				</tbody>
			</table>
			<p class="smcut-add"><button type="button" class="sm-link" data-smcut-add="rows"><?php sm_icon( 'plus' ); ?><?php esc_html_e( 'Add a line', 'sm-marketplace' ); ?></button> <button type="button" class="sm-link" data-smcut-paste><?php sm_icon( 'file' ); ?><?php esc_html_e( 'Paste a list (Excel)', 'sm-marketplace' ); ?></button></p>
			<div class="smcut-pastebox" data-smcut-pastebox hidden>
				<label for="smcut-paste"><?php esc_html_e( 'Paste your list', 'sm-marketplace' ); ?></label>
				<textarea id="smcut-paste" rows="5" dir="ltr" placeholder="IPE 200	5,10	4"></textarea>
				<small data-smcut-pastehelp></small>
				<button type="button" class="sm-btn sm-btn--ghost sm-btn--sm" data-smcut-pasteok></button>
			</div>

			<details class="smcut-stock"<?php echo $stock ? ' open' : ''; ?>>
				<summary><?php esc_html_e( '3. Offcuts already in stock (optional)', 'sm-marketplace' ); ?></summary>
				<p class="sm-muted"><?php esc_html_e( 'They are used first: you buy new bars only for what they cannot give.', 'sm-marketplace' ); ?></p>
				<table class="smcut-table" data-smcut-table="stock">
					<thead><tr><th><?php esc_html_e( 'Profile', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Size', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Length', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Qty', 'sm-marketplace' ); ?></th></tr></thead>
					<tbody>
						<?php
						$n = max( count( $stock ) + 1, 2 );
						for ( $i = 0; $i < $n; $i++ ) {
							$row( 'stock', $i, $stock[ $i ] ?? array(), __( 'Number of offcuts', 'sm-marketplace' ) );
						}
						?>
					</tbody>
				</table>
				<p class="smcut-add"><button type="button" class="sm-link" data-smcut-add="stock"><?php sm_icon( 'plus' ); ?><?php esc_html_e( 'Add a line', 'sm-marketplace' ); ?></button></p>
			</details>

			<button class="sm-btn sm-btn--accent sm-btn--block smcut-go" type="submit"><?php esc_html_e( 'Optimise the cutting', 'sm-marketplace' ); ?></button>
		</section>
	</form>

	<div class="smcut-result" id="smcut-result">
		<?php if ( ! is_array( $res ) ) : ?>
			<div class="smcut-empty">
				<?php sm_icon( 'ruler' ); ?>
				<p><?php esc_html_e( 'Give the pieces to cut and press « Optimise the cutting »: the plan appears here, bar by bar.', 'sm-marketplace' ); ?></p>
			</div>
		<?php else : ?>
			<?php if ( $res['errors'] ) : ?>
				<div class="sm-notice sm-notice--warn"><?php sm_icon( 'alert' ); ?><p><?php esc_html_e( 'Some lines are incomplete or wrong: they are marked in the form and left out of the plan.', 'sm-marketplace' ); ?></p></div>
			<?php endif; ?>
			<?php
			if ( $res['profiles'] ) :
				$t     = $res['totals'];
				$by    = implode( ' · ', array_map( static fn( $p ) => $p['new_bars'] . ' ' . $p['designation'], array_filter( $res['profiles'], static fn( $p ) => $p['new_bars'] > 0 ) ) );
				$stock_used = array_sum( array_column( $res['profiles'], 'stock_used' ) );
				?>
				<div class="smcut-totals">
					<div class="smcut-k"><small><?php esc_html_e( 'Bars to buy', 'sm-marketplace' ); ?></small><b><?php echo esc_html( sm_number( $t['new_bars'] ) ); ?></b><small><?php echo esc_html( $by ); ?><?php echo $stock_used ? esc_html( ' · ' . sprintf( /* translators: %d: offcuts used from the stock. */ _n( '+ %d offcut from the stock', '+ %d offcuts from the stock', $stock_used, 'sm-marketplace' ), $stock_used ) ) : ''; ?></small></div>
					<div class="smcut-k"><small><?php esc_html_e( 'Weight bought', 'sm-marketplace' ); ?></small><b><?php echo sm_kg( $t['bought_kg'], 1 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></b><small><?php echo esc_html( sprintf( /* translators: %s: weight of the pieces. */ __( 'pieces: %s kg', 'sm-marketplace' ), sm_number( $t['pieces_kg'], 1 ) ) ); ?></small></div>
					<div class="smcut-k is-hl"><small><?php esc_html_e( 'Offcuts', 'sm-marketplace' ); ?></small><b><?php echo sm_kg( $t['scrap_kg'], 1 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></b><small><?php echo esc_html( sprintf( /* translators: %s: percent. */ __( '%s %% of the bars used', 'sm-marketplace' ), sm_number( $t['scrap_pct'], 1 ) ) ); ?></small></div>
					<div class="smcut-k"><small><?php echo esc_html( sprintf( /* translators: %s: length in m. */ __( 'Reusable offcuts (≥ %s m)', 'sm-marketplace' ), sm_number( $res['settings']['reuse'] / 1000, 2 ) ) ); ?></small><b><?php echo esc_html( sm_number( $t['reusable'] ) ); ?></b><small><?php echo esc_html( implode( ' · ', array_map( static fn( $x ) => $x['count'] . ' × ' . sm_number( $x['length'] / 1000, 2 ) . ' m ' . $x['designation'], $t['reusable_items'] ) ) ); ?></small></div>
				</div>

				<?php foreach ( $res['profiles'] as $p ) : ?>
					<?php
					$all = array();
					foreach ( $p['bars'] as $b ) {
						$all = array_merge( $all, $b['pieces'] );
					}
					$all = array_values( array_unique( $all ) );
					rsort( $all );
					$title = sprintf( /* translators: 1: profile, 2: number of bars, 3: bar length. */ _n( '%1$s — %2$d bar of %3$s m', '%1$s — %2$d bars of %3$s m', max( 1, $p['new_bars'] ), 'sm-marketplace' ), $p['designation'], $p['new_bars'], sm_number( $res['settings']['bar'] / 1000, 0 ) );
					?>
					<section class="smcut-prof">
						<div class="smcut-prof__head">
							<h3><?php echo esc_html( $title ); ?><?php if ( $p['stock_used'] ) : ?> <small><?php echo esc_html( sprintf( /* translators: %d: offcuts from the stock. */ _n( '+ %d offcut from the stock', '+ %d offcuts from the stock', $p['stock_used'], 'sm-marketplace' ), $p['stock_used'] ) ); ?></small><?php endif; ?></h3>
							<span><?php echo esc_html( sprintf( /* translators: 1: kg/m, 2: pieces, 3: saw cuts. */ __( '%1$s kg/m · %2$d pieces · %3$d saw cuts', 'sm-marketplace' ), sm_number( $p['mass_kg_m'], 2 ), $p['pieces'], $p['cuts'] ) ); ?></span>
						</div>
						<?php foreach ( $p['bars'] as $b ) : ?>
							<div class="smcut-bar">
								<div class="smcut-bar__label">
									<span><b>× <?php echo esc_html( $b['count'] ); ?></b> <?php echo esc_html( 'stock' === $b['source'] ? sprintf( /* translators: %s: length. */ __( 'offcut from the stock, %s m', 'sm-marketplace' ), sm_number( $b['length'] / 1000, 2 ) ) : __( 'new bar', 'sm-marketplace' ) ); ?></span>
									<span>
										<?php if ( $b['loss'] > 0 ) : ?>
											<?php echo esc_html( sprintf( /* translators: %s: length. */ __( 'offcut %s m', 'sm-marketplace' ), sm_number( $b['loss'] / 1000, 2 ) ) ); ?><?php if ( $b['reusable'] ) : ?> · <b class="smcut-re"><?php echo esc_html( sprintf( /* translators: %s: length. */ __( '%s m reusable', 'sm-marketplace' ), sm_number( $b['offcut'] / 1000, 2 ) ) ); ?></b><?php endif; ?>
										<?php else : ?>
											<?php esc_html_e( 'no offcut', 'sm-marketplace' ); ?>
										<?php endif; ?>
									</span>
								</div>
								<div class="smcut-strip" role="img" aria-label="<?php echo esc_attr( implode( ' + ', array_map( static fn( $x ) => sm_number( $x / 1000, 2 ) . ' m', $b['pieces'] ) ) ); ?>">
									<?php foreach ( $b['pieces'] as $pc ) : ?>
										<span class="smcut-pc smcut-c<?php echo esc_attr( array_search( $pc, $all, true ) % 4 ); ?>" style="inline-size:<?php echo esc_attr( round( 100 * $pc / $b['length'], 3 ) ); ?>%"><?php echo esc_html( sm_number( $pc / 1000, 2 ) ); ?></span>
									<?php endforeach; ?>
									<?php if ( $b['loss'] > 0 ) : ?>
										<span class="smcut-sc<?php echo $b['reusable'] ? ' is-re' : ''; ?>" style="inline-size:<?php echo esc_attr( round( 100 * $b['loss'] / $b['length'], 3 ) ); ?>%"><?php echo $b['loss'] >= 0.06 * $b['length'] ? esc_html( sm_number( $b['loss'] / 1000, 2 ) ) : ''; ?></span>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
						<p class="smcut-sum">
							<?php if ( $p['new_bars'] ) : ?><span><?php echo esc_html( sprintf( /* translators: 1: metres, 2: kg. */ __( 'Bought: %1$s m · %2$s kg', 'sm-marketplace' ), sm_number( $p['bought_m'], 0 ), sm_number( $p['bought_kg'], 1 ) ) ); ?></span><?php endif; ?>
							<span><?php echo esc_html( sprintf( /* translators: 1: metres, 2: kg. */ __( 'Pieces: %1$s m · %2$s kg', 'sm-marketplace' ), sm_number( $p['pieces_m'], 2 ), sm_number( $p['pieces_kg'], 1 ) ) ); ?></span>
							<span class="is-w"><?php echo esc_html( sprintf( /* translators: 1: metres, 2: kg, 3: percent. */ __( 'Offcuts: %1$s m · %2$s kg · %3$s %%', 'sm-marketplace' ), sm_number( $p['scrap_m'], 2 ), sm_number( $p['scrap_kg'], 1 ), sm_number( $p['scrap_pct'], 1 ) ) ); ?></span>
							<?php if ( $p['stock_left'] ) : ?><span><?php echo esc_html( sprintf( /* translators: %d: offcuts not used. */ _n( '%d offcut of the stock not needed', '%d offcuts of the stock not needed', $p['stock_left'], 'sm-marketplace' ), $p['stock_left'] ) ); ?></span><?php endif; ?>
						</p>
						<?php if ( ! $p['optimal'] ) : ?>
							<p class="smcut-note"><?php esc_html_e( 'Long list: this is the best plan found in the time given; a better one may exist.', 'sm-marketplace' ); ?></p>
						<?php endif; ?>
					</section>
				<?php endforeach; ?>

				<p class="smcut-legend"><span><i class="smcut-c0"></i><?php esc_html_e( 'piece', 'sm-marketplace' ); ?></span><span><i class="smcut-sc"></i><?php esc_html_e( 'offcut (saw cuts included)', 'sm-marketplace' ); ?></span><span><i class="smcut-sc is-re"></i><?php esc_html_e( 'reusable offcut', 'sm-marketplace' ); ?></span></p>

				<div class="smcut-actions">
					<?php $q = $qs(); ?>
					<?php if ( $t['new_bars'] > 0 ) : ?>
						<a class="sm-btn sm-btn--accent" href="<?php echo esc_url( add_query_arg( array_merge( array( 'action' => 'sm_weight_cut_rfq', 'lang' => $lang ), $q ), admin_url( 'admin-post.php' ) ) ); ?>"><?php echo esc_html( sprintf( /* translators: %d: number of bars. */ _n( 'Create a request for quotation (%d bar)', 'Create a request for quotation (%d bars)', $t['new_bars'], 'sm-marketplace' ), $t['new_bars'] ) ); ?></a>
					<?php endif; ?>
					<a class="sm-btn sm-btn--ghost" href="<?php echo esc_url( add_query_arg( array_merge( array( 'action' => 'sm_weight_cut_pdf', 'lang' => $lang ), $q ), admin_url( 'admin-post.php' ) ) ); ?>"><?php sm_icon( 'file' ); ?><span><?php esc_html_e( 'Download the PDF', 'sm-marketplace' ); ?></span></a>
					<button type="button" class="sm-btn sm-btn--ghost" data-smcut-print><?php esc_html_e( 'Print', 'sm-marketplace' ); ?></button>
					<button type="button" class="sm-btn sm-btn--ghost" data-smcut-copy="<?php echo esc_url( add_query_arg( $q, $page ) ); ?>"><?php esc_html_e( 'Copy the link', 'sm-marketplace' ); ?></button>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>

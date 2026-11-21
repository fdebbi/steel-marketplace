<?php
/**
 * Cutting optimiser: form (bars, pieces, offcuts in stock) and plan (0.46.0).
 * Data: cutting_sections, cutting_input, cutting (sm-weight-engine 0.4.0).
 * 0.50.0: DQE IA (sm-ai 0.3.0, data ai_cutlist): import a document, check, add; plates and
 *         quantities in metres go to the request for quotation (sm-weight-engine 0.7.0).
 * 0.49.0: « My warehouse » first (sm-weight-engine 0.6.0, sm-stock 0.2.0): data stock_enabled.
 *
 * @var array $args { sections: array, input: array, result: array|null, raw: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$secs  = (array) $args['sections'];
$in    = (array) $args['input'];
$res   = $args['result'];
$wh    = ! empty( $args['warehouse'] );
$ai    = is_array( $args['ai'] ?? null ) ? $args['ai'] : null; // DQE IA: state, credits, result to check.
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
	$q = array( 'bar' => $bar / 1000, 'kerf' => $in['kerf'], 'reuse' => $in['reuse'] / 1000, 'src' => 1, 'market' => empty( $in['market'] ) ? '' : 1, 'lots' => empty( $in['lots'] ) ? '' : 1, 'conds' => (array) ( $in['conds'] ?? array() ), 'grade_min' => (int) ( $in['grade_min'] ?? 0 ), 'wilaya' => (string) ( $in['wilaya'] ?? '' ), 'mine' => empty( $in['mine'] ) ? '' : 1, 'mine_new' => empty( $in['mine_new'] ) ? '' : 1, 'mine_reuse' => empty( $in['mine_reuse'] ) ? '' : 1, 'mine_unpublished' => empty( $in['mine_unpublished'] ) ? '' : 1 );
	foreach ( array( 'lines' => 'rows', 'stock' => 'stock' ) as $k => $name ) {
		foreach ( array_values( (array) ( $in[ $k ] ?? array() ) ) as $i => $l ) {
			$q[ $name ][ $i ] = array( 'section' => $l['section'], 'length' => $l['length'] / 1000, 'qty' => $l['qty'] );
		}
	}
	foreach ( array_values( (array) ( $in['plates'] ?? array() ) ) as $i => $pl ) {
		$q['plates'][ $i ] = array( 't' => $pl['t'], 'l' => $pl['l'], 'w' => $pl['w'], 'qty' => $pl['qty'], 'label' => $pl['label'] );
	}
	foreach ( array_values( (array) ( $in['lin'] ?? array() ) ) as $i => $li ) {
		$q['lin'][ $i ] = array( 'section' => $li['section'], 'm' => $li['m'], 'label' => $li['label'] );
	}
	return $q;
};
$errs = array();
if ( is_array( $res ) ) {
	foreach ( $res['errors'] as $e ) {
		$errs[ $e['kind'] ][ $e['row'] ] = $e['message'];
	}
}

/* Hidden fields for a nested query (keeps the settings and the list). */
$hidden = static function ( array $q, $prefix = '' ) use ( &$hidden ) {
	foreach ( $q as $k => $v ) {
		$name = '' === $prefix ? (string) $k : $prefix . '[' . $k . ']';
		if ( is_array( $v ) ) {
			$hidden( $v, $name );
		} elseif ( '' !== (string) $v ) {
			echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $v ) . '">';
		}
	}
};

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
			<?php if ( $ai ) : ?>
				<div class="smcut-import" id="smcut-import">
					<p class="smcut-import__title"><?php sm_ai_badge(); ?> <b><?php esc_html_e( 'Import a document', 'sm-marketplace' ); ?></b></p>
					<p class="smcut-import__sub"><?php esc_html_e( 'Bill of quantities, quotation, purchase order, take-off, tender', 'sm-marketplace' ); ?><br><?php echo esc_html( __( 'Excel, PDF or photo', 'sm-marketplace' ) . ' · ' . sm_ai_price( $ai['credits'] ) ); ?></p>
					<?php if ( 'ok' === $ai['state'] ) : ?>
						<div class="smcut-import__go">
							<label class="sm-btn sm-btn--ai sm-btn--sm"><input type="file" name="document" form="smcut-ai-form" accept=".xlsx,.csv,.pdf,image/*" data-smcut-aifile hidden><?php esc_html_e( 'Choose the file', 'sm-marketplace' ); ?></label>
							<label class="sm-btn sm-btn--ai-outline sm-btn--sm"><input type="file" name="photo" form="smcut-ai-form" accept="image/*" capture="environment" data-smcut-aifile hidden><?php sm_icon( 'camera' ); ?><span><?php esc_html_e( 'Take a photo', 'sm-marketplace' ); ?></span></label>
							<button class="sm-btn sm-btn--ghost sm-btn--sm smcut-import__send" type="submit" form="smcut-ai-form" data-smcut-aisend><?php esc_html_e( 'Read the document', 'sm-marketplace' ); ?></button>
						</div>
						<small><?php esc_html_e( 'Photo: flat, with good light. Plans will come later.', 'sm-marketplace' ); ?><?php if ( null !== $ai['remaining'] ) : ?> · <?php echo esc_html( sprintf( /* translators: %d: credits left. */ _n( '%d credit left', '%d credits left', (int) $ai['remaining'], 'sm-marketplace' ), (int) $ai['remaining'] ) ); ?><?php endif; ?></small>
					<?php elseif ( 'login' === $ai['state'] ) : ?>
						<p class="smcut-import__why"><a href="<?php echo esc_url( sm_page_url( 'login' ) ); ?>"><?php esc_html_e( 'Log in to use this tool', 'sm-marketplace' ); ?></a></p>
					<?php elseif ( 'upgrade' === $ai['state'] ) : ?>
						<p class="smcut-import__why"><?php echo esc_html( $ai['reason'] ); ?> <a href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'plan' ) ) ); ?>"><?php esc_html_e( 'See the plans and the AI credit packs', 'sm-marketplace' ); ?></a></p>
					<?php else : ?>
						<p class="smcut-import__why"><?php echo esc_html( $ai['reason'] ); ?></p>
					<?php endif; ?>
				</div>
				<p class="smcut-import__or"><?php esc_html_e( 'or type the pieces:', 'sm-marketplace' ); ?></p>
			<?php endif; ?>
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

			<input type="hidden" name="src" value="1">
			<?php $hidden( array_intersect_key( $qs(), array( 'plates' => 1, 'lin' => 1 ) ) ); // Plates and metres of the DQE stay with the list. ?>
			<fieldset class="smcut-where">
				<legend class="sm-panel__title"><?php esc_html_e( '3. Where to look for the bars?', 'sm-marketplace' ); ?></legend>
				<?php if ( $wh ) : ?>
					<label class="smcut-chk"><input type="checkbox" name="mine" value="1" <?php checked( ! empty( $in['mine'] ) ); ?> data-smcut-mine><span><b><?php esc_html_e( 'My warehouse', 'sm-marketplace' ); ?></b><small><?php esc_html_e( 'Searched first: your bars, published or kept in stock only.', 'sm-marketplace' ); ?></small></span></label>
					<div class="smcut-filters is-st" data-smcut-minebox>
						<label class="smcut-chk"><input type="checkbox" name="mine_new" value="1" <?php checked( ! empty( $in['mine_new'] ) ); ?>><span><?php esc_html_e( 'New steel', 'sm-marketplace' ); ?></span></label>
						<label class="smcut-chk"><input type="checkbox" name="mine_reuse" value="1" <?php checked( ! empty( $in['mine_reuse'] ) ); ?>><span><?php esc_html_e( 'Reuse (offcuts and variants)', 'sm-marketplace' ); ?></span></label>
						<label class="smcut-chk"><input type="checkbox" name="mine_unpublished" value="1" <?php checked( ! empty( $in['mine_unpublished'] ) ); ?>><span><?php esc_html_e( 'Do not take the items published on the market', 'sm-marketplace' ); ?><small><?php esc_html_e( 'Only what is kept in stock only', 'sm-marketplace' ); ?></small></span></label>
					</div>
				<?php else : ?>
					<label class="smcut-chk is-off"><input type="checkbox" disabled><span><?php esc_html_e( 'My warehouse', 'sm-marketplace' ); ?><small>
						<?php if ( is_user_logged_in() ) : ?>
							<?php esc_html_e( 'With a company or professional account.', 'sm-marketplace' ); ?>
						<?php else : ?>
							<a href="<?php echo esc_url( sm_page_url( 'login' ) ); ?>"><?php esc_html_e( 'Log in with a company or professional account', 'sm-marketplace' ); ?></a>
						<?php endif; ?>
					</small></span></label>
				<?php endif; ?>
				<label class="smcut-chk"><input type="checkbox" name="market" value="1" <?php checked( ! empty( $in['market'] ) ); ?> data-smcut-market><span><b><?php esc_html_e( 'ReuSteel marketplace', 'sm-marketplace' ); ?></b><small><?php echo esc_html( $wh ? __( 'Listings of the other sellers: the same profile, the same length or the closest longer one.', 'sm-marketplace' ) : __( 'Published listings: the same profile, the same length or the closest longer one.', 'sm-marketplace' ) ); ?></small></span></label>
				<div class="smcut-filters" data-smcut-filters>
					<div class="sm-field">
						<label for="smcut-grade"><?php esc_html_e( 'Minimum grade', 'sm-marketplace' ); ?></label>
						<select id="smcut-grade" name="grade_min">
							<option value="0"><?php esc_html_e( 'Any grade', 'sm-marketplace' ); ?></option>
							<?php foreach ( array( 235 => 'S235JR', 275 => 'S275JR', 355 => 'S355JR', 460 => 'S460' ) as $gv => $gl ) : ?><option value="<?php echo esc_attr( $gv ); ?>" <?php selected( (int) $in['grade_min'], $gv ); ?>><?php echo esc_html( $gl ); ?></option><?php endforeach; ?>
						</select>
					</div>
					<fieldset class="smcut-conds">
						<legend><?php esc_html_e( 'Conditions accepted', 'sm-marketplace' ); ?></legend>
						<?php
						$cl = array( 'new' => __( 'New', 'sm-marketplace' ) ) + array_intersect_key( sm_condition_labels(), array_flip( array( 'new_surplus', 'excellent', 'good', 'inspection' ) ) );
						foreach ( $cl as $ck => $clabel ) :
							?>
							<label><input type="checkbox" name="conds[]" value="<?php echo esc_attr( $ck ); ?>" <?php checked( in_array( $ck, (array) $in['conds'], true ) ); ?>><span><?php echo esc_html( $clabel ); ?></span></label>
						<?php endforeach; ?>
					</fieldset>
					<div class="sm-field">
						<label for="smcut-wilaya"><?php esc_html_e( 'Wilaya', 'sm-marketplace' ); ?></label>
						<select id="smcut-wilaya" name="wilaya">
							<option value=""><?php esc_html_e( 'All wilayas', 'sm-marketplace' ); ?></option>
							<?php foreach ( sm_theme_wilayas() as $wc => $wn ) : ?><option value="<?php echo esc_attr( $wc ); ?>" <?php selected( (string) $in['wilaya'], (string) $wc ); ?>><?php echo esc_html( sm_t( $wn ) ); ?></option><?php endforeach; ?>
						</select>
					</div>
					<label class="smcut-chk"><input type="checkbox" name="lots" value="1" <?php checked( ! empty( $in['lots'] ) ); ?>><span><?php esc_html_e( 'Also offer lots sold whole when they help', 'sm-marketplace' ); ?></span></label>
				</div>
				<p class="smcut-chk is-fixed"><?php sm_icon( 'check' ); ?><span><?php esc_html_e( 'New bars for the rest', 'sm-marketplace' ); ?><small><?php esc_html_e( 'Request for quotation pre-filled', 'sm-marketplace' ); ?></small></span></p>
			</fieldset>

			<?php if ( $wh && ! $stock ) : // 0.49.1: with a warehouse, the offcuts are in it (section 4 only for visitors, clients, or a shared link that has some). ?>
				<p class="smcut-stocknote"><?php sm_icon( 'info' ); ?><span><?php esc_html_e( 'Your offcuts are in your warehouse: they are searched first.', 'sm-marketplace' ); ?> <a href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'stock-reuse' ) ) ); ?>"><?php esc_html_e( 'Add offcuts to the warehouse', 'sm-marketplace' ); ?></a></span></p>
			<?php else : ?>
			<details class="smcut-stock"<?php echo $stock ? ' open' : ''; ?>>
				<summary><?php esc_html_e( '4. Offcuts already at hand (optional)', 'sm-marketplace' ); ?></summary>
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
			<?php endif; ?>

			<button class="sm-btn sm-btn--accent sm-btn--block smcut-go" type="submit"><?php esc_html_e( 'Optimise the cutting', 'sm-marketplace' ); ?></button>
		</section>
	</form>
	<?php if ( $ai && 'ok' === $ai['state'] ) : ?>
		<?php sm_theme_form_open( 'ai_cutlist', array( 'id' => 'smcut-ai-form', 'class' => 'smcut-aiform', 'enctype' => 'multipart/form-data' ) + sm_ai_wait_attrs( __( 'The AI is reading your document…', 'sm-marketplace' ), '.smcut-form > .sm-panel' ) ); ?>
			<input type="hidden" name="for" value="cutting">
			<input type="hidden" name="back" value="<?php echo esc_url( add_query_arg( array_merge( $qs(), 'fr' !== $lang ? array( 'lang' => $lang ) : array() ), $page ) ); ?>">
		</form>
	<?php endif; ?>

	<div class="smcut-result" id="smcut-result">
		<?php if ( $ai && ! empty( $ai['result'] ) ) : ?>
			<?php sm_component( 'ai-doc-review', array( 'ai' => $ai, 'sections' => $secs ) ); ?>
		<?php endif; ?>
		<?php if ( ! is_array( $res ) ) : ?>
			<?php if ( ! ( $ai && ! empty( $ai['result'] ) ) ) : ?>
			<div class="smcut-empty">
				<?php sm_icon( 'ruler' ); ?>
				<p><?php esc_html_e( 'Give the pieces to cut and press « Optimise the cutting »: the plan appears here, bar by bar.', 'sm-marketplace' ); ?></p>
			</div>
			<?php endif; ?>
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
				<?php if ( $t['market_bars'] > 0 || ! empty( $t['mine_bars'] ) ) : ?>
				<div class="smcut-totals">
					<?php if ( ! empty( $t['mine_bars'] ) ) : ?>
						<div class="smcut-k is-st"><small><?php esc_html_e( 'In my warehouse', 'sm-marketplace' ); ?></small><b><?php echo esc_html( sprintf( /* translators: %d: bars. */ _n( '%d bar', '%d bars', $t['mine_bars'], 'sm-marketplace' ), $t['mine_bars'] ) ); ?></b><small><?php echo esc_html( sprintf( /* translators: %d: references. */ _n( '%d reference', '%d references', $t['mine_items'], 'sm-marketplace' ), $t['mine_items'] ) . ' · ' . sm_number( $t['mine_kg'], 1 ) . ' kg' ); ?></small></div>
					<?php endif; ?>
					<?php if ( $t['market_bars'] > 0 ) : ?>
					<div class="smcut-k is-mk"><small><?php esc_html_e( 'On the marketplace', 'sm-marketplace' ); ?></small><b><?php echo esc_html( sprintf( /* translators: %d: bars. */ _n( '%d bar', '%d bars', $t['market_bars'], 'sm-marketplace' ), $t['market_bars'] ) ); ?></b><small><?php echo esc_html( sprintf( /* translators: %d: listings. */ _n( '%d listing', '%d listings', $t['market_offers'], 'sm-marketplace' ), $t['market_offers'] ) . ( $t['market_cost'] > 0 ? ' · ≈ ' . sm_money( $t['market_cost'] ) : '' ) ); ?></small></div>
					<?php endif; ?>
					<?php if ( $t['new_bars'] > 0 ) : ?>
					<div class="smcut-k is-hl"><small><?php esc_html_e( 'To buy', 'sm-marketplace' ); ?></small><b><?php echo esc_html( sprintf( /* translators: %d: bars. */ _n( '%d bar', '%d bars', $t['new_bars'], 'sm-marketplace' ), $t['new_bars'] ) ); ?></b><small><?php echo esc_html( $by ); ?></small></div>
					<?php else : ?>
					<div class="smcut-k"><small><?php esc_html_e( 'To buy', 'sm-marketplace' ); ?></small><b><?php esc_html_e( 'Nothing', 'sm-marketplace' ); ?></b><small><?php echo esc_html( $t['market_bars'] > 0 ? __( 'No new bar needed', 'sm-marketplace' ) : __( 'Everything comes from your warehouse', 'sm-marketplace' ) ); ?></small></div>
					<?php endif; ?>
					<?php if ( ! ( $t['market_bars'] > 0 && ! empty( $t['mine_bars'] ) ) && $t['acquire_kg'] > 0 ) : ?>
					<div class="smcut-k"><small><?php esc_html_e( 'Weight to acquire', 'sm-marketplace' ); ?></small><b><?php echo sm_kg( $t['acquire_kg'], 1 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></b><small><?php echo esc_html( sprintf( /* translators: %s: weight of the pieces. */ __( 'pieces: %s kg', 'sm-marketplace' ), sm_number( $t['pieces_kg'], 1 ) ) ); ?></small></div>
					<?php endif; ?>
					<div class="smcut-k"><small><?php esc_html_e( 'Offcuts', 'sm-marketplace' ); ?></small><b><?php echo sm_kg( $t['scrap_kg'], 1 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></b><small><?php echo esc_html( sprintf( /* translators: %s: percent. */ __( '%s %% of the bars used', 'sm-marketplace' ), sm_number( $t['scrap_pct'], 1 ) ) . ( $t['reusable'] ? ' · ' . sprintf( /* translators: %d: reusable offcuts. */ _n( '%d reusable', '%d reusable', $t['reusable'], 'sm-marketplace' ), $t['reusable'] ) : '' ) ); ?></small></div>
				</div>
				<?php if ( $t['market_bars'] > 0 ) : ?>
				<p class="smcut-snap"><?php sm_icon( 'clock' ); ?><span><?php echo esc_html( sprintf( /* translators: 1: date, 2: hour. */ __( 'Picture of the marketplace on %1$s at %2$s: listings can change and nothing is reserved. Prices are those shown by the sellers.', 'sm-marketplace' ), wp_date( 'd/m/Y', $res['at'] ), wp_date( 'H:i', $res['at'] ) ) ); ?></span></p>
				<?php endif; ?>
				<?php sm_component( 'cutting-sources', array( 'result' => $res, 'warehouse' => $wh, 'rfq' => add_query_arg( array_merge( array( 'action' => 'sm_weight_cut_rfq', 'lang' => $lang ), $qs() ), admin_url( 'admin-post.php' ) ) ) ); ?>
				<?php else : ?>
				<?php if ( ! empty( $res['settings']['market'] ) ) : ?>
					<p class="smcut-snap"><?php sm_icon( 'info' ); ?><span><?php esc_html_e( 'No listing of the marketplace fits these pieces with your filters: the plan uses new bars.', 'sm-marketplace' ); ?></span></p>
				<?php endif; ?>
				<div class="smcut-totals">
					<div class="smcut-k"><small><?php esc_html_e( 'Bars to buy', 'sm-marketplace' ); ?></small><b><?php echo esc_html( sm_number( $t['new_bars'] ) ); ?></b><small><?php echo esc_html( $by ); ?><?php echo $stock_used ? esc_html( ' · ' . sprintf( /* translators: %d: offcuts used from the stock. */ _n( '+ %d offcut from the stock', '+ %d offcuts from the stock', $stock_used, 'sm-marketplace' ), $stock_used ) ) : ''; ?></small></div>
					<div class="smcut-k"><small><?php esc_html_e( 'Weight bought', 'sm-marketplace' ); ?></small><b><?php echo sm_kg( $t['bought_kg'], 1 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></b><small><?php echo esc_html( sprintf( /* translators: %s: weight of the pieces. */ __( 'pieces: %s kg', 'sm-marketplace' ), sm_number( $t['pieces_kg'], 1 ) ) ); ?></small></div>
					<div class="smcut-k is-hl"><small><?php esc_html_e( 'Offcuts', 'sm-marketplace' ); ?></small><b><?php echo sm_kg( $t['scrap_kg'], 1 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></b><small><?php echo esc_html( sprintf( /* translators: %s: percent. */ __( '%s %% of the bars used', 'sm-marketplace' ), sm_number( $t['scrap_pct'], 1 ) ) ); ?></small></div>
					<div class="smcut-k"><small><?php echo esc_html( sprintf( /* translators: %s: length in m. */ __( 'Reusable offcuts (≥ %s m)', 'sm-marketplace' ), sm_number( $res['settings']['reuse'] / 1000, 2 ) ) ); ?></small><b><?php echo esc_html( sm_number( $t['reusable'] ) ); ?></b><small><?php echo esc_html( implode( ' · ', array_map( static fn( $x ) => $x['count'] . ' × ' . sm_number( $x['length'] / 1000, 2 ) . ' m ' . $x['designation'], $t['reusable_items'] ) ) ); ?></small></div>
				</div>
				<?php endif; ?>

				<?php
				$offer_of = array();
				foreach ( $res['profiles'] as $pp ) {
					foreach ( array_merge( $pp['market'], $pp['mine'] ?? array() ) as $mo ) {
						$offer_of[ $mo['key'] ] = $mo;
					}
				}
				?>
				<?php foreach ( $res['profiles'] as $p ) : ?>
					<?php
					$all = array();
					foreach ( $p['bars'] as $b ) {
						$all = array_merge( $all, $b['pieces'] );
					}
					$all = array_values( array_unique( $all ) );
					rsort( $all );
					$parts = array();
					if ( ! empty( $p['mine_bars'] ) ) {
						/* translators: %d: bars. */
						$parts[] = sprintf( _n( '%d bar from my warehouse', '%d bars from my warehouse', $p['mine_bars'], 'sm-marketplace' ), $p['mine_bars'] );
					}
					if ( $p['market_bars'] > 0 ) {
						/* translators: %d: bars. */
						$parts[] = sprintf( _n( '%d bar from the marketplace', '%d bars from the marketplace', $p['market_bars'], 'sm-marketplace' ), $p['market_bars'] );
					}
					if ( $parts && $p['new_bars'] ) {
						/* translators: %d: bars. */
						$parts[] = sprintf( _n( '%d to buy', '%d to buy', $p['new_bars'], 'sm-marketplace' ), $p['new_bars'] );
					}
					$title = $parts
						? $p['designation'] . ' — ' . implode( ' + ', $parts )
						: sprintf( /* translators: 1: profile, 2: number of bars, 3: bar length. */ _n( '%1$s — %2$d bar of %3$s m', '%1$s — %2$d bars of %3$s m', max( 1, $p['new_bars'] ), 'sm-marketplace' ), $p['designation'], $p['new_bars'], sm_number( $res['settings']['bar'] / 1000, 0 ) );
					?>
					<section class="smcut-prof">
						<div class="smcut-prof__head">
							<h3><?php echo esc_html( $title ); ?><?php if ( $p['stock_used'] ) : ?> <small><?php echo esc_html( sprintf( /* translators: %d: offcuts from the stock. */ _n( '+ %d offcut from the stock', '+ %d offcuts from the stock', $p['stock_used'], 'sm-marketplace' ), $p['stock_used'] ) ); ?></small><?php endif; ?></h3>
							<span><?php echo esc_html( sprintf( /* translators: 1: kg/m, 2: pieces, 3: saw cuts. */ __( '%1$s kg/m · %2$d pieces · %3$d saw cuts', 'sm-marketplace' ), sm_number( $p['mass_kg_m'], 2 ), $p['pieces'], $p['cuts'] ) ); ?></span>
						</div>
						<?php foreach ( $p['bars'] as $b ) : ?>
							<div class="smcut-bar">
								<div class="smcut-bar__label">
									<span><b>× <?php echo esc_html( $b['count'] ); ?></b>
										<?php if ( 'market' === $b['source'] ) : ?>
											<?php $mo = $offer_of[ $b['origin'] ] ?? array(); ?>
											<?php echo esc_html( sprintf( /* translators: %s: length. */ __( 'bar of %s m', 'sm-marketplace' ), sm_number( $b['length'] / 1000, 2 ) ) ); ?> <span class="smcut-lbl is-mk"><?php echo esc_html( __( 'Marketplace', 'sm-marketplace' ) . ( ! empty( $mo['company'] ) ? ' · ' . $mo['company'] : '' ) ); ?></span>
										<?php elseif ( 'mine' === $b['source'] ) : ?>
											<?php $mo = $offer_of[ $b['origin'] ] ?? array(); ?>
											<?php echo esc_html( sprintf( /* translators: %s: length. */ __( 'bar of %s m', 'sm-marketplace' ), sm_number( $b['length'] / 1000, 2 ) ) ); ?> <span class="smcut-lbl is-st"><?php echo esc_html( __( 'My warehouse', 'sm-marketplace' ) . ( ! empty( $mo['ref'] ) ? ' · ' . $mo['ref'] : '' ) ); ?></span>
										<?php elseif ( 'stock' === $b['source'] ) : ?>
											<?php echo esc_html( sprintf( /* translators: %s: length. */ __( 'offcut from the stock, %s m', 'sm-marketplace' ), sm_number( $b['length'] / 1000, 2 ) ) ); ?>
										<?php else : ?>
											<?php esc_html_e( 'new bar', 'sm-marketplace' ); ?><?php if ( $t['market_bars'] > 0 || ! empty( $t['mine_bars'] ) ) : ?> <span class="smcut-lbl is-by"><?php esc_html_e( 'To buy', 'sm-marketplace' ); ?></span><?php endif; ?>
										<?php endif; ?>
									</span>
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
		<?php
		$x = $res['extras'] ?? array( 'plates' => array(), 'linear' => array() );
		if ( $x['plates'] || $x['linear'] ) :
			?>
			<section class="smcut-src is-x">
				<h3><span class="smcut-dot"></span><?php esc_html_e( 'Without a cutting plan: to the request for quotation', 'sm-marketplace' ); ?></h3>
				<?php foreach ( $x['linear'] as $l ) : ?>
					<div class="smcut-xrow"><span><b><?php echo esc_html( $l['designation'] ); ?></b> · <?php echo esc_html( sprintf( /* translators: 1: metres, 2: bars, 3: bar length. */ __( '%1$s m → %2$d bars of %3$s m', 'sm-marketplace' ), sm_number( $l['m'], 2 ), $l['bars'], sm_number( $l['bar'] / 1000, 0 ) ) ); ?><?php echo $l['label'] ? ' · ' . esc_html( $l['label'] ) : ''; ?></span><span><?php echo esc_html( sm_number( $l['kg'], 1 ) . ' kg' ); ?></span></div>
				<?php endforeach; ?>
				<?php foreach ( $x['plates'] as $pl ) : ?>
					<div class="smcut-xrow"><span><b><?php echo esc_html( sprintf( /* translators: %s: thickness. */ __( 'Plate %s mm', 'sm-marketplace' ), sm_number( $pl['t'], fmod( $pl['t'], 1 ) ? 1 : 0 ) ) ); ?></b> · <?php echo esc_html( $pl['l'] . ' × ' . $pl['w'] . ' mm × ' . $pl['qty'] ); ?><?php echo $pl['label'] ? ' · ' . esc_html( $pl['label'] ) : ''; ?></span><span><?php echo esc_html( sm_number( $pl['kg'], 1 ) . ' kg' ); ?></span></div>
				<?php endforeach; ?>
				<p class="sm-muted"><?php esc_html_e( 'Weights from the catalogue of the platform. These pieces are not in the cutting plan of the bars.', 'sm-marketplace' ); ?></p>
				<?php if ( ! $res['profiles'] || 0 === (int) ( $res['totals']['new_bars'] ?? 0 ) ) : ?>
					<a class="sm-btn sm-btn--accent" href="<?php echo esc_url( add_query_arg( array_merge( array( 'action' => 'sm_weight_cut_rfq', 'lang' => $lang ), $qs() ), admin_url( 'admin-post.php' ) ) ); ?>"><?php esc_html_e( 'Create the request for quotation', 'sm-marketplace' ); ?></a>
				<?php endif; ?>
			</section>
		<?php endif; ?>
		<?php endif; ?>
	</div>
</div>

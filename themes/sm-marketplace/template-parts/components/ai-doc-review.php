<?php
/**
 * « Read a document » — check what the AI read, then choose what to do (0.51.0,
 * sm-ai 0.4.0): cutting optimiser, request for quotation, or the warehouse.
 * Nothing is saved without the user: « sure » lines are ticked, the others not.
 * A line given only by its weight (or in metres) gets 12 m bars, changeable; the
 * number of bars follows (rounded up to buy, to the nearest for the warehouse,
 * which keeps the weight of the document). Replaces cutting-ai-review (0.50.0).
 * Data: ai_cutlist (result, token, for, can_rfq, warehouse) and cutting_sections.
 *
 * @var array $args { ai: array, sections: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$ai     = (array) $args['ai'];
$r      = (array) $ai['result'];
$secs   = (array) $args['sections'];
$fam_of = array();
foreach ( $secs as $code => $f ) {
	foreach ( $f['sections'] as $s ) {
		$fam_of[ $s[0] ] = $code;
	}
}
$for    = in_array( $ai['for'] ?? '', array( 'cutting', 'rfq', 'stock' ), true ) ? $ai['for'] : 'cutting';
$m      = static fn( $v ) => $v ? sm_number( $v, 2 ) : '';
$why_l  = array(
	'section'    => __( 'profile not found in the catalogue: choose it', 'sm-marketplace' ),
	'length'     => __( 'length missing', 'sm-marketplace' ),
	'quantity'   => __( 'number missing', 'sm-marketplace' ),
	'deduced'    => __( 'deduced by the AI, not written', 'sm-marketplace' ),
	'photo'      => __( 'read on a photo', 'sm-marketplace' ),
	'dimensions' => __( 'dimensions missing', 'sm-marketplace' ),
	'weight'     => __( 'computed from the weight', 'sm-marketplace' ),
	'metres'     => __( 'computed from the metres', 'sm-marketplace' ),
);
$bars   = (array) ( $r['bars'] ?? array() );
$plates = (array) ( $r['plates'] ?? array() );
$sure   = count( array_filter( $bars, static fn( $b ) => $b['sure'] ) ) + count( array_filter( $plates, static fn( $p ) => $p['sure'] ) );
$check  = count( $bars ) + count( $plates ) - $sure;
$why    = static fn( array $w ) => implode( ' · ', array_map( static fn( $k ) => $why_l[ $k ] ?? $k, $w ) );
$i18n   = array(
	/* translators: 1: exact number of bars, 2: bars to buy, 3: bars for the warehouse. */
	'calc' => __( '%1$s bars → %2$s (warehouse: %3$s)', 'sm-marketplace' ),
);
wp_enqueue_script( 'sm-ai-review', SM_THEME_URI . '/assets/js/ai-review.js', array(), sm_theme_asset_version( 'assets/js/ai-review.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
?>
<section class="smcut-ai smdoc" id="smcut-ai" data-smdoc="<?php echo esc_attr( wp_json_encode( array( 'sections' => array_map( static fn( $f ) => array_map( static fn( $s ) => array( $s[0], $s[1] ), $f['sections'] ), $secs ), 'i18n' => $i18n + array( 'size' => __( 'Size…', 'sm-marketplace' ) ) ) ) ); ?>">
	<h3><?php sm_ai_badge(); ?> <span><?php echo esc_html( '' !== ( $r['name'] ?? '' ) ? sprintf( /* translators: %s: file name. */ __( 'Document « %s »', 'sm-marketplace' ), $r['name'] ) : __( 'Your document', 'sm-marketplace' ) ); ?></span></h3>
	<?php if ( ! empty( $r['unreadable'] ) ) : ?>
		<div class="sm-notice sm-notice--warn"><?php sm_icon( 'alert' ); ?><p><?php esc_html_e( 'The document could not be read. For a photo: take it again flat, with more light, the whole table in the picture. The AI credits of the reading have been used.', 'sm-marketplace' ); ?></p></div>
	<?php else : ?>
		<p class="smcut-ai__lead"><?php echo esc_html( ! empty( $r['photo'] ) ? __( 'Read on a photo: every line is to check. Tick what is right: nothing is saved without you.', 'sm-marketplace' ) : __( 'Check what the AI understood: nothing is saved without you.', 'sm-marketplace' ) ); ?><?php echo '' !== ( $r['summary'] ?? '' ) ? ' ' . esc_html( $r['summary'] ) : ''; ?></p>
		<p class="smcut-meta">
			<?php if ( $sure ) : ?><span class="is-ok">✓ <?php echo esc_html( sprintf( /* translators: %d: lines. */ _n( '%d sure line', '%d sure lines', $sure, 'sm-marketplace' ), $sure ) ); ?></span><?php endif; ?>
			<?php if ( $check ) : ?><span class="is-wa">⚠ <?php echo esc_html( sprintf( /* translators: %d: lines. */ _n( '%d to check', '%d to check', $check, 'sm-marketplace' ), $check ) ); ?></span><?php endif; ?>
			<?php if ( ! empty( $r['ignored'] ) ) : ?><span><?php echo esc_html( sprintf( /* translators: %d: lines. */ _n( '%d line not steel framing, left out', '%d lines not steel framing, left out', $r['ignored'], 'sm-marketplace' ), $r['ignored'] ) ); ?></span><?php endif; ?>
		</p>
		<?php sm_theme_form_open( 'ai_doc_use', array( 'class' => 'sm-form smcut-ai__form' ) ); ?>
			<input type="hidden" name="r" value="<?php echo esc_attr( $ai['token'] ); ?>">
			<?php if ( $bars ) : ?>
				<div class="smcut-ai__scroll">
				<table class="smcut-ai__table">
					<thead><tr><th></th><th><?php esc_html_e( 'Line of the document', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Profile', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Length', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Qty', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Weight', 'sm-marketplace' ); ?></th></tr></thead>
					<tbody>
					<?php foreach ( $bars as $i => $b ) : ?>
						<?php
						$sid  = (int) ( $b['section']['section_id'] ?? 0 );
						$fam  = $sid && isset( $fam_of[ $sid ] ) ? $fam_of[ $sid ] : '';
						$pre  = 'ai_bar[' . $i . ']';
						$calc = 'written' !== ( $b['origin'] ?? 'written' );
						?>
						<tr class="<?php echo $b['sure'] ? 'is-ok' : 'is-wa'; ?>" data-smdoc-row data-kgm="<?php echo esc_attr( (float) ( $b['section']['mass_kg_m'] ?? 0 ) ); ?>" data-dockg="<?php echo esc_attr( (float) ( $b['doc_kg'] ?? 0 ) ); ?>" data-metres="<?php echo esc_attr( (float) ( $b['metres'] ?? 0 ) ); ?>">
							<td><input type="checkbox" name="<?php echo esc_attr( $pre ); ?>[on]" value="1" <?php checked( $b['sure'] ); ?> aria-label="<?php esc_attr_e( 'Add this line', 'sm-marketplace' ); ?>"><input type="hidden" name="<?php echo esc_attr( $pre ); ?>[text]" value="<?php echo esc_attr( mb_substr( $b['text'], 0, 120 ) ); ?>"><input type="hidden" name="<?php echo esc_attr( $pre ); ?>[doc_kg]" value="<?php echo esc_attr( (float) ( $b['doc_kg'] ?? 0 ) ); ?>"><input type="hidden" name="<?php echo esc_attr( $pre ); ?>[qty_stock]" value="<?php echo esc_attr( (int) ( $b['qty_stock'] ?? $b['quantity'] ) ); ?>" data-smdoc-stock></td>
							<td><?php echo esc_html( $b['text'] ); ?><small><?php echo esc_html( ( '' !== $b['line'] ? sprintf( /* translators: %s: line. */ __( 'line %s', 'sm-marketplace' ), $b['line'] ) : '' ) . ( '' !== $b['note'] ? ' · ' . $b['note'] : '' ) ); ?></small><?php if ( ! $b['sure'] ) : ?><small class="smcut-ai__why"><?php echo esc_html( $why( $b['why'] ) ); ?></small><?php endif; ?></td>
							<td>
								<span class="smcut-ai__sec">
									<select name="<?php echo esc_attr( $pre ); ?>[family]" data-smdoc-family aria-label="<?php esc_attr_e( 'Profile', 'sm-marketplace' ); ?>">
										<option value=""><?php esc_html_e( 'Profile…', 'sm-marketplace' ); ?></option>
										<?php foreach ( $secs as $code => $f ) : ?><option value="<?php echo esc_attr( $code ); ?>" <?php selected( $fam, $code ); ?>><?php echo esc_html( $code ); ?></option><?php endforeach; ?>
									</select>
									<select name="<?php echo esc_attr( $pre ); ?>[section]" data-smdoc-section aria-label="<?php esc_attr_e( 'Size', 'sm-marketplace' ); ?>">
										<option value=""><?php esc_html_e( 'Size…', 'sm-marketplace' ); ?></option>
										<?php if ( $fam && isset( $secs[ $fam ] ) ) : ?>
											<?php foreach ( $secs[ $fam ]['sections'] as $s ) : ?><option value="<?php echo esc_attr( $s[0] ); ?>" <?php selected( $sid, $s[0] ); ?>><?php echo esc_html( $s[1] ); ?></option><?php endforeach; ?>
										<?php endif; ?>
									</select>
								</span>
							</td>
							<td><span class="smcut-unit"><input name="<?php echo esc_attr( $pre ); ?>[length]" inputmode="decimal" list="smdoc-lengths" value="<?php echo esc_attr( $m( $b['length_m'] ) ); ?>" aria-label="<?php esc_attr_e( 'Length', 'sm-marketplace' ); ?>" data-smdoc-length><i>m</i></span></td>
							<td><input name="<?php echo esc_attr( $pre ); ?>[qty]" inputmode="numeric" value="<?php echo esc_attr( $b['quantity'] ? $b['quantity'] : '' ); ?>" aria-label="<?php esc_attr_e( 'Number of pieces', 'sm-marketplace' ); ?>" data-smdoc-qty>
								<?php if ( $calc && null !== $b['exact'] ) : ?><small class="smdoc-calc" data-smdoc-calc><?php echo esc_html( sprintf( $i18n['calc'], sm_number( $b['exact'], 2 ), $b['quantity'], $b['qty_stock'] ) ); ?></small><?php endif; ?>
							</td>
							<td class="smdoc-kg">
								<?php if ( $b['doc_kg'] > 0 ) : ?><b><?php echo esc_html( sm_number( $b['doc_kg'], 0 ) . ' kg' ); ?></b><small><?php esc_html_e( 'from the document', 'sm-marketplace' ); ?></small>
								<?php elseif ( $b['kg'] > 0 ) : ?><?php echo esc_html( sm_number( $b['kg'], 1 ) . ' kg' ); ?><small><?php esc_html_e( 'computed', 'sm-marketplace' ); ?></small>
								<?php else : ?>—<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<datalist id="smdoc-lengths"><option value="6"><option value="12"></datalist>
				</div>
				<p class="sm-muted smdoc-note"><?php esc_html_e( 'Mass per metre from the catalogue of the platform. Changing the length recomputes the number.', 'sm-marketplace' ); ?></p>
			<?php endif; ?>

			<?php if ( $plates ) : ?>
				<h4><?php esc_html_e( 'Plates', 'sm-marketplace' ); ?></h4>
				<div class="smcut-ai__scroll">
				<table class="smcut-ai__table">
					<thead><tr><th></th><th><?php esc_html_e( 'Line of the document', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Thickness', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Length × width', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Qty', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Weight', 'sm-marketplace' ); ?></th></tr></thead>
					<tbody>
					<?php foreach ( $plates as $i => $p ) : ?>
						<?php $pre = 'ai_plate[' . $i . ']'; ?>
						<tr class="<?php echo $p['sure'] ? 'is-ok' : 'is-wa'; ?>">
							<td><input type="checkbox" name="<?php echo esc_attr( $pre ); ?>[on]" value="1" <?php checked( $p['sure'] ); ?> aria-label="<?php esc_attr_e( 'Add this line', 'sm-marketplace' ); ?>"><input type="hidden" name="<?php echo esc_attr( $pre ); ?>[label]" value="<?php echo esc_attr( mb_substr( $p['text'], 0, 80 ) ); ?>"></td>
							<td><?php echo esc_html( $p['text'] ); ?><small><?php echo '' !== $p['line'] ? esc_html( sprintf( /* translators: %s: line. */ __( 'line %s', 'sm-marketplace' ), $p['line'] ) ) : ''; ?></small><?php if ( ! $p['sure'] ) : ?><small class="smcut-ai__why"><?php echo esc_html( $why( $p['why'] ) ); ?></small><?php endif; ?></td>
							<td><span class="smcut-unit"><input name="<?php echo esc_attr( $pre ); ?>[t]" inputmode="decimal" value="<?php echo esc_attr( $p['thickness'] ? sm_number( $p['thickness'], fmod( $p['thickness'], 1 ) ? 1 : 0 ) : '' ); ?>" aria-label="<?php esc_attr_e( 'Thickness', 'sm-marketplace' ); ?>"><i>mm</i></span></td>
							<td class="smcut-ai__lw"><input name="<?php echo esc_attr( $pre ); ?>[l]" inputmode="numeric" value="<?php echo esc_attr( $p['length'] ?: '' ); ?>" aria-label="<?php esc_attr_e( 'Length', 'sm-marketplace' ); ?>"> × <input name="<?php echo esc_attr( $pre ); ?>[w]" inputmode="numeric" value="<?php echo esc_attr( $p['width'] ?: '' ); ?>" aria-label="<?php esc_attr_e( 'Width', 'sm-marketplace' ); ?>"> <i>mm</i></td>
							<td><input name="<?php echo esc_attr( $pre ); ?>[qty]" inputmode="numeric" value="<?php echo esc_attr( $p['quantity'] ?: '' ); ?>" aria-label="<?php esc_attr_e( 'Number of pieces', 'sm-marketplace' ); ?>"></td>
							<td><?php echo $p['kg'] > 0 ? esc_html( sm_number( $p['kg'], 1 ) . ' kg' ) : '—'; ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $r['ignored'] ) && ! empty( $r['ignored_examples'] ) ) : ?>
				<details class="smcut-ai__ign"><summary><?php echo esc_html( sprintf( /* translators: %d: lines. */ _n( '%d line not steel framing, left out', '%d lines not steel framing, left out', $r['ignored'], 'sm-marketplace' ), $r['ignored'] ) ); ?></summary><p><?php echo esc_html( implode( ' · ', $r['ignored_examples'] ) ); ?>…</p></details>
			<?php endif; ?>

			<h4 class="smdoc-what"><?php esc_html_e( 'What to do with the ticked lines?', 'sm-marketplace' ); ?></h4>
			<div class="smdoc-dest">
				<div class="smdoc-d<?php echo 'cutting' === $for ? ' is-for' : ''; ?>">
					<b><?php sm_icon( 'ruler' ); ?> <?php esc_html_e( 'Optimise the cutting', 'sm-marketplace' ); ?></b>
					<p><?php esc_html_e( 'The bars go to the list of the optimiser (number rounded up); plates to the request for quotation.', 'sm-marketplace' ); ?></p>
					<button class="sm-btn <?php echo 'cutting' === $for ? 'sm-btn--accent' : 'sm-btn--ghost'; ?>" type="submit" name="dest" value="cutting"><?php esc_html_e( 'Open the optimiser', 'sm-marketplace' ); ?></button>
				</div>
				<?php if ( ! empty( $ai['can_rfq'] ) ) : ?>
					<div class="smdoc-d<?php echo 'rfq' === $for ? ' is-for' : ''; ?>">
						<b><?php sm_icon( 'file' ); ?> <?php esc_html_e( 'Request for quotation', 'sm-marketplace' ); ?></b>
						<p><?php esc_html_e( 'Form pre-filled: bars as lines, plates and weights in the description. You publish after checking.', 'sm-marketplace' ); ?></p>
						<button class="sm-btn <?php echo 'rfq' === $for ? 'sm-btn--accent' : 'sm-btn--ghost'; ?>" type="submit" name="dest" value="rfq"><?php esc_html_e( 'Prepare the request', 'sm-marketplace' ); ?></button>
					</div>
				<?php endif; ?>
				<?php if ( ! empty( $ai['warehouse'] ) ) : ?>
					<div class="smdoc-d is-st<?php echo 'stock' === $for ? ' is-for' : ''; ?>">
						<b><?php sm_icon( 'warehouse' ); ?> <?php esc_html_e( 'Add to my warehouse', 'sm-marketplace' ); ?></b>
						<span class="smdoc-seg">
							<label><input type="radio" name="stock_market" value="new" checked><span><?php esc_html_e( 'New steel', 'sm-marketplace' ); ?></span></label>
							<label><input type="radio" name="stock_market" value="reuse"><span><?php esc_html_e( 'Reuse', 'sm-marketplace' ); ?></span></label>
						</span>
						<p><?php esc_html_e( 'Kept in stock only, automatic references, weight of the document kept, number rounded to the nearest.', 'sm-marketplace' ); ?></p>
						<label class="smdoc-loc"><span><?php esc_html_e( 'Location', 'sm-marketplace' ); ?></span><input name="location" maxlength="120" placeholder="<?php esc_attr_e( 'e.g. Hangar 1', 'sm-marketplace' ); ?>"></label>
						<button class="sm-btn <?php echo 'stock' === $for ? 'sm-btn--accent' : 'sm-btn--ghost'; ?>" type="submit" name="dest" value="stock"><?php esc_html_e( 'Add to the warehouse', 'sm-marketplace' ); ?></button>
					</div>
				<?php endif; ?>
			</div>
			<p class="smdoc-foot"><small><?php echo esc_html( sprintf( /* translators: %s: price of the reading (« 1 credit »). */ __( 'Suggestion of the AI, to check · %s used for the reading · the file is not kept. Listings: from the warehouse, « On the market » item by item.', 'sm-marketplace' ), sm_ai_price( $ai['credits'] ?? 1 ) ) ); ?></small></p>
		</form>
	<?php endif; ?>
</section>

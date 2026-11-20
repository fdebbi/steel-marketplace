<?php
/**
 * DQE IA (0.50.0): what the AI read in the document, to check before adding it
 * to the cutting optimiser. Nothing is added without the user: « sure » lines
 * are ticked, the others are not. Bars go to the list of pieces; quantities in
 * metres and plates go to the request for quotation. A GET form back to the
 * optimiser, with the current list and settings kept.
 * Data: ai_cutlist (sm-ai 0.3.0).
 *
 * @var array $args { ai: array (result), sections: array, fam_of: array, page: string, query: array, bar: int, hidden: callable }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$r      = (array) $args['ai'];
$secs   = (array) $args['sections'];
$fam_of = (array) $args['fam_of'];
$hidden = $args['hidden'];
$m      = static fn( $v ) => $v ? sm_number( $v, 2 ) : '';
$why_l  = array(
	'section'    => __( 'profile not found in the catalogue: choose it', 'sm-marketplace' ),
	'length'     => __( 'length missing', 'sm-marketplace' ),
	'quantity'   => __( 'number missing', 'sm-marketplace' ),
	'deduced'    => __( 'deduced by the AI, not written', 'sm-marketplace' ),
	'photo'      => __( 'read on a photo', 'sm-marketplace' ),
	'dimensions' => __( 'dimensions missing', 'sm-marketplace' ),
);
$bars   = (array) ( $r['bars'] ?? array() );
$linear = (array) ( $r['linear'] ?? array() );
$plates = (array) ( $r['plates'] ?? array() );
$sure   = count( array_filter( $bars, static fn( $b ) => $b['sure'] ) );
$check  = count( $bars ) - $sure + count( array_filter( $plates, static fn( $p ) => ! $p['sure'] ) );
$back   = add_query_arg( $args['query'], $args['page'] );
$why    = static function ( array $w ) use ( $why_l ) {
	return implode( ' · ', array_map( static fn( $k ) => $why_l[ $k ] ?? $k, $w ) );
};
?>
<section class="smcut-ai" id="smcut-ai">
	<h3><?php sm_ai_badge(); ?> <span><?php echo esc_html( '' !== $r['name'] ? sprintf( /* translators: %s: file name. */ __( 'Document « %s »', 'sm-marketplace' ), $r['name'] ) : __( 'Your document', 'sm-marketplace' ) ); ?></span></h3>
	<?php if ( ! empty( $r['unreadable'] ) ) : ?>
		<div class="sm-notice sm-notice--warn"><?php sm_icon( 'alert' ); ?><p><?php esc_html_e( 'The document could not be read. For a photo: take it again flat, with more light, the whole table in the picture. The AI credit has been used.', 'sm-marketplace' ); ?></p></div>
		<p><a class="sm-btn sm-btn--ghost sm-btn--sm" href="<?php echo esc_url( $back ); ?>#smcut-import"><?php esc_html_e( 'Try another document', 'sm-marketplace' ); ?></a></p>
	<?php else : ?>
		<p class="smcut-ai__lead"><?php echo esc_html( $r['photo'] ? __( 'Read on a photo: every line is to check. Tick what is right: nothing is added without you.', 'sm-marketplace' ) : __( 'Check what the AI understood: nothing is added without you.', 'sm-marketplace' ) ); ?><?php echo '' !== $r['summary'] ? ' ' . esc_html( $r['summary'] ) : ''; ?></p>
		<p class="smcut-meta">
			<?php if ( $sure ) : ?><span class="is-ok">✓ <?php echo esc_html( sprintf( /* translators: %d: lines. */ _n( '%d sure line', '%d sure lines', $sure, 'sm-marketplace' ), $sure ) ); ?></span><?php endif; ?>
			<?php if ( $check ) : ?><span class="is-wa">⚠ <?php echo esc_html( sprintf( /* translators: %d: lines. */ _n( '%d to check', '%d to check', $check, 'sm-marketplace' ), $check ) ); ?></span><?php endif; ?>
			<?php if ( $linear ) : ?><span><?php echo esc_html( sprintf( /* translators: %d: lines. */ _n( '%d in metres', '%d in metres', count( $linear ), 'sm-marketplace' ), count( $linear ) ) ); ?></span><?php endif; ?>
			<?php if ( $plates ) : ?><span><?php echo esc_html( sprintf( /* translators: %d: lines. */ _n( '%d plate line', '%d plate lines', count( $plates ), 'sm-marketplace' ), count( $plates ) ) ); ?></span><?php endif; ?>
			<?php if ( $r['ignored'] ) : ?><span><?php echo esc_html( sprintf( /* translators: %d: lines. */ _n( '%d line not steel framing, left out', '%d lines not steel framing, left out', $r['ignored'], 'sm-marketplace' ), $r['ignored'] ) ); ?></span><?php endif; ?>
		</p>
		<form class="sm-form smcut-ai__form" method="get" action="<?php echo esc_url( $args['page'] ); ?>">
			<?php $hidden( $args['query'] ); ?>
			<?php if ( $bars ) : ?>
				<div class="smcut-ai__scroll">
				<table class="smcut-ai__table">
					<thead><tr><th></th><th><?php esc_html_e( 'Line of the document', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Understood as', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Length', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Qty', 'sm-marketplace' ); ?></th></tr></thead>
					<tbody>
					<?php foreach ( $bars as $i => $b ) : ?>
						<?php
						$sid = (int) ( $b['section']['section_id'] ?? 0 );
						$fam = $sid && isset( $fam_of[ $sid ] ) ? $fam_of[ $sid ] : '';
						$pre = 'ai_bar[' . $i . ']';
						?>
						<tr class="<?php echo $b['sure'] ? 'is-ok' : 'is-wa'; ?>" data-smcut-row>
							<td><input type="checkbox" name="<?php echo esc_attr( $pre ); ?>[on]" value="1" <?php checked( $b['sure'] ); ?> aria-label="<?php esc_attr_e( 'Add this line', 'sm-marketplace' ); ?>"></td>
							<td><?php echo esc_html( $b['text'] ); ?><small><?php echo esc_html( ( '' !== $b['line'] ? sprintf( /* translators: %s: line. */ __( 'line %s', 'sm-marketplace' ), $b['line'] ) : '' ) . ( '' !== $b['note'] ? ' · ' . $b['note'] : '' ) ); ?></small></td>
							<td>
								<span class="smcut-ai__sec">
									<select name="<?php echo esc_attr( $pre ); ?>[family]" data-smcut-family aria-label="<?php esc_attr_e( 'Profile', 'sm-marketplace' ); ?>">
										<option value=""><?php esc_html_e( 'Profile…', 'sm-marketplace' ); ?></option>
										<?php foreach ( $secs as $code => $f ) : ?><option value="<?php echo esc_attr( $code ); ?>" <?php selected( $fam, $code ); ?>><?php echo esc_html( $code ); ?></option><?php endforeach; ?>
									</select>
									<select name="<?php echo esc_attr( $pre ); ?>[section]" data-smcut-section aria-label="<?php esc_attr_e( 'Size', 'sm-marketplace' ); ?>">
										<option value=""><?php esc_html_e( 'Size…', 'sm-marketplace' ); ?></option>
										<?php if ( $fam && isset( $secs[ $fam ] ) ) : ?>
											<?php foreach ( $secs[ $fam ]['sections'] as $s ) : ?><option value="<?php echo esc_attr( $s[0] ); ?>" <?php selected( $sid, $s[0] ); ?>><?php echo esc_html( $s[1] ); ?></option><?php endforeach; ?>
										<?php endif; ?>
									</select>
								</span>
								<?php if ( ! $b['sure'] ) : ?><small class="smcut-ai__why"><?php echo esc_html( $why( $b['why'] ) ); ?></small><?php endif; ?>
							</td>
							<td><span class="smcut-unit"><input name="<?php echo esc_attr( $pre ); ?>[length]" inputmode="decimal" value="<?php echo esc_attr( $m( $b['length_m'] ) ); ?>" aria-label="<?php esc_attr_e( 'Length', 'sm-marketplace' ); ?>"><i>m</i></span></td>
							<td><input name="<?php echo esc_attr( $pre ); ?>[qty]" inputmode="numeric" value="<?php echo esc_attr( $b['quantity'] ? $b['quantity'] : '' ); ?>" aria-label="<?php esc_attr_e( 'Number of pieces', 'sm-marketplace' ); ?>"></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				</div>
			<?php endif; ?>

			<?php if ( $linear ) : ?>
				<h4><?php esc_html_e( 'In metres, without piece lengths', 'sm-marketplace' ); ?> <span class="smcut-lbl is-by"><?php esc_html_e( 'to the request for quotation', 'sm-marketplace' ); ?></span></h4>
				<?php foreach ( $linear as $i => $l ) : ?>
					<?php $ok = ! empty( $l['section'] ); ?>
					<label class="smcut-off">
						<input type="checkbox" name="ai_lin[<?php echo esc_attr( $i ); ?>][on]" value="1" <?php checked( $ok && $l['sure'] ); ?> <?php disabled( ! $ok ); ?>>
						<span><b><?php echo esc_html( $ok ? $l['section']['designation'] : $l['designation'] ); ?></b> · <?php echo esc_html( $l['text'] ); ?><?php if ( ! $ok ) : ?> <small class="smcut-ai__why"><?php echo esc_html( $why_l['section'] ); ?></small><?php endif; ?></span>
						<span><?php echo esc_html( sm_number( $l['metres'], 2 ) . ' m' ); ?></span>
						<span><?php echo $ok ? esc_html( sprintf( /* translators: 1: bars, 2: bar length. */ __( '→ %1$d bars of %2$s m', 'sm-marketplace' ), (int) ceil( $l['metres'] * 1000 / max( 1, (int) $args['bar'] ) ), sm_number( $args['bar'] / 1000, 0 ) ) ) : ''; ?></span>
						<?php if ( $ok ) : ?>
							<input type="hidden" name="ai_lin[<?php echo esc_attr( $i ); ?>][section]" value="<?php echo esc_attr( $l['section']['section_id'] ); ?>">
							<input type="hidden" name="ai_lin[<?php echo esc_attr( $i ); ?>][m]" value="<?php echo esc_attr( $l['metres'] ); ?>">
							<input type="hidden" name="ai_lin[<?php echo esc_attr( $i ); ?>][label]" value="<?php echo esc_attr( mb_substr( $l['text'], 0, 80 ) ); ?>">
						<?php endif; ?>
					</label>
				<?php endforeach; ?>
			<?php endif; ?>

			<?php if ( $plates ) : ?>
				<h4><?php esc_html_e( 'Plates', 'sm-marketplace' ); ?> <span class="smcut-lbl is-by"><?php esc_html_e( 'to the request for quotation', 'sm-marketplace' ); ?></span></h4>
				<div class="smcut-ai__scroll">
				<table class="smcut-ai__table">
					<thead><tr><th></th><th><?php esc_html_e( 'Line of the document', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Thickness', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Length × width', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Qty', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Weight', 'sm-marketplace' ); ?></th></tr></thead>
					<tbody>
					<?php foreach ( $plates as $i => $p ) : ?>
						<?php $pre = 'ai_plate[' . $i . ']'; ?>
						<tr class="<?php echo $p['sure'] ? 'is-ok' : 'is-wa'; ?>">
							<td><input type="checkbox" name="<?php echo esc_attr( $pre ); ?>[on]" value="1" <?php checked( $p['sure'] ); ?> aria-label="<?php esc_attr_e( 'Add this line', 'sm-marketplace' ); ?>"><input type="hidden" name="<?php echo esc_attr( $pre ); ?>[label]" value="<?php echo esc_attr( mb_substr( $p['text'], 0, 80 ) ); ?>"></td>
							<td><?php echo esc_html( $p['text'] ); ?><small><?php echo '' !== $p['line'] ? esc_html( sprintf( /* translators: %s: line. */ __( 'line %s', 'sm-marketplace' ), $p['line'] ) ) : ''; ?><?php if ( ! $p['sure'] ) : ?> · <span class="smcut-ai__why"><?php echo esc_html( $why( $p['why'] ) ); ?></span><?php endif; ?></small></td>
							<td><span class="smcut-unit"><input name="<?php echo esc_attr( $pre ); ?>[t]" inputmode="decimal" value="<?php echo esc_attr( $p['thickness'] ? sm_number( $p['thickness'], fmod( $p['thickness'], 1 ) ? 1 : 0 ) : '' ); ?>" aria-label="<?php esc_attr_e( 'Thickness', 'sm-marketplace' ); ?>"><i>mm</i></span></td>
							<td class="smcut-ai__lw"><input name="<?php echo esc_attr( $pre ); ?>[l]" inputmode="numeric" value="<?php echo esc_attr( $p['length'] ?: '' ); ?>" aria-label="<?php esc_attr_e( 'Length', 'sm-marketplace' ); ?>"> × <input name="<?php echo esc_attr( $pre ); ?>[w]" inputmode="numeric" value="<?php echo esc_attr( $p['width'] ?: '' ); ?>" aria-label="<?php esc_attr_e( 'Width', 'sm-marketplace' ); ?>"> <i>mm</i></td>
							<td><input name="<?php echo esc_attr( $pre ); ?>[qty]" inputmode="numeric" value="<?php echo esc_attr( $p['quantity'] ?: '' ); ?>" aria-label="<?php esc_attr_e( 'Number of pieces', 'sm-marketplace' ); ?>"></td>
							<td><?php echo $p['kg'] > 0 ? esc_html( sm_number( $p['kg'], 1 ) . ' kg' ) : '—'; ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				</div>
				<p class="sm-muted"><?php esc_html_e( 'Weight from the catalogue of the platform. Plates are not in the cutting plan of the bars.', 'sm-marketplace' ); ?></p>
			<?php endif; ?>

			<?php if ( $r['ignored'] && $r['ignored_examples'] ) : ?>
				<details class="smcut-ai__ign"><summary><?php echo esc_html( sprintf( /* translators: %d: lines. */ _n( '%d line not steel framing, left out', '%d lines not steel framing, left out', $r['ignored'], 'sm-marketplace' ), $r['ignored'] ) ); ?></summary><p><?php echo esc_html( implode( ' · ', $r['ignored_examples'] ) ); ?>…</p></details>
			<?php endif; ?>

			<div class="smcut-ai__go">
				<button class="sm-btn sm-btn--accent" type="submit"><?php esc_html_e( 'Add the ticked lines', 'sm-marketplace' ); ?></button>
				<a class="sm-btn sm-btn--ghost" href="<?php echo esc_url( $back ); ?>"><?php esc_html_e( 'Cancel', 'sm-marketplace' ); ?></a>
				<small><?php esc_html_e( 'Suggestion of the AI, to check · 1 credit used for the reading · the file is not kept', 'sm-marketplace' ); ?></small>
			</div>
		</form>
	<?php endif; ?>
</section>

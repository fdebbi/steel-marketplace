<?php
/**
 * Result of the AI document analysis.
 *
 * @var array $args { result: array{tool, token, data}, can_rfq: bool }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$r     = $args['result']['data'];
$units = array( 'pieces' => __( 'pcs', 'sm-marketplace' ), 'kg' => 'kg', 't' => 't', 'm' => 'm' );
?>
<div class="sm-panel">
	<div class="smai-tool__head">
		<h2 class="sm-panel__title"><?php esc_html_e( 'Steel items found', 'sm-marketplace' ); ?></h2>
		<?php if ( ! empty( $r['_meta']['simulation'] ) ) : ?><span class="smai-badge smai-badge--sim"><?php esc_html_e( 'Simulation', 'sm-marketplace' ); ?></span><?php endif; ?>
	</div>
	<?php if ( '' !== $r['summary'] ) : ?><p dir="auto"><?php echo esc_html( $r['summary'] ); ?></p><?php endif; ?>
	<?php if ( ! $r['items'] ) : ?>
		<p><?php esc_html_e( 'No steel item was found in this document.', 'sm-marketplace' ); ?></p>
	<?php else : ?>
		<div class="sm-table-wrap">
			<table class="sm-table smai-items">
				<thead><tr><th><?php esc_html_e( 'Item', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Grade', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Length', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Quantity', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Theoretical weight', 'sm-marketplace' ); ?></th></tr></thead>
				<tbody>
					<?php foreach ( $r['items'] as $it ) : ?>
						<tr class="<?php echo $it['section'] ? 'is-known' : 'is-unknown'; ?>">
							<td>
								<strong dir="auto"><?php echo esc_html( $it['designation'] ); ?></strong>
								<?php if ( $it['section'] ) : ?><span class="smai-tag smai-tag--ok"><?php esc_html_e( 'In the catalogue', 'sm-marketplace' ); ?></span><?php else : ?><span class="smai-tag"><?php esc_html_e( 'To specify', 'sm-marketplace' ); ?></span><?php endif; ?>
								<?php if ( $it['note'] ) : ?><br><small class="sm-muted" dir="auto"><?php echo esc_html( $it['note'] ); ?></small><?php endif; ?>
							</td>
							<td><?php echo esc_html( $it['grade_text'] ?: '—' ); ?></td>
							<td dir="ltr"><?php echo $it['length_m'] ? esc_html( sm_number( $it['length_m'], 2 ) . ' m' ) : '—'; ?></td>
							<td dir="ltr"><?php echo $it['quantity'] ? esc_html( sm_number( $it['quantity'] ) . ' ' . ( $units[ $it['unit'] ] ?? $it['unit'] ) ) : '—'; ?></td>
							<td dir="ltr"><?php echo $it['weight_kg'] > 0 ? esc_html( sm_number( $it['weight_kg'], 1 ) . ' kg' ) : '—'; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
				<?php if ( $r['weight_kg'] > 0 ) : ?>
					<tfoot><tr><th colspan="4"><?php esc_html_e( 'Theoretical weight of the catalogue profiles (weight engine)', 'sm-marketplace' ); ?></th><td dir="ltr"><strong><?php echo esc_html( sm_number( $r['weight_kg'], 1 ) . ' kg' ); ?></strong></td></tr></tfoot>
				<?php endif; ?>
			</table>
		</div>
	<?php endif; ?>
	<?php if ( $r['questions'] ) : ?>
		<div class="smai-ask">
			<h3><?php sm_icon( 'info' ); ?><?php esc_html_e( 'Points to check', 'sm-marketplace' ); ?></h3>
			<ul><?php foreach ( $r['questions'] as $q ) : ?><li dir="auto"><?php echo esc_html( $q ); ?></li><?php endforeach; ?></ul>
		</div>
	<?php endif; ?>
	<?php if ( $r['items'] && ! empty( $args['can_rfq'] ) ) : ?>
		<?php sm_theme_form_open( 'ai_use', array( 'class' => 'smp-actions smai-use' ) ); ?>
			<input type="hidden" name="r" value="<?php echo esc_attr( $args['result']['token'] ); ?>">
			<p class="sm-muted"><?php esc_html_e( 'Catalogue profiles become rows weighed by the engine; the other items go into the description. You complete and check before publishing.', 'sm-marketplace' ); ?></p>
			<button class="sm-btn sm-btn--accent" type="submit"><?php sm_icon( 'rfq' ); ?><?php esc_html_e( 'Create a request for quotation', 'sm-marketplace' ); ?></button>
		</form>
	<?php endif; ?>
</div>

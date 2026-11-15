<?php
/**
 * Inventory of a lot of several reuse listings: a title row per product
 * (designation, its own condition, number and weight), then one row per length
 * (plates: per size) with its number and weight (0.38.0).
 *
 * @var array $args { group: { items: [ { designation, sizes, rows: [ { size, qty, weight } ], pieces, condition {fr, ar}, weight } ], weight, pieces } }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$group  = (array) $args['group'];
$pieces = 0;
?>
<div class="sm-table-wrap" tabindex="0" role="region" aria-label="<?php esc_attr_e( 'Lot inventory', 'sm-marketplace' ); ?>">
	<table class="sm-table smg-table">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Length', 'sm-marketplace' ); ?></th>
				<th scope="col" class="num"><?php esc_html_e( 'Number (pieces)', 'sm-marketplace' ); ?></th>
				<th scope="col" class="num"><?php esc_html_e( 'Weight (kg)', 'sm-marketplace' ); ?></th>
			</tr>
		</thead>
		<?php foreach ( (array) $group['items'] as $it ) : ?>
			<?php
			$rows = (array) ( $it['rows'] ?? array() );
			$n    = $rows ? array_sum( wp_list_pluck( $rows, 'qty' ) ) : (int) $it['pieces'];
			$pieces += $n;
			?>
			<tbody class="smg-product">
				<tr class="smg-head">
					<th scope="rowgroup" colspan="2">
						<?php echo sm_tech( $it['designation'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<small>
							<?php
							$bits = array();
							if ( $it['condition'] ) {
								$bits[] = sm_t( $it['condition'] );
							}
							if ( $n > 0 ) {
								/* translators: %s: number of pieces. */
								$bits[] = sprintf( _n( '%s piece', '%s pieces', $n, 'sm-marketplace' ), sm_number( $n ) );
							} elseif ( '' !== (string) $it['sizes'] ) {
								$bits[] = $it['sizes'];
							}
							echo esc_html( implode( ' · ', $bits ) );
							?>
						</small>
					</th>
					<td class="num"><?php echo $it['weight'] > 0 ? esc_html( sm_number( $it['weight'] ) ) : '—'; ?></td>
				</tr>
				<?php foreach ( $rows as $r ) : ?>
					<tr class="smg-sub">
						<td><?php echo sm_tech( $r['size'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
						<td class="num"><?php echo esc_html( sm_number( $r['qty'] ) ); ?></td>
						<td class="num"><?php echo $r['weight'] > 0 ? esc_html( sm_number( $r['weight'] ) ) : '—'; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		<?php endforeach; ?>
		<tfoot>
			<tr>
				<th scope="row"><?php esc_html_e( 'Total', 'sm-marketplace' ); ?></th>
				<td class="num"><?php echo $pieces > 0 ? esc_html( sprintf( _n( '%s piece', '%s pieces', $pieces, 'sm-marketplace' ), sm_number( $pieces ) ) ) : '—'; ?></td>
				<td class="num"><strong><?php echo esc_html( sm_number( $group['weight'] ) ); ?></strong></td>
			</tr>
		</tfoot>
	</table>
</div>

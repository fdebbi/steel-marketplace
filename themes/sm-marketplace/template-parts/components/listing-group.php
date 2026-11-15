<?php
/**
 * Inventory of a lot of several reuse listings (0.37.0): each product with its
 * sizes, its own condition and its weight.
 *
 * @var array $args { group: { items: [ { title, designation, sizes, pieces, condition {fr, ar}, weight } ], weight, pieces } }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$group = (array) $args['group'];
?>
<div class="sm-table-wrap" tabindex="0" role="region" aria-label="<?php esc_attr_e( 'Lot inventory', 'sm-marketplace' ); ?>">
	<table class="sm-table sm-table--stack smg-table">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Product', 'sm-marketplace' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Lengths × number', 'sm-marketplace' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Condition', 'sm-marketplace' ); ?></th>
				<th scope="col" class="num"><?php esc_html_e( 'Weight (kg)', 'sm-marketplace' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( (array) $group['items'] as $it ) : ?>
				<tr>
					<th scope="row" data-label="<?php esc_attr_e( 'Product', 'sm-marketplace' ); ?>"><?php echo sm_tech( $it['designation'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></th>
					<td data-label="<?php esc_attr_e( 'Lengths × number', 'sm-marketplace' ); ?>"><?php echo '' !== (string) $it['sizes'] ? sm_tech( $it['sizes'] ) : '—'; // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
					<td data-label="<?php esc_attr_e( 'Condition', 'sm-marketplace' ); ?>"><?php echo $it['condition'] ? esc_html( sm_t( $it['condition'] ) ) : '—'; ?></td>
					<td class="num" data-label="<?php esc_attr_e( 'Weight (kg)', 'sm-marketplace' ); ?>"><?php echo $it['weight'] > 0 ? esc_html( sm_number( $it['weight'] ) ) : '—'; ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
		<tfoot>
			<tr>
				<th scope="row" colspan="3"><?php esc_html_e( 'Total steel weight', 'sm-marketplace' ); ?></th>
				<td class="num" data-label="<?php esc_attr_e( 'Total steel weight', 'sm-marketplace' ); ?>"><strong><?php echo esc_html( sm_number( $group['weight'] ) ); ?></strong></td>
			</tr>
		</tfoot>
	</table>
</div>

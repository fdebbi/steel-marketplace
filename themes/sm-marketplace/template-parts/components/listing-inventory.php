<?php
/**
 * Inventory table of a listing: elements, profile, number, length, weights.
 *
 * @var array $args { item: array, title: string }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$item      = (array) $args['item'];
$inv_title = (string) ( $args['title'] ?? '' );
?>
<div class="sm-table-wrap" tabindex="0" role="region" aria-label="<?php echo esc_attr( $inv_title ); ?>">
	<table class="sm-table sm-table--stack">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Element', 'sm-marketplace' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Profile', 'sm-marketplace' ); ?></th>
				<th scope="col" class="num"><?php esc_html_e( 'No. of pieces', 'sm-marketplace' ); ?></th>
				<th scope="col" class="num"><?php esc_html_e( 'Length (m)', 'sm-marketplace' ); ?></th>
				<th scope="col" class="num"><?php esc_html_e( 'Unit weight (kg/m)', 'sm-marketplace' ); ?></th>
				<th scope="col" class="num"><?php esc_html_e( 'Total weight (kg)', 'sm-marketplace' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $item['elements'] as $el ) : ?>
				<tr>
					<th scope="row" data-label="<?php esc_attr_e( 'Element', 'sm-marketplace' ); ?>"><?php sm_e( $el['type'] ); ?></th>
					<td data-label="<?php esc_attr_e( 'Profile', 'sm-marketplace' ); ?>"><?php echo sm_tech( $el['designation'] ); // phpcs:ignore ?></td>
					<td class="num" data-label="<?php esc_attr_e( 'No. of pieces', 'sm-marketplace' ); ?>"><?php echo esc_html( $el['qty'] ); ?></td>
					<td class="num" data-label="<?php esc_attr_e( 'Length (m)', 'sm-marketplace' ); ?>"><?php echo esc_html( sm_number( $el['length'], fmod( $el['length'] * 10, 1 ) ? 2 : 1 ) ); ?></td>
					<td class="num" data-label="<?php esc_attr_e( 'Unit weight (kg/m)', 'sm-marketplace' ); ?>"><?php echo esc_html( sm_number( $el['unit_weight'], 2 ) ); ?></td>
					<td class="num" data-label="<?php esc_attr_e( 'Total weight (kg)', 'sm-marketplace' ); ?>"><?php echo esc_html( sm_number( $el['total'] ) ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
		<tfoot>
			<tr>
				<?php $sm_el_kg = (float) ( $item['elements_kg'] ?? 0 ); // Buildings (0.31.0): the listed elements, not the whole structure. ?>
				<th scope="row" colspan="5"><?php echo esc_html( $sm_el_kg > 0 ? __( 'Weight of the declared elements', 'sm-marketplace' ) : __( 'Total steel weight', 'sm-marketplace' ) ); ?></th>
				<td class="num" data-label="<?php echo esc_attr( $sm_el_kg > 0 ? __( 'Weight of the declared elements', 'sm-marketplace' ) : __( 'Total steel weight', 'sm-marketplace' ) ); ?>"><strong><?php echo esc_html( sm_number( $sm_el_kg > 0 ? $sm_el_kg : $item['weight_kg'] ) ); ?></strong></td>
			</tr>
		</tfoot>
	</table>
</div>

<?php
/**
 * Orders of the account (Phase 10): plans, AI credit packs, listing options.
 *
 * @var array $args { orders: array (resource subscription → orders) }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$orders = (array) ( $args['orders'] ?? array() );
if ( ! $orders ) {
	return;
}
$tone = array( 'awaiting' => 'warn', 'review' => 'info', 'paid' => 'ok', 'rejected' => 'muted', 'cancelled' => 'muted' );
?>
<section class="sm-section-block">
	<h2 class="sm-panel__title"><?php esc_html_e( 'My orders', 'sm-marketplace' ); ?></h2>
	<div class="sm-table-wrap" tabindex="0" role="region" aria-label="<?php esc_attr_e( 'My orders', 'sm-marketplace' ); ?>">
		<table class="sm-table sm-table--stack sm-orders">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Order', 'sm-marketplace' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Date', 'sm-marketplace' ); ?></th>
					<th scope="col" class="num"><?php esc_html_e( 'Amount', 'sm-marketplace' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Status', 'sm-marketplace' ); ?></th>
					<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'sm-marketplace' ); ?></span></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $orders as $o ) : ?>
					<tr>
						<th scope="row" data-label="<?php esc_attr_e( 'Order', 'sm-marketplace' ); ?>"><a href="<?php echo esc_url( $o['url'] ); ?>"><?php echo esc_html( $o['label'] ); ?></a><br><small class="sm-muted" dir="ltr"><?php echo esc_html( $o['ref'] ); ?></small></th>
						<td data-label="<?php esc_attr_e( 'Date', 'sm-marketplace' ); ?>"><?php echo esc_html( sm_date( $o['date'] ) ); ?></td>
						<td class="num" data-label="<?php esc_attr_e( 'Amount', 'sm-marketplace' ); ?>"><?php echo esc_html( sm_money( $o['amount'] ) ); ?></td>
						<td data-label="<?php esc_attr_e( 'Status', 'sm-marketplace' ); ?>"><span class="sm-status sm-status--<?php echo esc_attr( $tone[ $o['status'] ] ?? 'muted' ); ?>"><?php echo esc_html( $o['status_label'] ); ?></span></td>
						<td class="sm-table__actions">
							<?php if ( $o['invoice'] ) : ?>
								<a class="sm-btn sm-btn--ghost sm-btn--sm" href="<?php echo esc_url( $o['invoice'] ); ?>"><?php sm_icon( 'file' ); ?><span><?php esc_html_e( 'Invoice', 'sm-marketplace' ); ?></span></a>
							<?php elseif ( in_array( $o['status'], array( 'awaiting', 'review' ), true ) ) : ?>
								<a class="sm-btn sm-btn--ghost sm-btn--sm" href="<?php echo esc_url( $o['url'] ); ?>"><?php esc_html_e( 'Continue', 'sm-marketplace' ); ?></a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</section>

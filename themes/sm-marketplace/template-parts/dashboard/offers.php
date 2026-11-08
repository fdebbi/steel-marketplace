<?php
/**
 * "Quotations sent": the supplier's offers and what the buyers decided.
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$data = sm_theme_data( 'my_offers', array( 'status' => sm_theme_query_arg( 'status' ) ) );
if ( ! is_array( $data ) || ! isset( $data['items'] ) ) {
	sm_component( 'empty-state', array( 'icon' => 'rfq', 'title' => __( 'Quotations sent', 'sm-marketplace' ), 'text' => __( 'This module is not active yet.', 'sm-marketplace' ) ) );
	return;
}
$labels = sm_offer_status_labels();
?>
<div class="sm-toolbar">
	<p class="sm-muted"><?php esc_html_e( 'Your answers to requests for quotation. Only the buyer sees them.', 'sm-marketplace' ); ?></p>
	<a class="sm-btn sm-btn--primary-outline" href="<?php echo esc_url( sm_page_url( 'rfq' ) ); ?>"><?php sm_icon( 'search' ); ?><?php esc_html_e( 'Find requests to answer', 'sm-marketplace' ); ?></a>
</div>
<?php if ( ! $data['items'] ) : ?>
	<?php sm_component( 'empty-state', array( 'icon' => 'rfq', 'title' => __( 'No quotation sent yet', 'sm-marketplace' ), 'text' => __( 'Browse the open requests and send your price and delivery time.', 'sm-marketplace' ), 'action_label' => __( 'See the requests for quotation', 'sm-marketplace' ), 'action_url' => sm_page_url( 'rfq' ) ) ); ?>
	<?php return; ?>
<?php endif; ?>
<ul class="smp-list" role="list">
	<?php foreach ( $data['items'] as $o ) : ?>
		<li class="smp-row smq-row smq-row--offer">
			<span class="smq-row__count" aria-hidden="true"><?php sm_icon( 'rfq' ); ?></span>
			<div>
				<p class="smp-row__title"><a href="<?php echo esc_url( sm_page_url( 'rfq', array( 'id' => $o['rfq_id'] ) ) ); ?>"><?php sm_e( $o['rfq']['title'] ); ?></a></p>
				<p class="smp-row__meta">
					<span class="smq-ostatus smq-ostatus--<?php echo esc_attr( $o['status'] ); ?>"><?php echo esc_html( $labels[ $o['status'] ] ?? $o['status'] ); ?></span>
					<span><strong><?php echo esc_html( sm_money( $o['total_price'] ) ); ?></strong></span>
					<?php if ( $o['price_per_kg'] > 0 ) : ?><span><?php echo sm_per_unit( $o['price_per_kg'], 'kg', false, 2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php endif; ?>
					<span><?php echo esc_html( sprintf( /* translators: %d: days. */ _n( '%d day', '%d days', $o['delivery_days'], 'sm-marketplace' ), $o['delivery_days'] ) ); ?></span>
					<span><?php echo esc_html( sm_ago( $o['date'] ) ); ?></span>
				</p>
			</div>
			<div class="smp-row__actions">
				<a class="sm-btn sm-btn--primary-outline sm-btn--sm" href="<?php echo esc_url( sm_page_url( 'rfq', array( 'id' => $o['rfq_id'] ) ) ); ?>"><?php esc_html_e( 'View', 'sm-marketplace' ); ?></a>
			</div>
		</li>
	<?php endforeach; ?>
</ul>

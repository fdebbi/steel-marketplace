<?php
/**
 * Partner engineer: invoicing. The fee of each mission: planned while the
 * mission is in progress, due once the platform delivered the study, paid when
 * the platform records the payment.
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$d = sm_theme_data( 'engineer_account', array( 'section' => 'billing' ) );
if ( ! is_array( $d ) || 'approved' !== $d['status'] ) {
	sm_component( 'empty-state', array( 'icon' => 'card', 'title' => __( 'Invoicing opens after the approval of your application', 'sm-marketplace' ) ) );
	return;
}
$labels = array(
	'planned' => array( 'info', __( 'Planned', 'sm-marketplace' ) ),
	'due'     => array( 'warn', __( 'Due', 'sm-marketplace' ) ),
	'paid'    => array( 'ok', __( 'Paid', 'sm-marketplace' ) ),
);
?>
<ul class="sm-kpis">
	<li><span><?php esc_html_e( 'Planned (missions in progress)', 'sm-marketplace' ); ?></span><strong><?php echo esc_html( sm_money( $d['money']['planned'] ) ); ?></strong></li>
	<li><span><?php esc_html_e( 'Due by the platform', 'sm-marketplace' ); ?></span><strong><?php echo esc_html( sm_money( $d['money']['due'] ) ); ?></strong></li>
	<li><span><?php esc_html_e( 'Paid', 'sm-marketplace' ); ?></span><strong><?php echo esc_html( sm_money( $d['money']['paid'] ) ); ?></strong></li>
</ul>
<section class="sm-panel">
	<h2 class="sm-panel__title"><?php esc_html_e( 'Fees by mission', 'sm-marketplace' ); ?></h2>
	<?php if ( $d['billing'] ) : ?>
		<div class="sm-table-wrap">
			<table class="sm-table sm-table--stack">
				<thead><tr><th><?php esc_html_e( 'Mission', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Fee', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Status', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Payment', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Your invoice', 'sm-marketplace' ); ?></th></tr></thead>
				<tbody>
					<?php foreach ( $d['billing'] as $b ) : ?>
						<tr>
							<td data-label="<?php esc_attr_e( 'Mission', 'sm-marketplace' ); ?>"><a href="<?php echo esc_url( $b['url'] ); ?>" dir="auto"><?php echo esc_html( $b['title'] ); ?></a><br><small dir="ltr"><?php echo esc_html( $b['ref'] ); ?></small></td>
							<td data-label="<?php esc_attr_e( 'Fee', 'sm-marketplace' ); ?>"><strong><?php echo esc_html( sm_money( $b['fee'] ) ); ?></strong></td>
							<td data-label="<?php esc_attr_e( 'Status', 'sm-marketplace' ); ?>"><span class="sm-status sm-status--<?php echo esc_attr( $labels[ $b['fee_status'] ][0] ?? 'muted' ); ?>"><?php echo esc_html( $labels[ $b['fee_status'] ][1] ?? $b['fee_status'] ); ?></span></td>
							<td data-label="<?php esc_attr_e( 'Payment', 'sm-marketplace' ); ?>"><?php echo $b['paid_at'] ? esc_html( sm_date( $b['paid_at'] ) . ( $b['reference'] ? ' · ' . $b['reference'] : '' ) ) : '—'; ?></td>
							<td data-label="<?php esc_attr_e( 'Your invoice', 'sm-marketplace' ); ?>">
								<?php if ( $b['invoices'] ) : ?>
									<?php foreach ( $b['invoices'] as $f ) : ?><a href="<?php echo esc_url( $f['url'] ); ?>" target="_blank" rel="noopener"><?php sm_icon( 'file' ); ?><?php echo esc_html( $f['name'] ); ?></a><br><?php endforeach; ?>
								<?php else : ?>
									<a href="<?php echo esc_url( $b['url'] ); ?>"><?php esc_html_e( 'Send it', 'sm-marketplace' ); ?></a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php else : ?>
		<p class="sm-muted"><?php esc_html_e( 'Your fees appear here when you accept a mission.', 'sm-marketplace' ); ?></p>
	<?php endif; ?>
	<p class="sm-help"><?php esc_html_e( 'The fee is the amount agreed with the platform for the mission. It becomes due when the platform has checked and delivered your work.', 'sm-marketplace' ); ?></p>
</section>

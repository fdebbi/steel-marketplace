<?php
/**
 * "My requests for quotation": new / edit form, or the list by status.
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$edit = absint( sm_theme_query_arg( 'edit', '' ) );
if ( sm_theme_query_arg( 'new' ) || $edit ) {
	sm_component( 'rfq-form', array( 'edit_id' => $edit ) );
	return;
}
$data = sm_theme_data( 'my_rfqs', array( 'status' => sm_theme_query_arg( 'status' ), 'page' => (int) sm_theme_query_arg( 'pg', 1 ) ) );
$new  = sm_page_url( 'account', array( 'view' => 'rfq', 'new' => 1 ) );
if ( ! is_array( $data ) || ! isset( $data['counts'] ) ) {
	sm_component( 'empty-state', array( 'icon' => 'rfq', 'title' => __( 'Requests for quotation', 'sm-marketplace' ), 'text' => __( 'This module is not active yet.', 'sm-marketplace' ) ) );
	return;
}
$counts  = (array) $data['counts'];
$current = sm_theme_query_arg( 'status' );
$labels  = sm_rfq_status_labels();
$wilayas = sm_theme_wilayas();
?>
<div class="sm-toolbar">
	<nav class="smp-filter" aria-label="<?php esc_attr_e( 'Filter by status', 'sm-marketplace' ); ?>">
		<a href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'rfq' ) ) ); ?>"<?php echo '' === $current ? ' aria-current="page"' : ''; ?>><?php esc_html_e( 'All', 'sm-marketplace' ); ?> (<?php echo esc_html( array_sum( $counts ) ); ?>)</a>
		<?php foreach ( $labels as $k => $l ) : ?>
			<?php if ( empty( $counts[ $k ] ) && $current !== $k ) { continue; } ?>
			<a href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'rfq', 'status' => $k ) ) ); ?>"<?php echo $current === $k ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $l ); ?> (<?php echo esc_html( (int) ( $counts[ $k ] ?? 0 ) ); ?>)</a>
		<?php endforeach; ?>
	</nav>
	<a class="sm-btn sm-btn--accent" href="<?php echo esc_url( $new ); ?>"><?php sm_icon( 'plus' ); ?><?php esc_html_e( 'New request', 'sm-marketplace' ); ?></a>
</div>
<?php if ( ! $data['items'] ) : ?>
	<?php sm_component( 'empty-state', array( 'icon' => 'rfq', 'title' => __( 'No request yet', 'sm-marketplace' ), 'text' => __( 'Describe what you need: suppliers and manufacturers answer with their prices and delivery times.', 'sm-marketplace' ), 'action_label' => __( 'New request', 'sm-marketplace' ), 'action_url' => $new ) ); ?>
	<?php return; ?>
<?php endif; ?>
<ul class="smp-list" role="list">
	<?php foreach ( $data['items'] as $r ) : ?>
		<li class="smp-row smq-row">
			<span class="smq-row__count" aria-hidden="true"><strong><?php echo esc_html( (int) $r['offers_count'] ); ?></strong><small><?php echo esc_html( _n( 'offer', 'offers', (int) $r['offers_count'], 'sm-marketplace' ) ); ?></small></span>
			<div>
				<p class="smp-row__title"><a href="<?php echo esc_url( sm_page_url( 'rfq', array( 'id' => $r['id'] ) ) ); ?>"><?php sm_e( $r['title'] ); ?></a></p>
				<p class="smp-row__meta">
					<span class="smq-status smq-status--<?php echo esc_attr( $r['status'] ); ?>"><?php echo esc_html( $labels[ $r['status'] ] ?? $r['status'] ); ?></span>
					<?php if ( $r['weight_kg'] ) : ?><span><?php echo sm_kg( $r['weight_kg'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php endif; ?>
					<span><?php echo esc_html( sm_t( $wilayas[ $r['wilaya'] ] ?? '' ) ); ?></span>
					<?php if ( $r['deadline'] ) : ?><span><?php echo esc_html( sprintf( /* translators: %s: date. */ __( 'Offers until %s', 'sm-marketplace' ), sm_date( $r['deadline'] ) ) ); ?></span><?php endif; ?>
				</p>
			</div>
			<div class="smp-row__actions">
				<a class="sm-btn sm-btn--primary-outline sm-btn--sm" href="<?php echo esc_url( sm_page_url( 'rfq', array( 'id' => $r['id'] ) ) . ( $r['offers_count'] ? '#smq-offers' : '' ) ); ?>"><?php echo esc_html( $r['offers_count'] ? __( 'Compare the offers', 'sm-marketplace' ) : __( 'View', 'sm-marketplace' ) ); ?></a>
				<?php if ( ! $r['offers_count'] && in_array( $r['status'], array( 'pending', 'open', 'rejected' ), true ) ) : ?>
					<a class="sm-btn sm-btn--ghost sm-btn--sm" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'rfq', 'edit' => $r['id'] ) ) ); ?>"><?php sm_icon( 'edit' ); ?><?php esc_html_e( 'Edit', 'sm-marketplace' ); ?></a>
				<?php endif; ?>
			</div>
		</li>
	<?php endforeach; ?>
</ul>

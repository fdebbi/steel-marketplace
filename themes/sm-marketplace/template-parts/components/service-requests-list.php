<?php
/**
 * Service requests of the company, received or sent, with a status filter.
 *
 * @var array $args { box: received|sent }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$box    = 'sent' === ( $args['box'] ?? '' ) ? 'sent' : 'received';
$status = sanitize_key( sm_theme_query_arg( 'status', '' ) );
$data   = sm_theme_data( 'service_requests', array( 'box' => $box, 'status' => $status ) );
$view   = 'sent' === $box ? 'my-service-requests' : 'service-requests';
if ( ! is_array( $data ) || ! isset( $data['items'] ) ) {
	sm_component( 'empty-state', array( 'icon' => 'message', 'title' => __( 'Service requests', 'sm-marketplace' ), 'text' => __( 'This module is not active yet.', 'sm-marketplace' ) ) );
	return;
}
$labels = sm_service_request_labels();
$tabs   = array( '' => __( 'All', 'sm-marketplace' ), 'new' => $labels['new'], 'quoted' => $labels['quoted'], 'accepted' => $labels['accepted'] );
?>
<div class="sm-toolbar">
	<p class="sm-muted"><?php echo esc_html( 'sent' === $box ? __( 'The requests you sent to workshops and professionals, and their quotes.', 'sm-marketplace' ) : __( 'Clients who asked you for a quote. Answer quickly: the client compares providers.', 'sm-marketplace' ) ); ?></p>
	<?php if ( 'sent' === $box ) : ?>
		<a class="sm-btn sm-btn--primary-outline" href="<?php echo esc_url( sm_page_url( 'services' ) ); ?>"><?php sm_icon( 'search' ); ?><?php esc_html_e( 'Find a provider', 'sm-marketplace' ); ?></a>
	<?php endif; ?>
</div>
<nav class="smp-filter" aria-label="<?php esc_attr_e( 'Filter by status', 'sm-marketplace' ); ?>">
	<?php foreach ( $tabs as $k => $l ) : ?>
		<a href="<?php echo esc_url( sm_page_url( 'account', array_filter( array( 'view' => $view, 'status' => $k ) ) ) ); ?>"<?php echo $k === $status ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $l ); ?></a>
	<?php endforeach; ?>
</nav>
<?php if ( ! $data['items'] ) : ?>
	<?php
	if ( 'sent' === $box ) {
		sm_component( 'empty-state', array( 'icon' => 'message', 'title' => __( 'No request sent', 'sm-marketplace' ), 'text' => __( 'Open a service page and describe your project: the provider answers with a quote.', 'sm-marketplace' ), 'action_label' => __( 'See the fabrication services', 'sm-marketplace' ), 'action_url' => sm_page_url( 'services' ) ) );
	} else {
		sm_component( 'empty-state', array( 'icon' => 'message', 'title' => __( 'No request yet', 'sm-marketplace' ), 'text' => __( 'Requests from clients appear here. A complete service page with photos gets more of them.', 'sm-marketplace' ), 'action_label' => __( 'My services', 'sm-marketplace' ), 'action_url' => sm_page_url( 'account', array( 'view' => 'services' ) ) ) );
	}
	?>
	<?php return; ?>
<?php endif; ?>
<ul class="sms-reqs" role="list">
	<?php foreach ( $data['items'] as $r ) : ?>
		<?php sm_component( 'service-request-row', array( 'request' => $r ) ); ?>
	<?php endforeach; ?>
</ul>

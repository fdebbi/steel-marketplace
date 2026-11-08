<?php
/**
 * "My services": service pages of the company, or the service form.
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$edit = absint( sm_theme_query_arg( 'edit', '' ) );
$new  = sanitize_key( sm_theme_query_arg( 'new', '' ) );
if ( $edit || $new ) {
	sm_component( 'service-form', array( 'edit_id' => $edit, 'kind' => $new ) );
	return;
}
$data = sm_theme_data( 'my_services' );
if ( ! is_array( $data ) || ! isset( $data['items'] ) ) {
	sm_component( 'empty-state', array( 'icon' => 'wrench', 'title' => __( 'My services', 'sm-marketplace' ), 'text' => __( 'This module is not active yet.', 'sm-marketplace' ) ) );
	return;
}
$labels = array(
	'published' => __( 'Published', 'sm-marketplace' ),
	'pending'   => __( 'Waiting for review', 'sm-marketplace' ),
	'paused'    => __( 'Paused', 'sm-marketplace' ),
	'rejected'  => __( 'Changes requested', 'sm-marketplace' ),
);
$act = static function ( $s, $do, $label, $confirm = '' ) {
	sm_theme_form_open( 'service_action', array( 'class' => 'smp-act' ) );
	echo '<input type="hidden" name="service_id" value="' . esc_attr( $s['id'] ) . '"><input type="hidden" name="do" value="' . esc_attr( $do ) . '">';
	echo '<button type="submit" class="sm-btn sm-btn--ghost sm-btn--sm"' . ( $confirm ? ' data-sm-confirm="' . esc_attr( $confirm ) . '"' : '' ) . '>' . esc_html( $label ) . '</button></form>';
};
$new_url = sm_page_url( 'account', array( 'view' => 'services', 'new' => 1 ) );
?>
<div class="sm-toolbar">
	<p class="sm-muted"><?php esc_html_e( 'One page per kind of work: clients find you by service and wilaya, then send you a request.', 'sm-marketplace' ); ?></p>
	<a class="sm-btn sm-btn--accent" href="<?php echo esc_url( $new_url ); ?>"><?php sm_icon( 'plus' ); ?><?php esc_html_e( 'New service page', 'sm-marketplace' ); ?></a>
</div>
<?php if ( ! $data['items'] ) : ?>
	<?php sm_component( 'empty-state', array( 'icon' => 'wrench', 'title' => __( 'No service page yet', 'sm-marketplace' ), 'text' => __( 'Present what you do, your capabilities and equipment, and the wilayas you serve.', 'sm-marketplace' ), 'action_label' => __( 'New service page', 'sm-marketplace' ), 'action_url' => $new_url ) ); ?>
	<?php return; ?>
<?php endif; ?>
<ul class="smp-list" role="list">
	<?php foreach ( $data['items'] as $s ) : ?>
		<li class="smp-row">
			<img class="smp-row__img" src="<?php echo esc_url( sm_image_url( $s['image'] ) ); ?>" alt="" width="96" height="96" loading="lazy">
			<div>
				<p class="smp-row__title"><a href="<?php echo esc_url( sm_page_url( 'service', array( 'id' => $s['id'] ) ) ); ?>"><?php sm_e( $s['title'] ); ?></a></p>
				<p class="smp-row__meta">
					<span class="smp-status smp-status--<?php echo esc_attr( $s['status'] ); ?>"><?php echo esc_html( $labels[ $s['status'] ] ?? $s['status'] ); ?></span>
					<span><?php echo esc_html( implode( ', ', array_map( 'sm_t', array_slice( $s['tags'], 0, 3 ) ) ) ); ?></span>
					<span><?php echo wp_kses_post( sm_service_price( $s ) ); ?></span>
					<span><?php sm_icon( 'eye' ); ?> <?php echo esc_html( sm_number( $s['views'] ) ); ?></span>
				</p>
			</div>
			<div class="smp-row__actions">
				<a class="sm-btn sm-btn--primary-outline sm-btn--sm" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'services', 'edit' => $s['id'] ) ) ); ?>"><?php sm_icon( 'edit' ); ?><?php esc_html_e( 'Edit', 'sm-marketplace' ); ?></a>
				<?php
				if ( 'published' === $s['status'] ) {
					$act( $s, 'pause', __( 'Pause', 'sm-marketplace' ) );
				} elseif ( 'paused' === $s['status'] ) {
					$act( $s, 'resume', __( 'Show again', 'sm-marketplace' ) );
				}
				$act( $s, 'delete', __( 'Delete', 'sm-marketplace' ), __( 'Delete this service page for good?', 'sm-marketplace' ) );
				?>
			</div>
		</li>
	<?php endforeach; ?>
</ul>

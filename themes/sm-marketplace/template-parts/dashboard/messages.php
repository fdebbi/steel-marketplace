<?php
/**
 * Enquiries received.
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$account = $args['account'];
$items   = empty( $account['real'] ) ? array() : (array) sm_theme_data( 'enquiries', array( 'box' => 'received' ) );
$status  = array(
	'new'      => array( 'info', __( 'New', 'sm-marketplace' ) ),
	'read'     => array( 'ok', __( 'Read', 'sm-marketplace' ) ),
	'archived' => array( 'muted', __( 'Archived', 'sm-marketplace' ) ),
);

if ( ! $items ) {
	sm_component(
		'empty-state',
		array(
			'icon'  => 'message',
			'title' => __( 'No enquiry yet', 'sm-marketplace' ),
			'text'  => __( 'Buyers and clients who contact you from your listings or your company page appear here. You are also notified by e-mail.', 'sm-marketplace' ),
		)
	);
	return;
}
?>
<ul class="sm-enquiries">
	<?php foreach ( $items as $e ) : ?>
		<li class="sm-panel sm-enquiry<?php echo 'new' === $e['status'] ? ' is-new' : ''; ?>">
			<div class="sm-enquiry__head">
				<div>
					<strong><?php echo esc_html( $e['name'] ); ?></strong>
					<small><?php echo esc_html( sm_ago( $e['date'] ) ); ?><?php if ( $e['subject'] ) : ?> · <?php echo esc_html( $e['subject'] ); ?><?php endif; ?></small>
				</div>
				<span class="sm-status sm-status--<?php echo esc_attr( $status[ $e['status'] ][0] ?? 'info' ); ?>"><?php echo esc_html( $status[ $e['status'] ][1] ?? $e['status'] ); ?></span>
			</div>
			<p class="sm-enquiry__msg" dir="auto"><?php echo nl2br( esc_html( $e['message'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
			<?php if ( $e['quantity'] ) : ?>
				<p class="sm-muted"><?php esc_html_e( 'Quantity needed', 'sm-marketplace' ); ?>: <bdi dir="auto"><?php echo esc_html( $e['quantity'] ); ?></bdi></p>
			<?php endif; ?>
			<div class="sm-enquiry__actions">
				<?php if ( $e['phone'] ) : ?><a class="sm-btn sm-btn--accent" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $e['phone'] ) ); ?>"><?php sm_icon( 'phone' ); ?><bdi dir="ltr"><?php echo esc_html( $e['phone'] ); ?></bdi></a><?php endif; ?>
				<?php if ( $e['email'] ) : ?><a class="sm-btn sm-btn--ghost" href="mailto:<?php echo esc_attr( $e['email'] ); ?>"><?php sm_icon( 'mail' ); ?><?php esc_html_e( 'Reply by e-mail', 'sm-marketplace' ); ?></a><?php endif; ?>
				<?php if ( 'new' === $e['status'] ) : ?>
					<?php sm_theme_form_open( 'enquiry_status', array( 'class' => 'sm-inline-form' ) ); ?>
						<input type="hidden" name="id" value="<?php echo esc_attr( $e['id'] ); ?>"><input type="hidden" name="status" value="read">
						<button class="sm-btn sm-btn--ghost" type="submit"><?php sm_icon( 'check' ); ?><?php esc_html_e( 'Mark as read', 'sm-marketplace' ); ?></button>
					</form>
				<?php endif; ?>
			</div>
		</li>
	<?php endforeach; ?>
</ul>

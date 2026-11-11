<?php
/**
 * Latest notifications (sm-core).
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$full  = 'notifications' === ( $args['view'] ?? '' );
$items = sm_theme_data( 'notifications', array( 'limit' => $full ? 100 : 10 ) );
if ( empty( $args['account']['real'] ) ) {
	return;
}
?>
<section class="sm-panel" id="sm-notifications">
	<div class="sm-panel__head">
		<h2 class="sm-panel__title"><?php echo esc_html( $full ? __( 'All notifications', 'sm-marketplace' ) : __( 'Notifications', 'sm-marketplace' ) ); ?></h2>
		<?php if ( array_filter( (array) $items, static fn( $n ) => ! $n['read'] ) ) : ?>
			<?php sm_theme_form_open( 'notifications_read', array( 'class' => 'sm-inline-form' ) ); ?>
				<button class="sm-link sm-link--btn" type="submit"><?php esc_html_e( 'Mark all as read', 'sm-marketplace' ); ?></button>
			</form>
		<?php endif; ?>
	</div>
	<?php if ( $items ) : ?>
		<ul class="sm-rowlist">
			<?php foreach ( $items as $n ) : ?>
				<li class="<?php echo $n['read'] ? '' : 'is-unread'; ?>">
					<span class="sm-rowlist__icon"><?php sm_icon( 'bell' ); ?></span>
					<div>
						<?php if ( $n['link'] ) : ?><a href="<?php echo esc_url( $n['read'] ? $n['link'] : $n['open'] ); ?>"><?php echo esc_html( $n['title'] ); ?></a><?php else : ?><strong><?php echo esc_html( $n['title'] ); ?></strong><?php endif; ?>
						<small><?php echo esc_html( $n['text'] ); ?> · <?php echo esc_html( sm_ago( $n['date'] ) ); ?></small>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php if ( ! $full && count( $items ) >= 10 ) : ?>
			<p><a class="sm-link" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'notifications' ) ) ); ?>"><?php esc_html_e( 'See all notifications', 'sm-marketplace' ); ?><?php sm_icon( 'arrow-end' ); ?></a></p>
		<?php endif; ?>
	<?php else : ?>
		<p class="sm-muted"><?php esc_html_e( 'No notification yet.', 'sm-marketplace' ); ?></p>
	<?php endif; ?>
</section>

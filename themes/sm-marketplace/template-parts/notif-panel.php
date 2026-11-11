<?php
/**
 * Floating panel of the bell (0.16.0): the latest notifications, "Mark all as
 * read" and "See all". Rendered in the header, opened by main.js; without
 * JavaScript the bell opens the notifications page.
 *
 * @var array $args { unread: int }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$items = (array) sm_theme_data( 'notifications', array( 'limit' => 8 ) );
?>
<div class="sm-notif__panel" id="sm-notif-panel" role="dialog" aria-label="<?php esc_attr_e( 'Notifications', 'sm-marketplace' ); ?>" hidden data-sm-notif-panel>
	<div class="sm-notif__head">
		<p class="sm-notif__title"><?php esc_html_e( 'Notifications', 'sm-marketplace' ); ?><?php if ( ! empty( $args['unread'] ) ) : ?> <span class="sm-notif__count"><?php echo esc_html( sprintf( /* translators: %d: unread notifications. */ _n( '%d new', '%d new', (int) $args['unread'], 'sm-marketplace' ), (int) $args['unread'] ) ); ?></span><?php endif; ?></p>
		<?php if ( ! empty( $args['unread'] ) ) : ?>
			<?php sm_theme_form_open( 'notifications_read', array( 'class' => 'sm-inline-form' ) ); ?>
				<button class="sm-link sm-link--btn sm-notif__all-read" type="submit"><?php esc_html_e( 'Mark all as read', 'sm-marketplace' ); ?></button>
			</form>
		<?php endif; ?>
	</div>
	<?php if ( $items ) : ?>
		<ul class="sm-notif__list" role="list">
			<?php foreach ( $items as $n ) : ?>
				<li class="sm-notif__item<?php echo $n['read'] ? '' : ' is-unread'; ?>">
					<a href="<?php echo esc_url( $n['link'] ? ( $n['read'] ? $n['link'] : $n['open'] ) : $n['open'] ); ?>">
						<span class="sm-notif__icon" aria-hidden="true"><?php sm_icon( 'bell' ); ?></span>
						<span class="sm-notif__text">
							<strong><?php echo esc_html( $n['title'] ); ?></strong>
							<span><?php echo esc_html( wp_trim_words( $n['text'], 16 ) ); ?></span>
							<small><?php echo esc_html( sm_ago( $n['date'] ) ); ?></small>
						</span>
						<?php if ( ! $n['read'] ) : ?><span class="screen-reader-text"><?php esc_html_e( 'Unread', 'sm-marketplace' ); ?></span><?php endif; ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p class="sm-notif__empty"><?php esc_html_e( 'No notification yet.', 'sm-marketplace' ); ?></p>
	<?php endif; ?>
	<a class="sm-btn sm-btn--ghost sm-btn--block sm-notif__more" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'notifications' ) ) ); ?>"><?php esc_html_e( 'See all notifications', 'sm-marketplace' ); ?></a>
</div>

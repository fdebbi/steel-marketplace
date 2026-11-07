<?php
/**
 * Latest notifications (sm-core).
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$items = sm_theme_data( 'notifications' );
if ( empty( $args['account']['real'] ) ) {
	return;
}
?>
<section class="sm-panel" id="sm-notifications">
	<div class="sm-panel__head">
		<h2 class="sm-panel__title"><?php esc_html_e( 'Notifications', 'sm-marketplace' ); ?></h2>
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
						<?php if ( $n['link'] ) : ?><a href="<?php echo esc_url( $n['link'] ); ?>"><?php echo esc_html( $n['title'] ); ?></a><?php else : ?><strong><?php echo esc_html( $n['title'] ); ?></strong><?php endif; ?>
						<small><?php echo esc_html( $n['text'] ); ?> · <?php echo esc_html( sm_ago( $n['date'] ) ); ?></small>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p class="sm-muted"><?php esc_html_e( 'No notification yet.', 'sm-marketplace' ); ?></p>
	<?php endif; ?>
</section>

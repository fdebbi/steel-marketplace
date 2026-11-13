<?php
/**
 * Header actions (search, saved, bell, account, publish). Also returned by the
 * sm_user_nav request, which corrects a page served by a cache in the wrong state (0.30.1).
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
$sm_account = is_user_logged_in() ? sm_theme_account() : null;
$sm_unread  = $sm_account ? (int) ( $sm_account['unread'] ?? 0 ) : 0;
?>
<a class="sm-icon-btn sm-header__search-mobile" href="<?php echo esc_url( sm_page_url( 'search' ) ); ?>"><?php sm_icon( 'search' ); ?><span class="screen-reader-text"><?php esc_html_e( 'Search', 'sm-marketplace' ); ?></span></a>
<a class="sm-icon-btn sm-hide-mobile" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'saved' ) ) ); ?>"><?php sm_icon( 'heart' ); ?><span class="screen-reader-text"><?php esc_html_e( 'Saved listings', 'sm-marketplace' ); ?></span></a>
<?php if ( $sm_account ) : ?>
	<div class="sm-notif sm-hide-mobile" data-sm-notif>
		<a class="sm-icon-btn sm-header__bell" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'notifications' ) ) ); ?>" aria-haspopup="true" aria-expanded="false" aria-controls="sm-notif-panel" data-sm-notif-toggle>
			<?php sm_icon( 'bell' ); ?>
			<?php if ( $sm_unread ) : ?><span class="sm-dot"><?php echo esc_html( min( 99, $sm_unread ) ); ?></span><?php endif; ?>
			<span class="screen-reader-text"><?php esc_html_e( 'Notifications', 'sm-marketplace' ); ?></span>
		</a>
		<?php get_template_part( 'template-parts/notif-panel', null, array( 'unread' => $sm_unread ) ); ?>
	</div>
	<a class="sm-header__account sm-hide-mobile" href="<?php echo esc_url( sm_page_url( 'account' ) ); ?>">
		<?php sm_avatar( array_merge( $sm_account['company'], array( 'class' => sm_account_class( $sm_account ) ) ), 'xs' ); ?>
		<span><?php esc_html_e( 'My account', 'sm-marketplace' ); ?></span>
	</a>
<?php elseif ( is_user_logged_in() ) : ?>
	<a class="sm-header__account sm-hide-mobile" href="<?php echo esc_url( admin_url() ); ?>"><?php sm_icon( 'user' ); ?><span><?php esc_html_e( 'Administration', 'sm-marketplace' ); ?></span></a>
<?php else : ?>
	<a class="sm-header__account sm-hide-mobile" href="<?php echo esc_url( sm_page_url( 'login' ) ); ?>"><?php sm_icon( 'user' ); ?><span><?php esc_html_e( 'Sign in', 'sm-marketplace' ); ?></span></a>
<?php endif; ?>
<?php if ( ! $sm_account || 'engineer' !== sm_account_class( $sm_account ) ) : ?>
	<a class="sm-btn sm-btn--accent sm-header__publish" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'publish' ) ) ); ?>"><?php sm_icon( 'plus' ); ?><span><?php esc_html_e( 'Publish', 'sm-marketplace' ); ?></span></a>
<?php else : ?>
	<a class="sm-btn sm-btn--accent sm-header__publish" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'missions' ) ) ); ?>"><?php sm_icon( 'hard-hat' ); ?><span><?php esc_html_e( 'My missions', 'sm-marketplace' ); ?></span></a>
<?php endif; ?>

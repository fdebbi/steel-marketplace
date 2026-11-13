<?php
/**
 * Foot of the mobile menu (publish, account / sign in). Also returned by the
 * sm_user_nav request (0.30.1).
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
?>
<a class="sm-btn sm-btn--accent sm-btn--block" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'publish' ) ) ); ?>"><?php sm_icon( 'plus' ); ?><?php esc_html_e( 'Publish a listing', 'sm-marketplace' ); ?></a>
<?php if ( is_user_logged_in() ) : ?>
	<a class="sm-btn sm-btn--ghost sm-btn--block" href="<?php echo esc_url( sm_page_url( 'account' ) ); ?>"><?php sm_icon( 'user' ); ?><?php esc_html_e( 'My account', 'sm-marketplace' ); ?></a>
	<a class="sm-btn sm-btn--ghost sm-btn--block" href="<?php echo esc_url( wp_logout_url() ); ?>"><?php sm_icon( 'logout' ); ?><?php esc_html_e( 'Sign out', 'sm-marketplace' ); ?></a>
<?php else : ?>
	<a class="sm-btn sm-btn--ghost sm-btn--block" href="<?php echo esc_url( sm_page_url( 'login' ) ); ?>"><?php sm_icon( 'user' ); ?><?php esc_html_e( 'Sign in', 'sm-marketplace' ); ?></a>
	<a class="sm-btn sm-btn--ghost sm-btn--block" href="<?php echo esc_url( sm_page_url( 'register' ) ); ?>"><?php esc_html_e( 'Create an account', 'sm-marketplace' ); ?></a>
<?php endif; ?>

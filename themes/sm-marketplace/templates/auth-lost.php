<?php
/**
 * Template Name: SM — Lost password
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
get_header();
get_template_part(
	'template-parts/auth-shell',
	null,
	array(
		'title'   => __( 'Reset your password', 'sm-marketplace' ),
		'lead'    => __( 'Enter your email or phone. We will send you a reset link or code.', 'sm-marketplace' ),
		'content' => static function () {
			?>
			<form class="sm-form" method="post" action="<?php echo esc_url( wp_lostpassword_url() ); ?>">
				<div class="sm-field"><label for="r-user"><?php esc_html_e( 'Email or phone', 'sm-marketplace' ); ?></label><input id="r-user" name="user_login" dir="ltr" autocomplete="username" required></div>
				<button class="sm-btn sm-btn--primary sm-btn--block sm-btn--lg" type="submit"><?php esc_html_e( 'Send reset link', 'sm-marketplace' ); ?></button>
				<p class="sm-form__alt"><a href="<?php echo esc_url( sm_page_url( 'login' ) ); ?>"><?php sm_icon( 'chevron-start' ); ?><?php esc_html_e( 'Back to sign in', 'sm-marketplace' ); ?></a></p>
			</form>
			<?php
		},
	)
);
get_footer();

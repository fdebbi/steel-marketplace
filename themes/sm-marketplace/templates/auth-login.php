<?php
/**
 * Template Name: SM — Sign in
 *
 * Presentation only. Authentication is processed by sm-core (Phase 2), which will
 * replace the form action and add nonces, rate limiting and SMS verification.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
get_header();
get_template_part(
	'template-parts/auth-shell',
	null,
	array(
		'title'   => __( 'Sign in', 'sm-marketplace' ),
		'lead'    => __( 'Welcome back. Sign in with your email or phone number.', 'sm-marketplace' ),
		'content' => static function () {
			?>
			<form class="sm-form" method="post" action="<?php echo esc_url( apply_filters( 'sm_login_action', wp_login_url( sm_page_url( 'account' ) ) ) ); ?>">
				<div class="sm-field">
					<label for="l-user"><?php esc_html_e( 'Email or phone', 'sm-marketplace' ); ?></label>
					<input id="l-user" name="log" autocomplete="username" dir="ltr" required>
				</div>
				<div class="sm-field">
					<label for="l-pass"><?php esc_html_e( 'Password', 'sm-marketplace' ); ?></label>
					<div class="sm-input-group">
						<input id="l-pass" name="pwd" type="password" autocomplete="current-password" dir="ltr" required>
						<button type="button" class="sm-input-group__btn" data-sm-password aria-controls="l-pass"><?php sm_icon( 'eye' ); ?><span class="screen-reader-text"><?php esc_html_e( 'Show', 'sm-marketplace' ); ?></span></button>
					</div>
				</div>
				<div class="sm-form__row">
					<label class="sm-check"><input type="checkbox" name="rememberme" value="forever"><span><?php esc_html_e( 'Remember me', 'sm-marketplace' ); ?></span></label>
					<a href="<?php echo esc_url( sm_page_url( 'lost' ) ); ?>"><?php esc_html_e( 'Forgot password?', 'sm-marketplace' ); ?></a>
				</div>
				<button class="sm-btn sm-btn--primary sm-btn--block sm-btn--lg" type="submit"><?php esc_html_e( 'Sign in', 'sm-marketplace' ); ?></button>
				<p class="sm-form__alt"><?php esc_html_e( 'New on the platform?', 'sm-marketplace' ); ?> <a href="<?php echo esc_url( sm_page_url( 'register' ) ); ?>"><?php esc_html_e( 'Create an account', 'sm-marketplace' ); ?></a></p>
			</form>
			<?php
		},
	)
);
get_footer();

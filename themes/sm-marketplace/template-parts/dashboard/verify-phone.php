<?php
/**
 * Enter the 6-digit verification code (level 1).
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$state = sm_theme_data( 'verification' );
if ( (int) $args['account']['company']['verification'] >= 1 ) {
	echo '<div class="sm-notice sm-notice--success">' . sm_get_icon( 'verified' ) . '<p>' . esc_html__( 'Your contact details are verified.', 'sm-marketplace' ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	return;
}
?>
<section class="sm-panel sm-otp">
	<?php sm_icon( 'email' === ( $state['channel'] ?? 'email' ) ? 'mail' : 'phone', 'sm-otp__icon' ); ?>
	<h2 class="sm-panel__title"><?php esc_html_e( 'Enter your verification code', 'sm-marketplace' ); ?></h2>
	<p class="sm-muted"><?php echo esc_html( $state['sent_message'] ?? '' ); ?></p>
	<?php sm_theme_form_open( 'verify_code', array( 'class' => 'sm-form sm-otp__form' ) ); ?>
		<label class="screen-reader-text" for="otp"><?php esc_html_e( 'Verification code', 'sm-marketplace' ); ?></label>
		<input id="otp" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" dir="ltr" class="sm-otp__input" placeholder="••••••" required<?php sm_theme_aria( 'code' ); ?>>
		<?php sm_theme_error( 'code' ); ?>
		<button class="sm-btn sm-btn--accent sm-btn--block sm-btn--lg" type="submit"><?php esc_html_e( 'Verify', 'sm-marketplace' ); ?></button>
	</form>
	<?php sm_theme_form_open( 'resend_code', array( 'class' => 'sm-inline-form' ) ); ?>
		<p class="sm-form__alt"><?php esc_html_e( 'No code received?', 'sm-marketplace' ); ?> <button class="sm-link sm-link--btn" type="submit"><?php esc_html_e( 'Send a new code', 'sm-marketplace' ); ?></button></p>
	</form>
</section>

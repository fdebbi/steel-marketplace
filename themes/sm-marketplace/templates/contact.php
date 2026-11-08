<?php
/**
 * Template Name: SM — Contact
 *
 * Contact form. Sent to sm-core (action sm_contact): the administrators are
 * alerted by e-mail and the visitor receives an acknowledgement.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
get_header();
$info = (array) sm_theme_data( 'contact_info' );
$acc  = is_user_logged_in() ? (array) sm_theme_account() : array();
$old  = sm_theme_old_all();
$val  = static function ( $k, $d = '' ) use ( $old ) {
	return isset( $old[ $k ] ) && ! is_array( $old[ $k ] ) ? (string) $old[ $k ] : (string) $d;
};
$sent = ! $old && (bool) array_filter( sm_theme_notice_list(), static fn( $n ) => 'success' === $n['type'] );
?>
<section class="sm-pagehead">
	<div class="sm-container">
		<?php sm_breadcrumb( array( array( __( 'Contact', 'sm-marketplace' ), '' ) ) ); ?>
		<h1 class="sm-pagehead__title"><?php esc_html_e( 'Contact us', 'sm-marketplace' ); ?></h1>
		<p class="sm-pagehead__intro"><?php esc_html_e( 'A question about the platform, your account, a verification or a partnership? Write to us: the team answers by e-mail.', 'sm-marketplace' ); ?></p>
	</div>
</section>
<div class="sm-container sm-section smc-layout">
	<div class="smc-main">
		<?php if ( ! $sent ) { sm_theme_notices( true ); } ?>
		<?php if ( empty( $info['form'] ) ) : ?>
			<?php sm_component( 'empty-state', array( 'icon' => 'mail', 'title' => __( 'Contact', 'sm-marketplace' ), 'text' => __( 'The contact form is not active yet.', 'sm-marketplace' ) ) ); ?>
		<?php elseif ( $sent ) : ?>
			<div class="sm-panel smc-done">
				<span class="smc-done__icon" aria-hidden="true"><?php sm_icon( 'check' ); ?></span>
				<h2 class="sm-panel__title"><?php esc_html_e( 'Message sent', 'sm-marketplace' ); ?></h2>
				<p><?php esc_html_e( 'You will receive an acknowledgement by e-mail. We answer as soon as possible.', 'sm-marketplace' ); ?></p>
				<a class="sm-btn sm-btn--primary-outline" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to the home page', 'sm-marketplace' ); ?></a>
			</div>
		<?php else : ?>
			<?php sm_theme_form_open( 'contact', array( 'class' => 'sm-form sm-panel smc-form' ) ); ?>
				<div class="sm-field sm-field--row">
					<div>
						<label for="smc-name"><?php esc_html_e( 'Your name', 'sm-marketplace' ); ?></label>
						<input id="smc-name" name="name" autocomplete="name" maxlength="120" value="<?php echo esc_attr( $val( 'name', $acc['user'] ?? '' ) ); ?>"<?php sm_theme_aria( 'name' ); ?>>
						<?php sm_theme_error( 'name' ); ?>
					</div>
					<div>
						<label for="smc-email"><?php esc_html_e( 'E-mail', 'sm-marketplace' ); ?></label>
						<input id="smc-email" name="email" type="email" autocomplete="email" dir="ltr" maxlength="190" value="<?php echo esc_attr( $val( 'email', $acc['user_email'] ?? '' ) ); ?>"<?php sm_theme_aria( 'email' ); ?>>
						<?php sm_theme_error( 'email' ); ?>
					</div>
				</div>
				<div class="sm-field sm-field--row">
					<div>
						<label for="smc-phone"><?php esc_html_e( 'Phone', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label>
						<input id="smc-phone" name="phone" type="tel" autocomplete="tel" dir="ltr" maxlength="20" value="<?php echo esc_attr( $val( 'phone', $acc['user_phone'] ?? '' ) ); ?>"<?php sm_theme_aria( 'phone' ); ?>>
						<?php sm_theme_error( 'phone' ); ?>
					</div>
					<div>
						<label for="smc-subject"><?php esc_html_e( 'Subject', 'sm-marketplace' ); ?></label>
						<input id="smc-subject" name="subject" maxlength="160" list="smc-subjects" value="<?php echo esc_attr( $val( 'subject' ) ); ?>"<?php sm_theme_aria( 'subject' ); ?>>
						<datalist id="smc-subjects">
							<option value="<?php esc_attr_e( 'My account', 'sm-marketplace' ); ?>"></option>
							<option value="<?php esc_attr_e( 'Verification of my company', 'sm-marketplace' ); ?>"></option>
							<option value="<?php esc_attr_e( 'Plans and payment', 'sm-marketplace' ); ?>"></option>
							<option value="<?php esc_attr_e( 'Partnership', 'sm-marketplace' ); ?>"></option>
							<option value="<?php esc_attr_e( 'Report a listing', 'sm-marketplace' ); ?>"></option>
						</datalist>
						<?php sm_theme_error( 'subject' ); ?>
					</div>
				</div>
				<div class="sm-field">
					<label for="smc-message"><?php esc_html_e( 'Message', 'sm-marketplace' ); ?></label>
					<textarea id="smc-message" name="message" rows="7" dir="auto" maxlength="4000"<?php sm_theme_aria( 'message' ); ?>><?php echo esc_textarea( $val( 'message' ) ); ?></textarea>
					<?php sm_theme_error( 'message' ); ?>
				</div>
				<div class="smp-actions">
					<button class="sm-btn sm-btn--accent" type="submit"><?php sm_icon( 'mail' ); ?><?php esc_html_e( 'Send the message', 'sm-marketplace' ); ?></button>
				</div>
			</form>
		<?php endif; ?>
	</div>
	<aside class="smc-aside">
		<div class="sm-panel">
			<h2 class="sm-panel__title"><?php esc_html_e( 'Other ways to reach us', 'sm-marketplace' ); ?></h2>
			<?php if ( ! empty( $info['email'] ) ) : ?>
				<p class="smc-line"><?php sm_icon( 'mail' ); ?><a href="<?php echo esc_url( 'mailto:' . $info['email'] ); ?>" dir="ltr"><?php echo esc_html( $info['email'] ); ?></a></p>
			<?php endif; ?>
			<p class="sm-muted"><?php esc_html_e( 'To contact a seller, a workshop or an engineer, use the button on their listing or page: your message goes directly to them.', 'sm-marketplace' ); ?></p>
		</div>
	</aside>
</div>
<?php
get_footer();

<?php
/**
 * Personal settings: name, language, password.
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$account = $args['account'];
if ( empty( $account['real'] ) ) {
	sm_component( 'empty-state', array( 'icon' => 'settings', 'title' => __( 'Sign in with a real account to change your settings.', 'sm-marketplace' ) ) );
	return;
}
$lang = sm_theme_old( 'language', $account['language'] ?? sm_theme_current_language() );
?>
<?php sm_theme_form_open( 'settings_save', array( 'class' => 'sm-form sm-form--dash' ) ); ?>
	<section class="sm-panel">
		<h2 class="sm-panel__title"><?php esc_html_e( 'Profile', 'sm-marketplace' ); ?></h2>
		<div class="sm-field sm-field--row">
			<div><label for="s-name"><?php esc_html_e( 'Full name', 'sm-marketplace' ); ?></label><input id="s-name" name="display_name" value="<?php echo esc_attr( sm_theme_old( 'display_name', $account['user'] ) ); ?>" dir="auto" required<?php sm_theme_aria( 'display_name' ); ?>><?php sm_theme_error( 'display_name' ); ?></div>
			<div><label for="s-email"><?php esc_html_e( 'Email', 'sm-marketplace' ); ?></label><input id="s-email" value="<?php echo esc_attr( $account['user_email'] ); ?>" dir="ltr" disabled></div>
		</div>
		<fieldset class="sm-field">
			<legend><?php esc_html_e( 'Language of the site and of your e-mails', 'sm-marketplace' ); ?></legend>
			<div class="sm-segment">
				<?php foreach ( sm_theme_languages() as $code => $language ) : ?>
					<label><input type="radio" name="language" value="<?php echo esc_attr( $code ); ?>" <?php checked( $lang, $code ); ?>><span lang="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $language['label'] ); ?></span></label>
				<?php endforeach; ?>
			</div>
		</fieldset>
	</section>
	<section class="sm-panel">
		<h2 class="sm-panel__title"><?php esc_html_e( 'Change password', 'sm-marketplace' ); ?></h2>
		<div class="sm-field sm-field--row">
			<div><label for="s-cur"><?php esc_html_e( 'Current password', 'sm-marketplace' ); ?></label><input id="s-cur" name="current_password" type="password" autocomplete="current-password" dir="ltr"<?php sm_theme_aria( 'current_password' ); ?>><?php sm_theme_error( 'current_password' ); ?></div>
			<div><label for="s-new"><?php esc_html_e( 'New password', 'sm-marketplace' ); ?></label><input id="s-new" name="new_password" type="password" autocomplete="new-password" minlength="10" dir="ltr"<?php sm_theme_aria( 'new_password' ); ?>><?php sm_theme_error( 'new_password' ); ?></div>
		</div>
		<p class="sm-help"><?php esc_html_e( 'Leave empty to keep your current password. At least 10 characters.', 'sm-marketplace' ); ?></p>
	</section>
	<div class="sm-stepper__nav">
		<button class="sm-btn sm-btn--accent" type="submit"><?php sm_icon( 'check' ); ?><?php esc_html_e( 'Save', 'sm-marketplace' ); ?></button>
	</div>
</form>

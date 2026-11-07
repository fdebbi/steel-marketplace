<?php
/**
 * Enquiry form (listing contact or service request), processed by sm-core.
 *
 * Contact requires an account (Phase 0): guests see sign-in links; accounts
 * without the right (e.g. engineering accounts on a listing) see the reason.
 *
 * @var array $args { kind: listing|service, module, entity_id, company, subject, title, id }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$args = wp_parse_args( $args, array( 'kind' => 'listing', 'module' => '', 'entity_id' => 0, 'company' => '', 'subject' => '', 'title' => __( 'Send an enquiry', 'sm-marketplace' ), 'id' => 'sm-enquiry' ) );
$cap  = 'service' === $args['kind'] ? 'service.request' : 'buy';
$here = sm_theme_core_active() && class_exists( 'SM\\Core\\I18n\\Language' ) ? SM\Core\I18n\Language::url_for( SM\Core\I18n\Language::current_unprefixed_url(), sm_theme_current_language() ) : '';
$acct = is_user_logged_in() ? sm_theme_account() : null;
?>
<section class="sm-panel" id="<?php echo esc_attr( $args['id'] ); ?>">
	<h2 class="sm-panel__title"><?php echo esc_html( $args['title'] ); ?></h2>
	<?php sm_theme_notices(); ?>

	<?php if ( sm_theme_core_active() && ! is_user_logged_in() ) : ?>
		<p><?php esc_html_e( 'Sign in or create a free account to contact companies on the platform.', 'sm-marketplace' ); ?></p>
		<div class="sm-row-wrap">
			<a class="sm-btn sm-btn--accent" href="<?php echo esc_url( add_query_arg( 'redirect_to', rawurlencode( $here . '#' . $args['id'] ), sm_page_url( 'login' ) ) ); ?>"><?php sm_icon( 'user' ); ?><?php esc_html_e( 'Sign in', 'sm-marketplace' ); ?></a>
			<a class="sm-btn sm-btn--ghost" href="<?php echo esc_url( sm_page_url( 'register' ) ); ?>"><?php esc_html_e( 'Create an account', 'sm-marketplace' ); ?></a>
		</div>

	<?php elseif ( sm_theme_core_active() && ( ! $acct || sm_theme_cannot_reason( $cap ) ) ) : ?>
		<p class="sm-notice sm-notice--warn"><?php sm_icon( 'lock' ); ?><span><?php echo esc_html( $acct ? sm_theme_cannot_reason( $cap ) : __( 'This user has no company account', 'sm-marketplace' ) ); ?></span></p>

	<?php else : ?>
		<?php sm_theme_form_open( 'enquiry' ); ?>
			<input type="hidden" name="kind" value="<?php echo esc_attr( $args['kind'] ); ?>">
			<input type="hidden" name="module" value="<?php echo esc_attr( $args['module'] ); ?>">
			<input type="hidden" name="entity_id" value="<?php echo esc_attr( $args['entity_id'] ); ?>">
			<input type="hidden" name="company" value="<?php echo esc_attr( $args['company'] ); ?>">
			<input type="hidden" name="subject" value="<?php echo esc_attr( $args['subject'] ); ?>">
			<div class="sm-field sm-field--row">
				<div><label for="<?php echo esc_attr( $args['id'] ); ?>-name"><?php esc_html_e( 'Full name', 'sm-marketplace' ); ?></label><input id="<?php echo esc_attr( $args['id'] ); ?>-name" name="name" autocomplete="name" dir="auto" required value="<?php echo esc_attr( sm_theme_old( 'name', $acct['user'] ?? '' ) ); ?>"></div>
				<div><label for="<?php echo esc_attr( $args['id'] ); ?>-phone"><?php esc_html_e( 'Phone', 'sm-marketplace' ); ?></label><input id="<?php echo esc_attr( $args['id'] ); ?>-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" dir="ltr" value="<?php echo esc_attr( sm_theme_old( 'phone', $acct['user_phone'] ?? '' ) ); ?>"<?php sm_theme_aria( 'phone' ); ?>><?php sm_theme_error( 'phone' ); ?></div>
			</div>
			<?php if ( 'listing' === $args['kind'] ) : ?>
				<div class="sm-field"><label for="<?php echo esc_attr( $args['id'] ); ?>-qty"><?php esc_html_e( 'Quantity needed', 'sm-marketplace' ); ?></label><input id="<?php echo esc_attr( $args['id'] ); ?>-qty" name="qty" dir="auto" value="<?php echo esc_attr( sm_theme_old( 'qty' ) ); ?>"></div>
			<?php endif; ?>
			<div class="sm-field"><label for="<?php echo esc_attr( $args['id'] ); ?>-msg"><?php esc_html_e( 'Message', 'sm-marketplace' ); ?></label><textarea id="<?php echo esc_attr( $args['id'] ); ?>-msg" name="message" rows="4" dir="auto" required minlength="10"<?php sm_theme_aria( 'message' ); ?>><?php echo esc_textarea( sm_theme_old( 'message' ) ); ?></textarea><?php sm_theme_error( 'message' ); ?></div>
			<button class="sm-btn sm-btn--accent" type="submit"><?php sm_icon( 'mail' ); ?><?php echo esc_html( 'service' === $args['kind'] ? __( 'Send the request', 'sm-marketplace' ) : __( 'Send enquiry', 'sm-marketplace' ) ); ?></button>
			<p class="sm-help"><?php esc_html_e( 'The company receives your name, phone and e-mail to answer you.', 'sm-marketplace' ); ?></p>
		</form>
	<?php endif; ?>
</section>

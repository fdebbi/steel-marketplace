<?php
/**
 * Enquiry form (listing contact or service request), processed by sm-core.
 *
 * Contact requires an account (Phase 0): guests see sign-in links; accounts
 * without the right (e.g. partner engineers on a listing) see the reason.
 *
 * @var array $args { kind: listing|service, module, entity_id, company, subject, title, id }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$args = wp_parse_args( $args, array( 'kind' => 'listing', 'module' => '', 'entity_id' => 0, 'company' => '', 'subject' => '', 'title' => __( 'Send an enquiry', 'sm-marketplace' ), 'id' => 'sm-enquiry', 'dialog' => false, 'seller' => '' ) );
// 0.38.0: on listing pages the form is a window opened by "Contact" (signed-in accounts only, no contact details shown).
$is_dialog = ! empty( $args['dialog'] );
$reopen    = $is_dialog && '' === sm_theme_old( 'product_id' ) && sm_theme_notice_list(); // Sent, or to correct: shown in the window.
$cap  = 'service' === $args['kind'] ? 'service.request' : 'buy';
$here = sm_theme_core_active() && class_exists( 'SM\\Core\\I18n\\Language' ) ? SM\Core\I18n\Language::url_for( SM\Core\I18n\Language::current_unprefixed_url(), sm_theme_current_language() ) : '';
$acct = is_user_logged_in() ? sm_theme_account() : null;
?>
<?php if ( $is_dialog ) : ?>
<dialog class="smc-dialog sm-contact-dialog" id="<?php echo esc_attr( $args['id'] ); ?>" aria-labelledby="<?php echo esc_attr( $args['id'] ); ?>-t"<?php echo $reopen ? ' data-smc-autoopen' : ''; ?>>
	<div class="smc-dialog__head">
		<div>
			<h2 id="<?php echo esc_attr( $args['id'] ); ?>-t"><?php echo esc_html( '' !== (string) $args['seller'] ? sprintf( /* translators: %s: company name. */ __( 'Contact %s', 'sm-marketplace' ), $args['seller'] ) : __( 'Contact the seller', 'sm-marketplace' ) ); ?></h2>
			<?php if ( is_user_logged_in() ) : ?><p><?php esc_html_e( 'Your message is sent with the name and the phone of your account.', 'sm-marketplace' ); ?></p><?php endif; ?>
		</div>
		<button type="button" class="smc-dialog__close" data-smc-close aria-label="<?php esc_attr_e( 'Close', 'sm-marketplace' ); ?>"><?php sm_icon( 'close' ); ?></button>
	</div>
	<div class="smc-dialog__body">
<?php else : ?>
<section class="sm-panel" id="<?php echo esc_attr( $args['id'] ); ?>">
	<?php if ( '' !== (string) $args['title'] ) : ?><h2 class="sm-panel__title"><?php echo esc_html( $args['title'] ); ?></h2><?php endif; ?>
<?php endif; ?>
	<?php
	if ( '' === sm_theme_old( 'product_id' ) ) { // Not the messages of the "Buy / offer" form (0.24.0).
		sm_theme_notices();
	}
	?>

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

			<div class="sm-field"><label for="<?php echo esc_attr( $args['id'] ); ?>-subject"><?php esc_html_e( 'Subject', 'sm-marketplace' ); ?></label><input id="<?php echo esc_attr( $args['id'] ); ?>-subject" name="subject" dir="auto" maxlength="200" value="<?php echo esc_attr( sm_theme_old( 'subject', $args['subject'] ) ); ?>"></div>
			<div class="sm-field"><label for="<?php echo esc_attr( $args['id'] ); ?>-msg"><?php esc_html_e( 'Message', 'sm-marketplace' ); ?></label><textarea id="<?php echo esc_attr( $args['id'] ); ?>-msg" name="message" rows="4" dir="auto" required minlength="10"<?php sm_theme_aria( 'message' ); ?>><?php echo esc_textarea( sm_theme_old( 'message' ) ); ?></textarea><?php sm_theme_error( 'message' ); ?></div>
			<button class="sm-btn sm-btn--accent<?php echo $is_dialog ? ' sm-btn--block' : ''; ?>" type="submit"><?php sm_icon( 'mail' ); ?><?php echo esc_html( 'service' === $args['kind'] ? __( 'Send the request', 'sm-marketplace' ) : __( 'Send the message', 'sm-marketplace' ) ); ?></button>
			<p class="sm-help"><?php esc_html_e( 'The company receives your message with the name, phone and e-mail of your account, and answers you.', 'sm-marketplace' ); ?></p>
		</form>
	<?php endif; ?>
<?php if ( $is_dialog ) : ?>
	</div>
</dialog>
<?php else : ?>
</section>
<?php endif; ?>

<?php
/**
 * One order (Phase 10): what to pay, the payment details of the platform, the
 * form to send the proof of payment, then the state of the check by the team.
 * Address: /compte/commande/?order=ID
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$sub   = sm_theme_data( 'subscription' );
$order = is_array( $sub ) && ! empty( $sub['order'] ) ? $sub['order'] : null;
$back  = sm_page_url( 'account', array( 'view' => ! empty( $sub['has_plans'] ) ? 'plan' : 'ai-credits' ) );
if ( ! $order ) {
	sm_component( 'empty-state', array( 'icon' => 'card', 'title' => __( 'Order not found', 'sm-marketplace' ), 'text' => __( 'This order does not exist or belongs to another account.', 'sm-marketplace' ) ) );
	return;
}
$pay     = $sub['payment'];
$methods = (array) $sub['methods'];
$tone    = array( 'awaiting' => 'warn', 'review' => 'info', 'paid' => 'ok', 'rejected' => 'muted', 'cancelled' => 'muted' );
$open    = in_array( $order['status'], array( 'awaiting', 'review' ), true );
?>
<p><a class="sm-link" href="<?php echo esc_url( $back ); ?>"><?php sm_icon( 'chevron-start' ); ?><?php esc_html_e( 'Back', 'sm-marketplace' ); ?></a></p>

<section class="sm-panel sm-order">
	<div class="sm-order__head">
		<div>
			<p class="sm-subnow__kicker" dir="ltr"><?php echo esc_html( $order['ref'] ); ?></p>
			<h2 class="sm-subnow__name"><?php echo esc_html( $order['label'] ); ?></h2>
		</div>
		<div class="sm-order__amount">
			<strong><?php echo esc_html( sm_money( $order['amount'] ) ); ?></strong>
			<small><?php esc_html_e( 'TVA included', 'sm-marketplace' ); ?></small>
		</div>
	</div>
	<p><span class="sm-status sm-status--<?php echo esc_attr( $tone[ $order['status'] ] ?? 'muted' ); ?>"><?php echo esc_html( $order['status_label'] ); ?></span></p>
	<?php if ( 'review' === $order['status'] ) : ?>
		<p><?php esc_html_e( 'Your proof of payment was received. The platform team checks it, usually within one working day; you are told by e-mail.', 'sm-marketplace' ); ?></p>
	<?php elseif ( 'paid' === $order['status'] ) : ?>
		<p><?php esc_html_e( 'Payment confirmed. Thank you.', 'sm-marketplace' ); ?></p>
		<p><a class="sm-btn sm-btn--primary" href="<?php echo esc_url( $order['invoice'] ); ?>"><?php sm_icon( 'file' ); ?><span><?php esc_html_e( 'See the invoice', 'sm-marketplace' ); ?></span></a></p>
	<?php elseif ( 'rejected' === $order['status'] ) : ?>
		<p>
			<?php
			/* translators: %s: reason given by the platform team. */
			echo esc_html( sprintf( __( 'The payment could not be confirmed. Reason: %s', 'sm-marketplace' ), $order['reason'] ) );
			?>
		</p>
	<?php endif; ?>
</section>

<?php if ( $open ) : ?>
	<section class="sm-panel">
		<h2 class="sm-panel__title"><?php esc_html_e( '1. Pay', 'sm-marketplace' ); ?></h2>
		<p>
			<?php
			/* translators: 1: amount, 2: order reference. */
			printf( esc_html__( 'Pay %1$s and write the reference %2$s on the payment so we can find it.', 'sm-marketplace' ), '<strong>' . esc_html( sm_money( $order['amount'] ) ) . '</strong>', '<strong dir="ltr">' . esc_html( $order['ref'] ) . '</strong>' ); // phpcs:ignore WordPress.Security.EscapeOutput
			?>
		</p>
		<dl class="sm-paydetails">
			<?php if ( $pay['legal_name'] ) : ?>
				<div><dt><?php esc_html_e( 'Beneficiary', 'sm-marketplace' ); ?></dt><dd><?php echo esc_html( $pay['legal_name'] ); ?></dd></div>
			<?php endif; ?>
			<?php if ( isset( $methods['transfer'] ) && $pay['rib'] ) : ?>
				<div><dt><?php esc_html_e( 'Bank transfer', 'sm-marketplace' ); ?></dt><dd><?php echo esc_html( $pay['bank_name'] ); ?><br><span class="sm-copy" dir="ltr"><?php echo esc_html( $pay['rib'] ); ?></span></dd></div>
			<?php endif; ?>
			<?php if ( isset( $methods['ccp'] ) && $pay['ccp'] ) : ?>
				<div><dt><?php esc_html_e( 'CCP payment', 'sm-marketplace' ); ?></dt><dd><span class="sm-copy" dir="ltr"><?php echo esc_html( $pay['ccp'] ); ?></span></dd></div>
			<?php endif; ?>
			<?php if ( isset( $methods['baridimob'] ) && $pay['baridimob'] ) : ?>
				<div><dt><?php esc_html_e( 'BaridiMob', 'sm-marketplace' ); ?></dt><dd><span class="sm-copy" dir="ltr"><?php echo esc_html( $pay['baridimob'] ); ?></span></dd></div>
			<?php endif; ?>
			<?php if ( isset( $methods['cheque'] ) && $pay['cheque_to'] ) : ?>
				<div><dt><?php esc_html_e( 'Cheque', 'sm-marketplace' ); ?></dt><dd><?php echo esc_html( $pay['cheque_to'] ); ?></dd></div>
			<?php endif; ?>
		</dl>
		<?php if ( ! $pay['rib'] && ! $pay['ccp'] && ! $pay['baridimob'] && ! $pay['cheque_to'] ) : ?>
			<p class="sm-notice sm-notice--warn"><?php esc_html_e( 'The payment details are not published yet. Contact the platform team to get them.', 'sm-marketplace' ); ?></p>
		<?php endif; ?>
		<?php if ( $pay['instructions'] ) : ?>
			<p class="sm-muted"><?php echo nl2br( esc_html( $pay['instructions'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
		<?php endif; ?>
	</section>

	<?php sm_theme_form_open( 'sub_proof', array( 'class' => 'sm-form sm-panel', 'enctype' => 'multipart/form-data' ) ); ?>
		<h2 class="sm-panel__title"><?php esc_html_e( '2. Send the proof of payment', 'sm-marketplace' ); ?></h2>
		<input type="hidden" name="order_id" value="<?php echo esc_attr( $order['id'] ); ?>">
		<fieldset class="sm-field">
			<legend class="sm-field__legend"><?php esc_html_e( 'How did you pay?', 'sm-marketplace' ); ?></legend>
			<div class="sm-segment">
				<?php foreach ( $methods as $key => $label ) : ?>
					<label><input type="radio" name="method" value="<?php echo esc_attr( $key ); ?>" <?php checked( sm_theme_old( 'method', '' ), $key ); ?>><span><?php echo esc_html( $label ); ?></span></label>
				<?php endforeach; ?>
			</div>
			<?php sm_theme_error( 'method' ); ?>
		</fieldset>
		<div class="sm-field">
			<label class="sm-upload sm-upload--small">
				<input type="file" name="proof[]" accept=".pdf,image/jpeg,image/png,image/webp" required data-sm-filename>
				<?php sm_icon( 'upload', 'sm-upload__icon' ); ?>
				<strong><?php esc_html_e( 'Receipt, transfer slip or screenshot of the payment', 'sm-marketplace' ); ?> *</strong>
				<small data-sm-filename-label><?php esc_html_e( 'PDF, JPG or PNG, 10 MB maximum', 'sm-marketplace' ); ?></small>
			</label>
			<?php sm_theme_error( 'proof' ); ?>
		</div>
		<div class="sm-field"><label for="sub-note"><?php esc_html_e( 'Note for the platform team (optional)', 'sm-marketplace' ); ?></label><textarea id="sub-note" name="note" rows="2" dir="auto"><?php echo esc_textarea( sm_theme_old( 'note', '' ) ); ?></textarea></div>
		<p class="sm-help"><?php esc_html_e( 'The proof is stored privately and only seen by the platform team.', 'sm-marketplace' ); ?></p>
		<button class="sm-btn sm-btn--accent" type="submit"><?php sm_icon( 'upload' ); ?><span><?php echo 'review' === $order['status'] ? esc_html__( 'Send another proof', 'sm-marketplace' ) : esc_html__( 'Send the proof', 'sm-marketplace' ); ?></span></button>
	</form>

	<?php if ( 'awaiting' === $order['status'] ) : ?>
		<?php sm_theme_form_open( 'sub_cancel', array( 'class' => 'sm-order__cancel' ) ); ?>
			<input type="hidden" name="order_id" value="<?php echo esc_attr( $order['id'] ); ?>">
			<button class="sm-btn sm-btn--ghost sm-btn--sm" type="submit"><?php esc_html_e( 'Cancel this order', 'sm-marketplace' ); ?></button>
		</form>
	<?php endif; ?>
<?php endif; ?>

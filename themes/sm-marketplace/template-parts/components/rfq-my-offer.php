<?php
/**
 * The supplier's own offer on a request: status, figures, withdraw.
 *
 * @var array $args { offer: array, item: array, compact?: bool }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$o      = $args['offer'];
$r      = $args['item'];
$labels = sm_offer_status_labels();
?>
<section class="sm-panel smq-mine smq-mine--<?php echo esc_attr( $o['status'] ); ?>">
	<h2 class="sm-panel__title"><?php esc_html_e( 'Your offer', 'sm-marketplace' ); ?> <span class="smq-ostatus smq-ostatus--<?php echo esc_attr( $o['status'] ); ?>"><?php echo esc_html( $labels[ $o['status'] ] ?? $o['status'] ); ?></span></h2>
	<dl class="sm-spec sm-spec--compact">
		<div><dt><?php esc_html_e( 'Total price', 'sm-marketplace' ); ?></dt><dd><strong><?php echo esc_html( sm_money( $o['total_price'] ) ); ?></strong></dd></div>
		<?php if ( $o['price_per_kg'] > 0 ) : ?><div><dt><?php esc_html_e( 'Price per kg', 'sm-marketplace' ); ?></dt><dd><?php echo sm_per_unit( $o['price_per_kg'], 'kg', false, 2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd></div><?php endif; ?>
		<div><dt><?php esc_html_e( 'Delivery', 'sm-marketplace' ); ?></dt><dd><?php echo esc_html( sprintf( /* translators: %d: days. */ _n( '%d day', '%d days', $o['delivery_days'], 'sm-marketplace' ), $o['delivery_days'] ) ); ?><?php echo $o['delivery_included'] ? ' · ' . esc_html__( 'included', 'sm-marketplace' ) : ''; ?></dd></div>
		<div><dt><?php esc_html_e( 'Valid until', 'sm-marketplace' ); ?></dt><dd><?php echo esc_html( sm_date( $o['valid_until'] ) ); ?></dd></div>
	</dl>
	<?php if ( 'accepted' === $o['status'] ) : ?>
		<p class="sm-notice sm-notice--success"><?php sm_icon( 'check' ); ?><span><?php esc_html_e( 'The buyer accepted your offer. Contact him to confirm the order.', 'sm-marketplace' ); ?></span></p>
	<?php endif; ?>
	<?php if ( in_array( $o['status'], array( 'submitted', 'shortlisted' ), true ) && $r['is_open'] ) : ?>
		<?php sm_theme_form_open( 'rfq_offer_action', array( 'class' => 'smp-act' ) ); ?>
			<input type="hidden" name="offer_id" value="<?php echo esc_attr( $o['id'] ); ?>"><input type="hidden" name="do" value="withdraw">
			<button class="sm-btn sm-btn--ghost sm-btn--sm" type="submit" data-sm-confirm="<?php esc_attr_e( 'Withdraw your offer?', 'sm-marketplace' ); ?>"><?php esc_html_e( 'Withdraw my offer', 'sm-marketplace' ); ?></button>
		</form>
	<?php endif; ?>
</section>

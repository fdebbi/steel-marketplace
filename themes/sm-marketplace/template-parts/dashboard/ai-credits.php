<?php
/**
 * "AI credits". With sm-subscriptions (Phase 10): the monthly credits of the
 * plan (or of the account without plan), the bought credits left, the packs to
 * buy and their orders. Without it: the use of each AI tool this month against
 * the allowance set by the administrator (sm-ai).
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$ai = sm_theme_data( 'ai_tools' );
if ( ! is_array( $ai ) || empty( $ai['tools'] ) ) {
	sm_component( 'empty-state', array( 'icon' => 'ai', 'title' => __( 'AI credits', 'sm-marketplace' ), 'text' => __( 'This module is not active yet.', 'sm-marketplace' ) ) );
	return;
}
$sub = sm_theme_data( 'subscription' );

if ( is_array( $sub ) && isset( $sub['credits'] ) ) :
	$cr     = $sub['credits'];
	$orders = array_values( array_filter( $sub['orders'], static fn( $o ) => 'pack' === $o['kind'] ) );
	?>
	<section class="sm-panel sm-credits">
		<h2 class="sm-panel__title"><?php esc_html_e( 'Your AI credits', 'sm-marketplace' ); ?></h2>
		<?php if ( null === $cr['total_left'] ) : ?>
			<p><?php esc_html_e( 'No limit on your account.', 'sm-marketplace' ); ?></p>
		<?php else : ?>
			<div class="sm-credits__total">
				<strong><bdi><?php echo esc_html( sm_number( $cr['total_left'] ) ); ?></bdi></strong>
				<span><?php echo esc_html( _n( 'credit left', 'credits left', (int) $cr['total_left'], 'sm-marketplace' ) ); ?></span>
			</div>
			<?php $pct = min( 100, round( $cr['used'] / max( 1, (int) $cr['monthly'] ) * 100 ) ); ?>
			<div class="sm-meter<?php echo $pct >= 100 ? ' is-full' : ''; ?>">
				<div class="sm-meter__label"><span><?php esc_html_e( 'Credits of the month', 'sm-marketplace' ); ?></span><span><bdi dir="ltr"><?php echo esc_html( min( $cr['used'], (int) $cr['monthly'] ) . ' / ' . (int) $cr['monthly'] ); ?></bdi></span></div>
				<div class="sm-meter__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( $pct ); ?>" aria-label="<?php esc_attr_e( 'Credits of the month', 'sm-marketplace' ); ?>"><span style="inline-size:<?php echo esc_attr( $pct ); ?>%"></span></div>
			</div>
			<p class="sm-muted">
				<?php
				/* translators: %d: bought credits left. */
				echo esc_html( sprintf( _n( 'Bought credits left: %d. They are used once the credits of the month are spent, and they do not expire.', 'Bought credits left: %d. They are used once the credits of the month are spent, and they do not expire.', (int) $cr['bought'], 'sm-marketplace' ), (int) $cr['bought'] ) );
				?>
			</p>
			<p class="sm-help"><?php esc_html_e( 'One use of an AI tool takes one credit. The credits of the month start again on the 1st.', 'sm-marketplace' ); ?></p>
		<?php endif; ?>
	</section>

	<?php if ( ! empty( $sub['packs'] ) ) : ?>
		<section class="sm-section-block">
			<h2 class="sm-panel__title"><?php esc_html_e( 'Buy AI credits', 'sm-marketplace' ); ?></h2>
			<div class="sm-packs">
				<?php foreach ( $sub['packs'] as $pack ) : ?>
					<article class="sm-pack">
						<p class="sm-pack__credits"><strong><bdi><?php echo esc_html( sm_number( $pack['credits'] ) ); ?></bdi></strong> <span><?php esc_html_e( 'AI credits', 'sm-marketplace' ); ?></span></p>
						<p class="sm-pack__price"><?php echo esc_html( sm_money( $pack['price'] ) ); ?></p>
						<?php if ( ! empty( $sub['can_order'] ) ) : ?>
							<?php sm_theme_form_open( 'sub_order' ); ?>
								<input type="hidden" name="kind" value="pack">
								<input type="hidden" name="item" value="<?php echo esc_attr( $pack['code'] ); ?>">
								<button class="sm-btn sm-btn--primary sm-btn--block" type="submit"><?php esc_html_e( 'Buy', 'sm-marketplace' ); ?></button>
							</form>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
			<?php if ( empty( $sub['can_order'] ) ) : ?>
				<p class="sm-help"><?php esc_html_e( 'Only the owner or a manager of the account can order.', 'sm-marketplace' ); ?></p>
			<?php else : ?>
				<p class="sm-help"><?php esc_html_e( 'Prices include TVA. The credits are added when our team has checked the payment.', 'sm-marketplace' ); ?></p>
			<?php endif; ?>
		</section>
	<?php endif; ?>

	<?php sm_component( 'order-history', array( 'orders' => $orders ) ); ?>
	<p><a class="sm-btn sm-btn--ghost" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'ai' ) ) ); ?>"><?php sm_icon( 'ai' ); ?><span><?php esc_html_e( 'Open the assistant', 'sm-marketplace' ); ?></span></a></p>
	<?php
	return;
endif;
?>
<section class="sm-panel">
	<h2 class="sm-panel__title"><?php esc_html_e( 'This month', 'sm-marketplace' ); ?></h2>
	<?php foreach ( $ai['tools'] as $t ) : ?>
		<?php $used = (int) ( $t['used'] ?? 0 ); ?>
		<?php if ( ! empty( $t['quota'] ) ) : ?>
			<?php $max = (int) $t['quota']; ?>
			<div class="sm-meter">
				<div class="sm-meter__label"><span><?php echo esc_html( $t['label'] ); ?></span><span><bdi dir="ltr"><?php echo esc_html( $used . ' / ' . $max ); ?></bdi></span></div>
				<div class="sm-meter__bar" role="progressbar" aria-valuemin="0" aria-valuemax="<?php echo esc_attr( $max ); ?>" aria-valuenow="<?php echo esc_attr( $used ); ?>" aria-label="<?php echo esc_attr( $t['label'] ); ?>"><span style="inline-size:<?php echo esc_attr( min( 100, round( $used / max( 1, $max ) * 100 ) ) ); ?>%"></span></div>
			</div>
		<?php else : ?>
			<div class="sm-meter">
				<div class="sm-meter__label"><span><?php echo esc_html( $t['label'] ); ?></span><span><?php echo esc_html( sprintf( /* translators: %d: uses this month. */ _n( '%d use, no monthly limit', '%d uses, no monthly limit', $used, 'sm-marketplace' ), $used ) ); ?></span></div>
			</div>
		<?php endif; ?>
	<?php endforeach; ?>
	<p class="sm-muted"><?php esc_html_e( 'The counters start again on the 1st of each month.', 'sm-marketplace' ); ?></p>
	<p><a class="sm-btn sm-btn--ghost" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'ai' ) ) ); ?>"><?php sm_icon( 'ai' ); ?><span><?php esc_html_e( 'Open the assistant', 'sm-marketplace' ); ?></span></a></p>
</section>

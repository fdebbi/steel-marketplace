<?php
/**
 * "Plan" (Phase 10): the plan in force and what the account uses of it, the
 * founding members offer, the plans with their order buttons (month / year),
 * and the history of orders with their invoices. Everything comes from
 * sm-subscriptions (resource subscription); nothing is hardcoded here.
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$sub = sm_theme_data( 'subscription' );
if ( ! is_array( $sub ) || ! isset( $sub['has_plans'] ) ) {
	sm_component( 'empty-state', array( 'icon' => 'card', 'title' => __( 'Your plan', 'sm-marketplace' ), 'text' => __( 'All the features of the platform are open to your account during the launch.', 'sm-marketplace' ) ) );
	return;
}
if ( empty( $sub['has_plans'] ) ) {
	?>
	<section class="sm-panel">
		<h2 class="sm-panel__title"><?php esc_html_e( 'Your plan', 'sm-marketplace' ); ?></h2>
		<p><?php esc_html_e( 'Your type of account has no plan: the platform is free for you. You can buy packs of AI credits when your monthly credits are used.', 'sm-marketplace' ); ?></p>
		<p><a class="sm-btn sm-btn--ghost" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'ai-credits' ) ) ); ?>"><?php sm_icon( 'ai' ); ?><span><?php esc_html_e( 'AI credits', 'sm-marketplace' ); ?></span></a></p>
	</section>
	<?php
	return;
}

$cur    = $sub['current'];
$labels = $sub['labels'];
$usage  = $sub['usage'];
$open   = array_filter( $sub['orders'], static fn( $o ) => in_array( $o['status'], array( 'awaiting', 'review' ), true ) );
$meters = array(
	'listings'   => $usage['listings'],
	'weight_t'   => $usage['weight_t'],
	'buildings'  => $usage['buildings'],
	'services'   => $usage['services'],
	'portfolio'  => $usage['portfolio'],
	'rfq_offers' => $usage['rfq_offers'],
);
if ( 'professional' === $sub['class'] ) {
	unset( $meters['buildings'] );
}
?>
<?php if ( $open ) : ?>
	<?php $o = reset( $open ); ?>
	<div class="sm-notice sm-notice--info" role="status">
		<?php sm_icon( 'info' ); ?>
		<div>
			<?php
			echo esc_html(
				'review' === $o['status']
					/* translators: 1: order reference, 2: label. */
					? sprintf( __( 'Order %1$s (%2$s): your proof of payment is being checked.', 'sm-marketplace' ), $o['ref'], $o['label'] )
					/* translators: 1: order reference, 2: label. */
					: sprintf( __( 'Order %1$s (%2$s) is waiting for your payment.', 'sm-marketplace' ), $o['ref'], $o['label'] )
			);
			?>
			<a class="sm-link" href="<?php echo esc_url( $o['url'] ); ?>"><?php esc_html_e( 'See the order', 'sm-marketplace' ); ?></a>
		</div>
	</div>
<?php endif; ?>

<?php if ( ! empty( $sub['founders']['on'] ) && 'free' === $cur['code'] && empty( $sub['founders']['member'] ) ) : ?>
	<section class="sm-founders">
		<?php sm_icon( 'star' ); ?>
		<div>
			<h2><?php esc_html_e( 'Founding members offer', 'sm-marketplace' ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: 1: plan name, 2: months, 3: places left. */
					esc_html( _n( 'Have your documents checked and get the plan %1$s free for %2$d months, then keep its price. %3$d place left.', 'Have your documents checked and get the plan %1$s free for %2$d months, then keep its price. %3$d places left.', (int) $sub['founders']['places_left'], 'sm-marketplace' ) ),
					'<strong>' . esc_html( $sub['founders']['plans'][ $sub['class'] ] ?? '' ) . '</strong>', // phpcs:ignore WordPress.Security.EscapeOutput
					(int) $sub['founders']['months'],
					(int) $sub['founders']['places_left']
				);
				?>
			</p>
		</div>
		<a class="sm-btn sm-btn--accent" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'verification' ) ) ); ?>"><?php esc_html_e( 'Verify my account', 'sm-marketplace' ); ?></a>
	</section>
<?php endif; ?>

<section class="sm-panel sm-subnow">
	<div class="sm-subnow__head">
		<div>
			<p class="sm-subnow__kicker"><?php esc_html_e( 'Your plan', 'sm-marketplace' ); ?></p>
			<h2 class="sm-subnow__name"><?php echo esc_html( $cur['name'] ); ?>
				<?php if ( $cur['founder'] ) : ?><span class="sm-plan__tag"><?php esc_html_e( 'Founding member', 'sm-marketplace' ); ?></span><?php endif; ?>
			</h2>
		</div>
		<p class="sm-subnow__end">
			<?php
			if ( $cur['ends_at'] ) {
				/* translators: %s: date. */
				echo esc_html( sprintf( __( 'Paid until %s', 'sm-marketplace' ), sm_date( $cur['ends_at'] ) ) );
			} else {
				esc_html_e( 'Free, without time limit', 'sm-marketplace' );
			}
			?>
		</p>
	</div>
	<div class="sm-subnow__meters">
		<?php foreach ( $meters as $key => $used ) : ?>
			<?php
			$max = (int) ( $cur['limits'][ $key ] ?? 0 );
			$lbl = $labels[ $key ] ?? $key;
			$txt = -1 === $max ? sm_plan_value( $key, $used ) . ' · ' . __( 'unlimited', 'sm-marketplace' ) : sm_plan_value( $key, $used ) . ' / ' . sm_plan_value( $key, $max );
			$pct = -1 === $max ? 0 : min( 100, round( (float) $used / max( 1, $max ) * 100 ) );
			?>
			<div class="sm-meter<?php echo $pct >= 100 ? ' is-full' : ''; ?>">
				<div class="sm-meter__label"><span><?php echo esc_html( $lbl ); ?></span><span><bdi dir="ltr"><?php echo esc_html( $txt ); ?></bdi></span></div>
				<div class="sm-meter__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( $pct ); ?>" aria-label="<?php echo esc_attr( $lbl ); ?>"><span style="inline-size:<?php echo esc_attr( $pct ); ?>%"></span></div>
			</div>
		<?php endforeach; ?>
		<?php $cr = $sub['credits']; ?>
		<?php if ( null !== $cr['monthly'] ) : ?>
			<?php $pct = min( 100, round( $cr['used'] / max( 1, $cr['monthly'] ) * 100 ) ); ?>
			<div class="sm-meter<?php echo $pct >= 100 ? ' is-full' : ''; ?>">
				<div class="sm-meter__label"><span><?php echo esc_html( $labels['ai_credits'] ); ?></span><span><bdi dir="ltr"><?php echo esc_html( min( $cr['used'], $cr['monthly'] ) . ' / ' . $cr['monthly'] ); ?></bdi><?php if ( $cr['bought'] ) : ?> · <?php echo esc_html( sprintf( /* translators: %d: bought credits left. */ _n( '+%d bought', '+%d bought', (int) $cr['bought'], 'sm-marketplace' ), (int) $cr['bought'] ) ); ?><?php endif; ?></span></div>
				<div class="sm-meter__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( $pct ); ?>" aria-label="<?php echo esc_attr( $labels['ai_credits'] ); ?>"><span style="inline-size:<?php echo esc_attr( $pct ); ?>%"></span></div>
			</div>
		<?php endif; ?>
	</div>
	<ul class="sm-subnow__facts">
		<li><?php sm_icon( $cur['limits']['contacts'] ? 'check' : 'close' ); ?><?php echo esc_html( $cur['limits']['contacts'] ? __( 'Your phone, e-mail, WhatsApp and website are shown on your public page.', 'sm-marketplace' ) : __( 'Your phone, e-mail, WhatsApp and website are hidden on your public page; buyers reach you through the contact form.', 'sm-marketplace' ) ); ?></li>
		<?php if ( ! empty( $cur['limits']['featured'] ) ) : ?>
			<li><?php sm_icon( 'star' ); ?><?php esc_html_e( 'Your account takes turns in the featured row of the home page.', 'sm-marketplace' ); ?></li>
		<?php endif; ?>
	</ul>
	<p class="sm-help"><?php esc_html_e( 'When a paid period ends, what goes beyond the free plan is paused, the oldest first. Nothing is deleted.', 'sm-marketplace' ); ?></p>
</section>

<section class="sm-section-block">
	<h2 class="sm-panel__title"><?php esc_html_e( 'Plans', 'sm-marketplace' ); ?></h2>
	<p class="sm-muted"><?php esc_html_e( 'Prices include TVA. Payment by bank transfer, CCP, BaridiMob or cheque; the plan starts when our team has checked the payment.', 'sm-marketplace' ); ?></p>
	<div class="sm-plans">
		<?php foreach ( $sub['plans'] as $i => $p ) : ?>
			<?php sm_component( 'plan-card', array( 'plan' => $p, 'labels' => $labels, 'class' => $sub['class'], 'mode' => 'account', 'can_order' => $sub['can_order'], 'highlight' => 1 === $i ) ); ?>
		<?php endforeach; ?>
	</div>
</section>

<?php sm_component( 'order-history', array( 'orders' => $sub['orders'] ) ); ?>

<?php
/**
 * One plan card (Phase 10), on the public pricing page and in the account.
 * Prices and limits come from sm-subscriptions (resources plans / subscription).
 *
 * @var array $args {
 *     plan:      array  { code, name, price_month, price_year, free, limits, current }
 *     labels:    array  limit key => label
 *     class:     string company|professional
 *     mode:      string public|account
 *     can_order: bool   (account) the user may order for the company
 *     highlight: bool
 *     current:   string (account) code of the plan in force
 * }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$plan      = $args['plan'];
$labels    = (array) ( $args['labels'] ?? array() );
$mode      = $args['mode'] ?? 'public';
$is_cur    = 'account' === $mode && ! empty( $plan['current'] );
$highlight = ! empty( $args['highlight'] );
$limits    = (array) $plan['limits'];
if ( 'professional' === ( $args['class'] ?? '' ) ) {
	unset( $labels['buildings'] );
}
$saving = sm_plan_saving( $plan );
?>
<article class="sm-plan<?php echo $highlight ? ' sm-plan--highlight' : ''; ?><?php echo $is_cur ? ' is-current' : ''; ?>" data-plan="<?php echo esc_attr( $plan['code'] ); ?>">
	<header class="sm-plan__head">
		<h3 class="sm-plan__name"><?php echo esc_html( $plan['name'] ); ?></h3>
		<?php if ( $is_cur ) : ?><span class="sm-plan__tag"><?php esc_html_e( 'Your plan', 'sm-marketplace' ); ?></span><?php endif; ?>
	</header>
	<p class="sm-plan__price">
		<?php if ( $plan['free'] ) : ?>
			<strong><?php echo esc_html( sm_money( 0 ) ); ?></strong>
		<?php else : ?>
			<strong><bdi><?php echo esc_html( sm_money( $plan['price_month'] ) ); ?></bdi></strong><span><?php esc_html_e( '/ month', 'sm-marketplace' ); ?></span>
		<?php endif; ?>
	</p>
	<?php if ( ! $plan['free'] ) : ?>
		<p class="sm-plan__year">
			<?php
			/* translators: %s: yearly price. */
			echo esc_html( sprintf( __( 'or %s per year', 'sm-marketplace' ), sm_money( $plan['price_year'] ) ) );
			if ( $saving > 0 ) {
				/* translators: %s: amount saved. */
				echo ' · <span class="sm-plan__save">' . esc_html( sprintf( __( '%s saved', 'sm-marketplace' ), sm_money( $saving ) ) ) . '</span>';
			}
			?>
		</p>
	<?php endif; ?>
	<ul class="sm-plan__limits">
		<?php foreach ( $labels as $key => $label ) : ?>
			<?php
			$v   = $limits[ $key ] ?? 0;
			$yes = in_array( $key, array( 'contacts', 'featured' ), true );
			?>
			<li class="<?php echo $yes && ! $v ? 'is-off' : ''; ?>">
				<?php if ( $yes ) : ?>
					<?php sm_icon( $v ? 'check' : 'close' ); ?><span><?php echo esc_html( $label ); ?></span>
				<?php else : ?>
					<span><?php echo esc_html( $label ); ?></span><strong><bdi><?php echo esc_html( sm_plan_value( $key, $v ) ); ?></bdi></strong>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
	<div class="sm-plan__actions">
		<?php if ( 'public' === $mode ) : ?>
			<a class="sm-btn <?php echo $highlight ? 'sm-btn--accent' : 'sm-btn--primary-outline'; ?> sm-btn--block" href="<?php echo esc_url( is_user_logged_in() ? sm_page_url( 'account', array( 'view' => 'plan' ) ) : sm_page_url( 'register' ) ); ?>">
				<?php $plan['free'] ? esc_html_e( 'Start free', 'sm-marketplace' ) : esc_html_e( 'Choose', 'sm-marketplace' ); ?>
			</a>
		<?php elseif ( $plan['free'] ) : ?>
			<p class="sm-help"><?php echo $is_cur ? esc_html__( 'Free, without time limit.', 'sm-marketplace' ) : esc_html__( 'Your account comes back to this plan when a paid period ends.', 'sm-marketplace' ); ?></p>
		<?php elseif ( empty( $args['can_order'] ) ) : ?>
			<p class="sm-help"><?php esc_html_e( 'Only the owner or a manager of the account can order.', 'sm-marketplace' ); ?></p>
		<?php else : ?>
			<?php foreach ( array( 'month', 'year' ) as $period ) : ?>
				<?php sm_theme_form_open( 'sub_order', array( 'class' => 'sm-plan__order' ) ); ?>
					<input type="hidden" name="kind" value="plan">
					<input type="hidden" name="item" value="<?php echo esc_attr( $plan['code'] ); ?>">
					<input type="hidden" name="period" value="<?php echo esc_attr( $period ); ?>">
					<button class="sm-btn <?php echo 'year' === $period ? ( $highlight ? 'sm-btn--accent' : 'sm-btn--primary' ) : 'sm-btn--ghost'; ?> sm-btn--block" type="submit">
						<?php
						$price = 'year' === $period ? $plan['price_year'] : $plan['price_month'];
						if ( $is_cur ) {
							/* translators: %s: price. */
							$text = 'year' === $period ? __( 'Renew for 1 year · %s', 'sm-marketplace' ) : __( 'Renew for 1 month · %s', 'sm-marketplace' );
						} else {
							/* translators: %s: price. */
							$text = 'year' === $period ? __( 'Pay 1 year · %s', 'sm-marketplace' ) : __( 'Pay 1 month · %s', 'sm-marketplace' );
						}
						echo esc_html( sprintf( $text, sm_money( $price ) ) );
						?>
					</button>
				</form>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</article>

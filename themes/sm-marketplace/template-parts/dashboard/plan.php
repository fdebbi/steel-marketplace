<?php
/**
 * "Plan" (0.17.1). Until the subscriptions module (Phase 10), every account
 * uses the platform for free; the page says so and shows the plan given by
 * the administrator, if any.
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$account = $args['account'] ?? array();
?>
<section class="sm-panel">
	<h2 class="sm-panel__title"><?php esc_html_e( 'Your plan', 'sm-marketplace' ); ?></h2>
	<?php if ( ! empty( $account['plan'] ) ) : ?>
		<p><strong><?php sm_e( $account['plan'] ); ?></strong></p>
	<?php else : ?>
		<p><strong><?php esc_html_e( 'Free during the launch', 'sm-marketplace' ); ?></strong></p>
		<p class="sm-muted"><?php esc_html_e( 'All the features of the platform are open to your account. The subscription plans will arrive soon: you will be told before anything changes.', 'sm-marketplace' ); ?></p>
	<?php endif; ?>
	<?php if ( is_array( sm_theme_data( 'ai_tools' ) ) ) : ?>
		<p><a class="sm-link" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'ai-credits' ) ) ); ?>"><?php esc_html_e( 'See my AI credits', 'sm-marketplace' ); ?></a></p>
	<?php endif; ?>
</section>

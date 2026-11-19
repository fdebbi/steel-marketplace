<?php
/**
 * Why an AI tool cannot be used now, and what to do (0.45.0):
 * login → sign in or create an account; upgrade → see the plans or buy AI
 * credits; blocked → the reason given by the assistant.
 *
 * @var array $args { state: login|upgrade|blocked, reason: string }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$state = $args['state'] ?? 'blocked';
if ( 'ok' === $state ) {
	return;
}
?>
<p class="smai-gate smai-gate--<?php echo esc_attr( $state ); ?>">
	<?php sm_icon( 'upgrade' === $state ? 'ai' : 'lock' ); ?>
	<span>
		<?php if ( 'login' === $state ) : ?>
			<b><?php esc_html_e( 'Sign in to use this tool.', 'sm-marketplace' ); ?></b>
			<a href="<?php echo esc_url( sm_page_url( 'login' ) ); ?>"><?php esc_html_e( 'Sign in', 'sm-marketplace' ); ?></a> ·
			<a href="<?php echo esc_url( sm_page_url( 'register' ) ); ?>"><?php esc_html_e( 'Create an account', 'sm-marketplace' ); ?></a>
		<?php elseif ( 'upgrade' === $state ) : ?>
			<b><?php esc_html_e( 'Upgrade your plan', 'sm-marketplace' ); ?></b> <?php esc_html_e( 'to benefit from the AI assistant, or buy a pack of AI credits.', 'sm-marketplace' ); ?>
			<a href="<?php echo esc_url( sm_page_url( 'pricing' ) ); ?>"><?php esc_html_e( 'See the plans', 'sm-marketplace' ); ?></a> ·
			<a href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'ai-credits' ) ) ); ?>"><?php esc_html_e( 'Buy credits', 'sm-marketplace' ); ?></a>
		<?php else : ?>
			<?php echo esc_html( (string) ( $args['reason'] ?? '' ) ); ?>
		<?php endif; ?>
	</span>
</p>

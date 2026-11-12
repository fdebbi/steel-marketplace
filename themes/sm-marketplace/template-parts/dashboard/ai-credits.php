<?php
/**
 * "AI credits" (0.17.1): what the company used this month on each AI tool,
 * against its monthly allowance set by the administrator (sm-ai). Paid credit
 * packs arrive with the subscriptions (Phase 10).
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
	<p class="sm-muted"><?php esc_html_e( 'The counters start again on the 1st of each month. Packs of AI credits will be offered with the subscriptions.', 'sm-marketplace' ); ?></p>
	<p><a class="sm-btn sm-btn--ghost" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'ai' ) ) ); ?>"><?php sm_icon( 'ai' ); ?><span><?php esc_html_e( 'Open the assistant', 'sm-marketplace' ); ?></span></a></p>
</section>

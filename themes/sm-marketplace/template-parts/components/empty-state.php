<?php
/**
 * Empty state.
 *
 * @var array $args { icon?, title, text?, action_label?, action_url? }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="sm-empty">
	<?php sm_icon( $args['icon'] ?? 'search', 'sm-empty__icon' ); ?>
	<p class="sm-empty__title"><?php echo esc_html( $args['title'] ?? '' ); ?></p>
	<?php if ( ! empty( $args['text'] ) ) : ?><p><?php echo esc_html( $args['text'] ); ?></p><?php endif; ?>
	<?php if ( ! empty( $args['action_url'] ) ) : ?>
		<a class="sm-btn sm-btn--primary" href="<?php echo esc_url( $args['action_url'] ); ?>"><?php echo esc_html( $args['action_label'] ); ?></a>
	<?php endif; ?>
</div>

<?php
/**
 * Link to an AI assistant tool, shown on the forms it can fill.
 *
 * @var array $args { tool: listing|document, text: string, button: string, for?: cutting|rfq|stock (0.51.0) }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$ai = sm_theme_data( 'ai_tools' );
if ( ! is_array( $ai ) || empty( $ai['tools'][ $args['tool'] ]['available'] ) ) {
	return;
}
?>
<aside class="smai-callout">
	<span class="smai-callout__icon" aria-hidden="true"><?php sm_icon( 'ai' ); ?></span>
	<p><?php echo esc_html( $args['text'] ); ?></p>
	<a class="sm-btn sm-btn--ai-outline sm-btn--sm" href="<?php echo esc_url( sm_page_url( 'account', array_filter( array( 'view' => 'ai', 'tool' => $args['tool'], 'for' => (string) ( $args['for'] ?? '' ) ) ) ) ); ?>"><?php sm_icon( 'ai' ); ?><span><?php echo esc_html( $args['button'] ); ?></span><?php sm_ai_badge( 'sm-ai-badge--sm' ); ?></a>
</aside>

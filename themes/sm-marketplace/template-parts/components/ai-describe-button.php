<?php
/**
 * "Write with the AI" button of a description field (0.45.0, sm-ai 0.2.0).
 * The AI writes the description from what is already entered in the form;
 * assets/js/ai-describe.js puts it in the field, where the seller edits it.
 * Without credits or without an account, the button leads to the AI page
 * that explains what to do.
 *
 * @var array $args { target: id of the textarea }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$d = sm_theme_data( 'ai_describe' );
if ( ! is_array( $d ) ) {
	return;
}
if ( 'ok' !== $d['state'] ) : ?>
	<a class="sm-btn sm-btn--ai-outline sm-btn--sm smai-desc-btn" href="<?php echo esc_url( sm_page_url( 'ai' ) . '#ai-describe' ); ?>" title="<?php echo esc_attr( $d['reason'] ); ?>"><?php sm_icon( 'ai' ); ?><span><?php esc_html_e( 'Write with the AI', 'sm-marketplace' ); ?></span><?php sm_ai_badge( 'sm-ai-badge--sm' ); ?></a>
	<?php
	return;
endif;
wp_enqueue_script( 'sm-ai-describe', SM_THEME_URI . '/assets/js/ai-describe.js', array(), sm_theme_asset_version( 'assets/js/ai-describe.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
$cfg = array(
	'target'  => (string) ( $args['target'] ?? '' ),
	'ajax'    => $d['ajax'],
	'nonce'   => $d['nonce'],
	'credits' => (int) $d['credits'],
	'i18n'    => array(
		'busy'      => __( 'The AI is writing…', 'sm-marketplace' ),
		'replace'   => __( 'Replace your description?', 'sm-marketplace' ),
		'replaceTx' => __( 'The AI writes a new description from the information of your listing. You can undo it.', 'sm-marketplace' ),
		'replaceOk' => __( 'Write a new description', 'sm-marketplace' ),
		/* translators: 1: price of the tool (« 1 credit »), 2: AI credits left. */
		'done'      => sprintf( __( 'Text proposed from the information of your listing: check it and change it freely. %1$s used · %2$s left.', 'sm-marketplace' ), sm_ai_price( $d['credits'] ), '%s' ),
		/* translators: %s: price of the tool (« 1 credit »). */
		'doneNoLeft' => sprintf( __( 'Text proposed from the information of your listing: check it and change it freely. %s used.', 'sm-marketplace' ), sm_ai_price( $d['credits'] ) ),
		'undo'      => __( 'Undo', 'sm-marketplace' ),
		'error'     => __( 'The AI could not write the description. Try again in a moment.', 'sm-marketplace' ),
		'simulation' => __( 'Simulation', 'sm-marketplace' ),
		'upgradeTx' => __( 'Upgrade your plan to benefit from the AI assistant, or buy a pack of AI credits.', 'sm-marketplace' ),
		'plans'     => __( 'See the plans', 'sm-marketplace' ),
		'plansUrl'  => sm_page_url( 'pricing' ),
		'buy'       => __( 'Buy credits', 'sm-marketplace' ),
		'buyUrl'    => sm_page_url( 'account', array( 'view' => 'ai-credits' ) ),
	),
);
?>
<button type="button" class="sm-btn sm-btn--ai-outline sm-btn--sm smai-desc-btn" data-smai-describe="<?php echo esc_attr( wp_json_encode( $cfg ) ); ?>"><?php sm_icon( 'ai' ); ?><span data-smai-describe-label><?php esc_html_e( 'Write with the AI', 'sm-marketplace' ); ?></span><?php sm_ai_badge( 'sm-ai-badge--sm' ); ?></button>

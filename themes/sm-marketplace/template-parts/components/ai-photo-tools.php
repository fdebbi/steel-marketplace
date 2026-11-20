<?php
/**
 * AI help in the publication form (0.52.0, sm-ai 0.5.0), from the photos of the
 * form (new files and photos already saved):
 *  - « Recognise from my photos »: up to 3 profiles of the catalogue, « Choose » fills
 *    the type and the size (the weight engine computes the weight);
 *  - « Assess the condition from my photos » (reuse): condition and surface suggested,
 *    what the AI sees and cannot see; « Apply » or « Ignore ». A visual estimate, never
 *    an inspection: the condition stays the seller's statement.
 * Nothing is filled without a click. Data: ai_form_photo.
 *
 * @var array $args { category: array, slug: string, market: string }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$ai = sm_theme_data( 'ai_form_photo' );
if ( ! is_array( $ai ) ) {
	return;
}
$cat       = (array) $args['category'];
$families  = array_values( array_diff( (array) ( $cat['families'] ?? array() ), array( 'PLATE' ) ) );
$recognize = 'engine' === ( $cat['kind'] ?? '' ) && $families && isset( $ai['tools']['recognize'] );
$condition = 'reuse' === $args['market'] && isset( $ai['tools']['condition'] ) && 'reuse-scrap' !== ( $args['slug'] ?? '' );
if ( ! $recognize && ! $condition ) {
	return;
}
$conds = sm_condition_labels();
$cfg   = array(
	'ajax'     => $ai['ajax'],
	'nonce'    => $ai['nonce'],
	'families' => $families,
	'conds'    => array_intersect_key( $conds, array_flip( array( 'excellent', 'good', 'inspection', 'scrap' ) ) ),
	'surfaces' => array(
		'clean'           => __( 'Clean', 'sm-marketplace' ),
		'light_rust'      => __( 'Light surface rust', 'sm-marketplace' ),
		'painted'         => __( 'Painted', 'sm-marketplace' ),
		'galvanized'      => __( 'Galvanised', 'sm-marketplace' ),
		'heavy_corrosion' => __( 'Heavy corrosion', 'sm-marketplace' ),
	),
	'i18n'     => array(
		'wait_recognize' => __( 'The AI is looking at your photos…', 'sm-marketplace' ),
		'wait_condition' => __( 'The AI is looking at the condition…', 'sm-marketplace' ),
		'sub'            => __( 'It can take up to a minute. Do not close the page.', 'sm-marketplace' ),
		'slow'           => __( 'The answer is taking longer: please wait a little more.', 'sm-marketplace' ),
		'likely'         => __( 'Likely', 'sm-marketplace' ),
		'possible'       => __( 'Possible', 'sm-marketplace' ),
		'unlikely'       => __( 'Unlikely', 'sm-marketplace' ),
		'choose'         => __( 'Choose', 'sm-marketplace' ),
		'chosen'         => __( 'Chosen', 'sm-marketplace' ),
		'no_size'        => __( 'size to choose', 'sm-marketplace' ),
		'other_cat'      => __( 'not in this category', 'sm-marketplace' ),
		'none'           => __( 'No profile of the catalogue recognised: take a photo of the end, with a tape measure.', 'sm-marketplace' ),
		'doubt'          => __( 'To remove the doubt:', 'sm-marketplace' ),
		'checked'        => __( 'Profiles checked against the catalogue of ReuSteel.', 'sm-marketplace' ),
		'suggest'        => __( 'Suggestion of the AI — to confirm by you', 'sm-marketplace' ),
		'surface'        => __( 'Surface seen', 'sm-marketplace' ),
		'sees'           => __( 'What the AI sees:', 'sm-marketplace' ),
		'cannot'         => __( 'What it cannot see:', 'sm-marketplace' ),
		'cannot_default' => __( 'remaining thickness, deformations, welds, grade', 'sm-marketplace' ),
		/* translators: %s: condition. */
		'apply'          => __( 'Apply « %s » and the surface', 'sm-marketplace' ),
		'ignore'         => __( 'Ignore', 'sm-marketplace' ),
		'applied'        => __( 'Applied: check it in the form.', 'sm-marketplace' ),
		'scrap'          => __( 'This steel looks good for recycling: publish it in the Scrap category?', 'sm-marketplace' ),
		'left'           => __( 'credits left: %d', 'sm-marketplace' ),
		'error'          => __( 'The AI service did not answer. Try again in a moment.', 'sm-marketplace' ),
	),
	'scrap_url' => sm_page_url( 'account', array( 'view' => 'publish', 'market' => 'reuse', 'cat' => 'reuse-scrap' ) ),
);
wp_enqueue_script( 'sm-ai-photo', SM_THEME_URI . '/assets/js/ai-photo.js', array(), sm_theme_asset_version( 'assets/js/ai-photo.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
$locked = static function ( $t ) {
	if ( 'ok' === $t['state'] ) {
		return '';
	}
	return 'login' === $t['state'] ? __( 'Sign in to use the AI assistant.', 'sm-marketplace' ) : $t['reason'];
};
?>
<div class="smaip" data-smaip="<?php echo esc_attr( wp_json_encode( $cfg ) ); ?>">
	<?php if ( $recognize ) : ?>
		<?php $t = $ai['tools']['recognize']; ?>
		<div class="smaip__tool" data-smaip-tool="recognize">
			<button type="button" class="sm-btn sm-btn--ai-outline sm-btn--sm" data-smaip-run="recognize" <?php disabled( 'ok' !== $t['state'] ); ?>><?php sm_ai_badge( 'sm-ai-badge--sm' ); ?><span><?php echo esc_html( sprintf( /* translators: %d: credits. */ _n( 'Recognise from my photos · %d credit', 'Recognise from my photos · %d credits', max( 1, (int) $t['credits'] ), 'sm-marketplace' ), max( 1, (int) $t['credits'] ) ) ); ?></span></button>
			<small><?php echo esc_html( $locked( $t ) ?: __( 'Uses the photos of the form (end view with a tape measure if possible).', 'sm-marketplace' ) ); ?></small>
			<div class="smaip__out" data-smaip-out aria-live="polite"></div>
		</div>
	<?php endif; ?>
	<?php if ( $condition ) : ?>
		<?php $t = $ai['tools']['condition']; ?>
		<div class="smaip__tool" data-smaip-tool="condition">
			<button type="button" class="sm-btn sm-btn--ai-outline sm-btn--sm" data-smaip-run="condition" <?php disabled( 'ok' !== $t['state'] ); ?>><?php sm_ai_badge( 'sm-ai-badge--sm' ); ?><span><?php echo esc_html( sprintf( /* translators: %d: credits. */ _n( 'Assess the condition from my photos · %d credit', 'Assess the condition from my photos · %d credits', max( 1, (int) $t['credits'] ), 'sm-marketplace' ), max( 1, (int) $t['credits'] ) ) ); ?></span></button>
			<small><?php echo esc_html( $locked( $t ) ?: __( 'Uses the photos of the form (close views of the surfaces and of the ends).', 'sm-marketplace' ) ); ?></small>
			<div class="smaip__out" data-smaip-out aria-live="polite"></div>
			<p class="smaip__warn"><?php sm_icon( 'info' ); ?><span><?php esc_html_e( 'Visual estimate, not an inspection: the condition stays your statement. For a reuse in a structure, ask for an inspection by an engineer verified on ReuSteel.', 'sm-marketplace' ); ?> <a href="<?php echo esc_url( sm_page_url( 'engineers' ) ); ?>"><?php esc_html_e( 'Engineering', 'sm-marketplace' ); ?></a></span></p>
		</div>
	<?php endif; ?>
</div>

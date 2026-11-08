<?php
/**
 * Smart search box (sm-ai) and, after a smart search, what was understood.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$info  = sm_theme_data( 'ai_search', array( 'token' => sm_theme_query_arg( 'ais', '' ) ) );
$ready = true === sm_theme_data( 'ai_search_ready' );
if ( ! $ready && ! is_array( $info ) ) {
	return;
}
?>
<div class="smai-search">
	<?php if ( is_array( $info ) && ! empty( $info['error'] ) ) : ?>
		<div class="sm-notice sm-notice--warn"><?php sm_icon( 'info' ); ?><p><?php echo esc_html( $info['error'] ); ?> <?php esc_html_e( 'A simple keyword search was done instead.', 'sm-marketplace' ); ?></p></div>
	<?php elseif ( is_array( $info ) ) : ?>
		<div class="smai-understood">
			<span class="smai-understood__icon" aria-hidden="true"><?php sm_icon( 'ai' ); ?></span>
			<div>
				<p class="smai-understood__title"><?php esc_html_e( 'Understood as', 'sm-marketplace' ); ?> <?php if ( ! empty( $info['simulation'] ) ) : ?><span class="smai-badge smai-badge--sim"><?php esc_html_e( 'Simulation', 'sm-marketplace' ); ?></span><?php endif; ?></p>
				<?php if ( $info['chips'] ) : ?>
					<ul class="smai-chips" role="list"><?php foreach ( $info['chips'] as $c ) : ?><li><?php echo esc_html( $c ); ?></li><?php endforeach; ?></ul>
				<?php else : ?>
					<p class="sm-muted"><?php esc_html_e( 'No precise filter: all listings are shown.', 'sm-marketplace' ); ?></p>
				<?php endif; ?>
				<p class="sm-muted smai-understood__q" dir="auto">« <?php echo esc_html( $info['query'] ); ?> »</p>
			</div>
		</div>
	<?php endif; ?>
	<?php if ( $ready ) : ?>
		<?php sm_theme_form_open( 'ai_search', array( 'class' => 'smai-search__form', 'role' => 'search', 'data-sm-busy' => __( 'Searching…', 'sm-marketplace' ) ) ); ?>
			<label class="screen-reader-text" for="smai-q"><?php esc_html_e( 'Describe what you need', 'sm-marketplace' ); ?></label>
			<span class="smai-search__icon" aria-hidden="true"><?php sm_icon( 'ai' ); ?></span>
			<input id="smai-q" name="q" dir="auto" maxlength="300" value="<?php echo esc_attr( is_array( $info ) ? (string) $info['query'] : '' ); ?>" placeholder="<?php esc_attr_e( 'Describe what you need, e.g. “used IPE 200 in Oran, at least 6 m”', 'sm-marketplace' ); ?>">
			<button class="sm-btn sm-btn--accent" type="submit"><span data-sm-busy-label><?php esc_html_e( 'Smart search', 'sm-marketplace' ); ?></span></button>
		</form>
	<?php endif; ?>
</div>

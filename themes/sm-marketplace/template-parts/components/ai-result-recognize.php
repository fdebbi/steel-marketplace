<?php
/**
 * Result of the AI profile recognition.
 *
 * @var array $args { result: array{tool, token, data} }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$r = $args['result']['data'];
?>
<div class="sm-panel">
	<div class="smai-tool__head">
		<h2 class="sm-panel__title"><?php esc_html_e( 'Possible profiles', 'sm-marketplace' ); ?></h2>
		<?php if ( ! empty( $r['_meta']['simulation'] ) ) : ?><span class="smai-badge smai-badge--sim"><?php esc_html_e( 'Simulation', 'sm-marketplace' ); ?></span><?php endif; ?>
	</div>
	<p class="sm-muted"><?php esc_html_e( 'To confirm by measuring: a photo is not a certified identification.', 'sm-marketplace' ); ?></p>
	<?php if ( ! $r['candidates'] ) : ?>
		<p><?php esc_html_e( 'The assistant could not recognise a profile of the catalogue on these photos. Try a photo of the end of the profile, with a tape measure.', 'sm-marketplace' ); ?></p>
	<?php else : ?>
		<ol class="smai-cands" role="list">
			<?php foreach ( $r['candidates'] as $i => $c ) : ?>
				<li class="smai-cand<?php echo 0 === $i ? ' is-first' : ''; ?>">
					<div class="smai-cand__main">
						<p class="smai-cand__name"><?php echo esc_html( $c['section'] ? $c['section']['designation'] : $c['family'] ); ?> <small><?php echo esc_html( $c['family_name'] ); ?></small></p>
						<?php sm_component( 'ai-confidence', array( 'value' => $c['confidence'] ) ); ?>
						<?php if ( $c['reason'] ) : ?><p class="smai-cand__why" dir="auto"><?php echo esc_html( $c['reason'] ); ?></p><?php endif; ?>
						<?php if ( $c['section'] ) : ?><p class="sm-muted"><?php echo esc_html( sprintf( /* translators: %s: kg/m. */ __( 'Catalogue: %s kg/m', 'sm-marketplace' ), sm_number( $c['section']['mass_kg_m'], 2 ) ) ); ?></p><?php endif; ?>
					</div>
					<div class="smai-cand__links">
						<a class="sm-btn sm-btn--ghost sm-btn--sm" href="<?php echo esc_url( sm_page_url( 'weight', array_filter( array( 'family' => $c['family'], 'section' => $c['section'] ? $c['section']['section_id'] : '' ) ) ) ); ?>"><?php sm_icon( 'weight' ); ?><?php esc_html_e( 'Weigh it', 'sm-marketplace' ); ?></a>
						<a class="sm-btn sm-btn--ghost sm-btn--sm" href="<?php echo esc_url( sm_page_url( 'search', array( 'q' => $c['section'] ? $c['section']['designation'] : $c['family'] ) ) ); ?>"><?php sm_icon( 'search' ); ?><?php esc_html_e( 'Find it on the marketplace', 'sm-marketplace' ); ?></a>
					</div>
				</li>
			<?php endforeach; ?>
		</ol>
	<?php endif; ?>
	<?php if ( $r['questions'] ) : ?>
		<div class="smai-ask">
			<h3><?php sm_icon( 'ruler' ); ?><?php esc_html_e( 'To remove the doubt', 'sm-marketplace' ); ?></h3>
			<ul><?php foreach ( $r['questions'] as $q ) : ?><li dir="auto"><?php echo esc_html( $q ); ?></li><?php endforeach; ?></ul>
		</div>
	<?php endif; ?>
</div>

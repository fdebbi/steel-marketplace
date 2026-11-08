<?php
/**
 * Result of the AI listing assistant.
 *
 * @var array $args { result: array{tool, token, data} }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$r    = $args['result']['data'];
$lang = sm_theme_current_language();
?>
<div class="sm-panel">
	<div class="smai-tool__head">
		<h2 class="sm-panel__title"><?php esc_html_e( 'Suggested listing', 'sm-marketplace' ); ?></h2>
		<?php if ( ! empty( $r['_meta']['simulation'] ) ) : ?><span class="smai-badge smai-badge--sim"><?php esc_html_e( 'Simulation', 'sm-marketplace' ); ?></span><?php endif; ?>
		<?php sm_component( 'ai-confidence', array( 'value' => $r['confidence'] ) ); ?>
	</div>
	<dl class="smai-facts">
		<div><dt><?php esc_html_e( 'Category', 'sm-marketplace' ); ?></dt><dd><?php echo $r['category'] ? esc_html( sm_t( $r['category_name'] ) ) : '<span class="sm-muted">' . esc_html__( 'Not found — choose it on the form', 'sm-marketplace' ) . '</span>'; ?></dd></div>
		<div><dt><?php esc_html_e( 'Profile', 'sm-marketplace' ); ?></dt><dd><?php echo $r['section'] ? esc_html( $r['section']['designation'] ) . ' <small class="sm-muted">' . esc_html( sprintf( /* translators: %s: kg/m. */ __( '%s kg/m (catalogue)', 'sm-marketplace' ), sm_number( $r['section']['mass_kg_m'], 2 ) ) ) . '</small>' : '<span class="sm-muted">' . esc_html__( 'Not identified', 'sm-marketplace' ) . '</span>'; ?></dd></div><?php // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php if ( $r['grade'] ) : ?><div><dt><?php esc_html_e( 'Grade', 'sm-marketplace' ); ?></dt><dd><?php echo esc_html( $r['grade'] ); ?></dd></div><?php endif; ?>
	</dl>
	<div class="smai-texts">
		<?php foreach ( array( 'fr' => 'Français', 'ar' => 'العربية' ) as $l => $ll ) : ?>
			<?php if ( '' !== $r['title'][ $l ] || '' !== $r['description'][ $l ] ) : ?>
				<div class="smai-text" lang="<?php echo esc_attr( $l ); ?>" dir="<?php echo 'ar' === $l ? 'rtl' : 'ltr'; ?>">
					<span class="smai-text__lang"><?php echo esc_html( $ll ); ?></span>
					<p class="smai-text__title"><?php echo esc_html( $r['title'][ $l ] ); ?></p>
					<p class="smai-text__body"><?php echo nl2br( esc_html( $r['description'][ $l ] ) ); ?></p>
				</div>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
	<?php if ( $r['missing'] ) : ?>
		<div class="smai-ask">
			<h3><?php sm_icon( 'info' ); ?><?php esc_html_e( 'To complete on the form', 'sm-marketplace' ); ?></h3>
			<ul><?php foreach ( $r['missing'] as $m ) : ?><li dir="auto"><?php echo esc_html( $m ); ?></li><?php endforeach; ?></ul>
		</div>
	<?php endif; ?>
	<?php if ( $r['warnings'] ) : ?>
		<div class="sm-notice sm-notice--warn"><?php sm_icon( 'alert' ); ?><ul><?php foreach ( $r['warnings'] as $w ) : ?><li dir="auto"><?php echo esc_html( $w ); ?></li><?php endforeach; ?></ul></div>
	<?php endif; ?>
	<?php sm_theme_form_open( 'ai_use', array( 'class' => 'smp-actions smai-use' ) ); ?>
		<input type="hidden" name="r" value="<?php echo esc_attr( $args['result']['token'] ); ?>">
		<p class="sm-muted"><?php esc_html_e( 'The form opens filled with these suggestions; quantity, length, price and photos are yours to give. Nothing is published before you submit.', 'sm-marketplace' ); ?></p>
		<button class="sm-btn sm-btn--accent" type="submit"><?php sm_icon( 'plus' ); ?><?php esc_html_e( 'Create the listing with these suggestions', 'sm-marketplace' ); ?></button>
	</form>
</div>

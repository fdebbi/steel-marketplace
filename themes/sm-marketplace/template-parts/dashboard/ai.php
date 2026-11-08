<?php
/**
 * "AI assistant": listing assistant, profile recognition, document analysis
 * (sm-ai). Each tool sends photos or a document with notes; the result is a
 * suggestion to check, which can pre-fill the listing or the request for
 * quotation form.
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$ai = sm_theme_data( 'ai_tools' );
if ( ! is_array( $ai ) || empty( $ai['tools'] ) ) {
	sm_component( 'empty-state', array( 'icon' => 'ai', 'title' => __( 'AI assistant', 'sm-marketplace' ), 'text' => __( 'This module is not active yet.', 'sm-marketplace' ) ) );
	return;
}
$tools = $ai['tools'];
$tool  = sanitize_key( sm_theme_query_arg( 'tool', '' ) );
if ( ! isset( $tools[ $tool ] ) ) {
	$avail = array_keys( array_filter( $tools, static fn( $t ) => $t['available'] ) );
	$tool  = $avail[0] ?? array_key_first( $tools );
}
$t      = $tools[ $tool ];
$result = sm_theme_data( 'ai_result', array( 'token' => sm_theme_query_arg( 'r', '' ) ) );
$result = is_array( $result ) && $result['tool'] === $tool ? $result : null;
$old    = sm_theme_old_all();
$icons  = array( 'listing' => 'package', 'recognize' => 'camera', 'document' => 'file' );
$intro  = array(
	'listing'   => __( 'Send photos of your steel and a few words: the assistant proposes the category, the profile, the title in French and Arabic and the description, and tells you what is missing. You check everything before publishing.', 'sm-marketplace' ),
	'recognize' => __( 'Not sure which profile it is? Send one or two photos (end view if possible): the assistant proposes the possible profiles, checked against the catalogue.', 'sm-marketplace' ),
	'document'  => __( 'Send a bill of quantities, a list of profiles or a specification (PDF or photo): the assistant extracts the steel items; the weight engine weighs the known profiles and you can create a request for quotation.', 'sm-marketplace' ),
);
$base = sm_page_url( 'account', array( 'view' => 'ai' ) );
?>
<div class="smai">
	<div class="sm-notice sm-notice--info smai-disclaimer"><?php sm_icon( 'ai' ); ?><p><?php esc_html_e( 'The AI gives suggestions to check. It never gives weights (computed by the weight engine) and never replaces an engineer, a structural check or a certification.', 'sm-marketplace' ); ?></p></div>

	<nav class="smai-tabs" aria-label="<?php esc_attr_e( 'AI tools', 'sm-marketplace' ); ?>">
		<?php foreach ( $tools as $id => $x ) : ?>
			<a class="smai-tab<?php echo $id === $tool ? ' is-current' : ''; ?><?php echo $x['available'] ? '' : ' is-locked'; ?>" href="<?php echo esc_url( add_query_arg( 'tool', $id, $base ) ); ?>"<?php echo $id === $tool ? ' aria-current="page"' : ''; ?>>
				<span class="smai-tab__icon"><?php sm_icon( $icons[ $id ] ?? 'ai' ); ?></span>
				<span><?php echo esc_html( $x['label'] ); ?></span>
			</a>
		<?php endforeach; ?>
	</nav>

	<section class="sm-panel smai-tool">
		<div class="smai-tool__head">
			<h2 class="sm-panel__title"><?php echo esc_html( $t['label'] ); ?></h2>
			<?php if ( $t['simulation'] ) : ?><span class="smai-badge smai-badge--sim"><?php esc_html_e( 'Simulation', 'sm-marketplace' ); ?></span><?php endif; ?>
			<?php if ( null !== $t['remaining'] ) : ?>
				<span class="smai-quota"><?php echo esc_html( sprintf( /* translators: %d: remaining uses. */ _n( '%d use left this month', '%d uses left this month', (int) $t['remaining'], 'sm-marketplace' ), (int) $t['remaining'] ) ); ?></span>
			<?php endif; ?>
		</div>
		<p class="sm-muted"><?php echo esc_html( $intro[ $tool ] ?? '' ); ?></p>

		<?php if ( ! $t['available'] ) : ?>
			<div class="sm-notice sm-notice--warn"><?php sm_icon( 'lock' ); ?><p><?php echo esc_html( $t['reason'] ); ?></p></div>
		<?php else : ?>
			<?php sm_theme_form_open( 'ai_run', array( 'class' => 'sm-form smai-form', 'enctype' => 'multipart/form-data', 'data-sm-busy' => __( 'The assistant is working… (up to a minute)', 'sm-marketplace' ) ) ); ?>
				<input type="hidden" name="tool" value="<?php echo esc_attr( $tool ); ?>">
				<?php if ( 'listing' === $tool && count( $ai['markets'] ) > 1 ) : ?>
					<div class="sm-field">
						<label for="smai-market"><?php esc_html_e( 'Market', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label>
						<select id="smai-market" name="market">
							<option value=""><?php esc_html_e( 'Let the assistant propose', 'sm-marketplace' ); ?></option>
							<?php foreach ( $ai['markets'] as $k => $l ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( (string) ( $old['market'] ?? '' ), $k ); ?>><?php echo esc_html( sm_t( $l ) ); ?></option><?php endforeach; ?>
						</select>
					</div>
				<?php endif; ?>
				<?php if ( 'document' === $tool ) : ?>
					<label class="sm-upload" data-sm-upload>
						<input type="file" name="document" accept="application/pdf,image/jpeg,image/png,image/webp"<?php sm_theme_aria( 'document' ); ?>>
						<?php sm_icon( 'file', 'sm-upload__icon' ); ?>
						<strong><?php esc_html_e( 'Choose the document', 'sm-marketplace' ); ?></strong>
						<small><?php echo esc_html( sprintf( /* translators: %d: MB. */ __( 'PDF or photo, %d MB at most.', 'sm-marketplace' ), (int) $ai['pdf_mb'] ) ); ?></small>
					</label>
					<?php sm_theme_error( 'document' ); ?>
				<?php else : ?>
					<label class="sm-upload" data-sm-upload>
						<input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple<?php sm_theme_aria( 'photos' ); ?>>
						<?php sm_icon( 'camera', 'sm-upload__icon' ); ?>
						<strong><?php echo esc_html( 'recognize' === $tool ? __( 'Add photos of the profile', 'sm-marketplace' ) : __( 'Add photos of the product', 'sm-marketplace' ) ); ?></strong>
						<small><?php echo esc_html( sprintf( /* translators: 1: photos, 2: MB. */ __( 'Up to %1$d photos (JPG, PNG, WebP, %2$d MB each).', 'sm-marketplace' ), (int) $ai['max_images'], (int) $ai['image_mb'] ) ); ?></small>
					</label>
					<ul class="sm-upload__previews" data-sm-previews></ul>
					<?php sm_theme_error( 'photos' ); ?>
				<?php endif; ?>
				<div class="sm-field">
					<label for="smai-notes"><?php echo esc_html( 'document' === $tool ? __( 'What are you looking for in it?', 'sm-marketplace' ) : __( 'Your notes', 'sm-marketplace' ) ); ?> <small class="sm-optional"><?php echo esc_html( 'listing' === $tool ? __( 'recommended', 'sm-marketplace' ) : __( 'optional', 'sm-marketplace' ) ); ?></small></label>
					<textarea id="smai-notes" name="notes" rows="3" dir="auto" maxlength="2000" placeholder="<?php echo esc_attr( 'listing' === $tool ? __( 'e.g. 12 bars HEA 160 S355JR, new, 12 m, stored under shelter in Rouiba', 'sm-marketplace' ) : ( 'recognize' === $tool ? __( 'e.g. height about 20 cm, found on a dismantled hangar', 'sm-marketplace' ) : __( 'e.g. only the structure, not the roofing', 'sm-marketplace' ) ) ); ?>"<?php sm_theme_aria( 'notes' ); ?>><?php echo esc_textarea( (string) ( $old['notes'] ?? '' ) ); ?></textarea>
					<?php sm_theme_error( 'notes' ); ?>
				</div>
				<div class="smp-actions">
					<button class="sm-btn sm-btn--accent" type="submit"><?php sm_icon( 'ai' ); ?><span data-sm-busy-label><?php esc_html_e( 'Ask the assistant', 'sm-marketplace' ); ?></span></button>
				</div>
			</form>
		<?php endif; ?>
	</section>

	<?php if ( $result ) : ?>
		<section id="sm-ai-result" class="smai-result" aria-live="polite">
			<?php sm_component( 'ai-result-' . $tool, array( 'result' => $result, 'can_rfq' => ! empty( $ai['can_rfq'] ) ) ); ?>
		</section>
	<?php endif; ?>
</div>

<?php
/**
 * Partner engineer: profile shown on the list of partner engineers (no contact
 * details), photo and availability for new missions.
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$d = sm_theme_data( 'engineer_account' );
if ( ! is_array( $d ) ) {
	sm_component( 'empty-state', array( 'icon' => 'user', 'title' => __( 'Partner profile', 'sm-marketplace' ), 'text' => __( 'This section needs the SM Engineering plugin.', 'sm-marketplace' ) ) );
	return;
}
$locked = in_array( $d['status'], array( 'approved', 'suspended' ), true );
?>
<div class="sm-dash__cols sm-dash__cols--wide">
	<section class="sm-panel">
		<h2 class="sm-panel__title"><?php esc_html_e( 'Your profile', 'sm-marketplace' ); ?></h2>
		<?php if ( 'rejected' === $d['status'] ) : ?>
			<div class="sm-notice sm-notice--warn"><?php sm_icon( 'alert' ); ?><p><?php esc_html_e( 'Your application was not approved. Send it again with your documents:', 'sm-marketplace' ); ?> <a href="<?php echo esc_url( $d['apply_url'] ); ?>"><?php esc_html_e( 'application page', 'sm-marketplace' ); ?></a></p></div>
		<?php endif; ?>
		<?php sm_theme_form_open( 'engineer_profile', array( 'class' => 'sm-form', 'enctype' => 'multipart/form-data' ) ); ?>
			<div class="sme-photo">
				<?php sm_avatar( array( 'class' => 'engineer', 'logo' => $d['photo'], 'initials' => $d['public']['initials'] ?? 'IN' ), 'xl' ); ?>
				<div>
					<label class="sm-upload sm-upload--small">
						<input type="file" name="photo[]" accept="image/jpeg,image/png,image/webp" data-sm-filename>
						<?php sm_icon( 'camera', 'sm-upload__icon' ); ?>
						<strong><?php echo esc_html( $d['photo'] ? __( 'Change the photo', 'sm-marketplace' ) : __( 'Add your photo', 'sm-marketplace' ) ); ?></strong>
						<small data-sm-filename-label><?php esc_html_e( 'JPG, PNG or WebP, 4 MB maximum.', 'sm-marketplace' ); ?></small>
					</label>
					<?php if ( $d['photo'] ) : ?><?php if ( ! empty( $d['photo_id'] ) ) : ?><?php sm_saved_files( array( 'ids' => array( $d['photo_id'] ), 'field' => 'photo[]', 'remove' => 'photo_remove' ) ); ?><?php else : ?><label class="sm-check"><input type="checkbox" name="photo_remove" value="1"><span><?php esc_html_e( 'Remove the photo', 'sm-marketplace' ); ?></span></label><?php endif; ?><?php endif; ?>
				</div>
			</div>
			<?php sm_component( 'engineer-fields', array( 'values' => $d['profile'], 'types' => $d['types'], 'titles' => $d['titles'] ?? array(), 'specs' => $d['specs'] ?? array(), 'locked' => $locked ) ); ?>
			<label class="sm-check"><input type="checkbox" name="available" value="1" <?php checked( $d['available'] ); ?>><span><?php esc_html_e( 'Available for new missions', 'sm-marketplace' ); ?></span></label>
			<button class="sm-btn sm-btn--primary" type="submit"><?php esc_html_e( 'Save my profile', 'sm-marketplace' ); ?></button>
		</form>
	</section>
	<aside class="sm-panel">
		<h2 class="sm-panel__title"><?php esc_html_e( 'Preview', 'sm-marketplace' ); ?></h2>
		<?php if ( $d['public'] ) : ?><?php sm_component( 'card-engineer', array( 'engineer' => $d['public'], 'preview' => true ) ); ?><?php endif; ?>
		<p class="sm-help"><?php esc_html_e( 'Visitors see this card once you are approved. Your phone number, e-mail and address are never shown.', 'sm-marketplace' ); ?></p>
	</aside>
</div>

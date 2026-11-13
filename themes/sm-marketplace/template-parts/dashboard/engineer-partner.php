<?php
/**
 * Partner engineer profile (0.30.0).
 *
 *   - application being checked: read only;
 *   - approved: a change is a request sent to the platform (the approved profile
 *     stays in use until it answers); one request at a time, it can be cancelled;
 *   - free, at once: mobile phone (here), availability (button at the top of the
 *     menu), password and language (Settings).
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
$status = $d['status'];
$summary = static function () use ( $d ) {
	?>
	<div class="sme-photo">
		<?php sm_avatar( array( 'class' => 'engineer', 'logo' => $d['photo'], 'initials' => $d['public']['initials'] ?? 'IN' ), 'xl' ); ?>
	</div>
	<dl class="sme-facts sme-facts--list">
		<?php foreach ( (array) $d['summary'] as $row ) : ?>
			<?php if ( '' !== (string) $row['value'] ) : ?><div><dt><?php echo esc_html( $row['label'] ); ?></dt><dd dir="auto"><?php echo esc_html( $row['value'] ); ?></dd></div><?php endif; ?>
		<?php endforeach; ?>
	</dl>
	<?php
};
?>
<div class="sm-dash__cols sm-dash__cols--wide">
	<section class="sm-panel">
		<h2 class="sm-panel__title"><?php esc_html_e( 'Your profile', 'sm-marketplace' ); ?></h2>
		<?php if ( 'pending' === $status ) : ?>
			<div class="sm-notice sm-notice--info"><?php sm_icon( 'lock' ); ?><p><?php esc_html_e( 'Your application is being checked by the platform: your profile cannot be changed for the moment. You receive the decision by e-mail.', 'sm-marketplace' ); ?></p></div>
			<?php $summary(); ?>
		<?php elseif ( 'rejected' === $status ) : ?>
			<div class="sm-notice sm-notice--warn"><?php sm_icon( 'alert' ); ?><p><?php esc_html_e( 'Your application was not approved. Send it again with your documents:', 'sm-marketplace' ); ?> <a href="<?php echo esc_url( $d['apply_url'] ); ?>"><?php esc_html_e( 'application page', 'sm-marketplace' ); ?></a></p></div>
			<?php $summary(); ?>
		<?php else : ?>
			<?php if ( $d['change'] ) : ?>
				<div class="sm-notice sm-notice--warn sme-change" role="status">
					<?php sm_icon( 'clock' ); ?>
					<div>
						<p><strong><?php esc_html_e( 'Change request waiting for the platform', 'sm-marketplace' ); ?></strong> — <?php esc_html_e( 'your approved profile stays in use until it answers.', 'sm-marketplace' ); ?></p>
						<ul class="sme-change__list">
							<?php foreach ( $d['change'] as $row ) : ?>
								<li><span><?php echo esc_html( $row['label'] ); ?></span> <?php if ( '' !== $row['before'] ) : ?><s dir="auto"><?php echo esc_html( wp_trim_words( $row['before'], 12 ) ); ?></s> → <?php endif; ?><strong dir="auto"><?php echo esc_html( wp_trim_words( $row['after'], 12 ) ); ?></strong></li>
							<?php endforeach; ?>
						</ul>
						<?php sm_theme_form_open( 'engineer_profile', array( 'class' => 'sme-actions' ) ); ?>
							<button class="sm-btn sm-btn--ghost sm-btn--sm" type="submit" name="cancel_change" value="1"><?php esc_html_e( 'Cancel my request', 'sm-marketplace' ); ?></button>
						</form>
					</div>
				</div>
			<?php elseif ( '' !== $d['change_reason'] ) : ?>
				<div class="sm-notice sm-notice--warn"><?php sm_icon( 'alert' ); ?><p><strong><?php esc_html_e( 'Your last change request was refused:', 'sm-marketplace' ); ?></strong> <?php echo esc_html( $d['change_reason'] ); ?></p></div>
			<?php endif; ?>
			<p class="sm-help"><?php sm_icon( 'info' ); ?> <?php esc_html_e( 'Your profile was approved by the platform. Any change is sent to the platform, which approves it before it applies.', 'sm-marketplace' ); ?></p>
			<?php sm_theme_form_open( 'engineer_profile', array( 'class' => 'sm-form', 'enctype' => 'multipart/form-data' ) ); ?>
				<div class="sme-photo">
					<?php sm_avatar( array( 'class' => 'engineer', 'logo' => $d['photo'], 'initials' => $d['public']['initials'] ?? 'IN' ), 'xl' ); ?>
					<div>
						<label class="sm-upload sm-upload--small">
							<input type="file" name="photo[]" accept="image/jpeg,image/png,image/webp" data-sm-filename>
							<?php sm_icon( 'camera', 'sm-upload__icon' ); ?>
							<strong><?php esc_html_e( 'Change the photo or logo', 'sm-marketplace' ); ?></strong>
							<small data-sm-filename-label><?php esc_html_e( 'JPG, PNG or WebP, 4 MB maximum.', 'sm-marketplace' ); ?></small>
						</label>
						<?php sm_theme_error( 'photo' ); ?>
					</div>
				</div>
				<div class="sm-field sm-field--row">
					<div><label for="e-last"><?php esc_html_e( 'Last name', 'sm-marketplace' ); ?></label><input id="e-last" name="last_name" dir="auto" maxlength="60" required value="<?php echo esc_attr( (string) sm_theme_old( 'last_name', $d['profile']['last_name'] ?? '' ) ); ?>"<?php sm_theme_aria( 'last_name' ); ?>><?php sm_theme_error( 'last_name' ); ?></div>
					<div><label for="e-first"><?php esc_html_e( 'First name', 'sm-marketplace' ); ?></label><input id="e-first" name="first_name" dir="auto" maxlength="60" required value="<?php echo esc_attr( (string) sm_theme_old( 'first_name', $d['profile']['first_name'] ?? '' ) ); ?>"<?php sm_theme_aria( 'first_name' ); ?>><?php sm_theme_error( 'first_name' ); ?></div>
				</div>
				<div class="sm-field">
					<label for="e-wilaya"><?php esc_html_e( 'Wilaya', 'sm-marketplace' ); ?></label>
					<select id="e-wilaya" name="wilaya" required<?php sm_theme_aria( 'wilaya' ); ?>>
						<?php foreach ( sm_theme_wilayas() as $code => $name ) : ?><option value="<?php echo esc_attr( $code ); ?>" <?php selected( (string) sm_theme_old( 'wilaya', $d['profile']['wilaya'] ?? '' ), (string) $code ); ?>><?php echo esc_html( $code . ' — ' . sm_t( $name ) ); ?></option><?php endforeach; ?>
					</select>
					<?php sm_theme_error( 'wilaya' ); ?>
				</div>
				<?php sm_component( 'engineer-fields', array( 'values' => $d['profile'], 'types' => $d['types'], 'titles' => $d['titles'] ?? array(), 'specs' => $d['specs'] ?? array() ) ); ?>
				<button class="sm-btn sm-btn--primary" type="submit"><?php echo esc_html( $d['change'] ? __( 'Replace my change request', 'sm-marketplace' ) : __( 'Send the change request', 'sm-marketplace' ) ); ?></button>
			</form>
		<?php endif; ?>
	</section>
	<aside class="sm-panel">
		<h2 class="sm-panel__title"><?php esc_html_e( 'Mobile phone', 'sm-marketplace' ); ?></h2>
		<?php sm_theme_form_open( 'engineer_phone', array( 'class' => 'sm-form' ) ); ?>
			<div class="sm-field">
				<label for="e-phone" class="screen-reader-text"><?php esc_html_e( 'Mobile phone', 'sm-marketplace' ); ?></label>
				<input id="e-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" dir="ltr" required value="<?php echo esc_attr( (string) sm_theme_old( 'phone', $d['phone'] ) ); ?>"<?php sm_theme_aria( 'phone' ); ?>>
				<?php sm_theme_error( 'phone' ); ?>
				<p class="sm-help"><?php esc_html_e( 'Seen only by the platform. Saved at once, without approval.', 'sm-marketplace' ); ?></p>
			</div>
			<button class="sm-btn sm-btn--ghost" type="submit"><?php esc_html_e( 'Save the phone number', 'sm-marketplace' ); ?></button>
		</form>
		<p class="sm-help"><?php esc_html_e( 'Password and language: in Settings. Availability: the button at the top of the menu.', 'sm-marketplace' ); ?></p>
	</aside>
</div>

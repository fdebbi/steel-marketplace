<?php
/**
 * Company profile form (business) / professional profile (engineering).
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$account = $args['account'];
$is_eng  = 'engineering' === $account['class'];
$e       = (array) ( $account['edit'] ?? array() );
$old     = static fn( $f ) => sm_theme_old( $f, $e[ $f ] ?? '' );
$labels  = sm_activity_labels();
unset( $labels['engineer'] );
$acts    = (array) sm_theme_old( 'activities', $account['company']['activities'] );

if ( empty( $account['real'] ) ) {
	sm_component( 'empty-state', array( 'icon' => 'company', 'title' => __( 'Sign in with a real account to edit the company profile.', 'sm-marketplace' ) ) );
	return;
}
if ( empty( $account['can_manage'] ) ) {
	echo '<div class="sm-notice sm-notice--warn">' . sm_get_icon( 'lock' ) . '<p>' . esc_html__( 'Only the account owner or a manager can edit the company.', 'sm-marketplace' ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	return;
}
?>
<?php sm_theme_form_open( 'company_save', array( 'class' => 'sm-form sm-form--dash' ) ); ?>
	<section class="sm-panel">
		<h2 class="sm-panel__title"><?php echo esc_html( $is_eng ? __( 'Identity', 'sm-marketplace' ) : __( 'Company', 'sm-marketplace' ) ); ?></h2>
		<div class="sm-field sm-field--row">
			<div><label for="c-name"><?php echo esc_html( $is_eng ? __( 'Name or office name', 'sm-marketplace' ) : __( 'Company or trade name', 'sm-marketplace' ) ); ?></label><input id="c-name" name="name" value="<?php echo esc_attr( $old( 'name' ) ); ?>" dir="auto" required<?php sm_theme_aria( 'name' ); ?>><?php sm_theme_error( 'name' ); ?></div>
			<div><label for="c-name-ar"><?php esc_html_e( 'Name in Arabic (optional)', 'sm-marketplace' ); ?></label><input id="c-name-ar" name="name_ar" value="<?php echo esc_attr( $old( 'name_ar' ) ); ?>" lang="ar" dir="rtl"></div>
		</div>
		<?php if ( $is_eng ) : ?>
			<div class="sm-field sm-field--row">
				<div><label for="c-title-fr"><?php esc_html_e( 'Professional title in French', 'sm-marketplace' ); ?></label><input id="c-title-fr" name="title_fr" value="<?php echo esc_attr( $old( 'title_fr' ) ); ?>" lang="fr" dir="ltr" placeholder="Bureau d’études — charpente métallique"></div>
				<div><label for="c-title-ar"><?php esc_html_e( 'Professional title in Arabic', 'sm-marketplace' ); ?></label><input id="c-title-ar" name="title_ar" value="<?php echo esc_attr( $old( 'title_ar' ) ); ?>" lang="ar" dir="rtl"></div>
			</div>
		<?php else : ?>
			<fieldset class="sm-field">
				<legend><?php esc_html_e( 'Business activities', 'sm-marketplace' ); ?></legend>
				<div class="sm-checkgrid">
					<?php foreach ( $labels as $key => $label ) : ?>
						<label class="sm-check"><input type="checkbox" name="activities[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $acts, true ) ); ?>><span><?php echo esc_html( $label ); ?></span></label>
					<?php endforeach; ?>
				</div>
				<?php sm_theme_error( 'activities' ); ?>
				<p class="sm-help"><?php esc_html_e( 'Your activities decide what you can publish. The account type (business or engineering) cannot be changed.', 'sm-marketplace' ); ?></p>
			</fieldset>
		<?php endif; ?>
		<div class="sm-field">
			<label for="c-about-fr"><?php esc_html_e( 'Presentation in French', 'sm-marketplace' ); ?></label>
			<textarea id="c-about-fr" name="about_fr" rows="4" lang="fr" dir="ltr"><?php echo esc_textarea( $old( 'about_fr' ) ); ?></textarea>
		</div>
		<div class="sm-field">
			<label for="c-about-ar"><?php esc_html_e( 'Presentation in Arabic', 'sm-marketplace' ); ?></label>
			<textarea id="c-about-ar" name="about_ar" rows="4" lang="ar" dir="rtl"><?php echo esc_textarea( $old( 'about_ar' ) ); ?></textarea>
		</div>
		<?php if ( $is_eng ) : ?>
			<div class="sm-field sm-field--row">
				<div><label for="c-qual"><?php esc_html_e( 'Qualifications (one per line)', 'sm-marketplace' ); ?></label><textarea id="c-qual" name="qualifications" rows="4" dir="auto"><?php echo esc_textarea( $old( 'qualifications' ) ); ?></textarea></div>
				<div><label for="c-spec"><?php esc_html_e( 'Specialisations (one per line)', 'sm-marketplace' ); ?></label><textarea id="c-spec" name="specialities" rows="4" dir="auto"><?php echo esc_textarea( $old( 'specialities' ) ); ?></textarea></div>
			</div>
			<div class="sm-field">
				<label for="c-exp"><?php esc_html_e( 'Years of professional experience', 'sm-marketplace' ); ?></label>
				<input id="c-exp" name="experience_years" type="number" min="0" max="70" inputmode="numeric" dir="ltr" value="<?php echo esc_attr( $old( 'experience_years' ) ); ?>" style="max-inline-size:160px">
			</div>
		<?php endif; ?>
	</section>

	<section class="sm-panel">
		<h2 class="sm-panel__title"><?php esc_html_e( 'Contact and location', 'sm-marketplace' ); ?></h2>
		<div class="sm-field sm-field--row">
			<div>
				<label for="c-wilaya"><?php esc_html_e( 'Wilaya', 'sm-marketplace' ); ?></label>
				<select id="c-wilaya" name="wilaya"<?php sm_theme_aria( 'wilaya' ); ?>>
					<?php foreach ( sm_theme_wilayas() as $code => $name ) : ?><option value="<?php echo esc_attr( $code ); ?>" <?php selected( $old( 'wilaya' ), (string) $code ); ?>><?php echo esc_html( $code . ' — ' . sm_t( $name ) ); ?></option><?php endforeach; ?>
				</select>
				<?php sm_theme_error( 'wilaya' ); ?>
			</div>
			<div><label for="c-commune"><?php esc_html_e( 'Commune', 'sm-marketplace' ); ?></label><input id="c-commune" name="commune" value="<?php echo esc_attr( $old( 'commune' ) ); ?>" dir="auto"></div>
		</div>
		<div class="sm-field"><label for="c-address"><?php esc_html_e( 'Address', 'sm-marketplace' ); ?></label><input id="c-address" name="address" value="<?php echo esc_attr( $old( 'address' ) ); ?>" dir="auto"></div>
		<?php
		sm_component(
			'location-map',
			array(
				'mode'          => 'picker',
				'lat'           => $old( 'lat' ),
				'lng'           => $old( 'lng' ),
				'map_public'    => sm_theme_old( 'map_public', $e['map_public'] ?? 1 ),
				'wilaya_select' => '#c-wilaya',
			)
		);
		?>
		<div class="sm-field sm-field--row sm-field--row3">
			<div><label for="c-phone"><?php esc_html_e( 'Public phone', 'sm-marketplace' ); ?></label><input id="c-phone" name="phone" type="tel" value="<?php echo esc_attr( $old( 'phone' ) ); ?>" dir="ltr"<?php sm_theme_aria( 'phone' ); ?>><?php sm_theme_error( 'phone' ); ?></div>
			<div><label for="c-email"><?php esc_html_e( 'Public e-mail', 'sm-marketplace' ); ?></label><input id="c-email" name="email" type="email" value="<?php echo esc_attr( $old( 'email' ) ); ?>" dir="ltr"<?php sm_theme_aria( 'email' ); ?>><?php sm_theme_error( 'email' ); ?></div>
			<div><label for="c-web"><?php esc_html_e( 'Website', 'sm-marketplace' ); ?></label><input id="c-web" name="website" type="url" value="<?php echo esc_attr( $old( 'website' ) ); ?>" dir="ltr" placeholder="https://"></div>
		</div>
	</section>

	<?php if ( ! $is_eng ) : ?>
		<section class="sm-panel">
			<h2 class="sm-panel__title"><?php esc_html_e( 'Registration numbers', 'sm-marketplace' ); ?></h2>
			<p class="sm-help"><?php esc_html_e( 'Stored encrypted and never shown publicly. Only the platform team sees them during verification.', 'sm-marketplace' ); ?></p>
			<div class="sm-field sm-field--row sm-field--row3">
				<div><label for="c-rc"><?php esc_html_e( 'RC number', 'sm-marketplace' ); ?></label><input id="c-rc" name="rc_number" value="<?php echo esc_attr( $old( 'rc_number' ) ); ?>" dir="ltr" autocomplete="off"></div>
				<div><label for="c-nif">NIF</label><input id="c-nif" name="nif" value="<?php echo esc_attr( $old( 'nif' ) ); ?>" dir="ltr" inputmode="numeric" autocomplete="off"></div>
				<div><label for="c-nis">NIS</label><input id="c-nis" name="nis" value="<?php echo esc_attr( $old( 'nis' ) ); ?>" dir="ltr" inputmode="numeric" autocomplete="off"></div>
			</div>
		</section>
	<?php endif; ?>

	<div class="sm-stepper__nav">
		<a class="sm-btn sm-btn--ghost" href="<?php echo esc_url( sm_page_url( 'company', array( 'c' => $account['company']['slug'] ) ) ); ?>" target="_blank" rel="noopener"><?php sm_icon( 'eye' ); ?><?php esc_html_e( 'View public page', 'sm-marketplace' ); ?></a>
		<button class="sm-btn sm-btn--accent" type="submit"><?php sm_icon( 'check' ); ?><?php esc_html_e( 'Save', 'sm-marketplace' ); ?></button>
	</div>
</form>

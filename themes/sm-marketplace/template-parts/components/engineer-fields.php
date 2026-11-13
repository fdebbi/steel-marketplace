<?php
/**
 * Partner engineer profile fields (application page and dashboard profile).
 *
 * @var array $args {
 *   values: array  Saved values (partner_type, bet_name, degree, speciality, experience, specialties, bio).
 *   titles: array  Professional titles code => label (0.29.0).
 *   specs:  array  Specialities code => label.
 *   types:  array  Engineering services code => label (stored as specialties).
 *   locked: bool   The title cannot change (approved engineer).
 * }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
$v      = (array) ( $args['values'] ?? array() );
$locked = ! empty( $args['locked'] );
$old    = static fn( $k ) => sm_theme_old( $k, $v[ $k ] ?? '' );
$specs  = (array) sm_theme_old( 'specialties', $v['specialties'] ?? array() );
$bet    = 'bet' === (string) $old( 'partner_type' );
$deg    = (string) $old( 'degree' );
$spec   = (string) $old( 'speciality' );
$titles = (array) ( $args['titles'] ?? array() );
$specl  = (array) ( $args['specs'] ?? array() );
?>
<fieldset class="sm-field">
	<legend><?php esc_html_e( 'You are', 'sm-marketplace' ); ?></legend>
	<div class="sm-checkgrid">
		<label class="sm-check"><input type="radio" name="partner_type" value="independent" <?php checked( ! $bet ); ?>><span><?php esc_html_e( 'Independent engineer', 'sm-marketplace' ); ?></span></label>
		<label class="sm-check"><input type="radio" name="partner_type" value="bet" <?php checked( $bet ); ?>><span><?php esc_html_e( 'Engineering office (BET)', 'sm-marketplace' ); ?></span></label>
	</div>
</fieldset>
<div class="sm-field sm-field--row">
	<div>
		<label for="e-bet"><?php esc_html_e( 'Name of the engineering office', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'BET only', 'sm-marketplace' ); ?></small></label>
		<input id="e-bet" name="bet_name" dir="auto" maxlength="160" value="<?php echo esc_attr( (string) $old( 'bet_name' ) ); ?>"<?php sm_theme_aria( 'bet_name' ); ?>>
		<?php sm_theme_error( 'bet_name' ); ?>
	</div>
	<div>
		<label for="e-exp"><?php esc_html_e( 'Years of experience', 'sm-marketplace' ); ?></label>
		<input id="e-exp" name="experience" type="number" inputmode="numeric" min="0" max="60" dir="ltr" required value="<?php echo esc_attr( (string) $old( 'experience' ) ); ?>"<?php sm_theme_aria( 'experience' ); ?>>
		<?php sm_theme_error( 'experience' ); ?>
	</div>
</div>
<div class="sm-field sm-field--row">
	<div>
		<label for="e-degree"><?php esc_html_e( 'Professional title', 'sm-marketplace' ); ?></label>
		<select id="e-degree" name="degree" required<?php sm_theme_aria( 'degree' ); ?>>
			<option value=""><?php esc_html_e( 'Select', 'sm-marketplace' ); ?></option>
			<?php foreach ( $titles as $code => $label ) : ?><option value="<?php echo esc_attr( $code ); ?>" <?php selected( $deg, (string) $code ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
		</select>
		<input name="degree_other" dir="auto" maxlength="120" value="<?php echo esc_attr( (string) $old( 'degree_other' ) ); ?>" placeholder="<?php esc_attr_e( 'Specify your title', 'sm-marketplace' ); ?>" aria-label="<?php esc_attr_e( 'Specify your title', 'sm-marketplace' ); ?>" style="margin-top:8px">
		<?php sm_theme_error( 'degree' ); ?><?php sm_theme_error( 'degree_other' ); ?>
	</div>
	<div>
		<label for="e-spec"><?php esc_html_e( 'Speciality', 'sm-marketplace' ); ?></label>
		<select id="e-spec" name="speciality" required<?php sm_theme_aria( 'speciality' ); ?>>
			<option value=""><?php esc_html_e( 'Select', 'sm-marketplace' ); ?></option>
			<?php foreach ( $specl as $code => $label ) : ?><option value="<?php echo esc_attr( $code ); ?>" <?php selected( $spec, (string) $code ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
		</select>
		<input name="speciality_other" dir="auto" maxlength="120" value="<?php echo esc_attr( (string) $old( 'speciality_other' ) ); ?>" placeholder="<?php esc_attr_e( 'Specify your speciality', 'sm-marketplace' ); ?>" aria-label="<?php esc_attr_e( 'Specify your speciality', 'sm-marketplace' ); ?>" style="margin-top:8px">
		<?php sm_theme_error( 'speciality' ); ?><?php sm_theme_error( 'speciality_other' ); ?>
	</div>
</div>
<fieldset class="sm-field">
	<legend><?php esc_html_e( 'Services offered', 'sm-marketplace' ); ?></legend>
	<div class="sm-checkgrid">
		<?php foreach ( (array) ( $args['types'] ?? array() ) as $code => $label ) : ?>
			<label class="sm-check"><input type="checkbox" name="specialties[]" value="<?php echo esc_attr( $code ); ?>" <?php checked( in_array( $code, $specs, true ) ); ?>><span><?php echo esc_html( $label ); ?></span></label>
		<?php endforeach; ?>
	</div>
	<p class="sm-help"><?php esc_html_e( 'Tick the engineering services you can carry out. The platform offers you the missions of these services.', 'sm-marketplace' ); ?></p>
	<?php sm_theme_error( 'specialties' ); ?>
</fieldset>
<div class="sm-field">
	<label for="e-bio"><?php esc_html_e( 'Experience and references', 'sm-marketplace' ); ?></label>
	<textarea id="e-bio" name="bio" rows="5" dir="auto" maxlength="2000" required placeholder="<?php esc_attr_e( 'Projects you designed or checked, software, standards (Eurocode 3, RPA, CCM 97)…', 'sm-marketplace' ); ?>"<?php sm_theme_aria( 'bio' ); ?>><?php echo esc_textarea( (string) $old( 'bio' ) ); ?></textarea>
	<p class="sm-help"><?php esc_html_e( 'Shown on the list of partner engineers. Do not write your phone number or e-mail: clients contact the platform.', 'sm-marketplace' ); ?></p>
	<?php sm_theme_error( 'bio' ); ?>
</div>

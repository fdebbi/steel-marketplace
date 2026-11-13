<?php
/**
 * Partner engineer profile fields (application page and dashboard profile).
 *
 * @var array $args {
 *   values: array  Saved values (title, diploma, school, grad_year, experience, specialties, bio).
 *   types:  array  Engineering services code => label (stored as specialties).
 *   locked: bool   Diploma, school and year cannot change (approved engineer).
 * }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
$v      = (array) ( $args['values'] ?? array() );
$locked = ! empty( $args['locked'] );
$old    = static fn( $k ) => sm_theme_old( $k, $v[ $k ] ?? '' );
$specs  = (array) sm_theme_old( 'specialties', $v['specialties'] ?? array() );
?>
<div class="sm-field">
	<label for="e-title"><?php esc_html_e( 'Professional title', 'sm-marketplace' ); ?></label>
	<input id="e-title" name="title" dir="auto" maxlength="160" required value="<?php echo esc_attr( (string) $old( 'title' ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. Civil engineer — steel structures', 'sm-marketplace' ); ?>"<?php sm_theme_aria( 'title' ); ?>>
	<?php sm_theme_error( 'title' ); ?>
</div>
<div class="sm-field sm-field--row">
	<div>
		<label for="e-diploma"><?php esc_html_e( 'Diploma', 'sm-marketplace' ); ?></label>
		<input id="e-diploma" name="diploma" dir="auto" maxlength="160" value="<?php echo esc_attr( (string) $old( 'diploma' ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. State engineer in civil engineering', 'sm-marketplace' ); ?>" <?php echo $locked ? 'readonly' : 'required'; ?><?php sm_theme_aria( 'diploma' ); ?>>
		<?php sm_theme_error( 'diploma' ); ?>
	</div>
	<div>
		<label for="e-school"><?php esc_html_e( 'University or school', 'sm-marketplace' ); ?></label>
		<input id="e-school" name="school" dir="auto" maxlength="160" value="<?php echo esc_attr( (string) $old( 'school' ) ); ?>" <?php echo $locked ? 'readonly' : 'required'; ?><?php sm_theme_aria( 'school' ); ?>>
		<?php sm_theme_error( 'school' ); ?>
	</div>
</div>
<div class="sm-field sm-field--row">
	<div>
		<label for="e-year"><?php esc_html_e( 'Year of the diploma', 'sm-marketplace' ); ?></label>
		<input id="e-year" name="grad_year" type="number" inputmode="numeric" min="1960" max="<?php echo esc_attr( gmdate( 'Y' ) ); ?>" dir="ltr" value="<?php echo esc_attr( (string) $old( 'grad_year' ) ); ?>" <?php echo $locked ? 'readonly' : 'required'; ?><?php sm_theme_aria( 'grad_year' ); ?>>
		<?php sm_theme_error( 'grad_year' ); ?>
	</div>
	<div>
		<label for="e-exp"><?php esc_html_e( 'Years of experience', 'sm-marketplace' ); ?></label>
		<input id="e-exp" name="experience" type="number" inputmode="numeric" min="0" max="60" dir="ltr" required value="<?php echo esc_attr( (string) $old( 'experience' ) ); ?>"<?php sm_theme_aria( 'experience' ); ?>>
		<?php sm_theme_error( 'experience' ); ?>
	</div>
</div>
<?php if ( $locked ) : ?>
	<p class="sm-help"><?php sm_icon( 'lock' ); ?> <?php esc_html_e( 'The diploma was checked by the platform. Write to the platform to change it.', 'sm-marketplace' ); ?></p>
<?php endif; ?>
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

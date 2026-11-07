<?php
/**
 * Template Name: SM — Register
 *
 * Two-step registration: account class (commercial or engineering), then details.
 * The account class cannot be mixed: engineers get a services-only account.
 * Account creation itself is handled by sm-core (Phase 2).
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
get_header();
get_template_part(
	'template-parts/auth-shell',
	null,
	array(
		'title'   => __( 'Create your account', 'sm-marketplace' ),
		'lead'    => __( 'Choose the type of account. You can enable several business activities later.', 'sm-marketplace' ),
		'content' => static function () {
			$type       = 'engineering' === sm_theme_old( 'account_class', sm_theme_query_arg( 'type' ) ) ? 'engineering' : 'commercial';
			$old_acts   = (array) sm_theme_old( 'activities', array() );
			$activities = sm_activity_labels();
			unset( $activities['engineer'] );
			?>
			<?php sm_theme_notices( true ); ?>
			<?php sm_theme_form_open( 'register', array( 'data-sm-register' => '' ) ); ?>
				<fieldset class="sm-choice" data-sm-choice>
					<legend class="screen-reader-text"><?php esc_html_e( 'Account type', 'sm-marketplace' ); ?></legend>
					<label class="sm-choice__item">
						<input type="radio" name="account_class" value="commercial" <?php checked( $type, 'commercial' ); ?>>
						<span class="sm-choice__card">
							<?php sm_icon( 'company' ); ?>
							<strong><?php esc_html_e( 'Business account', 'sm-marketplace' ); ?></strong>
							<small><?php esc_html_e( 'Buy and sell steel, reuse lots and buildings, answer quotations, offer fabrication.', 'sm-marketplace' ); ?></small>
						</span>
					</label>
					<label class="sm-choice__item">
						<input type="radio" name="account_class" value="engineering" <?php checked( $type, 'engineering' ); ?>>
						<span class="sm-choice__card">
							<?php sm_icon( 'hard-hat' ); ?>
							<strong><?php esc_html_e( 'Engineer / engineering office', 'sm-marketplace' ); ?></strong>
							<small><?php esc_html_e( 'Publish engineering services and receive client requests. No product trading.', 'sm-marketplace' ); ?></small>
						</span>
					</label>
				</fieldset>

				<fieldset class="sm-field" data-sm-show-for="commercial">
					<legend><?php esc_html_e( 'Business activities', 'sm-marketplace' ); ?></legend>
					<div class="sm-checkgrid">
						<?php foreach ( $activities as $key => $label ) : ?>
							<label class="sm-check"><input type="checkbox" name="activities[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $old_acts, true ) ); ?>><span><?php echo esc_html( $label ); ?></span></label>
						<?php endforeach; ?>
					</div>
					<?php sm_theme_error( 'activities' ); ?>
				</fieldset>

				<div class="sm-field">
					<label for="g-company"><span data-sm-show-for="commercial"><?php esc_html_e( 'Company or trade name', 'sm-marketplace' ); ?></span><span data-sm-show-for="engineering" hidden><?php esc_html_e( 'Name or office name', 'sm-marketplace' ); ?></span></label>
					<input id="g-company" name="company" autocomplete="organization" dir="auto" required value="<?php echo esc_attr( sm_theme_old( 'company' ) ); ?>"<?php sm_theme_aria( 'company' ); ?>>
					<?php sm_theme_error( 'company' ); ?>
				</div>
				<div class="sm-field sm-field--row">
					<div><label for="g-name"><?php esc_html_e( 'Full name', 'sm-marketplace' ); ?></label><input id="g-name" name="name" autocomplete="name" dir="auto" required value="<?php echo esc_attr( sm_theme_old( 'name' ) ); ?>"<?php sm_theme_aria( 'name' ); ?>><?php sm_theme_error( 'name' ); ?></div>
					<div>
						<label for="g-wilaya"><?php esc_html_e( 'Wilaya', 'sm-marketplace' ); ?></label>
						<select id="g-wilaya" name="wilaya" required>
							<option value=""><?php esc_html_e( 'Select', 'sm-marketplace' ); ?></option>
							<?php foreach ( sm_theme_wilayas() as $code => $name ) : ?><option value="<?php echo esc_attr( $code ); ?>" <?php selected( sm_theme_old( 'wilaya' ), (string) $code ); ?>><?php echo esc_html( $code . ' — ' . sm_t( $name ) ); ?></option><?php endforeach; ?>
						</select>
						<?php sm_theme_error( 'wilaya' ); ?>
					</div>
				</div>
				<div class="sm-field sm-field--row">
					<div><label for="g-phone"><?php esc_html_e( 'Mobile phone', 'sm-marketplace' ); ?></label><input id="g-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" dir="ltr" placeholder="05 / 06 / 07 …" required value="<?php echo esc_attr( sm_theme_old( 'phone' ) ); ?>"<?php sm_theme_aria( 'phone' ); ?>><?php sm_theme_error( 'phone' ); ?></div>
					<div><label for="g-email"><?php esc_html_e( 'Email', 'sm-marketplace' ); ?></label><input id="g-email" name="email" type="email" autocomplete="email" dir="ltr" required value="<?php echo esc_attr( sm_theme_old( 'email' ) ); ?>"<?php sm_theme_aria( 'email' ); ?>><?php sm_theme_error( 'email' ); ?></div>
				</div>
				<div class="sm-field">
					<label for="g-pass"><?php esc_html_e( 'Password', 'sm-marketplace' ); ?></label>
					<div class="sm-input-group">
						<input id="g-pass" name="password" type="password" autocomplete="new-password" minlength="10" dir="ltr" required aria-describedby="g-pass-help">
						<button type="button" class="sm-input-group__btn" data-sm-password aria-controls="g-pass"><?php sm_icon( 'eye' ); ?><span class="screen-reader-text"><?php esc_html_e( 'Show', 'sm-marketplace' ); ?></span></button>
					</div>
					<p class="sm-help" id="g-pass-help"><?php esc_html_e( 'At least 10 characters.', 'sm-marketplace' ); ?></p>
					<?php sm_theme_error( 'password' ); ?>
				</div>
				<label class="sm-check"><input type="checkbox" name="terms" required><span><?php esc_html_e( 'I accept the terms of use and the listing rules.', 'sm-marketplace' ); ?></span></label>
				<?php sm_theme_error( 'terms' ); ?>
				<button class="sm-btn sm-btn--accent sm-btn--block sm-btn--lg" type="submit"><?php esc_html_e( 'Create my account', 'sm-marketplace' ); ?></button>
				<p class="sm-help"><?php esc_html_e( 'You will receive a 6-digit code to verify your account. Business verification (RC, NIF, NIS) unlocks the verified badge.', 'sm-marketplace' ); ?></p>
				<p class="sm-form__alt"><?php esc_html_e( 'Already registered?', 'sm-marketplace' ); ?> <a href="<?php echo esc_url( sm_page_url( 'login' ) ); ?>"><?php esc_html_e( 'Sign in', 'sm-marketplace' ); ?></a></p>
			</form>
			<?php
		},
	)
);
get_footer();

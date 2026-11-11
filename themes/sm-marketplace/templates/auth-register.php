<?php
/**
 * Template Name: SM — Register
 *
 * Registration in one step. Three kinds of account (0.13.0):
 * - company: chooses one or more types (contractor, manufacturer, supplier…);
 * - professional: chooses one or more trades (welder, erector…), publishes
 *   services and a portfolio, buys and sells;
 * - client: private account, buys, sells and requests services and studies.
 * Partner engineers apply on their own page ("Become a partner engineer").
 * Types and trades come from SM Core (editable by the administrator).
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
		'lead'    => __( 'Choose the kind of account that describes you. Every account can buy and sell.', 'sm-marketplace' ),
		'content' => static function () {
			$types    = sm_account_types();
			$asked    = sm_theme_old( 'account_class', sm_theme_query_arg( 'type' ) );
			$type     = in_array( $asked, array( 'company', 'professional', 'client' ), true ) ? $asked : 'company';
			$old_acts = (array) sm_theme_old( 'activities', array() );
			$cards    = array(
				'company'      => array( 'company', __( 'Company', 'sm-marketplace' ), __( 'Steel construction, manufacturing, supply, recycling, dismantling. A public company page.', 'sm-marketplace' ) ),
				'professional' => array( 'wrench', __( 'Professional', 'sm-marketplace' ), __( 'Welder, erector, roofer, boilermaker… Publish your services and your completed projects.', 'sm-marketplace' ) ),
				'client'       => array( 'user', __( 'Client', 'sm-marketplace' ), __( 'Private account: buy, sell, ask for quotes, services and engineering studies.', 'sm-marketplace' ) ),
			);
			?>
			<?php sm_theme_notices( true ); ?>
			<?php sm_theme_form_open( 'register', array( 'data-sm-register' => '' ) ); ?>
				<fieldset class="sm-choice sm-choice--3" data-sm-choice>
					<legend class="screen-reader-text"><?php esc_html_e( 'Account type', 'sm-marketplace' ); ?></legend>
					<?php foreach ( $cards as $key => list( $icon, $title, $text ) ) : ?>
						<label class="sm-choice__item">
							<input type="radio" name="account_class" value="<?php echo esc_attr( $key ); ?>" <?php checked( $type, $key ); ?>>
							<span class="sm-choice__card">
								<?php sm_icon( $icon ); ?>
								<strong><?php echo esc_html( $title ); ?></strong>
								<small><?php echo esc_html( $text ); ?></small>
							</span>
						</label>
					<?php endforeach; ?>
				</fieldset>
				<div class="sm-auth__alt">
					<?php sm_icon( 'hard-hat' ); ?>
					<p><?php esc_html_e( 'Are you an engineer?', 'sm-marketplace' ); ?> <a href="<?php echo esc_url( sm_page_url( 'engineer-apply' ) ); ?>"><?php esc_html_e( 'Become a partner engineer of the platform', 'sm-marketplace' ); ?></a></p>
				</div>

				<?php foreach ( array( 'company' => __( 'Type of company', 'sm-marketplace' ), 'professional' => __( 'Your trades', 'sm-marketplace' ) ) as $class => $legend ) : ?>
					<fieldset class="sm-field" data-sm-show-for="<?php echo esc_attr( $class ); ?>">
						<legend><?php echo esc_html( $legend ); ?></legend>
						<div class="sm-checkgrid">
							<?php foreach ( (array) ( $types['classes'][ $class ]['activities'] ?? array() ) as $key => $label ) : ?>
								<label class="sm-check"><input type="checkbox" name="activities[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $old_acts, true ) ); ?>><span><?php echo esc_html( $label ); ?></span></label>
							<?php endforeach; ?>
						</div>
						<p class="sm-help"><?php echo esc_html( 'company' === $class ? __( 'Choose one or more. You can change them later in your company profile.', 'sm-marketplace' ) : __( 'Choose one or more trades.', 'sm-marketplace' ) ); ?></p>
						<?php sm_theme_error( 'activities' ); ?>
					</fieldset>
				<?php endforeach; ?>

				<div class="sm-field" data-sm-show-for="company professional">
					<label for="g-company"><span data-sm-show-for="company"><?php esc_html_e( 'Company or trade name', 'sm-marketplace' ); ?></span><span data-sm-show-for="professional" hidden><?php esc_html_e( 'Business name (optional)', 'sm-marketplace' ); ?></span></label>
					<input id="g-company" name="company" autocomplete="organization" dir="auto" value="<?php echo esc_attr( sm_theme_old( 'company' ) ); ?>"<?php sm_theme_aria( 'company' ); ?>>
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
				<p class="sm-help"><?php esc_html_e( 'You will receive a 6-digit code to verify your account. Sending your documents later unlocks the verified badge.', 'sm-marketplace' ); ?></p>
				<p class="sm-form__alt"><?php esc_html_e( 'Already registered?', 'sm-marketplace' ); ?> <a href="<?php echo esc_url( sm_page_url( 'login' ) ); ?>"><?php esc_html_e( 'Sign in', 'sm-marketplace' ); ?></a></p>
			</form>
			<?php
		},
	)
);
get_footer();

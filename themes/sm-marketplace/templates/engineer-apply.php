<?php
/**
 * Template Name: SM — Become a partner engineer
 *
 * Application of a partner engineer (sm-engineering): a separate account,
 * the diploma and documents, checked by the platform before approval.
 * States: guest (full form), reapply (after a refusal), engineer (already
 * applied: status), other (signed in with another kind of account), closed.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
get_header();
get_template_part(
	'template-parts/auth-shell',
	null,
	array(
		'title'   => __( 'Become a partner engineer', 'sm-marketplace' ),
		'lead'    => __( 'The platform receives the study and inspection requests of its clients and gives them to its partner engineers as subcontractors. You work on the missions; the platform deals with the clients, the quotes and the payments.', 'sm-marketplace' ),
		'claim'   => __( 'Engineering missions in steel construction, without looking for clients.', 'sm-marketplace' ),
		'points'  => array(
			__( 'Missions matching your specialties', 'sm-marketplace' ),
			__( 'Fee agreed before you start, paid by the platform', 'sm-marketplace' ),
			__( 'Your contact details never published', 'sm-marketplace' ),
		),
		'content' => static function () {
			$d = sm_theme_data( 'engineer_apply' );
			if ( ! is_array( $d ) ) {
				sm_component( 'empty-state', array( 'icon' => 'hard-hat', 'title' => __( 'Applications open soon', 'sm-marketplace' ), 'text' => __( 'This page needs the SM Engineering plugin.', 'sm-marketplace' ) ) );
				return;
			}
			sm_theme_notices( true );
			$state = $d['state'];
			if ( 'engineer' === $state ) {
				$msg = 'approved' === $d['status'] ? __( 'You are a partner engineer of the platform. Your missions are in your dashboard.', 'sm-marketplace' ) : ( 'suspended' === $d['status'] ? __( 'Your partner account is suspended. Write to the platform for details.', 'sm-marketplace' ) : __( 'Your application is being reviewed. You receive the decision by e-mail.', 'sm-marketplace' ) );
				echo '<div class="sm-notice sm-notice--info">' . sm_get_icon( 'info' ) . '<p>' . esc_html( $msg ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
				echo '<p><a class="sm-btn sm-btn--primary" href="' . esc_url( $d['account'] ) . '">' . esc_html__( 'Open my dashboard', 'sm-marketplace' ) . '</a></p>';
				return;
			}
			if ( 'other' === $state ) {
				echo '<div class="sm-notice sm-notice--warn">' . sm_get_icon( 'alert' ) . '<p>' . esc_html__( 'You are signed in with a company, professional or client account. A partner engineer has a separate account: sign out, then apply with another e-mail address.', 'sm-marketplace' ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
				echo '<p><a class="sm-btn sm-btn--ghost" href="' . esc_url( wp_logout_url( sm_page_url( 'engineer-apply' ) ) ) . '">' . esc_html__( 'Sign out', 'sm-marketplace' ) . '</a></p>';
				return;
			}
			if ( empty( $d['open'] ) ) {
				echo '<div class="sm-notice sm-notice--info">' . sm_get_icon( 'info' ) . '<p>' . esc_html__( 'Applications are closed for the moment. Come back later or write to the platform.', 'sm-marketplace' ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
				return;
			}
			$guest = 'guest' === $state;
			?>
			<ol class="sm-steps-inline">
				<li><?php sm_icon( 'file' ); ?><span><?php esc_html_e( 'You apply with your diploma', 'sm-marketplace' ); ?></span></li>
				<li><?php sm_icon( 'shield' ); ?><span><?php esc_html_e( 'The platform checks it', 'sm-marketplace' ); ?></span></li>
				<li><?php sm_icon( 'hard-hat' ); ?><span><?php esc_html_e( 'You receive missions', 'sm-marketplace' ); ?></span></li>
			</ol>
			<?php if ( 'reapply' === $state ) : ?>
				<div class="sm-notice sm-notice--warn"><?php sm_icon( 'alert' ); ?><p><strong><?php esc_html_e( 'Your application was not approved:', 'sm-marketplace' ); ?></strong> <?php echo esc_html( $d['reason'] ); ?> <?php esc_html_e( 'Correct it and send it again.', 'sm-marketplace' ); ?></p></div>
			<?php endif; ?>
			<?php sm_theme_form_open( 'engineer_apply', array( 'class' => 'sm-form sme-apply', 'enctype' => 'multipart/form-data' ) ); ?>
				<?php if ( $guest ) : ?>
					<h2 class="sm-form__section"><?php esc_html_e( 'Your account', 'sm-marketplace' ); ?></h2>
					<div class="sm-field sm-field--row">
						<div><label for="a-name"><?php esc_html_e( 'Full name', 'sm-marketplace' ); ?></label><input id="a-name" name="name" autocomplete="name" dir="auto" required value="<?php echo esc_attr( sm_theme_old( 'name' ) ); ?>"<?php sm_theme_aria( 'name' ); ?>><?php sm_theme_error( 'name' ); ?></div>
						<div>
							<label for="a-wilaya"><?php esc_html_e( 'Wilaya', 'sm-marketplace' ); ?></label>
							<select id="a-wilaya" name="wilaya" required>
								<option value=""><?php esc_html_e( 'Select', 'sm-marketplace' ); ?></option>
								<?php foreach ( sm_theme_wilayas() as $code => $name ) : ?><option value="<?php echo esc_attr( $code ); ?>" <?php selected( sm_theme_old( 'wilaya' ), (string) $code ); ?>><?php echo esc_html( $code . ' — ' . sm_t( $name ) ); ?></option><?php endforeach; ?>
							</select>
							<?php sm_theme_error( 'wilaya' ); ?>
						</div>
					</div>
					<div class="sm-field sm-field--row">
						<div><label for="a-phone"><?php esc_html_e( 'Mobile phone', 'sm-marketplace' ); ?></label><input id="a-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" dir="ltr" placeholder="05 / 06 / 07 …" required value="<?php echo esc_attr( sm_theme_old( 'phone' ) ); ?>"<?php sm_theme_aria( 'phone' ); ?>><?php sm_theme_error( 'phone' ); ?></div>
						<div><label for="a-email"><?php esc_html_e( 'Email', 'sm-marketplace' ); ?></label><input id="a-email" name="email" type="email" autocomplete="email" dir="ltr" required value="<?php echo esc_attr( sm_theme_old( 'email' ) ); ?>"<?php sm_theme_aria( 'email' ); ?>><?php sm_theme_error( 'email' ); ?></div>
					</div>
					<div class="sm-field">
						<label for="a-pass"><?php esc_html_e( 'Password', 'sm-marketplace' ); ?></label>
						<div class="sm-input-group">
							<input id="a-pass" name="password" type="password" autocomplete="new-password" minlength="10" dir="ltr" required aria-describedby="a-pass-help">
							<button type="button" class="sm-input-group__btn" data-sm-password aria-controls="a-pass"><?php sm_icon( 'eye' ); ?><span class="screen-reader-text"><?php esc_html_e( 'Show', 'sm-marketplace' ); ?></span></button>
						</div>
						<p class="sm-help" id="a-pass-help"><?php esc_html_e( 'At least 10 characters.', 'sm-marketplace' ); ?></p>
						<?php sm_theme_error( 'password' ); ?>
					</div>
				<?php endif; ?>

				<h2 class="sm-form__section"><?php esc_html_e( 'Your profile', 'sm-marketplace' ); ?></h2>
				<?php sm_component( 'engineer-fields', array( 'values' => $d['values'], 'types' => $d['types'] ) ); ?>
				<label class="sm-upload sm-upload--small">
					<input type="file" name="photo[]" accept="image/jpeg,image/png,image/webp" data-sm-filename>
					<?php sm_icon( 'camera', 'sm-upload__icon' ); ?>
					<strong><?php esc_html_e( 'Your photo', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></strong>
					<small data-sm-filename-label><?php esc_html_e( 'Shown on the list of partner engineers. JPG, PNG or WebP, 4 MB maximum.', 'sm-marketplace' ); ?></small>
				</label>

				<h2 class="sm-form__section"><?php esc_html_e( 'Documents', 'sm-marketplace' ); ?></h2>
				<p class="sm-help"><?php esc_html_e( 'Kept in a private space, seen only by the platform team.', 'sm-marketplace' ); ?></p>
				<?php foreach ( $d['documents'] as $key => $doc ) : ?>
					<div class="sm-field">
						<label class="sm-upload sm-upload--small">
							<input type="file" name="doc_<?php echo esc_attr( $key ); ?>" accept=".pdf,image/jpeg,image/png" <?php echo $doc['required'] ? 'required' : ''; ?> data-sm-filename>
							<?php sm_icon( 'file', 'sm-upload__icon' ); ?>
							<strong><?php echo esc_html( $doc['label'] ); ?><?php echo $doc['required'] ? ' *' : ''; ?></strong>
							<small data-sm-filename-label><?php echo esc_html( sprintf( /* translators: %d: MB. */ __( 'PDF, JPG or PNG, %d MB maximum', 'sm-marketplace' ), (int) $d['file_mb'] ) ); ?></small>
						</label>
						<?php sm_theme_error( 'doc_' . $key ); ?>
					</div>
				<?php endforeach; ?>

				<?php if ( $guest ) : ?>
					<label class="sm-check"><input type="checkbox" name="terms" required><span><?php esc_html_e( 'I accept the terms of use and the rules of partner engineers: I work on the missions the platform gives me and never contact its clients directly.', 'sm-marketplace' ); ?></span></label>
					<?php sm_theme_error( 'terms' ); ?>
				<?php endif; ?>
				<button class="sm-btn sm-btn--accent sm-btn--block sm-btn--lg" type="submit"><?php echo esc_html( $guest ? __( 'Send my application', 'sm-marketplace' ) : __( 'Send my application again', 'sm-marketplace' ) ); ?></button>
				<?php if ( $guest ) : ?>
					<p class="sm-form__alt"><?php esc_html_e( 'Already a partner?', 'sm-marketplace' ); ?> <a href="<?php echo esc_url( sm_page_url( 'login' ) ); ?>"><?php esc_html_e( 'Sign in', 'sm-marketplace' ); ?></a></p>
				<?php endif; ?>
			</form>
			<?php
		},
	)
);
get_footer();

<?php
/**
 * Template Name: SM — Become a partner engineer
 *
 * Application of a partner engineer (sm-engineering), 0.29.0: an assistant in
 * five steps shown as a window over the page (full screen on a phone):
 *
 *   1. personal information   last name, first name, mobile, wilaya, e-mail
 *   2. professional profile   independent engineer or engineering office (BET),
 *                              title and speciality (lists), years of experience,
 *                              services (multiple choice), presentation
 *   3. documents              diploma, identity, BET approval (MHUV) for a BET,
 *                              photo or logo (required since 0.30.0); photos are made lighter
 *   4. password and terms
 *   5. check and confirm      summary, 6-digit code sent to the e-mail address
 *
 * One form, sent once at the end: the account is created only after the e-mail
 * code is confirmed. A draft of the texts (never the password nor the files) is
 * kept on the device. States: guest, reapply (after a refusal: steps 2, 3, 4),
 * sent, engineer, other, closed.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
get_header();
$d     = sm_theme_data( 'engineer_apply' );
$close = sm_page_url( 'engineers' );

/** Window around a simple message (states other than the form). */
$box = static function ( $icon, $title, $text, $actions = '' ) use ( $close ) {
	?>
	<div class="wz-page">
		<div class="wz wz--msg" role="dialog" aria-modal="false" aria-labelledby="wz-title">
			<div class="wz__top">
				<span class="wz__brand"><?php sm_icon( 'hard-hat' ); ?></span>
				<div><h1 class="wz__title" id="wz-title"><?php esc_html_e( 'Become a partner engineer', 'sm-marketplace' ); ?></h1></div>
				<a class="wz__close" href="<?php echo esc_url( $close ); ?>"><?php sm_icon( 'close' ); ?><span class="screen-reader-text"><?php esc_html_e( 'Close', 'sm-marketplace' ); ?></span></a>
			</div>
			<div class="wz__body wz__done">
				<div class="big"><?php sm_icon( $icon ); ?></div>
				<h2 class="wz__h"><?php echo esc_html( $title ); ?></h2>
				<?php sm_theme_notices( true ); ?>
				<?php echo $text; // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts below. ?>
				<?php if ( $actions ) : ?><p class="wz__actions"><?php echo $actions; // phpcs:ignore WordPress.Security.EscapeOutput ?></p><?php endif; ?>
			</div>
		</div>
	</div>
	<?php
};

if ( ! is_array( $d ) ) {
	$box( 'hard-hat', __( 'Applications open soon', 'sm-marketplace' ), '<p class="wz__lead">' . esc_html__( 'This page needs the SM Engineering plugin.', 'sm-marketplace' ) . '</p>' );
	get_footer();
	return;
}
$state = $d['state'];
if ( ! empty( $d['sent'] ) ) {
	$box(
		'check',
		__( 'Application sent', 'sm-marketplace' ),
		'<p class="wz__lead">' . esc_html__( 'Your e-mail address is confirmed and your account is created.', 'sm-marketplace' ) . '</p><ol><li>' . esc_html__( 'The team checks your diploma and your documents (usually within 48 hours).', 'sm-marketplace' ) . '</li><li>' . esc_html__( 'You receive an e-mail as soon as your profile is approved.', 'sm-marketplace' ) . '</li><li>' . esc_html__( 'The platform then offers you the missions of your services.', 'sm-marketplace' ) . '</li></ol>',
		'<a class="sm-btn sm-btn--primary" href="' . esc_url( $d['account'] ) . '">' . esc_html__( 'Go to my dashboard', 'sm-marketplace' ) . '</a>'
	);
	get_footer();
	return;
}
if ( 'engineer' === $state ) {
	$msg = 'approved' === $d['status'] ? __( 'You are a partner engineer of the platform. Your missions are in your dashboard.', 'sm-marketplace' ) : ( 'suspended' === $d['status'] ? __( 'Your partner account is suspended. Write to the platform for details.', 'sm-marketplace' ) : __( 'Your application is being reviewed. You receive the decision by e-mail.', 'sm-marketplace' ) );
	$box( 'info', __( 'Your partner account', 'sm-marketplace' ), '<p class="wz__lead">' . esc_html( $msg ) . '</p>', '<a class="sm-btn sm-btn--primary" href="' . esc_url( $d['account'] ) . '">' . esc_html__( 'Open my dashboard', 'sm-marketplace' ) . '</a>' );
	get_footer();
	return;
}
if ( 'other' === $state ) {
	$box( 'alert', __( 'A separate account', 'sm-marketplace' ), '<p class="wz__lead">' . esc_html__( 'You are signed in with a company, professional or client account. A partner engineer has a separate account: sign out, then apply with another e-mail address.', 'sm-marketplace' ) . '</p>', '<a class="sm-btn sm-btn--ghost" href="' . esc_url( wp_logout_url( sm_page_url( 'engineer-apply' ) ) ) . '">' . esc_html__( 'Sign out', 'sm-marketplace' ) . '</a>' );
	get_footer();
	return;
}
if ( empty( $d['open'] ) ) {
	$box( 'info', __( 'Applications closed', 'sm-marketplace' ), '<p class="wz__lead">' . esc_html__( 'Applications are closed for the moment. Come back later or write to the platform.', 'sm-marketplace' ) . '</p>' );
	get_footer();
	return;
}

$guest = 'guest' === $state;
$v     = (array) $d['values'];
$old   = static fn( $k, $def = '' ) => sm_theme_old( $k, $v[ $k ] ?? $def );
$bet   = 'bet' === (string) $old( 'partner_type', 'independent' );
$specs = array_map( 'strval', (array) sm_theme_old( 'specialties', $v['specialties'] ?? array() ) );
$steps = $guest
	? array( 1 => __( 'Personal information', 'sm-marketplace' ), 2 => __( 'Professional information', 'sm-marketplace' ), 3 => __( 'Documents', 'sm-marketplace' ), 4 => __( 'Password and terms', 'sm-marketplace' ), 5 => __( 'Verification', 'sm-marketplace' ) )
	: array( 2 => __( 'Professional information', 'sm-marketplace' ), 3 => __( 'Documents', 'sm-marketplace' ), 4 => __( 'Terms', 'sm-marketplace' ) );
$total = count( $steps );
$req   = static fn() => '<span class="wz__req" aria-hidden="true">*</span>';
$i18n  = array(
	'step'      => __( 'Step %1$d of %2$d', 'sm-marketplace' ),
	'chosen'    => __( '%d services chosen', 'sm-marketplace' ),
	'none'      => __( 'Choose the services', 'sm-marketplace' ),
	'required'  => __( 'Complete this field.', 'sm-marketplace' ),
	'email'     => __( 'Enter a valid e-mail address.', 'sm-marketplace' ),
	'phone'     => __( 'Enter a valid Algerian mobile number (05, 06 or 07 followed by 8 digits).', 'sm-marketplace' ),
	'services'  => __( 'Choose at least one service.', 'sm-marketplace' ),
	'file'      => __( 'Add this document.', 'sm-marketplace' ),
	'tooBig'    => __( 'This file is too heavy (%s MB maximum).', 'sm-marketplace' ),
	'same'      => __( 'The two passwords are different.', 'sm-marketplace' ),
	'short'     => __( 'At least 10 characters.', 'sm-marketplace' ),
	'weak'      => __( 'Weak password', 'sm-marketplace' ),
	'fair'      => __( 'Fair password', 'sm-marketplace' ),
	'good'      => __( 'Good password', 'sm-marketplace' ),
	'strong'    => __( 'Strong password', 'sm-marketplace' ),
	'identical' => __( 'Identical', 'sm-marketplace' ),
	'resendIn'  => __( 'Send the code again in %s', 'sm-marketplace' ),
	'resend'    => __( 'Send the code again', 'sm-marketplace' ),
	'sending'   => __( 'Sending the code…', 'sm-marketplace' ),
	'sentTo'    => __( 'Code sent to %s', 'sm-marketplace' ),
	'checking'  => __( 'Checking…', 'sm-marketplace' ),
	'codeLen'   => __( 'Enter the 6 digits of the code.', 'sm-marketplace' ),
	'network'   => __( 'The connection failed. Check your network and try again.', 'sm-marketplace' ),
	'compress'  => __( 'Making the photo lighter…', 'sm-marketplace' ),
	'saved'     => __( 'Draft saved on this device', 'sm-marketplace' ),
	'years'     => __( '%s years', 'sm-marketplace' ),
	'docsOk'    => __( '%d documents added', 'sm-marketplace' ),
	'filesOut'  => __( 'Add your documents again: files are never kept in the draft.', 'sm-marketplace' ),
	'replace'   => __( 'Replace', 'sm-marketplace' ),
);
$cfg   = array(
	'guest'  => $guest,
	'ajax'   => $d['ajax'],
	'resend' => (int) $d['resend'],
	'maxMb'  => (int) $d['file_mb'],
	'draft'  => 'sm-eng-apply-v1',
	'i18n'   => $i18n,
);
?>
<div class="wz-page">
	<?php sm_theme_form_open( 'engineer_apply', array( 'class' => 'wz', 'enctype' => 'multipart/form-data', 'data-wz' => '', 'role' => 'dialog', 'aria-modal' => 'false', 'aria-labelledby' => 'wz-title' ) ); ?>
		<script type="application/json" data-wz-config><?php echo wp_json_encode( $cfg ); ?></script>
		<div class="wz__top">
			<span class="wz__brand"><?php sm_icon( 'hard-hat' ); ?></span>
			<div>
				<h1 class="wz__title" id="wz-title"><?php echo esc_html( $guest ? __( 'Become a partner engineer', 'sm-marketplace' ) : __( 'Send your application again', 'sm-marketplace' ) ); ?></h1>
				<p class="wz__sub"><?php echo esc_html( sprintf( /* translators: %d: number of steps. */ __( '%d steps · about 5 minutes', 'sm-marketplace' ), $total ) ); ?></p>
			</div>
			<a class="wz__close" href="<?php echo esc_url( $close ); ?>" data-wz-close><?php sm_icon( 'close' ); ?><span class="screen-reader-text"><?php esc_html_e( 'Close', 'sm-marketplace' ); ?></span></a>
		</div>
		<ol class="wz__steps" aria-label="<?php esc_attr_e( 'Steps', 'sm-marketplace' ); ?>">
			<?php $n = 0; foreach ( $steps as $k => $label ) : ++$n; ?>
				<li data-wz-nav="<?php echo (int) $k; ?>"><span><b><?php echo (int) $n; ?></b><?php echo esc_html( $label ); ?></span></li>
			<?php endforeach; ?>
		</ol>
		<div class="wz__mob" aria-hidden="true"><div><span data-wz-count></span><small data-wz-name></small></div><div class="wz__bar"><i data-wz-progress></i></div></div>

		<div class="wz__body" data-wz-body>
			<?php sm_theme_notices( true ); ?>
			<?php if ( 'reapply' === $state ) : ?>
				<div class="sm-notice sm-notice--warn"><?php sm_icon( 'alert' ); ?><p><strong><?php esc_html_e( 'Your application was not approved:', 'sm-marketplace' ); ?></strong> <?php echo esc_html( $d['reason'] ); ?> <?php esc_html_e( 'Correct it and send it again.', 'sm-marketplace' ); ?></p></div>
			<?php endif; ?>
			<noscript><div class="sm-notice sm-notice--warn"><p><?php esc_html_e( 'This form needs JavaScript. Turn it on in your browser, then reload the page.', 'sm-marketplace' ); ?></p></div></noscript>

			<?php if ( $guest ) : ?>
			<section class="wz__panel" data-wz-step="1" aria-labelledby="wz-h1">
				<h2 class="wz__h" id="wz-h1"><?php esc_html_e( 'Your personal information', 'sm-marketplace' ); ?></h2>
				<p class="wz__lead"><?php esc_html_e( 'It is used to create your account and to reach you. It is never shown to clients.', 'sm-marketplace' ); ?> <?php esc_html_e( 'Already a partner?', 'sm-marketplace' ); ?> <a href="<?php echo esc_url( sm_page_url( 'login' ) ); ?>"><?php esc_html_e( 'Sign in', 'sm-marketplace' ); ?></a></p>
				<div class="wz__grid">
					<div><label class="l" for="w-last"><?php esc_html_e( 'Last name', 'sm-marketplace' ); ?></label><input class="i" id="w-last" name="last_name" autocomplete="family-name" dir="auto" required maxlength="60" value="<?php echo esc_attr( sm_theme_old( 'last_name', $d['last_name'] ) ); ?>"<?php sm_theme_aria( 'last_name' ); ?>><?php sm_theme_error( 'last_name' ); ?></div>
					<div><label class="l" for="w-first"><?php esc_html_e( 'First name', 'sm-marketplace' ); ?></label><input class="i" id="w-first" name="first_name" autocomplete="given-name" dir="auto" required maxlength="60" value="<?php echo esc_attr( sm_theme_old( 'first_name', $d['first_name'] ) ); ?>"<?php sm_theme_aria( 'first_name' ); ?>><?php sm_theme_error( 'first_name' ); ?></div>
					<div><label class="l" for="w-phone"><?php esc_html_e( 'Mobile phone', 'sm-marketplace' ); ?></label><input class="i" id="w-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" dir="ltr" placeholder="05 / 06 / 07 …" required data-wz-phone value="<?php echo esc_attr( sm_theme_old( 'phone' ) ); ?>"<?php sm_theme_aria( 'phone' ); ?>><?php sm_theme_error( 'phone' ); ?></div>
					<div>
						<label class="l" for="w-wilaya"><?php esc_html_e( 'Wilaya', 'sm-marketplace' ); ?></label>
						<select class="i" id="w-wilaya" name="wilaya" required<?php sm_theme_aria( 'wilaya' ); ?>>
							<option value=""><?php esc_html_e( 'Select', 'sm-marketplace' ); ?></option>
							<?php foreach ( sm_theme_wilayas() as $code => $name ) : ?><option value="<?php echo esc_attr( $code ); ?>" <?php selected( (string) sm_theme_old( 'wilaya' ), (string) $code ); ?>><?php echo esc_html( $code . ' — ' . sm_t( $name ) ); ?></option><?php endforeach; ?>
						</select>
						<?php sm_theme_error( 'wilaya' ); ?>
					</div>
					<div class="full"><label class="l" for="w-email"><?php esc_html_e( 'Email', 'sm-marketplace' ); ?></label><input class="i" id="w-email" name="email" type="email" autocomplete="email" dir="ltr" required value="<?php echo esc_attr( sm_theme_old( 'email' ) ); ?>"<?php sm_theme_aria( 'email' ); ?>><p class="hint"><?php esc_html_e( 'A verification code is sent to it at the last step.', 'sm-marketplace' ); ?></p><?php sm_theme_error( 'email' ); ?></div>
				</div>
			</section>
			<?php endif; ?>

			<section class="wz__panel" data-wz-step="2" aria-labelledby="wz-h2">
				<h2 class="wz__h" id="wz-h2"><?php esc_html_e( 'Your professional profile', 'sm-marketplace' ); ?></h2>
				<p class="wz__lead"><?php esc_html_e( 'The platform uses it to give you the missions that suit you.', 'sm-marketplace' ); ?></p>
				<div class="wz__seg" role="radiogroup" aria-label="<?php esc_attr_e( 'You are', 'sm-marketplace' ); ?>">
					<label><input type="radio" name="partner_type" value="independent" <?php checked( ! $bet ); ?> data-wz-type><?php sm_icon( 'user' ); ?><strong><?php esc_html_e( 'Independent engineer', 'sm-marketplace' ); ?></strong></label>
					<label><input type="radio" name="partner_type" value="bet" <?php checked( $bet ); ?> data-wz-type><?php sm_icon( 'company' ); ?><strong><?php esc_html_e( 'Engineering office (BET)', 'sm-marketplace' ); ?></strong></label>
				</div>
				<div class="wz__grid wz__grid--top">
					<div data-wz-bet><label class="l" for="w-bet"><?php esc_html_e( 'Name of the engineering office', 'sm-marketplace' ); ?></label><input class="i" id="w-bet" name="bet_name" dir="auto" maxlength="160" value="<?php echo esc_attr( (string) $old( 'bet_name' ) ); ?>" data-wz-bet-required<?php sm_theme_aria( 'bet_name' ); ?>><?php sm_theme_error( 'bet_name' ); ?></div>
					<div><label class="l" for="w-exp"><?php esc_html_e( 'Years of experience', 'sm-marketplace' ); ?></label><input class="i" id="w-exp" name="experience" type="number" inputmode="numeric" min="0" max="60" dir="ltr" required value="<?php echo esc_attr( (string) $old( 'experience' ) ); ?>"<?php sm_theme_aria( 'experience' ); ?>><?php sm_theme_error( 'experience' ); ?></div>
					<div>
						<label class="l" for="w-degree"><?php esc_html_e( 'Professional title', 'sm-marketplace' ); ?></label>
						<select class="i" id="w-degree" name="degree" required data-wz-other="w-degree-other"<?php sm_theme_aria( 'degree' ); ?>>
							<option value=""><?php esc_html_e( 'Select', 'sm-marketplace' ); ?></option>
							<?php foreach ( $d['titles'] as $code => $label ) : ?><option value="<?php echo esc_attr( $code ); ?>" <?php selected( (string) $old( 'degree' ), (string) $code ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
						</select>
						<input class="i wz__other" id="w-degree-other" name="degree_other" dir="auto" maxlength="120" placeholder="<?php esc_attr_e( 'Specify your title', 'sm-marketplace' ); ?>" value="<?php echo esc_attr( (string) $old( 'degree_other' ) ); ?>" aria-label="<?php esc_attr_e( 'Specify your title', 'sm-marketplace' ); ?>">
						<?php sm_theme_error( 'degree' ); ?><?php sm_theme_error( 'degree_other' ); ?>
					</div>
					<div>
						<label class="l" for="w-spec"><?php esc_html_e( 'Speciality', 'sm-marketplace' ); ?></label>
						<select class="i" id="w-spec" name="speciality" required data-wz-other="w-spec-other"<?php sm_theme_aria( 'speciality' ); ?>>
							<option value=""><?php esc_html_e( 'Select', 'sm-marketplace' ); ?></option>
							<?php foreach ( $d['specs'] as $code => $label ) : ?><option value="<?php echo esc_attr( $code ); ?>" <?php selected( (string) $old( 'speciality' ), (string) $code ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
						</select>
						<input class="i wz__other" id="w-spec-other" name="speciality_other" dir="auto" maxlength="120" placeholder="<?php esc_attr_e( 'Specify your speciality', 'sm-marketplace' ); ?>" value="<?php echo esc_attr( (string) $old( 'speciality_other' ) ); ?>" aria-label="<?php esc_attr_e( 'Specify your speciality', 'sm-marketplace' ); ?>">
						<?php sm_theme_error( 'speciality' ); ?><?php sm_theme_error( 'speciality_other' ); ?>
					</div>
					<div class="full">
						<span class="l" id="w-svc-l"><?php esc_html_e( 'Services you carry out', 'sm-marketplace' ); ?></span>
						<div class="wz__ms" data-wz-ms>
							<button class="wz__ms-btn" type="button" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="w-svc-l" data-wz-ms-btn><span class="wz__ms-tags" data-wz-ms-tags></span><?php sm_icon( 'chevron-down' ); ?></button>
							<div class="wz__ms-list" role="group" aria-labelledby="w-svc-l" hidden data-wz-ms-list>
								<?php foreach ( $d['types'] as $code => $label ) : ?>
									<label><input type="checkbox" name="specialties[]" value="<?php echo esc_attr( $code ); ?>" <?php checked( in_array( (string) $code, $specs, true ) ); ?>><span><?php echo esc_html( $label ); ?></span></label>
								<?php endforeach; ?>
								<div class="ft"><span data-wz-ms-count></span><button type="button" class="wz__ms-ok" data-wz-ms-ok><?php esc_html_e( 'OK', 'sm-marketplace' ); ?></button></div>
							</div>
						</div>
						<?php sm_theme_error( 'specialties' ); ?>
					</div>
					<div class="full"><label class="l" for="w-bio"><?php esc_html_e( 'Presentation', 'sm-marketplace' ); ?></label><textarea class="i" id="w-bio" name="bio" rows="2" dir="auto" maxlength="2000" minlength="20" required placeholder="<?php esc_attr_e( 'Projects you designed or checked, software, standards (Eurocode 3, RPA, CCM 97)…', 'sm-marketplace' ); ?>"<?php sm_theme_aria( 'bio' ); ?>><?php echo esc_textarea( (string) $old( 'bio' ) ); ?></textarea><?php sm_theme_error( 'bio' ); ?></div>
				</div>
			</section>

			<section class="wz__panel" data-wz-step="3" aria-labelledby="wz-h3">
				<h2 class="wz__h" id="wz-h3"><?php esc_html_e( 'Your documents', 'sm-marketplace' ); ?></h2>
				<p class="wz__lead"><?php esc_html_e( 'All documents are required. Choose your files or take them in photo: photos are made lighter before sending.', 'sm-marketplace' ); ?></p>
				<div class="wz__docs">
					<?php foreach ( $d['documents'] as $key => $doc ) : ?>
						<div class="wz__doc" data-wz-doc<?php echo $doc['bet'] ? ' data-wz-bet' : ''; ?>>
							<span class="ic"><?php sm_icon( $doc['bet'] ? 'company' : 'file' ); ?></span>
							<div>
								<strong><?php echo esc_html( $doc['label'] ); ?> <?php echo $req(); // phpcs:ignore WordPress.Security.EscapeOutput ?></strong>
								<small data-wz-doc-info><?php echo esc_html( sprintf( /* translators: %d: MB. */ __( 'PDF, JPG or PNG, %d MB maximum', 'sm-marketplace' ), (int) $d['file_mb'] ) ); ?></small>
								<div class="wz__bar" hidden data-wz-doc-bar><i></i></div>
								<?php sm_theme_error( 'doc_' . $key ); ?>
							</div>
							<label class="sm-btn sm-btn--ghost sm-btn--sm wz__pick">
								<?php sm_icon( 'upload' ); ?><span data-wz-doc-btn><?php esc_html_e( 'Add', 'sm-marketplace' ); ?></span>
								<input class="screen-reader-text" type="file" name="doc_<?php echo esc_attr( $key ); ?>" accept=".pdf,image/jpeg,image/png" data-wz-file data-required="1"<?php echo $doc['bet'] ? ' data-wz-bet-required' : ''; ?> aria-label="<?php echo esc_attr( $doc['label'] ); ?>">
							</label>
						</div>
					<?php endforeach; ?>
					<div class="wz__doc" data-wz-doc>
						<span class="ic"><?php sm_icon( 'camera' ); ?></span>
						<div>
							<strong><?php esc_html_e( 'Photo or logo', 'sm-marketplace' ); ?> <?php echo $req(); // phpcs:ignore WordPress.Security.EscapeOutput ?></strong>
							<small data-wz-doc-info><?php esc_html_e( 'JPG or PNG — resized automatically', 'sm-marketplace' ); ?></small>
							<div class="wz__bar" hidden data-wz-doc-bar><i></i></div>
							<?php sm_theme_error( 'photo' ); ?>
						</div>
						<label class="sm-btn sm-btn--ghost sm-btn--sm wz__pick">
							<?php sm_icon( 'upload' ); ?><span data-wz-doc-btn><?php esc_html_e( 'Choose', 'sm-marketplace' ); ?></span>
							<input class="screen-reader-text" type="file" name="photo[]" accept="image/jpeg,image/png,image/webp" data-wz-file<?php echo $guest || empty( $d['has_photo'] ) ? ' data-required="1"' : ''; ?> aria-label="<?php esc_attr_e( 'Photo or logo', 'sm-marketplace' ); ?>">
						</label>
					</div>
				</div>
				<p class="wz__lock"><?php sm_icon( 'lock' ); ?><span><?php esc_html_e( 'Your documents are kept in a private space: only the platform team sees them.', 'sm-marketplace' ); ?></span></p>
				<p class="wz__filesout" hidden data-wz-filesout><?php sm_icon( 'info' ); ?><span><?php echo esc_html( $i18n['filesOut'] ); ?></span></p>
			</section>

			<section class="wz__panel" data-wz-step="4" aria-labelledby="wz-h4">
				<h2 class="wz__h" id="wz-h4"><?php echo esc_html( $guest ? __( 'Password and terms', 'sm-marketplace' ) : __( 'Terms', 'sm-marketplace' ) ); ?></h2>
				<p class="wz__lead"><?php echo esc_html( $guest ? __( 'Last step before the check of your e-mail address.', 'sm-marketplace' ) : __( 'Accept the rules, then send your application again.', 'sm-marketplace' ) ); ?></p>
				<?php if ( $guest ) : ?>
					<div class="wz__grid">
						<div>
							<label class="l" for="w-pass"><?php esc_html_e( 'Password', 'sm-marketplace' ); ?></label>
							<div class="sm-input-group">
								<input class="i" id="w-pass" name="password" type="password" autocomplete="new-password" minlength="10" dir="ltr" required data-wz-pass<?php sm_theme_aria( 'password' ); ?>>
								<button type="button" class="sm-input-group__btn" data-sm-password aria-controls="w-pass"><?php sm_icon( 'eye' ); ?><span class="screen-reader-text"><?php esc_html_e( 'Show', 'sm-marketplace' ); ?></span></button>
							</div>
							<div class="wz__meter" aria-hidden="true" data-wz-meter><i></i><i></i><i></i><i></i></div>
							<p class="hint" data-wz-meter-text><?php esc_html_e( 'At least 10 characters.', 'sm-marketplace' ); ?></p>
							<?php sm_theme_error( 'password' ); ?>
						</div>
						<div>
							<label class="l" for="w-pass2"><?php esc_html_e( 'Confirm the password', 'sm-marketplace' ); ?></label>
							<input class="i" id="w-pass2" type="password" autocomplete="new-password" dir="ltr" required data-wz-pass2>
							<p class="hint" data-wz-pass2-text></p>
						</div>
					</div>
				<?php endif; ?>
				<p class="wz__terms-h"><?php esc_html_e( 'Rules of partner engineers', 'sm-marketplace' ); ?></p>
				<div class="wz__terms">
					<?php esc_html_e( 'In short:', 'sm-marketplace' ); ?>
					<ul>
						<li><?php esc_html_e( 'The platform offers you missions; you are free to accept them.', 'sm-marketplace' ); ?></li>
						<li><?php esc_html_e( 'You never contact the clients of the platform directly.', 'sm-marketplace' ); ?></li>
						<li><?php esc_html_e( 'Your fee is set when the mission is given to you and paid by the platform.', 'sm-marketplace' ); ?></li>
						<li><?php esc_html_e( 'Your documents and studies stay confidential.', 'sm-marketplace' ); ?></li>
					</ul>
				</div>
				<label class="wz__check"><input type="checkbox" name="terms" value="1" required <?php checked( (bool) sm_theme_old( 'terms' ) ); ?>><span><?php esc_html_e( 'I accept the terms of use and the rules of partner engineers.', 'sm-marketplace' ); ?></span></label>
				<?php sm_theme_error( 'terms' ); ?>
			</section>

			<?php if ( $guest ) : ?>
			<section class="wz__panel" data-wz-step="5" aria-labelledby="wz-h5">
				<h2 class="wz__h" id="wz-h5"><?php esc_html_e( 'Check and confirm', 'sm-marketplace' ); ?></h2>
				<p class="wz__lead"><?php esc_html_e( 'Read your application again, then type the code sent to your e-mail address.', 'sm-marketplace' ); ?></p>
				<div class="wz__recap">
					<div class="wz__card"><h3><?php esc_html_e( 'Personal', 'sm-marketplace' ); ?> <button type="button" data-wz-go="1"><?php esc_html_e( 'Edit', 'sm-marketplace' ); ?></button></h3><div data-wz-recap="1"></div></div>
					<div class="wz__card"><h3><?php esc_html_e( 'Professional', 'sm-marketplace' ); ?> <button type="button" data-wz-go="2"><?php esc_html_e( 'Edit', 'sm-marketplace' ); ?></button></h3><div data-wz-recap="2"></div></div>
					<div class="wz__card"><h3><?php esc_html_e( 'Documents', 'sm-marketplace' ); ?> <button type="button" data-wz-go="3"><?php esc_html_e( 'Edit', 'sm-marketplace' ); ?></button></h3><div data-wz-recap="3"></div></div>
					<div class="wz__card"><h3><?php esc_html_e( 'Account', 'sm-marketplace' ); ?> <button type="button" data-wz-go="4"><?php esc_html_e( 'Edit', 'sm-marketplace' ); ?></button></h3><div data-wz-recap="4"></div></div>
				</div>
				<div class="wz__otp">
					<p data-wz-otp-to><?php sm_icon( 'mail' ); ?> <span></span></p>
					<div class="wz__boxes" dir="ltr" data-wz-boxes>
						<?php for ( $k = 0; $k < 6; $k++ ) : ?><input type="text" inputmode="numeric" maxlength="1" autocomplete="<?php echo 0 === $k ? 'one-time-code' : 'off'; ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: position. */ __( 'Digit %d of the code', 'sm-marketplace' ), $k + 1 ) ); ?>"><?php endfor; ?>
					</div>
					<p class="wz__otp-msg" role="alert" data-wz-otp-msg><?php sm_theme_error( 'email_code' ); ?></p>
					<p class="wz__resend"><?php esc_html_e( 'Not received? Check your spam folder.', 'sm-marketplace' ); ?> <button type="button" class="sm-link" data-wz-resend disabled></button></p>
				</div>
			</section>
			<?php endif; ?>
		</div>

		<div class="wz__foot">
			<span class="wz__saved" data-wz-saved hidden><?php sm_icon( 'check' ); ?><span><?php echo esc_html( $i18n['saved'] ); ?></span></span>
			<span class="sp"></span>
			<button class="sm-btn sm-btn--ghost" type="button" data-wz-prev><?php sm_icon( 'chevron-start' ); ?><?php esc_html_e( 'Back', 'sm-marketplace' ); ?></button>
			<button class="sm-btn sm-btn--primary" type="button" data-wz-next><?php esc_html_e( 'Continue', 'sm-marketplace' ); ?><?php sm_icon( 'chevron-end' ); ?></button>
			<button class="sm-btn sm-btn--accent" type="submit" data-wz-submit hidden><?php sm_icon( 'check' ); ?><?php echo esc_html( $guest ? __( 'Confirm and send', 'sm-marketplace' ) : __( 'Send my application again', 'sm-marketplace' ) ); ?></button>
		</div>
	</form>
</div>
<?php
get_footer();

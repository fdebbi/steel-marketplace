<?php
/**
 * Study request in 4 steps (0.39.0, sm-engineering 0.6.0):
 * 1. the need (one part: technical study, follow-up or expertise),
 * 2. the type of project, 3. the dimensions with an indicative sketch drawn
 * while typing (or the questions of the follow-up / expertise), 4. place,
 * documents and sending.
 *
 * Without JavaScript every step stays usable: the parts and the shapes are
 * shown or hidden by CSS (:has), and the server checks the answers.
 *
 * @var array $args { d: mission_form data, pre: prefill from a listing, engineer: preferred engineer id }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$d        = (array) $args['d'];
$pre      = (array) ( $args['pre'] ?? array() );
$engineer = (int) ( $args['engineer'] ?? 0 );
$from     = is_array( $d['from'] ?? null ) ? $d['from'] : null;
$sent     = null !== sm_theme_old( 'part', null ) || null !== sm_theme_old( 'title', null ); // Back after an error.
$ob       = (array) sm_theme_old( 'b', array() );
$seed     = $from ? (array) $from['brief'] : (array) ( $pre['brief'] ?? array() );
$bv       = static function ( $k, $def = '' ) use ( $sent, $ob, $seed ) {
	$v = $sent ? ( $ob[ $k ] ?? $def ) : ( $seed[ $k ] ?? $def );
	if ( is_float( $v ) || is_int( $v ) ) {
		$v = rtrim( rtrim( number_format( (float) $v, 2, ',', '' ), '0' ), ',' );
	}
	return $v;
};
$bon      = static fn( $k ) => ! empty( $sent ? ( $ob[ $k ] ?? '' ) : ( $seed[ $k ] ?? '' ) );
$choices  = (array) ( $d['choices'] ?? array() );
$families = (array) ( $d['families'] ?? array() );
$services = (array) ( $d['services'] ?? array() );
$type     = (string) ( $d['type'] ?? '' );
$part_of  = array();
foreach ( $services as $sv ) {
	$part_of[ $sv['code'] ] = $sv['part'] ?? 'study';
}
$part     = (string) sm_theme_old( 'part', $from ? 'follow' : ( '' !== $type ? ( $part_of[ $type ] ?? 'study' ) : 'study' ) );
$project  = (string) sm_theme_old( 'project_type', $from['project_type'] ?? ( $pre['project_type'] ?? '' ) );
$chosen   = array_map( 'strval', (array) sm_theme_old( 'services', '' !== $type ? array( $type ) : array_keys( array_filter( $part_of, static fn( $p ) => 'study' === $p ) ) ) );
$ticked   = (array) sm_theme_old( 'prestations', array() );
$icons    = array(
	'study'     => '<path d="M6 36V16l15-9 15 9v20"/><path d="M6 36h30M14 36V22h14v14"/><path d="M14 22l14 14M28 22L14 36"/>',
	'follow'    => '<path d="M8 34l8-20h10l8 20"/><path d="M12 24h18"/><circle cx="21" cy="9" r="4"/>',
	'expertise' => '<circle cx="18" cy="18" r="10"/><path d="M26 26l10 10M14 18h8M18 14v8"/>',
);
$pics     = array(
	'storage'      => '<path d="M6 42V20l26-12 26 12v22M2 42h60"/>',
	'industrial'   => '<path d="M4 42V22l14-8 14 8 14-8 14 8v20M2 42h60"/>',
	'agricultural' => '<path d="M8 42V18l24-10 24 10v24M2 42h60M20 42V30h24v12"/>',
	'livestock'    => '<path d="M8 42V18l24-10 24 10v24M2 42h60M14 30h36"/>',
	'public'       => '<path d="M8 42V24q24-22 48 0v18M2 42h60"/>',
	'offices'      => '<path d="M14 42V8h36v34M14 20h36M14 31h36M2 42h60"/>',
	'commercial'   => '<path d="M10 42V14h44v28M10 24h44M26 42V30h12v12M2 42h60"/>',
	'housing'      => '<path d="M14 42V18l18-10 18 10v24M14 30h36M2 42h60"/>',
);
$roof_pic = array(
	'mono'   => '<path d="M4 24V12l38-8v20"/>',
	'duo'    => '<path d="M4 24V12l19-9 19 9v12"/>',
	'multi'  => '<path d="M2 24V12l10-7 11 7 11-7 10 7v12"/>',
	'curved' => '<path d="M4 24V12q19-14 38 0v12"/>',
	'flat'   => '<path d="M4 24V7h38v17"/>',
	'shed'   => '<path d="M2 24V8l14 6V8l14 6V8l14 6v10"/>',
);
$fam_hint = array(
	'portal' => __( 'portal frames', 'sm-marketplace' ),
	'levels' => __( 'floors', 'sm-marketplace' ),
	'free'   => __( 'describe it', 'sm-marketplace' ),
);
// A number with its unit; never required in HTML (hidden parts stay sendable), the server checks.
$unit = static function ( $key, $label, $u, $req = false, $ph = '' ) use ( $bv ) {
	$id = 'smx-' . $key;
	echo '<div class="smx-f"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . ( $req ? ' <span class="smx-req" aria-hidden="true">*</span>' : '' ) . '</label>';
	echo '<span class="smx-u"><input id="' . esc_attr( $id ) . '" name="b[' . esc_attr( $key ) . ']" inputmode="decimal" dir="ltr" autocomplete="off" value="' . esc_attr( (string) $bv( $key ) ) . '" placeholder="' . esc_attr( $ph ) . '" data-smx-in="' . esc_attr( $key ) . '"' . ( $req ? ' aria-required="true"' : '' ) . '>' . ( '' !== $u ? '<em>' . esc_html( $u ) . '</em>' : '' ) . '</span>';
	sm_theme_error( 'b_' . $key );
	echo '</div>';
};
$select = static function ( $key, $label, $list, $def = '' ) use ( $bv, $choices ) {
	$id  = 'smx-' . $key;
	$cur = (string) $bv( $key, $def );
	echo '<div class="smx-f"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label><select id="' . esc_attr( $id ) . '" name="b[' . esc_attr( $key ) . ']" data-smx-in="' . esc_attr( $key ) . '">';
	foreach ( (array) ( $choices[ $list ] ?? array() ) as $c => $l ) {
		echo '<option value="' . esc_attr( $c ) . '"' . selected( $cur, (string) $c, false ) . '>' . esc_html( $l ) . '</option>';
	}
	echo '</select>';
	sm_theme_error( 'b_' . $key );
	echo '</div>';
};
$chips = static function ( $key, $list, $multi = false ) use ( $bv, $choices ) {
	$cur = $bv( $key, $multi ? array() : '' );
	foreach ( (array) ( $choices[ $list ] ?? array() ) as $c => $l ) {
		$on = $multi ? in_array( (string) $c, array_map( 'strval', (array) $cur ), true ) : (string) $cur === (string) $c;
		echo '<label class="smx-chip"><input type="' . ( $multi ? 'checkbox' : 'radio' ) . '" name="b[' . esc_attr( $key ) . ']' . ( $multi ? '[]' : '' ) . '" value="' . esc_attr( $c ) . '"' . checked( $on, true, false ) . ' data-smx-in="' . esc_attr( $key ) . '"><span>' . esc_html( $l ) . '</span></label>';
	}
	sm_theme_error( 'b_' . $key );
};
$i18n = sm_study_sketch_i18n();
?>
<?php sm_theme_form_open( 'mission_request', array( 'class' => 'sm-form smx', 'enctype' => 'multipart/form-data' ) ); ?>
	<?php sm_theme_notices( true ); ?>
	<?php if ( ! empty( $args['listing'] ) ) : ?><input type="hidden" name="listing" value="<?php echo (int) $args['listing']; ?>"><?php endif; ?>
	<?php if ( $from ) : ?>
		<input type="hidden" name="b[from]" value="<?php echo (int) $from['id']; ?>">
		<div class="sme-pre" role="note">
			<p class="sme-pre__title"><?php sm_icon( 'hard-hat' ); ?><?php echo esc_html( sprintf( /* translators: 1: reference, 2: title. */ __( 'Follow-up of the study %1$s — %2$s', 'sm-marketplace' ), $from['ref'], $from['title'] ) ); ?></p>
			<p class="sm-help"><?php esc_html_e( 'The project and its dimensions are taken from the delivered study.', 'sm-marketplace' ); ?></p>
		</div>
	<?php endif; ?>
	<?php if ( ! empty( $pre['rows'] ) ) : ?>
		<div class="sme-pre" role="note">
			<p class="sme-pre__title"><?php sm_icon( (string) ( $pre['icon'] ?? 'warehouse' ) ); ?><?php echo esc_html( (string) ( $pre['heading'] ?? __( 'Building concerned by the inspection', 'sm-marketplace' ) ) ); ?></p>
			<dl>
				<?php foreach ( $pre['rows'] as $row ) : ?>
					<div><dt><?php echo esc_html( $row[0] ); ?></dt><dd><?php if ( 0 === strpos( (string) $row[1], 'http' ) ) : ?><a href="<?php echo esc_url( $row[1] ); ?>"><?php esc_html_e( 'See the listing', 'sm-marketplace' ); ?></a><?php else : ?><bdi dir="auto"><?php echo esc_html( $row[1] ); ?></bdi><?php endif; ?></dd></div>
				<?php endforeach; ?>
			</dl>
			<p class="sm-help"><?php esc_html_e( 'Your request goes to the platform, which sends you a quote and appoints the engineer.', 'sm-marketplace' ); ?></p>
		</div>
	<?php endif; ?>

	<ol class="smx-steps" aria-hidden="true">
		<li><a href="#smx-s1"><?php esc_html_e( 'Need', 'sm-marketplace' ); ?></a></li>
		<li><a href="#smx-s2"><?php esc_html_e( 'Type of project', 'sm-marketplace' ); ?></a></li>
		<li><a href="#smx-s3"><?php esc_html_e( 'Details', 'sm-marketplace' ); ?></a></li>
		<li><a href="#smx-s4"><?php esc_html_e( 'Place and sending', 'sm-marketplace' ); ?></a></li>
	</ol>

	<?php /* 1. The need. */ ?>
	<fieldset class="smx-step" id="smx-s1">
		<legend class="smx-step__title"><b>1</b><?php esc_html_e( 'What do you need?', 'sm-marketplace' ); ?></legend>
		<p class="sm-help"><?php esc_html_e( 'One choice per request. For a follow-up after a study, you can ask for it once the study is delivered.', 'sm-marketplace' ); ?></p>
		<div class="smx-parts">
			<?php foreach ( (array) ( $d['parts'] ?? array() ) as $code => $p ) : ?>
				<label class="smx-part">
					<input type="radio" name="part" value="<?php echo esc_attr( $code ); ?>" <?php checked( $part, $code ); ?> data-smx-part>
					<span class="smx-part__box">
						<svg viewBox="0 0 42 42" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><?php echo $icons[ $code ] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></svg>
						<strong><?php echo esc_html( $p[0] ); ?></strong>
						<small><?php echo esc_html( $p[1] ); ?></small>
					</span>
				</label>
			<?php endforeach; ?>
		</div>
		<?php sm_theme_error( 'part' ); ?>
		<?php foreach ( array( 'study', 'follow', 'expertise' ) as $pc ) : ?>
			<div class="smx-only sme-svc" data-part="<?php echo esc_attr( $pc ); ?>">
				<p class="smx-sub"><?php echo esc_html( 'study' === $pc ? __( 'Prestations of the study — tick one or more', 'sm-marketplace' ) : __( 'Prestations — untick those you do not need', 'sm-marketplace' ) ); ?></p>
				<?php foreach ( $services as $sv ) : ?>
					<?php
					if ( ( $sv['part'] ?? 'study' ) !== $pc ) {
						continue;
					}
					$on   = 'study' !== $pc || in_array( $sv['code'], $chosen, true );
					$mine = $sent ? array_map( 'intval', (array) ( $ticked[ $sv['code'] ] ?? array() ) ) : array_keys( $sv['items'] );
					?>
					<div class="sme-svc__item<?php echo $on ? ' is-on' : ''; ?>" data-sm-svc>
						<?php if ( 'study' === $pc ) : ?>
							<label class="sme-svc__head"><input type="checkbox" name="services[]" value="<?php echo esc_attr( $sv['code'] ); ?>" <?php checked( $on ); ?> data-sm-svc-toggle><strong><?php echo esc_html( $sv['title'] ); ?></strong></label>
						<?php else : ?>
							<input type="hidden" name="services[]" value="<?php echo esc_attr( $sv['code'] ); ?>">
						<?php endif; ?>
						<?php if ( $sv['items'] ) : ?>
							<details class="sme-prest"<?php echo 'study' !== $pc || $sv['code'] === $type ? ' open' : ''; ?>>
								<summary><?php echo esc_html( 'study' === $pc ? __( 'Detail of the prestations', 'sm-marketplace' ) : $sv['title'] ); ?></summary>
								<div class="sme-prest__group" data-sm-svc-items>
									<?php foreach ( $sv['items'] as $i => $item ) : ?>
										<label><input type="checkbox" name="prestations[<?php echo esc_attr( $sv['code'] ); ?>][]" value="<?php echo (int) $i; ?>" <?php checked( in_array( (int) $i, $mine, true ) ); ?>><span><?php echo esc_html( $item ); ?></span></label>
									<?php endforeach; ?>
								</div>
							</details>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
		<?php sm_theme_error( 'services' ); ?>
		<?php sm_theme_error( 'prestations' ); ?>
	</fieldset>

	<?php /* 2. Type of project. */ ?>
	<fieldset class="smx-step" id="smx-s2">
		<legend class="smx-step__title"><b>2</b><?php esc_html_e( 'Type of project', 'sm-marketplace' ); ?></legend>
		<div class="smx-types">
			<?php foreach ( (array) ( $d['projects'] ?? array() ) as $code => $label ) : ?>
				<?php $fam = (string) ( $families[ $code ] ?? 'free' ); ?>
				<label class="smx-type">
					<input type="radio" name="project_type" value="<?php echo esc_attr( $code ); ?>" data-fam="<?php echo esc_attr( $fam ); ?>" <?php checked( $project, (string) $code ); ?> data-smx-type>
					<span class="smx-type__box">
						<svg viewBox="0 0 64 48" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"<?php echo isset( $pics[ $code ] ) ? '' : ' stroke-dasharray="4 3"'; ?>><?php echo $pics[ $code ] ?? '<rect x="12" y="10" width="40" height="30" rx="4"/>'; // phpcs:ignore WordPress.Security.EscapeOutput ?></svg>
						<strong><?php echo esc_html( $label ); ?></strong>
						<small><?php echo esc_html( $fam_hint[ $fam ] ?? '' ); ?></small>
					</span>
				</label>
			<?php endforeach; ?>
		</div>
		<?php sm_theme_error( 'project_type' ); ?>
	</fieldset>

	<?php /* 3. Details of the part. */ ?>
	<fieldset class="smx-step" id="smx-s3">
		<legend class="smx-step__title"><b>3</b><span class="smx-only" data-part="study"><?php esc_html_e( 'Dimensions and sketch', 'sm-marketplace' ); ?></span><span class="smx-only" data-part="follow"><?php esc_html_e( 'The follow-up', 'sm-marketplace' ); ?></span><span class="smx-only" data-part="expertise"><?php esc_html_e( 'The expertise', 'sm-marketplace' ); ?></span></legend>

		<div class="smx-only" data-part="study">
			<p class="smx-need-type sm-help"><?php esc_html_e( 'Choose the type of project first: the questions depend on it.', 'sm-marketplace' ); ?></p>
			<div class="smx-geo" data-smx-form>
				<div class="smx-geo__fields">
					<p class="smx-fam sm-help" data-fam="free"><?php esc_html_e( 'Describe the project in the description (step 4): dimensions, use, what you expect.', 'sm-marketplace' ); ?></p>
					<div class="smx-fam smx-grid" data-fam="portal levels">
						<?php
						$unit( 'width', __( 'Width (span)', 'sm-marketplace' ), 'm', true, '20' );
						$unit( 'length', __( 'Length', 'sm-marketplace' ), 'm', true, '40' );
						?>
					</div>
					<div class="smx-fam smx-grid" data-fam="portal">
						<?php
						$unit( 'col_h', __( 'Height of the columns', 'sm-marketplace' ), 'm', true, '7' );
						$unit( 'ridge_h', __( 'Height at the ridge', 'sm-marketplace' ), 'm', false, '9' );
						$unit( 'spans', __( 'Number of spans', 'sm-marketplace' ), '', false, '1' );
						$unit( 'bay', __( 'Spacing of the frames', 'sm-marketplace' ), 'm', false, '5' );
						?>
					</div>
					<div class="smx-fam" data-fam="portal">
						<p class="smx-sub"><?php esc_html_e( 'Shape of the roof', 'sm-marketplace' ); ?></p>
						<div class="smx-roofs">
							<?php $roof = (string) $bv( 'roof', 'duo' ); ?>
							<?php foreach ( (array) ( $choices['roof'] ?? array() ) as $c => $l ) : ?>
								<label class="smx-roof"><input type="radio" name="b[roof]" value="<?php echo esc_attr( $c ); ?>" <?php checked( $roof, (string) $c ); ?> data-smx-in="roof"><span><svg viewBox="0 0 46 26" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><?php echo $roof_pic[ $c ] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></svg><?php echo esc_html( $l ); ?></span></label>
							<?php endforeach; ?>
						</div>
						<div class="smx-opt">
							<label class="smx-switch"><input type="checkbox" name="b[crane]" value="1" <?php checked( $bon( 'crane' ) ); ?> data-smx-in="crane"><span><?php esc_html_e( 'Overhead crane', 'sm-marketplace' ); ?></span></label>
							<div class="smx-grid smx-opt__more">
								<?php
								$unit( 'crane_t', __( 'Capacity', 'sm-marketplace' ), 't', false, '5' );
								$unit( 'crane_rail', __( 'Height of the rail', 'sm-marketplace' ), 'm', false, '5,5' );
								$unit( 'crane_n', __( 'Number of cranes', 'sm-marketplace' ), '', false, '1' );
								?>
							</div>
						</div>
						<div class="smx-opt">
							<label class="smx-switch"><input type="checkbox" name="b[mezz]" value="1" <?php checked( $bon( 'mezz' ) ); ?> data-smx-in="mezz"><span><?php esc_html_e( 'Mezzanine', 'sm-marketplace' ); ?></span></label>
							<div class="smx-grid smx-opt__more">
								<?php
								$unit( 'mezz_w', __( 'Width of the mezzanine', 'sm-marketplace' ), 'm', false, '6' );
								$unit( 'mezz_l', __( 'Length of the mezzanine', 'sm-marketplace' ), 'm', false, '20' );
								$unit( 'mezz_h', __( 'Height of the floor', 'sm-marketplace' ), 'm', false, '3,5' );
								$select( 'mezz_use', __( 'Use', 'sm-marketplace' ), 'mezz_use', 'offices' );
								?>
							</div>
						</div>
						<div class="smx-opt">
							<p class="smx-sub"><?php esc_html_e( 'Roofing', 'sm-marketplace' ); ?></p>
							<div class="smx-chips"><?php $chips( 'roofing', 'roofing' ); ?></div>
							<p class="smx-sub"><?php esc_html_e( 'Walls (cladding)', 'sm-marketplace' ); ?></p>
							<div class="smx-chips"><?php $chips( 'walls', 'walls' ); ?></div>
							<div class="smx-grid smx-walls-mixed">
								<?php
								$unit( 'wall_h', __( 'Height of the wall', 'sm-marketplace' ), 'm', true, '1,5' );
								$select( 'walls_top', __( 'Cladding above', 'sm-marketplace' ), 'walls_top', 'sandwich' );
								?>
							</div>
						</div>
						<div class="smx-opt" data-smx-doors>
							<p class="smx-sub"><?php esc_html_e( 'Doors', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'each side closed unless you add doors', 'sm-marketplace' ); ?></small></p>
							<p class="sm-help"><?php esc_html_e( 'A door sits in the middle of a bay (between two frames). Tick the bays, or click a bay on the plan.', 'sm-marketplace' ); ?></p>
							<?php
							$side_names = array(
								'front' => __( 'Front long side', 'sm-marketplace' ),
								'back'  => __( 'Back long side', 'sm-marketplace' ),
								'left'  => __( 'Left gable', 'sm-marketplace' ),
								'right' => __( 'Right gable', 'sm-marketplace' ),
							);
							$bays_now = max( 1, (int) ( ( function () use ( $bv ) {
								$l   = (float) str_replace( ',', '.', (string) $bv( 'length' ) );
								$bay = (float) str_replace( ',', '.', (string) $bv( 'bay' ) );
								return $l > 0 ? ceil( $l / ( $bay > 0 ? $bay : 6 ) - 0.01 ) : 10;
							} )() ) );
							foreach ( $side_names as $side => $label ) :
								$long = in_array( $side, array( 'front', 'back' ), true );
								$mode = (string) $bv( 'd_' . $side, 'closed' );
								$list = $long ? 'long' : 'gable';
								$on   = array_map( 'intval', (array) $bv( 'd_' . $side . '_bays', array() ) );
								?>
								<div class="smx-side" data-smx-side="<?php echo esc_attr( $side ); ?>">
									<div class="smx-side__head">
										<strong><?php echo esc_html( $label ); ?></strong>
										<span class="smx-seg">
											<?php foreach ( (array) ( $choices[ $list ] ?? array() ) as $c => $l ) : ?>
												<label><input type="radio" name="b[d_<?php echo esc_attr( $side ); ?>]" value="<?php echo esc_attr( $c ); ?>" <?php checked( $mode, (string) $c ); ?> data-smx-in="d_<?php echo esc_attr( $side ); ?>"><span><?php echo esc_html( $l ); ?></span></label>
											<?php endforeach; ?>
										</span>
									</div>
									<div class="smx-side__more">
										<?php if ( $long ) : ?>
											<div class="smx-bays" data-smx-bays>
												<?php for ( $i = 1; $i <= 40; $i++ ) : ?>
													<label<?php echo $i > $bays_now ? ' hidden' : ''; ?>><input type="checkbox" name="b[d_<?php echo esc_attr( $side ); ?>_bays][]" value="<?php echo (int) $i; ?>" <?php checked( in_array( $i, $on, true ) ); ?> data-smx-in="d_<?php echo esc_attr( $side ); ?>_bays" data-smx-bay="<?php echo (int) $i; ?>"><span>T<?php echo (int) $i; ?></span></label>
												<?php endfor; ?>
											</div>
											<?php sm_theme_error( 'b_d_' . $side ); ?>
										<?php endif; ?>
										<div class="smx-grid">
											<?php
											$unit( 'd_' . $side . '_w', __( 'Width of a door', 'sm-marketplace' ), 'm', false, $long ? '4' : '5' );
											$unit( 'd_' . $side . '_h', __( 'Height of a door', 'sm-marketplace' ), 'm', false, $long ? '4,5' : '5' );
											?>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
					<div class="smx-fam" data-fam="levels">
						<p class="smx-sub"><?php esc_html_e( 'Levels', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'one row per level, from the ground floor up', 'sm-marketplace' ); ?></small></p>
						<?php
						$floors = array_values( array_filter( (array) $bv( 'floors', array() ), 'is_array' ) );
						if ( ! $floors ) {
							$floors = array( array( 'h' => '', 'use' => '' ) );
						}
						$fnum = static fn( $v ) => is_float( $v ) || is_int( $v ) ? rtrim( rtrim( number_format( (float) $v, 2, ',', '' ), '0' ), ',' ) : (string) $v;
						?>
						<div class="smx-floors" data-smx-floors data-ground="<?php esc_attr_e( 'Ground floor', 'sm-marketplace' ); ?>" data-level="<?php esc_attr_e( 'F+%d', 'sm-marketplace' ); ?>">
							<div class="smx-floor smx-floor--head" aria-hidden="true"><span><?php esc_html_e( 'Level', 'sm-marketplace' ); ?></span><span><?php esc_html_e( 'Height', 'sm-marketplace' ); ?></span><span><?php esc_html_e( 'Use', 'sm-marketplace' ); ?></span><span></span></div>
							<?php foreach ( $floors as $i => $fl ) : ?>
								<div class="smx-floor" data-smx-floor>
									<strong data-smx-floor-name><?php echo esc_html( 0 === $i ? __( 'Ground floor', 'sm-marketplace' ) : sprintf( /* translators: %d: floor number. */ __( 'F+%d', 'sm-marketplace' ), $i ) ); ?></strong>
									<span class="smx-u"><input name="b[floors][<?php echo (int) $i; ?>][h]" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( $fnum( $fl['h'] ?? '' ) ); ?>" placeholder="3,4" aria-label="<?php esc_attr_e( 'Height', 'sm-marketplace' ); ?>" data-smx-in="floor_h"><em>m</em></span>
									<select name="b[floors][<?php echo (int) $i; ?>][use]" aria-label="<?php esc_attr_e( 'Use', 'sm-marketplace' ); ?>" data-smx-in="floor_use">
										<option value=""><?php esc_html_e( 'Use', 'sm-marketplace' ); ?></option>
										<?php foreach ( (array) ( $choices['floor_use'] ?? array() ) as $c => $l ) : ?><option value="<?php echo esc_attr( $c ); ?>" <?php selected( (string) ( $fl['use'] ?? '' ), (string) $c ); ?>><?php echo esc_html( $l ); ?></option><?php endforeach; ?>
									</select>
									<button type="button" class="smr-remove" data-smx-floor-remove aria-label="<?php esc_attr_e( 'Remove this level', 'sm-marketplace' ); ?>">×</button>
									<?php sm_theme_error( 'b_floors_' . $i ); ?>
								</div>
							<?php endforeach; ?>
						</div>
						<button type="button" class="smr-add" data-smx-floor-add><?php sm_icon( 'plus' ); ?><span><?php esc_html_e( 'Add a level', 'sm-marketplace' ); ?></span></button>
						<?php sm_theme_error( 'b_floors' ); ?>
						<div class="smx-grid" style="margin-block-start:10px">
							<?php $select( 'floor', __( 'Floors', 'sm-marketplace' ), 'floor', 'unknown' ); ?>
						</div>
					</div>
					<div class="smx-fam smx-opt" data-fam="portal levels free">
						<p class="smx-sub"><?php esc_html_e( 'Use of the building', 'sm-marketplace' ); ?></p>
						<div class="smx-chips"><?php $chips( 'use', 'use' ); ?></div>
						<div class="smx-grid">
							<?php $select( 'soil', __( 'Soil study available?', 'sm-marketplace' ), 'soil', 'unknown' ); ?>
						</div>
					</div>
				</div>
				<div class="smx-fam smx-sketch" data-fam="portal levels" data-smx-sketch data-i18n="<?php echo esc_attr( wp_json_encode( $i18n ) ); ?>">
					<div class="smx-sketch__view">
						<p class="smx-sketch__title"><span data-smx-front-title><?php esc_html_e( 'Front view (left gable)', 'sm-marketplace' ); ?></span><small><?php esc_html_e( 'Indicative sketch', 'sm-marketplace' ); ?></small></p>
						<div data-smx-front><p class="sm-help"><?php echo esc_html( $i18n['empty'] ); ?></p></div>
					</div>
					<div class="smx-sketch__view" data-smx-plan-box>
						<p class="smx-sketch__title"><span><?php esc_html_e( 'Plan view', 'sm-marketplace' ); ?></span><small data-smx-plan-note></small></p>
						<div data-smx-plan></div>
					</div>
					<div class="smx-figs" data-smx-figs></div>
					<p class="sm-help"><?php esc_html_e( 'Sketch drawn from your dimensions. The engineer sets the sections, the loads and the zones (RPA, RNV) from the municipality.', 'sm-marketplace' ); ?></p>
				</div>
			</div>
		</div>

		<div class="smx-only" data-part="follow">
			<p class="smx-sub"><?php esc_html_e( 'Phases to follow', 'sm-marketplace' ); ?></p>
			<div class="smx-chips"><?php $chips( 'phases', 'phases', true ); ?></div>
			<div class="smx-grid">
				<?php
				$unit( 'visits', __( 'Number of visits', 'sm-marketplace' ), '', false, '6' );
				$unit( 'months', __( 'Duration of the works', 'sm-marketplace' ), __( 'months', 'sm-marketplace' ), false, '3' );
				?>
			</div>
			<p class="smx-sub"><?php esc_html_e( 'Who made the study?', 'sm-marketplace' ); ?></p>
			<div class="smx-chips"><?php $chips( 'study_by', 'study_by' ); ?></div>
		</div>

		<div class="smx-only" data-part="expertise">
			<p class="smx-sub"><?php esc_html_e( 'What is to inspect?', 'sm-marketplace' ); ?></p>
			<div class="smx-chips"><?php $chips( 'object', 'object' ); ?></div>
			<p class="smx-sub"><?php esc_html_e( 'What do you see?', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></p>
			<div class="smx-chips"><?php $chips( 'findings', 'findings', true ); ?></div>
			<div class="smx-grid">
				<?php
				$unit( 'age', __( 'Age of the building', 'sm-marketplace' ), __( 'years', 'sm-marketplace' ), false, '15' );
				$unit( 'exp_w', __( 'Approximate width', 'sm-marketplace' ), 'm', false, '20' );
				$unit( 'exp_l', __( 'Approximate length', 'sm-marketplace' ), 'm', false, '40' );
				?>
			</div>
			<p class="smx-sub"><?php esc_html_e( 'Urgency', 'sm-marketplace' ); ?></p>
			<div class="smx-chips"><?php $chips( 'urgency', 'urgency' ); ?></div>
		</div>
	</fieldset>

	<?php /* 4. Place, documents, sending. */ ?>
	<fieldset class="smx-step" id="smx-s4">
		<legend class="smx-step__title"><b>4</b><?php esc_html_e( 'Location, project and documents', 'sm-marketplace' ); ?></legend>
		<?php
		$wil      = (string) sm_theme_old( 'wilaya', $from['wilaya'] ?? '' );

		?>
		<section class="smx-zone">
			<h3 class="smx-zone__title"><?php sm_icon( 'map-pin' ); ?><?php esc_html_e( 'Location', 'sm-marketplace' ); ?></h3>
			<div class="smx-grid smx-grid--2">
				<div class="smx-f">
					<label for="m-wilaya"><?php echo esc_html( $pre ? __( 'Wilaya where you want to reassemble the building', 'sm-marketplace' ) : __( 'Wilaya', 'sm-marketplace' ) ); ?> <span class="smx-req" aria-hidden="true">*</span></label>
					<select id="m-wilaya" name="wilaya" required<?php sm_theme_aria( 'wilaya' ); ?>>
						<option value=""><?php esc_html_e( 'Select', 'sm-marketplace' ); ?></option>
						<?php foreach ( sm_theme_wilayas() as $code => $name ) : ?><option value="<?php echo esc_attr( $code ); ?>" <?php selected( $wil, (string) $code ); ?>><?php echo esc_html( $code . ' — ' . sm_t( $name ) ); ?></option><?php endforeach; ?>
					</select>
					<?php sm_theme_error( 'wilaya' ); ?>
				</div>
				<?php sm_component( 'commune-field', array( 'name' => 'b[commune]', 'id' => 'smx-commune', 'wilaya' => $wil, 'wilaya_select' => '#m-wilaya', 'value' => (string) $bv( 'commune' ), 'required' => true, 'error' => 'b_commune', 'class' => 'smx-f' ) ); ?>
			</div>
			<?php
			sm_component(
				'location-map',
				array(
					'mode'          => 'picker',
					'lat'           => (string) sm_theme_old( 'lat', (string) ( $seed['lat'] ?? '' ) ),
					'lng'           => (string) sm_theme_old( 'lng', (string) ( $seed['lng'] ?? '' ) ),
					'wilaya_select' => '#m-wilaya',
					'no_public'     => true,
					'legend'        => __( 'Place of the project on the map (optional)', 'sm-marketplace' ),
					'help'          => __( 'The map opens on the wilaya. Drag the marker (or click the map) to the exact place, or use your current location if you are on site.', 'sm-marketplace' ),
					'note'          => __( 'Exact place seen only by the platform and the engineer in charge of the study.', 'sm-marketplace' ),
				)
			);
			sm_theme_error( 'b_location' );
			?>
		</section>
		<section class="smx-zone">
			<h3 class="smx-zone__title"><?php sm_icon( 'file' ); ?><?php esc_html_e( 'Project', 'sm-marketplace' ); ?></h3>
			<div class="smx-f">
				<label for="m-title"><?php esc_html_e( 'Title of the project', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional — otherwise made from the type and the dimensions', 'sm-marketplace' ); ?></small></label>
				<input id="m-title" name="title" dir="auto" maxlength="200" value="<?php echo esc_attr( sm_theme_old( 'title', $from['title'] ?? ( $pre['title'] ?? '' ) ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. Storage hangar 20 × 40 m', 'sm-marketplace' ); ?>"<?php sm_theme_aria( 'title' ); ?>>
				<?php sm_theme_error( 'title' ); ?>
			</div>
			<div class="smx-f" style="margin-block-start:12px">
				<label for="m-desc"><?php esc_html_e( 'Description', 'sm-marketplace' ); ?> <small class="sm-optional smx-desc-opt"><?php esc_html_e( 'what the dimensions do not say', 'sm-marketplace' ); ?></small></label>
				<textarea id="m-desc" name="description" rows="5" dir="auto" maxlength="5000" placeholder="<?php esc_attr_e( 'Possible extension, stored goods, constraints of the site, what you expect from the study…', 'sm-marketplace' ); ?>"<?php sm_theme_aria( 'description' ); ?>><?php echo esc_textarea( sm_theme_old( 'description', $pre['description'] ?? '' ) ); ?></textarea>
				<?php sm_theme_error( 'description' ); ?>
			</div>
		<?php if ( $d['engineers'] ) : ?>
			<div class="sm-field">
				<label for="m-eng"><?php esc_html_e( 'Preferred engineer', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label>
				<select id="m-eng" name="engineer">
					<option value=""><?php esc_html_e( 'Let the platform choose', 'sm-marketplace' ); ?></option>
					<?php foreach ( $d['engineers'] as $eid => $label ) : ?><option value="<?php echo (int) $eid; ?>" <?php selected( (int) sm_theme_old( 'engineer', $engineer ), (int) $eid ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
				</select>
			</div>
		<?php endif; ?>
		</section>
		<section class="smx-zone">
			<h3 class="smx-zone__title"><?php sm_icon( 'upload' ); ?><?php esc_html_e( 'Documents', 'sm-marketplace' ); ?></h3>
		<label class="sm-upload sm-upload--small">
			<input type="file" name="files[]" multiple data-sm-filename>
			<?php sm_icon( 'upload', 'sm-upload__icon' ); ?>
			<strong><span class="smx-only" data-part="study follow"><?php esc_html_e( 'Plans, soil study, photos', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></span><span class="smx-only" data-part="expertise"><?php echo esc_html( empty( $args['listing'] ) ? __( 'Photos of the building — 2 at least', 'sm-marketplace' ) : __( 'Photos and documents', 'sm-marketplace' ) ); ?></span></strong>
			<small data-sm-filename-label><?php echo esc_html( sprintf( /* translators: 1: number of files, 2: MB, 3: extensions. */ __( 'Up to %1$d files of %2$d MB (%3$s).', 'sm-marketplace' ), (int) $d['max_files'], (int) $d['file_mb'], strtoupper( implode( ', ', $d['extensions'] ) ) ) ); ?></small>
		</label>
		<?php sm_theme_error( 'files' ); ?>
		</section>
		<button class="sm-btn sm-btn--accent sm-btn--lg" type="submit"><?php sm_icon( 'hard-hat' ); ?><?php esc_html_e( 'Send my request to the platform', 'sm-marketplace' ); ?></button>
	</fieldset>
	<?php sm_legal_notice(); // 0.63.0 ?>
</form>

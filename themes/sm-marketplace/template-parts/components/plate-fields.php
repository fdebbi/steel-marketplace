<?php
/**
 * Plates in the product form (0.36.0): kind of plate, common thickness and
 * format (or typed), holes of a perforated plate, drawing of the sheet and
 * weight of one sheet (estimated, the seller may give the real one).
 *
 * @var array $args { state, family, summary, calc, field_error, render_grade }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$st          = (array) $args['state'];
$family      = (array) $args['family'];
$calc        = is_array( $args['calc'] ?? null ) ? $args['calc'] : null;
$field_error = $args['field_error'];
$rows_mode   = ! empty( $args['rows'] ); // 0.37.0: reuse, the sizes are given row by row.
$v           = static fn( $k, $d = '' ) => isset( $st[ $k ] ) && ! is_array( $st[ $k ] ) ? (string) $st[ $k ] : $d;
$kinds       = array();
foreach ( (array) ( $family['plates'] ?? array() ) as $k ) {
	$kinds[ $k['key'] ] = $k;
}
$kind    = isset( $kinds[ $v( 'plate_kind' ) ] ) ? $v( 'plate_kind' ) : '';
$def     = $kind ? $kinds[ $kind ] : null;
$num     = static fn( $x ) => rtrim( rtrim( number_format( (float) $x, 2, '.', '' ), '0' ), '.' );
$pt      = $v( 'plate_t' );
$fmt     = $v( 'plate_format' );
$dims_t  = (string) ( $st['dims']['t'] ?? '' );
$offered = $def ? array_map( static fn( $o ) => $num( $o['value'] ), $def['thicknesses'] ) : array();
if ( '' === $pt && '' !== $dims_t ) {
	$pt = in_array( $num( $dims_t ), $offered, true ) ? $num( $dims_t ) : 'custom';
}
if ( '' === $pt && $def && ! $offered ) {
	$pt = 'custom'; // "Other plate": the thickness is typed.
}
if ( '' === $fmt && '' !== $v( 'width' ) ) {
	$fmt = 'custom';
}
// Pattern of the sheet in the pictures.
$pattern = static function ( $key, $id ) {
	switch ( $key ) {
		case 'tread':
			return '<pattern id="' . $id . '" width="8" height="8" patternUnits="userSpaceOnUse" patternTransform="rotate(-35)"><rect x="1" y="3" width="5" height="1.8" rx=".9" fill="#2f363c"/></pattern>';
		case 'alu_tread':
			return '<pattern id="' . $id . '" width="8" height="8" patternUnits="userSpaceOnUse"><rect x="1" y="2" width="5" height="1.6" fill="#6b757d"/><rect x="5" y="4.5" width="1.6" height="3.4" fill="#6b757d"/></pattern>';
		case 'perforated':
			return '<pattern id="' . $id . '" width="6" height="6" patternUnits="userSpaceOnUse"><circle cx="3" cy="3" r="1.5" fill="#fff"/></pattern>';
		case 'galvanized':
			return '<pattern id="' . $id . '" width="7" height="7" patternUnits="userSpaceOnUse"><circle cx="3.5" cy="3.5" r="1.3" fill="#d3dbe0"/></pattern>';
	}
	return '';
};
$fill = array(
	'hot'        => '#3b4148',
	'cold'       => '#8a949c',
	'galvanized' => '#aeb8bf',
	'tread'      => '#9aa4ab',
	'perforated' => '#8a949c',
	'stainless'  => '#dfe5ea',
	'alu_tread'  => '#e3e7ea',
	'other'      => 'none',
);
$icon = static function ( $key ) use ( $pattern, $fill ) {
	$id  = 'smpl-i-' . $key;
	$pat = $pattern( $key, $id );
	$svg = '<svg viewBox="0 0 64 48" aria-hidden="true" focusable="false">' . ( $pat ? '<defs>' . $pat . '</defs>' : '' );
	$svg .= '<path d="M6 34 L22 14 H58 L42 34 Z" fill="' . $fill[ $key ] . '" stroke="#141b22" stroke-width="1.5"' . ( 'other' === $key ? ' stroke-dasharray="4 3"' : '' ) . '/>';
	if ( $pat ) {
		$svg .= '<path d="M6 34 L22 14 H58 L42 34 Z" fill="url(#' . $id . ')"/>';
	}
	if ( 'stainless' === $key ) {
		$svg .= '<path d="M16 30 L28 16" stroke="#fff" stroke-width="3"/>';
	}
	return $svg . '</svg>';
};
?>
<fieldset class="sm-field smpl-kinds">
	<legend class="sm-field__legend"><?php esc_html_e( 'Kind of plate', 'sm-marketplace' ); ?></legend>
	<div class="smpl-kinds__list">
		<?php foreach ( $kinds as $key => $k ) : ?>
			<label class="smpl-kind">
				<input type="radio" name="plate_kind" value="<?php echo esc_attr( $key ); ?>" <?php checked( $kind, $key ); ?><?php sm_theme_aria( 'plate_kind' ); ?>>
				<span class="smpl-kind__card">
					<?php echo $icon( $key ); // phpcs:ignore WordPress.Security.EscapeOutput -- built above from fixed values. ?>
					<strong><?php echo esc_html( sm_t( $k['name'] ) ); ?></strong>
					<small><?php echo esc_html( sm_t( $k['hint'] ) ); ?></small>
				</span>
			</label>
		<?php endforeach; ?>
	</div>
	<?php sm_theme_error( 'plate_kind' ); ?>
</fieldset>

<?php if ( $def ) : ?>
	<div class="smw-size smp-size smpl-size">
		<div class="smw-size__fields">
			<fieldset class="sm-field">
				<legend class="sm-field__legend"><?php esc_html_e( 'Thickness', 'sm-marketplace' ); ?> <small><?php echo esc_html( $def['relief'] ? __( '(base / with relief, mm)', 'sm-marketplace' ) : '(mm)' ); ?></small></legend>
				<div class="smpl-chips">
					<?php foreach ( $def['thicknesses'] as $o ) : ?>
						<label class="smpl-chip"><input type="radio" name="plate_t" value="<?php echo esc_attr( $num( $o['value'] ) ); ?>" <?php checked( $pt, $num( $o['value'] ) ); ?>><span dir="ltr"><?php echo esc_html( $o['label'] ); ?></span></label>
					<?php endforeach; ?>
					<label class="smpl-chip smpl-chip--other"><input type="radio" name="plate_t" value="custom" <?php checked( $pt, 'custom' ); ?>><span><?php esc_html_e( 'Other…', 'sm-marketplace' ); ?></span></label>
				</div>
				<?php if ( 'custom' === $pt ) : ?>
					<div class="smpl-inline">
						<label for="smp-d-t"><?php echo esc_html( $def['relief'] ? __( 'Base thickness', 'sm-marketplace' ) : __( 'Thickness t', 'sm-marketplace' ) ); ?> <small>(mm)</small></label>
						<input id="smp-d-t" name="dims[t]" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( $dims_t ); ?>">
					</div>
				<?php endif; ?>
				<?php $field_error( 'section' ); ?>
			</fieldset>

			<?php if ( ! $rows_mode ) : ?>
			<fieldset class="sm-field">
				<legend class="sm-field__legend"><?php esc_html_e( 'Size of the sheet', 'sm-marketplace' ); ?> <small>(mm)</small></legend>
				<div class="smpl-chips">
					<?php foreach ( (array) ( $family['formats'] ?? array() ) as $f ) : ?>
						<?php $fv = (int) $f[0] . 'x' . (int) $f[1]; ?>
						<label class="smpl-chip"><input type="radio" name="plate_format" value="<?php echo esc_attr( $fv ); ?>" <?php checked( $fmt, $fv ); ?>><span dir="ltr"><?php echo esc_html( (int) $f[0] . ' × ' . (int) $f[1] ); ?></span></label>
					<?php endforeach; ?>
					<label class="smpl-chip smpl-chip--other"><input type="radio" name="plate_format" value="custom" <?php checked( $fmt, 'custom' ); ?>><span><?php esc_html_e( 'Custom…', 'sm-marketplace' ); ?></span></label>
				</div>
				<?php if ( 'custom' === $fmt ) : ?>
					<div class="sm-field--row smpl-inline">
						<div><label for="smp-width"><?php esc_html_e( 'Plate width', 'sm-marketplace' ); ?> <small>(mm)</small></label><input id="smp-width" name="width" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( $v( 'width' ) ); ?>" placeholder="1500"></div>
						<div><label for="smp-plen"><?php esc_html_e( 'Plate length', 'sm-marketplace' ); ?> <small>(mm)</small></label><input id="smp-plen" name="plate_length" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( $v( 'plate_length' ) ); ?>" placeholder="3000"></div>
					</div>
				<?php endif; ?>
				<?php $field_error( 'width' ); ?>
				<?php $field_error( 'length' ); ?>
			</fieldset>
			<?php endif; ?>

			<?php if ( $def['holes'] ) : ?>
				<fieldset class="sm-field">
					<legend class="sm-field__legend"><?php esc_html_e( 'Holes', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional — makes the weight more accurate', 'sm-marketplace' ); ?></small></legend>
					<div class="sm-field--row">
						<div><label for="smp-hd"><?php esc_html_e( 'Hole diameter', 'sm-marketplace' ); ?> <small>(mm)</small></label><input id="smp-hd" name="hole_d" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( $v( 'hole_d' ) ); ?>" placeholder="5"></div>
						<div><label for="smp-hp"><?php esc_html_e( 'Pitch', 'sm-marketplace' ); ?> <small>(mm)</small></label><input id="smp-hp" name="hole_p" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( $v( 'hole_p' ) ); ?>" placeholder="8"></div>
					</div>
					<?php $field_error( 'holes' ); ?>
				</fieldset>
			<?php endif; ?>

			<?php
			if ( ! in_array( $kind, array( 'stainless', 'alu_tread' ), true ) ) {
				( $args['render_grade'] )();
			}
			?>
		</div>

		<?php if ( $rows_mode && $calc && ! empty( $calc['kg_per_m2'] ) ) : ?>
			<figure class="smw-profile smp-profile smpl-figure">
				<figcaption>
					<p class="smw-profile__name"><?php echo esc_html( trim( sm_t( $def['title'] ) . ' ' . (string) ( $calc['plate_label'] ?? '' ) . ( $def['relief'] ? '' : ' mm' ) ) ); ?></p>
					<p class="smp-kgm"><?php echo sm_tech( sm_number( $calc['kg_per_m2'], 2 ) . ' kg/m²' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
				</figcaption>
			</figure>
		<?php elseif ( $calc && ! empty( $calc['width_mm'] ) ) : ?>
			<?php
			$w     = (float) $calc['width_mm'];
			$l     = (float) $calc['length_mm'];
			$ratio = max( 0.25, min( 1, $w / max( 1, $l ) ) );
			$long  = 150;
			$deep  = 34 + 36 * $ratio;
			$x0    = 14;
			$y0    = 20 + $deep;
			$sk    = 0.75 * $deep;
			$pid   = 'smpl-p-' . $kind;
			$pat   = $pattern( $kind, $pid );
			$top   = sprintf( 'M%1$s %2$s L%3$s %4$s H%5$s L%6$s %2$s Z', $x0, $y0, $x0 + $sk, $y0 - $deep, $x0 + $sk + $long, $x0 + $long );
			$label = (string) ( $calc['plate_label'] ?? '' ) . ( $def['relief'] ? '' : ' mm' );
			?>
			<figure class="smw-profile smp-profile smpl-figure">
				<div class="smw-profile__drawing" dir="ltr">
					<svg viewBox="0 0 <?php echo (int) ceil( $x0 + $long + $sk + 70 ); ?> <?php echo (int) ( $y0 + 34 ); ?>" role="img" aria-label="<?php echo esc_attr( sprintf( '%s × %s mm', $num( $w ), $num( $l ) ) ); ?>">
						<?php echo $pat ? '<defs>' . $pat . '</defs>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<path d="<?php echo esc_attr( $top ); ?>" fill="<?php echo esc_attr( 'none' === $fill[ $kind ] ? '#eef1f3' : $fill[ $kind ] ); ?>" stroke="#141b22" stroke-width="1.6"/>
						<?php if ( $pat ) : ?><path d="<?php echo esc_attr( $top ); ?>" fill="url(#<?php echo esc_attr( $pid ); ?>)"/><?php endif; ?>
						<path d="<?php echo esc_attr( sprintf( 'M%1$s %2$s V%3$s H%4$s V%2$s M%4$s %3$s L%5$s %6$s V%7$s', $x0, $y0, $y0 + 6, $x0 + $long, $x0 + $sk + $long, $y0 - $deep + 6, $y0 - $deep ) ); ?>" fill="none" stroke="#141b22" stroke-width="1.2"/>
						<line x1="<?php echo (int) $x0; ?>" y1="<?php echo (int) ( $y0 + 16 ); ?>" x2="<?php echo (int) ( $x0 + $long ); ?>" y2="<?php echo (int) ( $y0 + 16 ); ?>" stroke="#5b6670"/>
						<text x="<?php echo (int) ( $x0 + $long / 2 ); ?>" y="<?php echo (int) ( $y0 + 30 ); ?>" text-anchor="middle" font-size="11" fill="#141b22">L = <?php echo esc_html( $num( $l ) ); ?></text>
						<text x="<?php echo (int) ( $x0 + $long + $sk / 2 + 8 ); ?>" y="<?php echo (int) ( $y0 - $deep / 2 + 4 ); ?>" font-size="11" fill="#141b22">l = <?php echo esc_html( $num( $w ) ); ?></text>
					</svg>
				</div>
				<figcaption>
					<p class="smw-profile__name"><?php echo esc_html( trim( sm_t( $def['title'] ) . ' ' . $label ) ); ?></p>
					<p class="smp-kgm"><?php echo sm_tech( $num( $w ) . ' × ' . $num( $l ) . ' mm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
					<p class="smw-source"><?php echo esc_html( sprintf( /* translators: %s: area in m². */ __( 'Area %s m² per sheet', 'sm-marketplace' ), sm_number( $w * $l / 1e6, fmod( $w * $l / 1e6, 1 ) ? 2 : 0 ) ) ); ?></p>
				</figcaption>
			</figure>
		<?php endif; ?>
	</div>

	<?php if ( ! $rows_mode && $calc && ! empty( $calc['per_piece_kg'] ) ) : ?>
		<div class="smpl-weight">
			<div class="smpl-weight__est">
				<span><?php esc_html_e( 'Estimated weight of one sheet', 'sm-marketplace' ); ?></span>
				<strong data-smpl-estimate><?php echo sm_tech( '≈ ' . sm_number( $calc['per_piece_kg'], 1 ) . "\u{00A0}kg" ); // phpcs:ignore WordPress.Security.EscapeOutput ?></strong>
				<small>
					<?php
					if ( 'table' === ( $calc['plate_basis'] ?? '' ) ) {
						/* translators: %s: weight per m². */
						echo esc_html( sprintf( __( 'From the usual weight of this plate: %s kg/m².', 'sm-marketplace' ), sm_number( $calc['kg_per_m2'], 2 ) ) );
					} elseif ( ! empty( $calc['open_ratio'] ) ) {
						/* translators: %s: share of the surface taken by the holes, in %. */
						echo esc_html( sprintf( __( 'Holes deducted: about %s %% of the surface.', 'sm-marketplace' ), sm_number( $calc['open_ratio'] * 100 ) ) );
					} elseif ( $def['holes'] ) {
						esc_html_e( 'Weight of the plain sheet: give the holes or the real weight.', 'sm-marketplace' );
					} else {
						/* translators: %s: weight per m². */
						echo esc_html( sprintf( __( 'Thickness × area: %s kg/m².', 'sm-marketplace' ), sm_number( $calc['kg_per_m2'], 2 ) ) );
					}
					?>
				</small>
			</div>
			<div class="smpl-weight__real">
				<label for="smp-piece"><?php esc_html_e( 'Real weight of one sheet', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label>
				<div class="smpl-weight__input"><input id="smp-piece" name="piece_kg" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( $v( 'piece_kg' ) ); ?>" placeholder="<?php echo esc_attr( sm_number( $calc['per_piece_kg'], 1 ) ); ?>"<?php sm_theme_aria( 'piece_kg' ); ?>><span>kg</span></div>
				<?php $field_error( 'piece_kg' ); ?>
			</div>
			<p class="smpl-weight__hint"><?php esc_html_e( 'Do you know the exact weight? Enter it: this is the weight buyers will see.', 'sm-marketplace' ); ?></p>
		</div>
	<?php endif; ?>
<?php endif; ?>

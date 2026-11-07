<?php
/**
 * Weight calculator (data and calculations from sm-weight-engine).
 *
 * A plain GET form: it works without JavaScript (each "Continue" reloads the
 * page with the choices in the URL, so a calculation can be shared as a link).
 * With JavaScript (assets/js/weight.js) the same HTML is refreshed in place on
 * every change from ?smw_partial=1, so there is a single rendering path.
 *
 * @var array $args { state: array (family, section, dims, mode, length, length_custom, width, bars, total_kg) }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$families = (array) sm_theme_data( 'weight_families' );
if ( ! $families ) {
	sm_component( 'empty-state', array( 'icon' => 'scale', 'title' => __( 'The weight calculator is not available yet.', 'sm-marketplace' ) ) );
	return;
}
sm_theme_enqueue_weight();
$st       = (array) ( $args['state'] ?? array() );
$settings = (array) sm_theme_data( 'weight_settings' );
$lengths  = (array) ( $settings['bar_lengths'] ?? array() );
$code     = strtoupper( (string) ( $st['family'] ?? '' ) );
$family   = $code ? sm_theme_data( 'weight_family', array( 'code' => $code ) ) : null;
$is_plate = $family && 'plate' === $family['shape'];
$mode     = 'weight' === ( $st['mode'] ?? '' ) ? 'weight' : 'bars';
$length   = (string) ( $st['length'] ?? ( $settings['default_length'] ?? '' ) );
$custom   = 'custom' === $length || ( $length && ! in_array( (int) $length, $lengths, true ) && ! $is_plate );

$groups = array(
	'beams'    => __( 'Beams', 'sm-marketplace' ),
	'channels' => __( 'Channels', 'sm-marketplace' ),
	'angles'   => __( 'Angles', 'sm-marketplace' ),
	'tubes'    => __( 'Tubes', 'sm-marketplace' ),
	'bars'     => __( 'Flats and bars', 'sm-marketplace' ),
	'plates'   => __( 'Plates', 'sm-marketplace' ),
);
$dim_labels = array(
	'h'  => __( 'Height h', 'sm-marketplace' ),
	'b'  => __( 'Width b', 'sm-marketplace' ),
	't'  => __( 'Thickness t', 'sm-marketplace' ),
	'd'  => __( 'Diameter D', 'sm-marketplace' ),
	'a'  => __( 'Side a', 'sm-marketplace' ),
	'tw' => __( 'Web tw', 'sm-marketplace' ),
	'tf' => __( 'Flange tf', 'sm-marketplace' ),
	'r'  => __( 'Root radius r', 'sm-marketplace' ),
	'r1' => __( 'Root radius r1', 'sm-marketplace' ),
	'r2' => __( 'Toe radius r2', 'sm-marketplace' ),
);

// The chosen size: a catalogue row, or typed dimensions (formula families).
$section     = null;
$typed       = null;
$typed_error = '';
if ( $family && ! empty( $st['section'] ) ) {
	$section = sm_theme_data( 'weight_section', array( 'id' => (int) $st['section'] ) );
	if ( $section && $section['family'] !== $family['code'] ) {
		$section = null;
	}
}
$dims_in = array_filter( (array) ( $st['dims'] ?? array() ), static fn( $v ) => '' !== (string) $v );
if ( $family && ! $section && 'formula' === $family['method'] && $dims_in ) {
	$d = $dims_in;
	if ( 'SHS' === $family['code'] && isset( $d['b'] ) ) {
		$d['h'] = $d['b'];
	}
	$typed = sm_theme_data( 'weight_drawing', array( 'family' => $family['code'], 'dims' => $d ) );
	$typed_error = $typed['error'] ?? '';
	if ( $typed_error || ! $typed ) {
		$typed = null;
	}
}
$ready = $family && ( $section || $typed || $is_plate && ! empty( $dims_in['t'] ) );

// Result.
$result = null;
if ( $ready ) {
	$calc = array(
		'family' => $family['code'],
		'mode'   => $mode,
		'length' => $custom ? (float) str_replace( ',', '.', (string) ( $st['length_custom'] ?? 0 ) ) * 1000 : (float) $length,
		'width'  => $st['width'] ?? '',
	);
	if ( $is_plate ) {
		$calc['length'] = (float) str_replace( ',', '.', (string) ( $st['plate_length'] ?? 0 ) );
	}
	if ( $section ) {
		$calc['section'] = $section['id'];
	} else {
		$calc['dims'] = $typed ? $typed['dims'] : $dims_in;
	}
	if ( 'weight' === $mode ) {
		$calc['total_kg'] = $st['total_kg'] ?? '';
	} else {
		$calc['bars'] = $st['bars'] ?? '';
	}
	$has_qty = 'weight' === $mode ? '' !== (string) ( $st['total_kg'] ?? '' ) : '' !== (string) ( $st['bars'] ?? '' );
	if ( $has_qty ) {
		$result = sm_theme_data( 'weight_calc', $calc );
	}
}
$name = static fn( $f ) => sm_t( $f['name'] );
$m    = static fn( $mm ) => sm_number( $mm / 1000, fmod( $mm, 1000 ) ? 2 : 0 ) . "\u{00A0}m";
?>
<form class="smw" method="get" action="<?php echo esc_url( sm_page_url( 'weight' ) ); ?>" data-smw novalidate>
	<fieldset class="smw-step">
		<legend class="smw-step__title"><span class="smw-step__n">1</span><?php esc_html_e( 'Product type', 'sm-marketplace' ); ?></legend>
		<?php foreach ( $groups as $gkey => $glabel ) : ?>
			<?php $in_group = array_filter( $families, static fn( $f ) => $f['group'] === $gkey ); ?>
			<?php if ( ! $in_group ) { continue; } ?>
			<p class="smw-group"><?php echo esc_html( $glabel ); ?></p>
			<div class="smw-types">
				<?php foreach ( $in_group as $f ) : ?>
					<label class="smw-type">
						<input type="radio" name="family" value="<?php echo esc_attr( $f['code'] ); ?>" <?php checked( $code, $f['code'] ); ?>>
						<span class="smw-type__card">
							<span class="smw-type__icon" dir="ltr"><?php echo $f['icon']; // phpcs:ignore WordPress.Security.EscapeOutput -- SVG built from numbers by sm-weight-engine. ?></span>
							<span class="smw-type__code"><?php echo esc_html( in_array( $f['code'], array( 'FLAT', 'ROUND', 'SQUARE', 'PLATE' ), true ) ? '' : $f['code'] ); ?></span>
							<span class="smw-type__name"><?php echo esc_html( $name( $f ) ); ?></span>
						</span>
					</label>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
		<noscript><button class="sm-btn sm-btn--primary" type="submit"><?php esc_html_e( 'Continue', 'sm-marketplace' ); ?></button></noscript>
	</fieldset>

	<?php if ( $family ) : ?>
		<fieldset class="smw-step" id="smw-size">
			<legend class="smw-step__title"><span class="smw-step__n">2</span><?php echo esc_html( $is_plate ? __( 'Thickness', 'sm-marketplace' ) : __( 'Size', 'sm-marketplace' ) ); ?> — <?php echo esc_html( $name( $family ) ); ?></legend>
			<div class="smw-size">
				<div class="smw-size__fields">
					<?php if ( $family['sections'] ) : ?>
						<div class="sm-field">
							<label for="smw-section"><?php echo esc_html( 'formula' === $family['method'] ? __( 'Common size', 'sm-marketplace' ) : __( 'Size (European catalogue)', 'sm-marketplace' ) ); ?></label>
							<select id="smw-section" name="section" dir="ltr">
								<option value=""><?php echo esc_html( 'formula' === $family['method'] ? __( '— or type the dimensions below —', 'sm-marketplace' ) : __( 'Choose…', 'sm-marketplace' ) ); ?></option>
								<?php foreach ( $family['sections'] as $s ) : ?>
									<option value="<?php echo esc_attr( $s['id'] ); ?>" <?php selected( (int) ( $section['id'] ?? 0 ), $s['id'] ); ?>><?php echo esc_html( $s['designation'] . ' — ' . sm_number( $s['mass_kg_m'], 2 ) . ' kg/m' ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					<?php endif; ?>
					<?php if ( 'formula' === $family['method'] ) : ?>
						<?php
						$keys = $family['dims_keys'];
						if ( 'SHS' === $family['code'] ) {
							$keys = array( 'b', 't' );
						}
						$shown = $section ? $section['dims'] : ( $typed['dims'] ?? $dims_in );
						?>
						<div class="sm-field sm-field--row<?php echo count( $keys ) > 2 ? ' sm-field--row3' : ''; ?>">
							<?php foreach ( $keys as $k ) : ?>
								<div>
									<label for="smw-d-<?php echo esc_attr( $k ); ?>"><?php echo esc_html( 'SHS' === $family['code'] && 'b' === $k ? __( 'Side', 'sm-marketplace' ) : $dim_labels[ $k ] ); ?> <small>(mm)</small></label>
									<input id="smw-d-<?php echo esc_attr( $k ); ?>" name="dims[<?php echo esc_attr( $k ); ?>]" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( isset( $shown[ $k ] ) ? (string) $shown[ $k ] : '' ); ?>"<?php echo $section ? ' data-smw-from-list' : ''; ?>>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<?php if ( $typed_error && count( $dims_in ) >= count( 'SHS' === $family['code'] ? array( 'b', 't' ) : $family['dims_keys'] ) ) : ?>
						<p class="sm-notice sm-notice--warn"><?php sm_icon( 'alert' ); ?><span><?php echo esc_html( $typed_error ); ?></span></p>
					<?php endif; ?>
					<noscript><button class="sm-btn sm-btn--ghost" type="submit"><?php esc_html_e( 'Continue', 'sm-marketplace' ); ?></button></noscript>
				</div>

				<?php $shape = $section ? $section : $typed; ?>
				<?php if ( $shape && ! $is_plate ) : ?>
					<figure class="smw-profile">
						<div class="smw-profile__drawing" dir="ltr"><?php echo $shape['drawing']; // phpcs:ignore WordPress.Security.EscapeOutput -- SVG built from numbers by sm-weight-engine. ?></div>
						<figcaption>
							<p class="smw-profile__name"><?php echo sm_tech( $shape['designation'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
							<dl class="smw-dims">
								<?php foreach ( $shape['dims'] as $k => $v ) : ?>
									<?php if ( 'SHS' === $family['code'] && 'h' === $k ) { continue; } ?>
									<div><dt><?php echo esc_html( $dim_labels[ $k ] ?? $k ); ?></dt><dd><?php echo sm_tech( sm_number( $v, fmod( (float) $v, 1 ) ? 1 : 0 ) . ' mm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd></div>
								<?php endforeach; ?>
								<?php if ( $section ) : ?>
									<div><dt><?php esc_html_e( 'Area', 'sm-marketplace' ); ?></dt><dd><?php echo sm_tech( sm_number( $section['area_mm2'] / 100, 2 ) . ' cm²' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd></div>
									<div class="is-key"><dt><?php esc_html_e( 'Weight per metre', 'sm-marketplace' ); ?></dt><dd><?php echo sm_tech( sm_number( $section['mass_kg_m'], 2 ) . ' kg/m' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd></div>
								<?php endif; ?>
							</dl>
							<?php if ( $section && ! empty( $section['source'] ) ) : ?>
								<p class="smw-source"><?php sm_icon( 'file' ); ?><span><?php echo esc_html( $section['source']['standard'] ); ?><?php if ( $section['source']['url'] ) : ?> · <a href="<?php echo esc_url( $section['source']['url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Source table', 'sm-marketplace' ); ?></a><?php endif; ?></span></p>
							<?php elseif ( $typed ) : ?>
								<p class="smw-source"><?php sm_icon( 'file' ); ?><span><?php echo esc_html( $family['standard'] ); ?></span></p>
							<?php endif; ?>
						</figcaption>
					</figure>
				<?php endif; ?>
			</div>
		</fieldset>
	<?php endif; ?>

	<?php if ( $ready ) : ?>
		<fieldset class="smw-step" id="smw-qty">
			<legend class="smw-step__title"><span class="smw-step__n">3</span><?php esc_html_e( 'Quantity', 'sm-marketplace' ); ?></legend>
			<div class="smw-modes" role="radiogroup" aria-label="<?php esc_attr_e( 'What do you know?', 'sm-marketplace' ); ?>">
				<label class="smw-mode"><input type="radio" name="mode" value="bars" <?php checked( $mode, 'bars' ); ?>><span><?php echo esc_html( $is_plate ? __( 'Number of plates', 'sm-marketplace' ) : __( 'Number of bars', 'sm-marketplace' ) ); ?></span></label>
				<label class="smw-mode"><input type="radio" name="mode" value="weight" <?php checked( $mode, 'weight' ); ?>><span><?php esc_html_e( 'Total weight', 'sm-marketplace' ); ?></span></label>
			</div>
			<div class="sm-field sm-field--row">
				<?php if ( $is_plate ) : ?>
					<div><label for="smw-width"><?php esc_html_e( 'Plate width', 'sm-marketplace' ); ?> <small>(mm)</small></label><input id="smw-width" name="width" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( (string) ( $st['width'] ?? '' ) ); ?>" placeholder="1500"></div>
					<div><label for="smw-plen"><?php esc_html_e( 'Plate length', 'sm-marketplace' ); ?> <small>(mm)</small></label><input id="smw-plen" name="plate_length" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( (string) ( $st['plate_length'] ?? '' ) ); ?>" placeholder="3000"></div>
				<?php else : ?>
					<div>
						<label for="smw-length"><?php esc_html_e( 'Bar length', 'sm-marketplace' ); ?></label>
						<select id="smw-length" name="length" dir="ltr">
							<?php foreach ( $lengths as $l ) : ?><option value="<?php echo esc_attr( $l ); ?>" <?php selected( ! $custom && (int) $length === (int) $l ); ?>><?php echo esc_html( $m( $l ) ); ?></option><?php endforeach; ?>
							<option value="custom" <?php selected( $custom ); ?>><?php esc_html_e( 'Other length…', 'sm-marketplace' ); ?></option>
						</select>
					</div>
					<div<?php echo $custom ? '' : ' hidden'; ?> data-smw-custom>
						<label for="smw-lc"><?php esc_html_e( 'Length', 'sm-marketplace' ); ?> <small>(m)</small></label>
						<input id="smw-lc" name="length_custom" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( (string) ( $st['length_custom'] ?? '' ) ); ?>" placeholder="9">
					</div>
				<?php endif; ?>
			</div>
			<div class="sm-field">
				<?php if ( 'weight' === $mode ) : ?>
					<label for="smw-total"><?php esc_html_e( 'Total weight', 'sm-marketplace' ); ?> <small>(kg)</small></label>
					<input id="smw-total" name="total_kg" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( (string) ( $st['total_kg'] ?? '' ) ); ?>" placeholder="5000">
				<?php else : ?>
					<label for="smw-bars"><?php echo esc_html( $is_plate ? __( 'Number of plates', 'sm-marketplace' ) : __( 'Number of bars', 'sm-marketplace' ) ); ?></label>
					<input id="smw-bars" name="bars" inputmode="numeric" dir="ltr" value="<?php echo esc_attr( (string) ( $st['bars'] ?? '' ) ); ?>" placeholder="10">
				<?php endif; ?>
			</div>
			<button class="sm-btn sm-btn--accent" type="submit" data-smw-submit><?php sm_icon( 'scale' ); ?><?php esc_html_e( 'Calculate', 'sm-marketplace' ); ?></button>
		</fieldset>
	<?php endif; ?>

	<div class="smw-result" aria-live="polite" data-smw-result>
		<?php if ( $result && ! empty( $result['error'] ) ) : ?>
			<p class="sm-notice sm-notice--warn"><?php sm_icon( 'alert' ); ?><span><?php echo esc_html( $result['error'] ); ?></span></p>
		<?php elseif ( $result ) : ?>
			<?php
			/* translators: %s: weight of one piece. */
			$one   = $is_plate ? __( 'One plate weighs %s', 'sm-marketplace' ) : __( 'One bar weighs %s', 'sm-marketplace' );
			/* translators: 1: number of pieces, 2: their weight. */
			$whole = $is_plate ? __( '%1$s whole plates = %2$s', 'sm-marketplace' ) : __( '%1$s whole bars = %2$s', 'sm-marketplace' );
			$len   = $is_plate ? sm_number( $result['width_mm'] ) . ' × ' . sm_number( $result['length_mm'] ) . ' mm' : $m( $result['length_mm'] );
			?>
			<div class="smw-result__card">
				<span class="smw-chip"><?php echo esc_html( sm_weight_source_label( 'theoretical' ) ); ?></span>
				<?php if ( 'weight' === $result['mode'] ) : ?>
					<p class="smw-result__label"><?php echo esc_html( $is_plate ? __( 'Number of plates', 'sm-marketplace' ) : __( 'Number of bars', 'sm-marketplace' ) ); ?></p>
					<p class="smw-result__value"><bdi dir="ltr">≈ <?php echo esc_html( sm_number( $result['bars_exact'], 2 ) ); ?></bdi></p>
					<p class="smw-result__detail">
						<?php
						printf(
							/* translators: 1: weight, 2: size and length of one piece. */
							esc_html__( 'for %1$s of %2$s', 'sm-marketplace' ),
							sm_kg( $result['total_kg'], 1 ), // phpcs:ignore WordPress.Security.EscapeOutput
							sm_tech( $result['designation'] . ' · ' . $len ) // phpcs:ignore WordPress.Security.EscapeOutput
						);
						?>
					</p>
					<ul class="smw-result__list">
						<?php if ( $result['bars_full'] > 0 ) : ?>
							<li><?php printf( esc_html( $whole ), sm_tech( (string) $result['bars_full'] ), sm_kg( $result['bars_full_kg'], 1 ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
						<?php endif; ?>
						<li><?php printf( esc_html( $whole ), sm_tech( (string) $result['bars_next'] ), sm_kg( $result['bars_next_kg'], 1 ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
						<li><?php printf( esc_html( $one ), sm_kg( $result['per_piece_kg'], 2 ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
					</ul>
				<?php else : ?>
					<p class="smw-result__label"><?php esc_html_e( 'Total weight', 'sm-marketplace' ); ?></p>
					<p class="smw-result__value"><?php echo sm_kg( $result['total_kg'], 1 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
					<p class="smw-result__detail"><?php echo sm_tech( $result['bars'] . ' × ' . $result['designation'] . ' · ' . $len ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
					<ul class="smw-result__list">
						<li><?php printf( esc_html( $one ), sm_kg( $result['per_piece_kg'], 2 ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
						<?php if ( $is_plate ) : ?>
							<li><?php echo sm_tech( sm_number( $result['kg_per_m2'], 2 ) . ' kg/m²' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
						<?php else : ?>
							<li><?php echo sm_tech( sm_number( $result['mass_kg_m'], 2 ) . ' kg/m' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
						<?php endif; ?>
					</ul>
				<?php endif; ?>
				<p class="smw-result__note"><?php sm_icon( 'info' ); ?><span><?php printf( /* translators: %s: density. */ esc_html__( 'Theoretical weight: nominal dimensions × %s kg/m³. The weighed weight can differ within the manufacturing tolerances.', 'sm-marketplace' ), esc_html( sm_number( $result['density'] ) ) ); ?></span></p>
			</div>
		<?php elseif ( $ready ) : ?>
			<p class="smw-result__empty"><?php echo esc_html( 'weight' === $mode ? __( 'Enter the total weight to see the number of pieces.', 'sm-marketplace' ) : __( 'Enter the number of pieces to see the total weight.', 'sm-marketplace' ) ); ?></p>
		<?php endif; ?>
	</div>
</form>

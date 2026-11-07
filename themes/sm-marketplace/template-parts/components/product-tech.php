<?php
/**
 * Technical part of the product form: category fields, quantity, price and
 * the summary computed on the server (sm-products + sm-weight-engine).
 *
 * Refreshed in place by assets/js/product-form.js (?smp_partial=1) on every
 * change; without JavaScript the "Update" button reloads the form.
 *
 * @var array $args { state: array, category: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$st  = (array) ( $args['state'] ?? array() );
$cat = (array) $args['category'];
$v   = static fn( $k, $d = '' ) => isset( $st[ $k ] ) && ! is_array( $st[ $k ] ) ? (string) $st[ $k ] : $d;
$pv  = (array) sm_theme_data( 'product_preview', $st );
$sum = $pv['summary'] ?? null;
$err = (array) ( $pv['errors'] ?? array() );
$ps  = (array) sm_theme_data( 'product_settings' );
$ws  = (array) sm_theme_data( 'weight_settings' );
$unit_labels = array(
	'kg'   => 'kg',
	'unit' => __( 'piece', 'sm-marketplace' ),
	'm2'   => 'm²',
	'ml'   => __( 'linear metre', 'sm-marketplace' ),
);
// Server errors of the previous submission win; live errors show only once the field was filled.
$field_error = static function ( $key ) use ( $err, $st ) {
	$msg = sm_theme_field_error( $key );
	if ( ! $msg && isset( $err[ $key ] ) ) {
		$filled = array( 'section' => 'section', 'bars' => 'bars', 'total_kg' => 'total_kg', 'actual_weight' => 'actual_weight', 'length' => 'length_custom', 'width' => 'width', 'price' => 'price', 'price_unit' => 'price', 'quantity' => 'quantity' );
		if ( isset( $filled[ $key ] ) && '' !== (string) ( $st[ $filled[ $key ] ] ?? '' ) && ! is_array( $st[ $filled[ $key ] ] ) ) {
			$msg = $err[ $key ];
		}
	}
	if ( $msg ) {
		printf( '<p class="sm-field-error" id="err-%s">%s</p>', esc_attr( $key ), esc_html( $msg ) );
	}
};
?>
<div class="smp-tech" data-smp-tech>
	<?php if ( 'engine' === $cat['kind'] ) : ?>
		<?php
		$all_fam  = array();
		foreach ( (array) sm_theme_data( 'weight_families' ) as $f ) {
			$all_fam[ $f['code'] ] = $f;
		}
		$families = array_values( array_filter( array_map( static fn( $c ) => $all_fam[ $c ] ?? null, $cat['families'] ) ) );
		$code     = count( $families ) === 1 ? $families[0]['code'] : strtoupper( $v( 'family' ) );
		$family   = $code ? sm_theme_data( 'weight_family', array( 'code' => $code ) ) : null;
		$is_plate = $family && 'plate' === $family['shape'];
		?>
		<?php if ( count( $families ) > 1 ) : ?>
			<fieldset class="sm-field">
				<legend class="sm-field__legend"><?php esc_html_e( 'Product type', 'sm-marketplace' ); ?></legend>
				<div class="smw-types smp-types">
					<?php foreach ( $families as $f ) : ?>
						<label class="smw-type">
							<input type="radio" name="family" value="<?php echo esc_attr( $f['code'] ); ?>" <?php checked( $code, $f['code'] ); ?>>
							<span class="smw-type__card">
								<span class="smw-type__icon" dir="ltr"><?php echo $f['icon']; // phpcs:ignore WordPress.Security.EscapeOutput -- SVG built from numbers by sm-weight-engine. ?></span>
								<span class="smw-type__code"><?php echo esc_html( $f['code'] ); ?></span>
								<span class="smw-type__name"><?php echo esc_html( sm_t( $f['name'] ) ); ?></span>
							</span>
						</label>
					<?php endforeach; ?>
				</div>
				<?php $field_error( 'family' ); ?>
			</fieldset>
		<?php else : ?>
			<input type="hidden" name="family" value="<?php echo esc_attr( $code ); ?>">
		<?php endif; ?>

		<?php if ( $family ) : ?>
			<?php
			$section = null;
			if ( $v( 'section' ) && is_numeric( $v( 'section' ) ) ) {
				$section = sm_theme_data( 'weight_section', array( 'id' => (int) $v( 'section' ) ) );
				if ( $section && $section['family'] !== $family['code'] ) {
					$section = null;
				}
			}
			$dims_in = array_filter( (array) ( $st['dims'] ?? array() ), static fn( $x ) => '' !== (string) $x );
			$typed   = null;
			if ( ! $section && 'formula' === $family['method'] && $dims_in && ! $is_plate ) {
				$d = $dims_in;
				if ( 'SHS' === $family['code'] && isset( $d['b'] ) ) {
					$d['h'] = $d['b'];
				}
				$typed = sm_theme_data( 'weight_drawing', array( 'family' => $family['code'], 'dims' => $d ) );
				if ( ! empty( $typed['error'] ) ) {
					$typed = null;
				}
			}
			$shape = $section ? $section : $typed;
			?>
			<div class="smw-size smp-size">
				<div class="smw-size__fields">
					<?php if ( $family['sections'] && ! $is_plate ) : ?>
						<div class="sm-field">
							<label for="smp-section"><?php echo esc_html( 'formula' === $family['method'] ? __( 'Common size', 'sm-marketplace' ) : __( 'Size (European catalogue)', 'sm-marketplace' ) ); ?></label>
							<select id="smp-section" name="section" dir="ltr"<?php sm_theme_aria( 'section' ); ?>>
								<option value=""><?php echo esc_html( 'formula' === $family['method'] ? __( '— or type the dimensions below —', 'sm-marketplace' ) : __( 'Choose…', 'sm-marketplace' ) ); ?></option>
								<?php foreach ( $family['sections'] as $s ) : ?>
									<option value="<?php echo esc_attr( $s['id'] ); ?>" <?php selected( (int) ( $section['id'] ?? 0 ), $s['id'] ); ?>><?php echo esc_html( $s['designation'] . ' — ' . sm_number( $s['mass_kg_m'], 2 ) . ' kg/m' ); ?></option>
								<?php endforeach; ?>
							</select>
							<?php $field_error( 'section' ); ?>
						</div>
					<?php endif; ?>
					<?php if ( 'formula' === $family['method'] ) : ?>
						<?php
						$keys   = 'SHS' === $family['code'] ? array( 'b', 't' ) : $family['dims_keys'];
						$shown  = $section ? $section['dims'] : $dims_in;
						$labels = array( 'h' => __( 'Height h', 'sm-marketplace' ), 'b' => 'SHS' === $family['code'] ? __( 'Side', 'sm-marketplace' ) : __( 'Width b', 'sm-marketplace' ), 't' => __( 'Thickness t', 'sm-marketplace' ), 'd' => __( 'Diameter D', 'sm-marketplace' ), 'a' => __( 'Side a', 'sm-marketplace' ) );
						?>
						<div class="sm-field sm-field--row<?php echo count( $keys ) > 2 ? ' sm-field--row3' : ''; ?>">
							<?php foreach ( $keys as $k ) : ?>
								<div>
									<label for="smp-d-<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $labels[ $k ] ?? $k ); ?> <small>(mm)</small></label>
									<input id="smp-d-<?php echo esc_attr( $k ); ?>" name="dims[<?php echo esc_attr( $k ); ?>]" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( isset( $shown[ $k ] ) ? (string) $shown[ $k ] : '' ); ?>">
								</div>
							<?php endforeach; ?>
						</div>
						<?php if ( ! $section ) { $field_error( 'section' ); } ?>
					<?php endif; ?>
					<div class="sm-field">
						<label for="smp-grade"><?php esc_html_e( 'Steel grade', 'sm-marketplace' ); ?></label>
						<?php $grade = $v( 'grade' ); $grades = (array) ( $ps['grades'] ?? array() ); $other = $grade && ! in_array( $grade, $grades, true ) && 'OTHER' !== $grade; ?>
						<select id="smp-grade" name="grade" dir="ltr">
							<option value=""><?php esc_html_e( 'Not specified', 'sm-marketplace' ); ?></option>
							<?php foreach ( $grades as $g ) : ?><option value="<?php echo esc_attr( $g ); ?>" <?php selected( $grade, $g ); ?>><?php echo esc_html( $g ); ?></option><?php endforeach; ?>
							<option value="OTHER" <?php selected( $other || 'OTHER' === $grade ); ?>><?php esc_html_e( 'Other…', 'sm-marketplace' ); ?></option>
						</select>
						<?php if ( $other || 'OTHER' === $grade ) : ?>
							<input name="grade_other" class="smp-inline" dir="ltr" value="<?php echo esc_attr( $other ? $grade : $v( 'grade_other' ) ); ?>" placeholder="S460N" aria-label="<?php esc_attr_e( 'Other steel grade', 'sm-marketplace' ); ?>">
						<?php endif; ?>
					</div>
				</div>
				<?php if ( $shape ) : ?>
					<figure class="smw-profile smp-profile">
						<div class="smw-profile__drawing" dir="ltr"><?php echo $shape['drawing']; // phpcs:ignore WordPress.Security.EscapeOutput -- SVG built from numbers. ?></div>
						<figcaption>
							<p class="smw-profile__name"><?php echo sm_tech( $shape['designation'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
							<?php if ( $section ) : ?>
								<p class="smp-kgm"><?php echo sm_tech( sm_number( $section['mass_kg_m'], 2 ) . ' kg/m' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
								<p class="smw-source"><?php sm_icon( 'file' ); ?><span><?php echo esc_html( $section['source']['standard'] ?? '' ); ?></span></p>
							<?php endif; ?>
						</figcaption>
					</figure>
				<?php endif; ?>
			</div>

			<?php if ( $is_plate ) : ?>
				<div class="sm-field sm-field--row sm-field--row3">
					<div><label for="smp-d-t"><?php esc_html_e( 'Thickness t', 'sm-marketplace' ); ?> <small>(mm)</small></label><input id="smp-d-t" name="dims[t]" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( (string) ( $dims_in['t'] ?? '' ) ); ?>"><?php $field_error( 'section' ); ?></div>
					<div><label for="smp-width"><?php esc_html_e( 'Plate width', 'sm-marketplace' ); ?> <small>(mm)</small></label><input id="smp-width" name="width" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( $v( 'width' ) ); ?>" placeholder="1500"><?php $field_error( 'width' ); ?></div>
					<div><label for="smp-plen"><?php esc_html_e( 'Plate length', 'sm-marketplace' ); ?> <small>(mm)</small></label><input id="smp-plen" name="plate_length" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( $v( 'plate_length' ) ); ?>" placeholder="3000"><?php $field_error( 'length' ); ?></div>
				</div>
			<?php else : ?>
				<?php
				$lengths = (array) ( $ws['bar_lengths'] ?? array() );
				$length  = $v( 'length', (string) ( $ws['default_length'] ?? '' ) );
				$custom  = 'custom' === $length;
				?>
				<div class="sm-field sm-field--row">
					<div>
						<label for="smp-length"><?php esc_html_e( 'Bar length', 'sm-marketplace' ); ?></label>
						<select id="smp-length" name="length" dir="ltr">
							<?php foreach ( $lengths as $l ) : ?><option value="<?php echo esc_attr( $l ); ?>" <?php selected( ! $custom && (int) $length === (int) $l ); ?>><?php echo esc_html( sm_number( $l / 1000, fmod( $l, 1000 ) ? 2 : 0 ) . "\u{00A0}m" ); ?></option><?php endforeach; ?>
							<option value="custom" <?php selected( $custom ); ?>><?php esc_html_e( 'Other length…', 'sm-marketplace' ); ?></option>
						</select>
					</div>
					<?php if ( $custom ) : ?>
						<div><label for="smp-lc"><?php esc_html_e( 'Length', 'sm-marketplace' ); ?> <small>(m)</small></label><input id="smp-lc" name="length_custom" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( $v( 'length_custom' ) ); ?>"><?php $field_error( 'length' ); ?></div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php $mode = 'weight' === $v( 'qty_mode' ) ? 'weight' : 'bars'; ?>
			<fieldset class="sm-field">
				<legend class="sm-field__legend"><?php esc_html_e( 'Quantity for sale', 'sm-marketplace' ); ?></legend>
				<div class="smw-modes">
					<label class="smw-mode"><input type="radio" name="qty_mode" value="bars" <?php checked( $mode, 'bars' ); ?>><span><?php echo esc_html( $is_plate ? __( 'Number of plates', 'sm-marketplace' ) : __( 'Number of bars', 'sm-marketplace' ) ); ?></span></label>
					<label class="smw-mode"><input type="radio" name="qty_mode" value="weight" <?php checked( $mode, 'weight' ); ?>><span><?php esc_html_e( 'Total weight', 'sm-marketplace' ); ?></span></label>
				</div>
				<?php if ( 'weight' === $mode ) : ?>
					<label for="smp-total"><?php esc_html_e( 'Total weight', 'sm-marketplace' ); ?> <small>(kg)</small></label>
					<input id="smp-total" name="total_kg" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( $v( 'total_kg' ) ); ?>"<?php sm_theme_aria( 'total_kg' ); ?>>
					<?php $field_error( 'total_kg' ); ?>
				<?php else : ?>
					<label for="smp-bars"><?php echo esc_html( $is_plate ? __( 'Number of plates', 'sm-marketplace' ) : __( 'Number of bars', 'sm-marketplace' ) ); ?></label>
					<input id="smp-bars" name="bars" inputmode="numeric" dir="ltr" value="<?php echo esc_attr( $v( 'bars' ) ); ?>"<?php sm_theme_aria( 'bars' ); ?>>
					<?php $field_error( 'bars' ); ?>
				<?php endif; ?>
			</fieldset>
			<?php $source = 'actual' === $v( 'weight_source' ) ? 'actual' : 'theoretical'; ?>
			<fieldset class="sm-field">
				<legend class="sm-field__legend"><?php esc_html_e( 'Weight shown to buyers', 'sm-marketplace' ); ?></legend>
				<div class="sm-segment">
					<label><input type="radio" name="weight_source" value="theoretical" <?php checked( $source, 'theoretical' ); ?>><span><?php sm_icon( 'ruler' ); ?><?php echo esc_html( 'weight' === $mode ? __( 'Estimated', 'sm-marketplace' ) : __( 'Theoretical (calculated)', 'sm-marketplace' ) ); ?></span></label>
					<label><input type="radio" name="weight_source" value="actual" <?php checked( $source, 'actual' ); ?>><span><?php sm_icon( 'scale' ); ?><?php esc_html_e( 'Actual (weighed)', 'sm-marketplace' ); ?></span></label>
				</div>
				<?php if ( 'actual' === $source && 'bars' === $mode ) : ?>
					<label for="smp-actual"><?php esc_html_e( 'Measured weight (kg)', 'sm-marketplace' ); ?></label>
					<input id="smp-actual" name="actual_weight" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( $v( 'actual_weight' ) ); ?>">
					<?php $field_error( 'actual_weight' ); ?>
				<?php endif; ?>
			</fieldset>
		<?php endif; ?>

	<?php else : ?>
		<?php $specs = (array) ( $st['specs'] ?? array() ); ?>
		<div class="sm-field-grid">
			<?php foreach ( $cat['fields'] as $f ) : ?>
				<?php $id = 'smp-s-' . $f['key']; $val = (string) ( $specs[ $f['key'] ] ?? '' ); ?>
				<div class="sm-field">
					<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( sm_t( $f['label'] ) ); ?><?php echo empty( $f['unit'] ) ? '' : ' <small>(' . esc_html( $f['unit'] ) . ')</small>'; ?><?php echo empty( $f['required'] ) ? ' <small class="sm-optional">' . esc_html__( 'optional', 'sm-marketplace' ) . '</small>' : ''; ?></label>
					<?php if ( 'select' === $f['type'] ) : ?>
						<select id="<?php echo esc_attr( $id ); ?>" name="specs[<?php echo esc_attr( $f['key'] ); ?>]"<?php sm_theme_aria( 'specs_' . $f['key'] ); ?>>
							<option value=""><?php esc_html_e( 'Choose…', 'sm-marketplace' ); ?></option>
							<?php foreach ( $f['options'] as $o ) : ?><option value="<?php echo esc_attr( $o['value'] ); ?>" <?php selected( $val, $o['value'] ); ?>><?php echo esc_html( sm_t( array( 'fr' => $o['fr'], 'ar' => $o['ar'] ) ) ); ?></option><?php endforeach; ?>
						</select>
					<?php else : ?>
						<input id="<?php echo esc_attr( $id ); ?>" name="specs[<?php echo esc_attr( $f['key'] ); ?>]" value="<?php echo esc_attr( $val ); ?>"<?php echo 'number' === $f['type'] ? ' inputmode="decimal" dir="ltr"' : ' dir="auto"'; ?><?php sm_theme_aria( 'specs_' . $f['key'] ); ?>>
					<?php endif; ?>
					<?php sm_theme_error( 'specs_' . $f['key'] ); ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php $unit = in_array( $v( 'unit' ), $cat['units'], true ) ? $v( 'unit' ) : $cat['units'][0]; ?>
		<div class="sm-field sm-field--row">
			<div>
				<label for="smp-qty"><?php esc_html_e( 'Available quantity', 'sm-marketplace' ); ?></label>
				<input id="smp-qty" name="quantity" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( $v( 'quantity' ) ); ?>"<?php sm_theme_aria( 'quantity' ); ?>>
				<?php $field_error( 'quantity' ); ?>
			</div>
			<div>
				<label for="smp-unit"><?php esc_html_e( 'Unit', 'sm-marketplace' ); ?></label>
				<select id="smp-unit" name="unit"><?php foreach ( $cat['units'] as $u ) : ?><option value="<?php echo esc_attr( $u ); ?>" <?php selected( $unit, $u ); ?>><?php echo esc_html( $unit_labels[ $u ] ?? $u ); ?></option><?php endforeach; ?></select>
			</div>
		</div>
		<?php if ( 'kg' !== $unit ) : ?>
			<div class="sm-field">
				<label for="smp-actual"><?php esc_html_e( 'Total weight (kg)', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional, if weighed', 'sm-marketplace' ); ?></small></label>
				<input id="smp-actual" name="actual_weight" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( $v( 'actual_weight' ) ); ?>">
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<?php
	$units = $cat['units'];
	$price_unit = 'engine' === $cat['kind'] ? ( in_array( $v( 'price_unit' ), $units, true ) ? $v( 'price_unit' ) : $units[0] ) : ( $unit ?? $units[0] );
	?>
	<div class="sm-field sm-field--row">
		<div>
			<label for="smp-price"><?php esc_html_e( 'Price', 'sm-marketplace' ); ?> <small>(<?php echo esc_html( sm_currency_label() ); ?>)</small></label>
			<input id="smp-price" name="price" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( $v( 'price' ) ); ?>"<?php sm_theme_aria( 'price' ); ?>>
			<?php $field_error( 'price' ); ?>
		</div>
		<div>
			<?php if ( 'engine' === $cat['kind'] ) : ?>
				<label for="smp-punit"><?php esc_html_e( 'Price per', 'sm-marketplace' ); ?></label>
				<select id="smp-punit" name="price_unit"><?php foreach ( $units as $u ) : ?><option value="<?php echo esc_attr( $u ); ?>" <?php selected( $price_unit, $u ); ?>><?php echo esc_html( 'unit' === $u ? ( ! empty( $is_plate ) ? __( 'plate', 'sm-marketplace' ) : __( 'bar', 'sm-marketplace' ) ) : ( $unit_labels[ $u ] ?? $u ) ); ?></option><?php endforeach; ?></select>
				<?php $field_error( 'price_unit' ); ?>
			<?php else : ?>
				<p class="smp-per"><?php echo esc_html( sprintf( /* translators: %s: unit. */ __( 'per %s', 'sm-marketplace' ), $unit_labels[ $price_unit ] ?? $price_unit ) ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<div class="smp-summary" aria-live="polite">
		<p class="smp-summary__title"><?php esc_html_e( 'Summary shown to buyers', 'sm-marketplace' ); ?></p>
		<dl class="sm-spec sm-spec--compact">
			<div><dt><?php esc_html_e( 'Total weight', 'sm-marketplace' ); ?></dt><dd>
				<?php if ( $sum && $sum['total_weight'] > 0 ) : ?>
					<?php echo sm_kg( $sum['total_weight'], 1 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<small><?php echo esc_html( sm_weight_source_label( 'none' === $sum['weight_source'] ? 'actual' : $sum['weight_source'] ) ); ?></small>
				<?php else : ?>—<?php endif; ?>
			</dd></div>
			<?php if ( 'engine' === $cat['kind'] ) : ?>
				<div><dt><?php echo esc_html( ! empty( $is_plate ) ? __( 'Number of plates', 'sm-marketplace' ) : __( 'Number of bars', 'sm-marketplace' ) ); ?></dt><dd>
					<?php if ( $sum && $sum['pieces'] > 0 ) : ?>
						<?php echo sm_tech( ( 'weight' === $sum['qty_mode'] ? '≈ ' . sm_number( $sum['pieces'], 1 ) : sm_number( $sum['pieces'] ) ) . ( $sum['length_mm'] && empty( $is_plate ) ? ' × ' . sm_number( $sum['length_mm'] / 1000, fmod( $sum['length_mm'], 1000 ) ? 2 : 0 ) . ' m' : '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php else : ?>—<?php endif; ?>
				</dd></div>
			<?php endif; ?>
			<div class="is-key"><dt><?php esc_html_e( 'Total price', 'sm-marketplace' ); ?></dt><dd><?php echo $sum && $sum['total_price'] > 0 ? esc_html( sm_money( $sum['total_price'] ) ) : '—'; ?></dd></div>
			<?php if ( $sum && $sum['price_per_kg'] > 0 && 'kg' !== $sum['price_unit'] ) : ?>
				<div><dt><?php esc_html_e( 'Equivalent price per kg', 'sm-marketplace' ); ?></dt><dd><?php echo sm_per_unit( $sum['price_per_kg'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd></div>
			<?php endif; ?>
		</dl>
		<p class="sm-help"><?php esc_html_e( 'Weight and totals are calculated by the platform from the European tables; you can still give the weighed weight.', 'sm-marketplace' ); ?></p>
	</div>
	<noscript><button class="sm-btn sm-btn--ghost" type="submit" name="intent" value="refresh"><?php esc_html_e( 'Update', 'sm-marketplace' ); ?></button></noscript>
</div>

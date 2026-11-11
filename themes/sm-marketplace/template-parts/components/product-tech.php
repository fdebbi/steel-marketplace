<?php
/**
 * Technical part of the product form: category fields, quantity, price and
 * the summary computed on the server (sm-products + sm-weight-engine).
 *
 * Refreshed in place by assets/js/product-form.js (?smp_partial=1) on every
 * change; without JavaScript the "Update" button reloads the form.
 *
 * @var array $args { state: array, category: array, market?: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$st  = (array) ( $args['state'] ?? array() );
$cat = (array) $args['category'];
$mk  = (array) ( $args['market'] ?? array() );
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
	'lot'  => __( 'the whole lot', 'sm-marketplace' ),
);
// Server errors of the previous submission win; live errors show only once the field was filled.
$field_error = static function ( $key ) use ( $err, $st ) {
	$msg = sm_theme_field_error( $key );
	if ( ! $msg && isset( $err[ $key ] ) ) {
		$filled = array( 'section' => 'section', 'bars' => 'bars', 'total_kg' => 'total_kg', 'actual_weight' => 'actual_weight', 'length' => 'length_custom', 'width' => 'width', 'price' => 'price', 'price_unit' => 'price', 'quantity' => 'quantity', 'lot_price' => 'lot_price' );
		if ( isset( $filled[ $key ] ) && '' !== (string) ( $st[ $filled[ $key ] ] ?? '' ) && ! is_array( $st[ $filled[ $key ] ] ) ) {
			$msg = $err[ $key ];
		}
	}
	if ( ! $msg && isset( $err[ $key ] ) && 0 === strpos( $key, 'specs_' ) && '' !== (string) ( $st['specs'][ substr( $key, 6 ) ] ?? '' ) ) {
		$msg = $err[ $key ];
	}
	if ( ! $msg && isset( $err[ $key ] ) && ( 0 === strpos( $key, 'lines_' ) || in_array( $key, array( 'condition', 'attrs_cut', 'attrs_inc_dismantling', 'price_unit' ), true ) ) ) {
		$msg = $err[ $key ]; // Only reported once something was typed or chosen.
		if ( 'condition' === $key && '' === (string) ( $st['condition'] ?? '' ) ) {
			$msg = '';
		}
	}
	if ( $msg ) {
		printf( '<p class="sm-field-error" id="err-%s">%s</p>', esc_attr( $key ), esc_html( $msg ) );
	}
};
// Category fields (specs and weight kinds).
$render_fields = static function () use ( $cat, $st ) {
	$specs = (array) ( $st['specs'] ?? array() );
	if ( empty( $cat['fields'] ) ) {
		return;
	}
	echo '<div class="sm-field-grid">';
	foreach ( $cat['fields'] as $f ) {
		$id  = 'smp-s-' . $f['key'];
		$val = (string) ( $specs[ $f['key'] ] ?? '' );
		echo '<div class="sm-field"><label for="' . esc_attr( $id ) . '">' . esc_html( sm_t( $f['label'] ) ) . ( empty( $f['unit'] ) ? '' : ' <small>(' . esc_html( $f['unit'] ) . ')</small>' ) . ( empty( $f['required'] ) ? ' <small class="sm-optional">' . esc_html__( 'optional', 'sm-marketplace' ) . '</small>' : '' ) . '</label>';
		if ( 'select' === $f['type'] ) {
			echo '<select id="' . esc_attr( $id ) . '" name="specs[' . esc_attr( $f['key'] ) . ']"';
			sm_theme_aria( 'specs_' . $f['key'] );
			echo '><option value="">' . esc_html__( 'Choose…', 'sm-marketplace' ) . '</option>';
			foreach ( $f['options'] as $o ) {
				echo '<option value="' . esc_attr( $o['value'] ) . '"' . selected( $val, $o['value'], false ) . '>' . esc_html( sm_t( array( 'fr' => $o['fr'], 'ar' => $o['ar'] ) ) ) . '</option>';
			}
			echo '</select>';
		} else {
			echo '<input id="' . esc_attr( $id ) . '" name="specs[' . esc_attr( $f['key'] ) . ']" value="' . esc_attr( $val ) . '"' . ( 'number' === $f['type'] ? ' inputmode="decimal" dir="ltr"' : ' dir="auto"' );
			sm_theme_aria( 'specs_' . $f['key'] );
			echo '>';
		}
		sm_theme_error( 'specs_' . $f['key'] );
		echo '</div>';
	}
	echo '</div>';
};
// Steel grade (engine and lines kinds).
$render_grade = static function () use ( $v, $ps ) {
	$grade  = $v( 'grade' );
	$grades = (array) ( $ps['grades'] ?? array() );
	$other  = $grade && ! in_array( $grade, $grades, true ) && 'OTHER' !== $grade;
	echo '<div class="sm-field"><label for="smp-grade">' . esc_html__( 'Steel grade', 'sm-marketplace' ) . '</label><select id="smp-grade" name="grade" dir="ltr"><option value="">' . esc_html__( 'Not specified', 'sm-marketplace' ) . '</option>';
	foreach ( $grades as $g ) {
		echo '<option value="' . esc_attr( $g ) . '"' . selected( $grade, $g, false ) . '>' . esc_html( $g ) . '</option>';
	}
	echo '<option value="OTHER"' . selected( $other || 'OTHER' === $grade, true, false ) . '>' . esc_html__( 'Other…', 'sm-marketplace' ) . '</option></select>';
	if ( $other || 'OTHER' === $grade ) {
		echo '<input name="grade_other" class="smp-inline" dir="ltr" value="' . esc_attr( $other ? $grade : $v( 'grade_other' ) ) . '" placeholder="S460N" aria-label="' . esc_attr__( 'Other steel grade', 'sm-marketplace' ) . '">';
	}
	echo '</div>';
};
?>
<div class="smp-tech" data-smp-tech>
	<?php ob_start(); // Market part: first, or after the dimensions for a building. ?>
	<?php if ( ! empty( $mk['conditions'] ) ) : ?>
		<?php $cond = $v( 'condition' ); ?>
		<fieldset class="sm-field smp-conditions">
			<legend class="sm-field__legend"><?php esc_html_e( 'Condition of the steel', 'sm-marketplace' ); ?></legend>
			<div class="smp-cond-list">
				<?php foreach ( $mk['conditions'] as $code => $label ) : ?>
					<label class="smp-cond smp-cond--<?php echo esc_attr( (string) sm_condition_level( $code ) ); ?>">
						<input type="radio" name="condition" value="<?php echo esc_attr( $code ); ?>" <?php checked( $cond, $code ); ?><?php sm_theme_aria( 'condition' ); ?>>
						<span class="smp-cond__card"><span class="smp-cond__dot" aria-hidden="true"></span><?php echo esc_html( sm_t( $label ) ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
			<?php $field_error( 'condition' ); ?>
		</fieldset>
	<?php endif; ?>
	<?php if ( ! empty( $mk['fields'] ) ) : ?>
		<?php
		$attrs     = (array) ( $st['attrs'] ?? array() );
		$grouped   = array_filter( $mk['fields'], static fn( $f ) => ! empty( $f['group'] ) );
		$ungrouped = array_filter( $mk['fields'], static fn( $f ) => empty( $f['group'] ) );
		?>
		<div class="sm-field-grid smp-attrs">
			<?php foreach ( $ungrouped as $f ) : ?>
				<?php $id = 'smp-a-' . $f['key']; $val = (string) ( $attrs[ $f['key'] ] ?? '' ); ?>
				<?php if ( 'bool' === $f['type'] ) : ?>
					<div class="sm-field smp-attr-bool">
						<label class="sm-check" for="<?php echo esc_attr( $id ); ?>"><input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="attrs[<?php echo esc_attr( $f['key'] ); ?>]" value="1" <?php checked( '1', $val ); ?>><span><?php echo esc_html( sm_t( $f['label'] ) ); ?></span></label>
						<?php $field_error( 'attrs_' . $f['key'] ); ?>
					</div>
				<?php else : ?>
					<div class="sm-field">
						<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( sm_t( $f['label'] ) ); ?><?php echo empty( $f['required'] ) ? ' <small class="sm-optional">' . esc_html__( 'optional', 'sm-marketplace' ) . '</small>' : ''; ?></label>
						<select id="<?php echo esc_attr( $id ); ?>" name="attrs[<?php echo esc_attr( $f['key'] ); ?>]"<?php sm_theme_aria( 'attrs_' . $f['key'] ); ?>>
							<option value=""><?php esc_html_e( 'Choose…', 'sm-marketplace' ); ?></option>
							<?php foreach ( $f['options'] as $o ) : ?><option value="<?php echo esc_attr( $o['value'] ); ?>" <?php selected( $val, $o['value'] ); ?>><?php echo esc_html( sm_t( array( 'fr' => $o['fr'], 'ar' => $o['ar'] ) ) ); ?></option><?php endforeach; ?>
						</select>
						<?php sm_theme_error( 'attrs_' . $f['key'] ); ?>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	<?php if ( ! empty( $grouped ) ) : ?>
		<fieldset class="sm-field smp-includes">
			<legend class="sm-field__legend"><?php esc_html_e( 'What the price includes', 'sm-marketplace' ); ?></legend>
			<div class="smp-includes__list">
				<?php foreach ( $grouped as $f ) : ?>
					<?php
					if ( 'inc_dismantling' === $f['key'] && 'dismantled' === $v( 'mode' ) && empty( $attrs[ $f['key'] ] ) ) {
						continue; // Already dismantled.
					}
					$id = 'smp-a-' . $f['key'];
					?>
					<label class="smp-include" for="<?php echo esc_attr( $id ); ?>"><input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="attrs[<?php echo esc_attr( $f['key'] ); ?>]" value="1" <?php checked( '1', (string) ( $attrs[ $f['key'] ] ?? '' ) ); ?>><span><?php sm_icon( 'check' ); ?><?php echo esc_html( sm_t( $f['label'] ) ); ?></span></label>
					<?php $field_error( 'attrs_' . $f['key'] ); ?>
				<?php endforeach; ?>
			</div>
		</fieldset>
	<?php endif; ?>
	<?php
	$market_html = ob_get_clean();
	if ( 'building' !== $cat['kind'] ) {
		echo $market_html; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above.
	}
	?>

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
					<?php $render_grade(); ?>
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

	<?php elseif ( 'lines' === $cat['kind'] ) : ?>
		<div class="smp-lines-head">
			<?php $render_grade(); ?>
		</div>
		<?php sm_component( 'product-lines', array( 'state' => $st, 'category' => $cat, 'summary' => $sum, 'field_error' => $field_error, 'legend' => __( 'Content of the lot', 'sm-marketplace' ) ) ); ?>
		<?php $source = 'actual' === $v( 'weight_source' ) ? 'actual' : 'theoretical'; ?>
		<fieldset class="sm-field">
			<legend class="sm-field__legend"><?php esc_html_e( 'Weight shown to buyers', 'sm-marketplace' ); ?></legend>
			<div class="sm-segment">
				<label><input type="radio" name="weight_source" value="theoretical" <?php checked( $source, 'theoretical' ); ?>><span><?php sm_icon( 'ruler' ); ?><?php esc_html_e( 'Theoretical (calculated)', 'sm-marketplace' ); ?></span></label>
				<label><input type="radio" name="weight_source" value="actual" <?php checked( $source, 'actual' ); ?>><span><?php sm_icon( 'scale' ); ?><?php esc_html_e( 'Actual (weighed)', 'sm-marketplace' ); ?></span></label>
			</div>
			<?php if ( 'actual' === $source ) : ?>
				<label for="smp-actual"><?php esc_html_e( 'Measured weight of the lot (kg)', 'sm-marketplace' ); ?></label>
				<input id="smp-actual" name="actual_weight" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( $v( 'actual_weight' ) ); ?>">
				<?php $field_error( 'actual_weight' ); ?>
			<?php endif; ?>
		</fieldset>

	<?php elseif ( 'building' === $cat['kind'] ) : ?>
		<?php
		$mode   = $v( 'mode' );
		$specs  = (array) ( $st['specs'] ?? array() );
		$sv     = static fn( $k ) => (string) ( $specs[ $k ] ?? '' );
		$has_inv = (bool) array_filter( (array) ( $st['lines'] ?? array() ), static fn( $r ) => is_array( $r ) && '' !== (string) ( $r['section'] ?? '' ) );
		?>
		<fieldset class="sm-field smb-mode">
			<legend class="sm-field__legend"><?php esc_html_e( 'State of the building', 'sm-marketplace' ); ?></legend>
			<div class="smb-modes">
				<label class="smb-mode-card"><input type="radio" name="mode" value="assembled" <?php checked( $mode, 'assembled' ); ?>><span><?php sm_icon( 'warehouse' ); ?><strong><?php esc_html_e( 'Assembled', 'sm-marketplace' ); ?></strong><small><?php esc_html_e( 'Still standing: sold at a price for the whole building.', 'sm-marketplace' ); ?></small></span></label>
				<label class="smb-mode-card"><input type="radio" name="mode" value="dismantled" <?php checked( $mode, 'dismantled' ); ?>><span><?php sm_icon( 'layers' ); ?><strong><?php esc_html_e( 'Dismantled', 'sm-marketplace' ); ?></strong><small><?php esc_html_e( 'Taken down: sold with the inventory of its elements.', 'sm-marketplace' ); ?></small></span></label>
			</div>
			<?php sm_theme_error( 'mode' ); ?>
		</fieldset>
		<?php if ( $mode ) : ?>
			<div class="smb-dims">
				<?php
				$dim_fields = array(
					'length_m' => array( __( 'Length', 'sm-marketplace' ), 'm', '40' ),
					'width_m'  => array( __( 'Width (span)', 'sm-marketplace' ), 'm', '20' ),
					'height_m' => array( __( 'Eaves height', 'sm-marketplace' ), 'm', '8' ),
					'portals'  => array( __( 'Portal frames', 'sm-marketplace' ), '', '9' ),
					'slope'    => array( __( 'Roof slope', 'sm-marketplace' ), '%', '10' ),
				);
				foreach ( $dim_fields as $k => list( $label, $unit, $ph ) ) :
					?>
					<div class="sm-field">
						<label for="smb-<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $label ); ?><?php echo $unit ? ' <small>(' . esc_html( $unit ) . ')</small>' : ''; ?><?php echo 'slope' === $k ? ' <small class="sm-optional">' . esc_html__( 'optional', 'sm-marketplace' ) . '</small>' : ''; ?></label>
						<input id="smb-<?php echo esc_attr( $k ); ?>" name="specs[<?php echo esc_attr( $k ); ?>]" inputmode="<?php echo 'portals' === $k ? 'numeric' : 'decimal'; ?>" dir="ltr" value="<?php echo esc_attr( $sv( $k ) ); ?>" placeholder="<?php echo esc_attr( $ph ); ?>"<?php sm_theme_aria( 'specs_' . $k ); ?>>
						<?php $field_error( 'specs_' . $k ); ?>
					</div>
				<?php endforeach; ?>
				<div class="smb-area" aria-live="polite">
					<span><?php esc_html_e( 'Total area', 'sm-marketplace' ); ?></span>
					<strong><?php echo $sum && $sum['quantity'] > 0 ? sm_tech( sm_number( $sum['quantity'], fmod( (float) $sum['quantity'], 1 ) ? 1 : 0 ) . ' m²' ) : '—'; // phpcs:ignore WordPress.Security.EscapeOutput ?></strong>
				</div>
			</div>
			<?php echo $market_html; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above. ?>
			<?php if ( 'dismantled' === $mode ) : ?>
				<?php sm_component( 'product-lines', array( 'state' => $st, 'category' => $cat, 'summary' => $sum, 'field_error' => $field_error, 'elements' => (array) ( $mk['elements'] ?? array() ), 'legend' => __( 'Inventory of the structural elements', 'sm-marketplace' ), 'max' => (int) ( $cat['max_lines'] ?? 30 ) ) ); ?>
			<?php else : ?>
				<?php $src = 'actual' === $v( 'weight_source' ) ? 'actual' : 'estimated'; ?>
				<?php if ( ! $has_inv ) : ?>
					<div class="sm-field">
						<label for="smp-total"><?php esc_html_e( 'Estimated weight of the steel structure', 'sm-marketplace' ); ?> <small>(kg)</small></label>
						<input id="smp-total" name="total_kg" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( $v( 'total_kg' ) ); ?>"<?php sm_theme_aria( 'total_kg' ); ?>>
						<input type="hidden" name="weight_source" value="estimated">
						<p class="sm-help"><?php esc_html_e( 'Shown to buyers as an estimate. For a calculated weight, list the elements below instead.', 'sm-marketplace' ); ?></p>
						<?php $field_error( 'total_kg' ); ?>
					</div>
				<?php endif; ?>
				<details class="smb-inv"<?php echo $has_inv ? ' open' : ''; ?>>
					<summary><?php sm_icon( 'list' ); ?><?php esc_html_e( 'List the structural elements (optional): the weight is then calculated', 'sm-marketplace' ); ?></summary>
					<?php sm_component( 'product-lines', array( 'state' => $st, 'category' => $cat, 'summary' => $sum, 'field_error' => $field_error, 'elements' => (array) ( $mk['elements'] ?? array() ), 'legend' => __( 'Inventory of the structural elements', 'sm-marketplace' ), 'max' => (int) ( $cat['max_lines'] ?? 30 ) ) ); ?>
				</details>
			<?php endif; ?>
		<?php endif; ?>

	<?php elseif ( 'weight' === $cat['kind'] ) : ?>
		<?php $render_fields(); ?>
		<?php $source = 'actual' === $v( 'weight_source' ) ? 'actual' : 'estimated'; ?>
		<fieldset class="sm-field">
			<legend class="sm-field__legend"><?php esc_html_e( 'Weight of the lot', 'sm-marketplace' ); ?></legend>
			<div class="sm-segment">
				<label><input type="radio" name="weight_source" value="estimated" <?php checked( $source, 'estimated' ); ?>><span><?php sm_icon( 'ruler' ); ?><?php esc_html_e( 'Estimated', 'sm-marketplace' ); ?></span></label>
				<label><input type="radio" name="weight_source" value="actual" <?php checked( $source, 'actual' ); ?>><span><?php sm_icon( 'scale' ); ?><?php esc_html_e( 'Actual (weighed)', 'sm-marketplace' ); ?></span></label>
			</div>
			<label for="smp-total"><?php echo esc_html( 'actual' === $source ? __( 'Weighed weight', 'sm-marketplace' ) : __( 'Estimated weight', 'sm-marketplace' ) ); ?> <small>(kg)</small></label>
			<input id="smp-total" name="total_kg" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( $v( 'total_kg' ) ); ?>"<?php sm_theme_aria( 'total_kg' ); ?>>
			<?php $field_error( 'total_kg' ); ?>
		</fieldset>

	<?php else : ?>
		<?php $render_fields(); ?>
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
	$units      = $cat['units'];
	$has_punit  = 'specs' !== $cat['kind'];
	$lot_ok     = ! empty( $cat['lot'] ) && ! ( 'building' === $cat['kind'] && 'assembled' === $v( 'mode' ) );
	$sale       = in_array( $v( 'sale_mode' ), array( 'detail', 'lot', 'both' ), true ) ? $v( 'sale_mode' ) : ( 'lot' === $v( 'price_unit' ) ? 'lot' : 'detail' );
	if ( $lot_ok ) {
		$units = (array) ( $cat['detail_units'] ?? array_diff( $units, array( 'lot' ) ) );
	} else {
		$sale = 'detail';
	}
	$def_unit   = 'building' === $cat['kind'] && 'dismantled' === $v( 'mode' ) && in_array( 'kg', $units, true ) ? 'kg' : $units[0];
	$price_unit = $has_punit ? ( in_array( $v( 'price_unit' ), $units, true ) ? $v( 'price_unit' ) : $def_unit ) : ( $unit ?? $units[0] );
	$unit_name  = static fn( $u ) => 'unit' === $u && 'engine' === $cat['kind'] ? ( ! empty( $is_plate ) ? __( 'plate', 'sm-marketplace' ) : __( 'bar', 'sm-marketplace' ) ) : ( 'unit' === $u ? __( 'piece', 'sm-marketplace' ) : ( 'lot' === $u && 'building' === $cat['kind'] ? __( 'the whole building', 'sm-marketplace' ) : ( $unit_labels[ $u ] ?? $u ) ) );
	?>
	<?php if ( $lot_ok ) : ?>
		<fieldset class="sm-field smp-sale">
			<legend class="sm-field__legend"><?php esc_html_e( 'How do you sell it?', 'sm-marketplace' ); ?></legend>
			<div class="smp-sale__modes">
				<label class="smp-sale__mode"><input type="radio" name="sale_mode" value="detail" <?php checked( $sale, 'detail' ); ?>><span><strong><?php esc_html_e( 'By the piece / by the kg', 'sm-marketplace' ); ?></strong><small><?php esc_html_e( 'The buyer takes the quantity he needs.', 'sm-marketplace' ); ?></small></span></label>
				<label class="smp-sale__mode"><input type="radio" name="sale_mode" value="lot" <?php checked( $sale, 'lot' ); ?>><span><strong><?php esc_html_e( 'Whole lot only', 'sm-marketplace' ); ?></strong><small><?php esc_html_e( 'One price for everything.', 'sm-marketplace' ); ?></small></span></label>
				<label class="smp-sale__mode"><input type="radio" name="sale_mode" value="both" <?php checked( $sale, 'both' ); ?>><span><strong><?php esc_html_e( 'Both', 'sm-marketplace' ); ?></strong><small><?php esc_html_e( 'A detail price and a price for the whole lot.', 'sm-marketplace' ); ?></small></span></label>
			</div>
		</fieldset>
	<?php endif; ?>
	<?php if ( 'lot' !== $sale ) : ?>
		<div class="sm-field sm-field--row">
			<div>
				<label for="smp-price"><?php echo esc_html( 'both' === $sale ? __( 'Detail price', 'sm-marketplace' ) : __( 'Price', 'sm-marketplace' ) ); ?> <small>(<?php echo esc_html( sm_currency_label() ); ?>)</small></label>
				<input id="smp-price" name="price" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( $v( 'price' ) ); ?>"<?php sm_theme_aria( 'price' ); ?>>
				<?php $field_error( 'price' ); ?>
			</div>
			<div>
				<?php if ( $has_punit ) : ?>
					<label for="smp-punit"><?php esc_html_e( 'Price per', 'sm-marketplace' ); ?></label>
					<select id="smp-punit" name="price_unit"><?php foreach ( $units as $u ) : ?><option value="<?php echo esc_attr( $u ); ?>" <?php selected( $price_unit, $u ); ?>><?php echo esc_html( $unit_name( $u ) ); ?></option><?php endforeach; ?></select>
					<?php $field_error( 'price_unit' ); ?>
				<?php else : ?>
					<p class="smp-per"><?php echo esc_html( sprintf( /* translators: %s: unit. */ __( 'per %s', 'sm-marketplace' ), $unit_labels[ $price_unit ] ?? $price_unit ) ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>
	<?php if ( 'detail' !== $sale ) : ?>
		<div class="sm-field">
			<label for="smp-lot"><?php echo esc_html( 'building' === $cat['kind'] ? __( 'Price of the whole building', 'sm-marketplace' ) : __( 'Price of the whole lot', 'sm-marketplace' ) ); ?> <small>(<?php echo esc_html( sm_currency_label() ); ?>)</small></label>
			<input id="smp-lot" name="lot_price" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( $v( 'lot_price' ) ); ?>"<?php sm_theme_aria( 'lot_price' ); ?>>
			<?php $field_error( 'lot_price' ); ?>
		</div>
	<?php endif; ?>

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
			<?php if ( 'lines' === $cat['kind'] ) : ?>
				<div><dt><?php esc_html_e( 'Pieces in the lot', 'sm-marketplace' ); ?></dt><dd>
					<?php if ( $sum && $sum['pieces'] > 0 ) : ?>
						<?php echo sm_tech( sm_number( $sum['pieces'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<small><?php echo esc_html( sprintf( /* translators: %s: number of sizes. */ _n( '%s size', '%s sizes', count( $sum['lines'] ), 'sm-marketplace' ), sm_number( count( $sum['lines'] ) ) ) ); ?></small>
					<?php else : ?>—<?php endif; ?>
				</dd></div>
			<?php endif; ?>
			<?php if ( 'building' === $cat['kind'] ) : ?>
				<div><dt><?php esc_html_e( 'Total area', 'sm-marketplace' ); ?></dt><dd><?php echo $sum && $sum['quantity'] > 0 ? sm_tech( sm_number( $sum['quantity'], fmod( (float) $sum['quantity'], 1 ) ? 1 : 0 ) . ' m²' ) : '—'; // phpcs:ignore WordPress.Security.EscapeOutput ?></dd></div>
			<?php endif; ?>
			<div class="is-key"><dt><?php echo esc_html( 'lot' === ( $sum['price_unit'] ?? '' ) ? ( 'building' === $cat['kind'] ? __( 'Asking price', 'sm-marketplace' ) : __( 'Price of the lot', 'sm-marketplace' ) ) : __( 'Total price', 'sm-marketplace' ) ); ?></dt><dd><?php echo $sum && $sum['total_price'] > 0 ? esc_html( sm_money( $sum['total_price'] ) ) : '—'; ?></dd></div>
			<?php if ( $sum && 'both' === ( $sum['sale_mode'] ?? '' ) && $sum['lot_price'] > 0 ) : ?>
				<div class="is-key"><dt><?php esc_html_e( 'Price of the whole lot', 'sm-marketplace' ); ?></dt><dd><?php echo esc_html( sm_money( $sum['lot_price'] ) ); ?><?php if ( $sum['lot_per_kg'] > 0 ) : ?> <small><?php echo sm_per_unit( $sum['lot_per_kg'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></small><?php endif; ?></dd></div>
			<?php endif; ?>
			<?php if ( $sum && $sum['price_per_kg'] > 0 && 'kg' !== $sum['price_unit'] ) : ?>
				<div><dt><?php esc_html_e( 'Equivalent price per kg', 'sm-marketplace' ); ?></dt><dd><?php echo sm_per_unit( $sum['price_per_kg'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd></div>
			<?php endif; ?>
			<?php if ( 'building' === $cat['kind'] && $sum && $sum['price_per_m2'] > 0 ) : ?>
				<div><dt><?php esc_html_e( 'Price per m²', 'sm-marketplace' ); ?></dt><dd><?php echo sm_per_unit( $sum['price_per_m2'], 'm²' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd></div>
			<?php endif; ?>
		</dl>
		<?php if ( 'building' === $cat['kind'] ) : ?>
			<p class="sm-help"><?php esc_html_e( 'Price per kg and per m² are calculated by the platform from your price, the area and the steel weight.', 'sm-marketplace' ); ?></p>
		<?php elseif ( 'weight' === $cat['kind'] ) : ?>
			<p class="sm-help"><?php esc_html_e( 'The weight of this lot is given by you: say whether it was weighed or estimated. Buyers see the difference.', 'sm-marketplace' ); ?></p>
		<?php else : ?>
			<p class="sm-help"><?php esc_html_e( 'Weight and totals are calculated by the platform from the European tables; you can still give the weighed weight.', 'sm-marketplace' ); ?></p>
		<?php endif; ?>
		<?php if ( 'building' === $cat['kind'] && '' !== $v( 'condition' ) && 'new' !== $v( 'condition' ) ) : ?>
			<p class="smp-advice-note"><?php sm_icon( 'hard-hat' ); ?><span><?php esc_html_e( 'Buyers will see a notice recommending a check by a qualified engineer before reassembling a used building.', 'sm-marketplace' ); ?></span></p>
		<?php elseif ( 'building' !== $cat['kind'] && ! empty( $mk['conditions'] ) && 'scrap' !== $v( 'condition' ) ) : ?>
			<p class="smp-advice-note"><?php sm_icon( 'hard-hat' ); ?><span><?php esc_html_e( 'Buyers will see a notice recommending a check by a qualified engineer before any structural reuse.', 'sm-marketplace' ); ?></span></p>
		<?php endif; ?>
	</div>
	<noscript><button class="sm-btn sm-btn--ghost" type="submit" name="intent" value="refresh"><?php esc_html_e( 'Update', 'sm-marketplace' ); ?></button></noscript>
</div>

<?php
/**
 * Inventory rows of the product form: one row per size and length (and element
 * type when the market has them, e.g. buildings: columns, rafters, purlins…).
 * Each filled row shows the weight computed by the server; one empty row is
 * always offered at the end.
 *
 * @var array $args { state: array, category: array, summary: array|null, field_error: callable,
 *                    elements?: array, legend: string, max?: int }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$st          = (array) $args['state'];
$cat         = (array) $args['category'];
$sum         = $args['summary'] ?? null;
$field_error = $args['field_error'];
$elements    = (array) ( $args['elements'] ?? array() );
$legend      = (string) $args['legend'];
$max         = (int) ( $args['max'] ?? 30 );
?>
<?php
$all_fam = array();
foreach ( (array) sm_theme_data( 'weight_families' ) as $f ) {
	$all_fam[ $f['code'] ] = $f;
}
$families = array_values( array_filter( array_map( static fn( $c ) => $all_fam[ $c ] ?? null, $cat['families'] ) ) );
$rows     = array_values( array_filter( (array) ( $st['lines'] ?? array() ), static fn( $r ) => is_array( $r ) && implode( '', array_map( 'strval', array_intersect_key( $r, array_flip( array( 'section', 'qty', 'length' ) ) ) ) ) !== '' ) );
$rows     = array_slice( $rows, 0, max( 1, $max - 1 ) );
$raw_rows = array_values( (array) ( $st['lines'] ?? array() ) );
$tail     = $raw_rows ? end( $raw_rows ) : null;
$last_fam = $rows ? (string) ( end( $rows )['family'] ?? '' ) : '';
if ( is_array( $tail ) && ! in_array( $tail, $rows, true ) && ! empty( $tail['family'] ) ) {
	$last_fam = (string) $tail['family']; // Type chosen on the empty row is kept.
}
$last_el  = is_array( $tail ) && ! in_array( $tail, $rows, true ) ? (string) ( $tail['element'] ?? '' ) : '';
$rows[]   = array( 'element' => $last_el, 'family' => $last_fam ? $last_fam : ( $families[0]['code'] ?? '' ), 'section' => '', 'qty' => '', 'length' => '' );
$results  = (array) ( $sum['line_rows'] ?? array() );
$sections = array();
?>
<fieldset class="sm-field smp-lines">
	<legend class="sm-field__legend"><?php echo esc_html( $legend ); ?></legend>
	<p class="sm-help"><?php esc_html_e( 'One row per size and length. A new empty row appears when you fill the last one; clear a row to remove it.', 'sm-marketplace' ); ?></p>
	<ol class="smp-lines__list" role="list">
		<?php foreach ( $rows as $i => $row ) : ?>
			<?php
			$fam = strtoupper( (string) ( $row['family'] ?? '' ) );
			if ( ! isset( $all_fam[ $fam ] ) || ! in_array( $fam, $cat['families'], true ) ) {
				$fam = $families[0]['code'] ?? '';
			}
			if ( $fam && ! isset( $sections[ $fam ] ) ) {
				$fd               = sm_theme_data( 'weight_family', array( 'code' => $fam ) );
				$sections[ $fam ] = (array) ( $fd['sections'] ?? array() );
			}
			$sec = (string) ( $row['section'] ?? '' );
			$res = $results[ $i ] ?? null;
			$n   = 'lines[' . $i . ']';
			?>
			<li class="smp-line<?php echo $res ? ' is-done' : ''; ?><?php echo $elements ? ' smp-line--el' : ''; ?>" data-smp-line>
				<span class="smp-line__n" aria-hidden="true"><?php echo esc_html( $i + 1 ); ?></span>
				<div class="smp-line__fields">
					<?php if ( $elements ) : ?>
					<div class="smp-line__f smp-line__f--el">
						<label for="smp-l<?php echo esc_attr( $i ); ?>-e"><?php esc_html_e( 'Element', 'sm-marketplace' ); ?></label>
						<select id="smp-l<?php echo esc_attr( $i ); ?>-e" name="<?php echo esc_attr( $n ); ?>[element]">
							<option value=""><?php esc_html_e( 'Choose…', 'sm-marketplace' ); ?></option>
							<?php foreach ( $elements as $code => $label ) : ?><option value="<?php echo esc_attr( $code ); ?>" <?php selected( (string) ( $row['element'] ?? '' ), $code ); ?>><?php echo esc_html( sm_t( $label ) ); ?></option><?php endforeach; ?>
						</select>
					</div>
				<?php endif; ?>
				<div class="smp-line__f smp-line__f--fam">
						<label for="smp-l<?php echo esc_attr( $i ); ?>-f"><?php esc_html_e( 'Type', 'sm-marketplace' ); ?></label>
						<select id="smp-l<?php echo esc_attr( $i ); ?>-f" name="<?php echo esc_attr( $n ); ?>[family]" dir="ltr">
							<?php foreach ( $families as $f ) : ?><option value="<?php echo esc_attr( $f['code'] ); ?>" <?php selected( $fam, $f['code'] ); ?>><?php echo esc_html( $f['code'] ); ?></option><?php endforeach; ?>
						</select>
					</div>
					<div class="smp-line__f smp-line__f--size">
						<label for="smp-l<?php echo esc_attr( $i ); ?>-s"><?php esc_html_e( 'Size', 'sm-marketplace' ); ?></label>
						<select id="smp-l<?php echo esc_attr( $i ); ?>-s" name="<?php echo esc_attr( $n ); ?>[section]" dir="ltr">
							<option value=""><?php esc_html_e( 'Choose…', 'sm-marketplace' ); ?></option>
							<?php foreach ( $sections[ $fam ] ?? array() as $o ) : ?><option value="<?php echo esc_attr( $o['id'] ); ?>" <?php selected( $sec, (string) $o['id'] ); ?>><?php echo esc_html( $o['designation'] ); ?></option><?php endforeach; ?>
						</select>
					</div>
					<div class="smp-line__f smp-line__f--qty">
						<label for="smp-l<?php echo esc_attr( $i ); ?>-q"><?php esc_html_e( 'No. of pieces', 'sm-marketplace' ); ?></label>
						<input id="smp-l<?php echo esc_attr( $i ); ?>-q" name="<?php echo esc_attr( $n ); ?>[qty]" inputmode="numeric" dir="ltr" value="<?php echo esc_attr( (string) ( $row['qty'] ?? '' ) ); ?>">
					</div>
					<div class="smp-line__f smp-line__f--len">
						<label for="smp-l<?php echo esc_attr( $i ); ?>-l"><?php esc_html_e( 'Length', 'sm-marketplace' ); ?> <small>(m)</small></label>
						<input id="smp-l<?php echo esc_attr( $i ); ?>-l" name="<?php echo esc_attr( $n ); ?>[length]" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( (string) ( $row['length'] ?? '' ) ); ?>">
					</div>
				</div>
				<p class="smp-line__result" aria-live="polite">
					<?php if ( $res ) : ?>
						<span class="smp-line__kgm"><?php echo sm_tech( sm_number( $res['kg_m'], 2 ) . ' kg/m' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<strong><?php echo sm_kg( $res['total'], 1 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></strong>
					<?php endif; ?>
				</p>
				<?php if ( $i < count( $rows ) - 1 ) : ?>
					<button type="button" class="sm-icon-btn smp-line__remove" data-smp-line-remove><?php sm_icon( 'trash', '', __( 'Remove this row', 'sm-marketplace' ) ); ?></button>
				<?php endif; ?>
				<?php $field_error( 'lines_' . $i ); ?>
			</li>
		<?php endforeach; ?>
	</ol>
	<?php sm_theme_error( 'lines' ); ?>
</fieldset>

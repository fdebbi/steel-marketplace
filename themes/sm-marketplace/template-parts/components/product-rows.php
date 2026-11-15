<?php
/**
 * Reuse (0.37.0): one size in several lengths (plates: several sizes) with their
 * number, or the total weight of loose pieces. Each row is weighed by the server
 * (sm-products Spec::rows()); "+ Add a length" adds an empty row in the browser.
 *
 * @var array $args { state, plate, summary, calc, field_error, errors }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$st          = (array) $args['state'];
$plate       = ! empty( $args['plate'] );
$sum         = is_array( $args['summary'] ?? null ) ? $args['summary'] : null;
$err         = (array) ( $args['errors'] ?? array() );
$field_error = $args['field_error'];
$v           = static fn( $k, $d = '' ) => isset( $st[ $k ] ) && ! is_array( $st[ $k ] ) ? (string) $st[ $k ] : $d;
$mode        = 'weight' === $v( 'qty_mode' ) ? 'weight' : 'rows';
$rows        = array_values( array_map( static fn( $r ) => is_array( $r ) ? $r : array(), (array) ( $st['rows'] ?? array() ) ) );
while ( $rows && '' === trim( implode( '', array_map( 'strval', end( $rows ) ) ) ) ) {
	array_pop( $rows ); // Empty rows at the end; the ones in between keep their place (and their result).
}
$show        = max( count( $rows ) + 1, 3 );
$results     = (array) ( $sum['line_rows'] ?? array() );
$cols        = $plate ? array( 'length' => __( 'Length (mm)', 'sm-marketplace' ), 'width' => __( 'Width (mm)', 'sm-marketplace' ), 'qty' => __( 'Number (pieces)', 'sm-marketplace' ) ) : array( 'length' => __( 'Length (m)', 'sm-marketplace' ), 'qty' => __( 'Number (pieces)', 'sm-marketplace' ) );
$total_n     = 0;
?>
<fieldset class="sm-field smr-qty">
	<legend class="sm-field__legend"><?php esc_html_e( 'Quantity for sale', 'sm-marketplace' ); ?></legend>
	<div class="smw-modes">
		<label class="smw-mode"><input type="radio" name="qty_mode" value="rows" <?php checked( $mode, 'rows' ); ?>><span><?php echo esc_html( $plate ? __( 'By sizes', 'sm-marketplace' ) : __( 'By lengths', 'sm-marketplace' ) ); ?></span></label>
		<label class="smw-mode"><input type="radio" name="qty_mode" value="weight" <?php checked( $mode, 'weight' ); ?>><span><?php esc_html_e( 'By total weight', 'sm-marketplace' ); ?></span></label>
	</div>
	<?php if ( 'weight' === $mode ) : ?>
		<p class="sm-help"><?php esc_html_e( 'For loose pieces of various lengths: give the total weight.', 'sm-marketplace' ); ?></p>
		<label for="smp-total"><?php esc_html_e( 'Total weight', 'sm-marketplace' ); ?> <small>(kg)</small></label>
		<input id="smp-total" name="total_kg" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( $v( 'total_kg' ) ); ?>"<?php sm_theme_aria( 'total_kg' ); ?>>
		<?php $field_error( 'total_kg' ); ?>
	<?php else : ?>
		<p class="sm-help"><?php echo esc_html( $plate ? __( 'One row per size of piece.', 'sm-marketplace' ) : __( 'One row per length.', 'sm-marketplace' ) ); ?></p>
		<div class="smr-table" data-smr>
			<div class="smr-row smr-row--head" aria-hidden="true">
				<?php foreach ( $cols as $label ) : ?><span><?php echo esc_html( $label ); ?></span><?php endforeach; ?>
				<span class="smr-num"><?php esc_html_e( 'Weight', 'sm-marketplace' ); ?></span><span></span>
			</div>
			<?php for ( $i = 0; $i < $show; $i++ ) : ?>
				<?php
				$row = (array) ( $rows[ $i ] ?? array() );
				$res = $results[ $i ] ?? null;
				if ( $res ) {
					$total_n += (int) ( $row['qty'] ?? 0 );
				}
				?>
				<div class="smr-row" data-smr-row>
					<?php foreach ( $cols as $k => $label ) : ?>
						<label class="smr-cell"><span class="smr-cell__label"><?php echo esc_html( $label ); ?></span><input name="rows[<?php echo (int) $i; ?>][<?php echo esc_attr( $k ); ?>]" inputmode="<?php echo 'qty' === $k ? 'numeric' : 'decimal'; ?>" dir="ltr" value="<?php echo esc_attr( (string) ( $row[ $k ] ?? '' ) ); ?>" placeholder="<?php echo esc_attr( 'qty' === $k ? '0' : ( $plate ? ( 'width' === $k ? '1500' : '3000' ) : '12' ) ); ?>"></label>
					<?php endforeach; ?>
					<span class="smr-num smr-weight"><?php echo $res ? sm_tech( sm_number( $res['total'] ) . "\u{00A0}kg" ) : '—'; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<button type="button" class="smr-remove" data-smr-remove aria-label="<?php esc_attr_e( 'Remove this row', 'sm-marketplace' ); ?>">×</button>
					<?php if ( ! empty( $err[ 'rows_' . $i ] ) && '' !== implode( '', array_map( 'strval', $row ) ) ) : ?>
						<p class="sm-field-error smr-error"><?php echo esc_html( $err[ 'rows_' . $i ] ); ?></p>
					<?php endif; ?>
					<?php sm_theme_error( 'rows_' . $i ); ?>
				</div>
			<?php endfor; ?>
			<div class="smr-row smr-row--total">
				<span><?php esc_html_e( 'Total', 'sm-marketplace' ); ?></span>
				<?php if ( $plate ) : ?><span></span><?php endif; ?>
				<span><?php echo $sum && ! empty( $sum['pieces'] ) ? esc_html( sprintf( /* translators: %s: number of pieces. */ ( $plate ? _n( '%s piece', '%s pieces', (int) $sum['pieces'], 'sm-marketplace' ) : _n( '%s bar', '%s bars', (int) $sum['pieces'], 'sm-marketplace' ) ), sm_number( $sum['pieces'] ) ) ) : '—'; ?></span>
				<span class="smr-num"><strong><?php echo $sum && $sum['total_weight'] > 0 ? sm_tech( sm_number( $sum['total_weight'] ) . "\u{00A0}kg" ) : '—'; // phpcs:ignore WordPress.Security.EscapeOutput ?></strong></span><span></span>
			</div>
		</div>
		<button type="button" class="smr-add" data-smr-add><?php sm_icon( 'plus' ); ?><span><?php echo esc_html( $plate ? __( 'Add a size', 'sm-marketplace' ) : __( 'Add a length', 'sm-marketplace' ) ); ?></span></button>
		<?php sm_theme_error( 'rows' ); ?>
	<?php endif; ?>
</fieldset>
<?php $source = 'actual' === $v( 'weight_source' ) ? 'actual' : 'theoretical'; ?>
<fieldset class="sm-field">
	<legend class="sm-field__legend"><?php esc_html_e( 'Weight shown to buyers', 'sm-marketplace' ); ?></legend>
	<div class="sm-segment">
		<label><input type="radio" name="weight_source" value="theoretical" <?php checked( $source, 'theoretical' ); ?>><span><?php sm_icon( 'ruler' ); ?><?php echo esc_html( 'weight' === $mode ? __( 'Estimated', 'sm-marketplace' ) : __( 'Theoretical (calculated)', 'sm-marketplace' ) ); ?></span></label>
		<label><input type="radio" name="weight_source" value="actual" <?php checked( $source, 'actual' ); ?>><span><?php sm_icon( 'scale' ); ?><?php esc_html_e( 'Actual (weighed)', 'sm-marketplace' ); ?></span></label>
	</div>
	<?php if ( 'actual' === $source && 'rows' === $mode ) : ?>
		<label for="smp-actual"><?php esc_html_e( 'Measured weight (kg)', 'sm-marketplace' ); ?></label>
		<input id="smp-actual" name="actual_weight" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( $v( 'actual_weight' ) ); ?>">
		<?php $field_error( 'actual_weight' ); ?>
	<?php endif; ?>
</fieldset>

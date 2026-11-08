<?php
/**
 * Quantity part of the request form: profile rows weighed by the server, or a
 * free description with quantity and unit. Refreshed in place by
 * assets/js/product-form.js (?smq_partial=1).
 *
 * @var array $args { state: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$st  = (array) ( $args['state'] ?? array() );
$set = (array) sm_theme_data( 'rfq_settings' );
$pv  = (array) sm_theme_data( 'rfq_preview', $st );
$sum = $pv['summary'] ?? null;
$err = (array) ( $pv['errors'] ?? array() );
$v   = static fn( $k, $d = '' ) => isset( $st[ $k ] ) && ! is_array( $st[ $k ] ) ? (string) $st[ $k ] : $d;
$field_error = static function ( $key ) use ( $err ) {
	$msg = sm_theme_field_error( $key );
	if ( ! $msg && isset( $err[ $key ] ) && 0 === strpos( $key, 'lines_' ) ) {
		$msg = $err[ $key ];
	}
	if ( $msg ) {
		printf( '<p class="sm-field-error" id="err-%s">%s</p>', esc_attr( $key ), esc_html( $msg ) );
	}
};
$has_rows = ! empty( $sum['lines'] );
$cat      = array( 'families' => (array) ( $set['families'] ?? array() ), 'max_lines' => 30 );
?>
<div class="smp-tech smq-tech" data-smp-tech>
	<?php sm_component( 'product-lines', array( 'state' => $st, 'category' => $cat, 'summary' => $sum, 'field_error' => $field_error, 'legend' => __( 'Profiles needed (optional)', 'sm-marketplace' ) ) ); ?>
	<?php if ( $has_rows ) : ?>
		<div class="smq-total" aria-live="polite">
			<span><?php echo esc_html( sprintf( /* translators: %s: number of pieces. */ __( '%s pieces', 'sm-marketplace' ), sm_number( $sum['quantity'] ) ) ); ?></span>
			<strong><?php echo sm_kg( $sum['weight_kg'], 1 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></strong>
			<small><?php esc_html_e( 'Theoretical weight, European tables', 'sm-marketplace' ); ?></small>
		</div>
	<?php else : ?>
		<fieldset class="sm-field smq-free">
			<legend class="sm-field__legend"><?php esc_html_e( 'Or describe it freely', 'sm-marketplace' ); ?></legend>
			<div class="sm-field-grid">
				<div class="sm-field">
					<label for="smq-desig"><?php esc_html_e( 'Product or structure', 'sm-marketplace' ); ?></label>
					<input id="smq-desig" name="designation" dir="auto" maxlength="120" value="<?php echo esc_attr( $v( 'designation' ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. Storage hangar, sandwich panels 40 mm', 'sm-marketplace' ); ?>">
				</div>
				<div class="sm-field">
					<label for="smq-dims"><?php esc_html_e( 'Dimensions', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label>
					<input id="smq-dims" name="dimensions" dir="auto" maxlength="190" value="<?php echo esc_attr( $v( 'dimensions' ) ); ?>" placeholder="25 × 15 × 7 m">
				</div>
			</div>
			<div class="sm-field sm-field--row sm-field--row3">
				<div>
					<label for="smq-qty"><?php esc_html_e( 'Quantity', 'sm-marketplace' ); ?></label>
					<input id="smq-qty" name="quantity" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( $v( 'quantity' ) ); ?>"<?php sm_theme_aria( 'quantity' ); ?>>
					<?php sm_theme_error( 'quantity' ); ?>
				</div>
				<div>
					<label for="smq-unit"><?php esc_html_e( 'Unit', 'sm-marketplace' ); ?></label>
					<select id="smq-unit" name="unit"><?php foreach ( (array) ( $set['units'] ?? array() ) as $u ) : ?><option value="<?php echo esc_attr( $u ); ?>" <?php selected( $v( 'unit', 'pieces' ), $u ); ?>><?php echo esc_html( sm_rfq_unit_label( $u ) ); ?></option><?php endforeach; ?></select>
					<?php sm_theme_error( 'unit' ); ?>
				</div>
				<div>
					<label for="smq-w"><?php esc_html_e( 'Weight, if known', 'sm-marketplace' ); ?> <small>(kg)</small></label>
					<input id="smq-w" name="weight_kg" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( $v( 'weight_kg' ) ); ?>"<?php sm_theme_aria( 'weight_kg' ); ?>>
					<?php sm_theme_error( 'weight_kg' ); ?>
				</div>
			</div>
		</fieldset>
	<?php endif; ?>
	<noscript><button class="sm-btn sm-btn--ghost" type="submit" name="intent" value="refresh"><?php esc_html_e( 'Update', 'sm-marketplace' ); ?></button></noscript>
</div>

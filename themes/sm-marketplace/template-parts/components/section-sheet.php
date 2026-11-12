<?php
/**
 * Characteristics of a steel section, shown beside its drawing on a listing
 * without photo (0.21.0): exact dimensions, cross-section area and weight per
 * metre, from the standard tables of the weight engine (never typed by hand).
 *
 * @var array $args { sheet: { shape, designation, dims, area_mm2, mass_kg_m, mass_kg_m2, standard } }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$sheet = (array) ( $args['sheet'] ?? array() );
$dims  = (array) ( $sheet['dims'] ?? array() );
if ( ! $dims ) {
	return;
}
$angle  = 'angle' === ( $sheet['shape'] ?? '' );
$labels = array(
	'h'  => $angle ? __( 'Leg', 'sm-marketplace' ) : __( 'Height', 'sm-marketplace' ),
	'b'  => $angle ? __( 'Leg', 'sm-marketplace' ) : __( 'Width', 'sm-marketplace' ),
	'd'  => __( 'Outside diameter', 'sm-marketplace' ),
	'a'  => __( 'Side', 'sm-marketplace' ),
	'tw' => __( 'Web thickness', 'sm-marketplace' ),
	'tf' => __( 'Flange thickness', 'sm-marketplace' ),
	't'  => __( 'Thickness', 'sm-marketplace' ),
	'r'  => __( 'Root radius', 'sm-marketplace' ),
	'r1' => __( 'Root radius', 'sm-marketplace' ),
	'r2' => __( 'Toe radius', 'sm-marketplace' ),
);
$symbols = array( 'd' => 'D' );
?>
<div class="sm-sheet">
	<p class="sm-sheet__title"><?php esc_html_e( 'Section characteristics', 'sm-marketplace' ); ?></p>
	<p class="sm-sheet__name"><?php echo sm_tech( (string) $sheet['designation'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped by sm_tech(). ?></p>
	<dl class="sm-sheet__list">
		<?php foreach ( $dims as $key => $value ) : ?>
			<?php if ( ! isset( $labels[ $key ] ) || (float) $value <= 0 ) { continue; } ?>
			<div><dt><?php echo esc_html( $labels[ $key ] ); ?> <span class="sm-sheet__sym"><?php echo esc_html( $symbols[ $key ] ?? $key ); ?></span></dt><dd><?php echo sm_tech( sm_number( $value, floor( $value ) == $value ? 0 : 1 ) . "\u{00A0}mm" ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd></div>
		<?php endforeach; ?>
		<?php if ( ! empty( $sheet['area_mm2'] ) ) : ?>
			<div class="sm-sheet__sep"><dt><?php esc_html_e( 'Cross-section area', 'sm-marketplace' ); ?></dt><dd><?php echo sm_tech( sm_number( $sheet['area_mm2'] / 100, 2 ) . "\u{00A0}cm²" ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd></div>
		<?php endif; ?>
		<?php if ( ! empty( $sheet['mass_kg_m'] ) ) : ?>
			<div class="sm-sheet__key"><dt><?php esc_html_e( 'Weight per metre', 'sm-marketplace' ); ?></dt><dd><?php echo sm_tech( sm_number( $sheet['mass_kg_m'], 2 ) . "\u{00A0}kg/ml" ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd></div>
		<?php elseif ( ! empty( $sheet['mass_kg_m2'] ) ) : ?>
			<div class="sm-sheet__key"><dt><?php esc_html_e( 'Weight per square metre', 'sm-marketplace' ); ?></dt><dd><?php echo sm_tech( sm_number( $sheet['mass_kg_m2'], 2 ) . "\u{00A0}kg/m²" ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd></div>
		<?php endif; ?>
	</dl>
	<?php if ( ! empty( $sheet['standard'] ) ) : ?>
		<p class="sm-sheet__std"><?php echo esc_html( sprintf( /* translators: %s: standard, e.g. EN 10365. */ __( 'Standard values: %s', 'sm-marketplace' ), $sheet['standard'] ) ); ?></p>
	<?php endif; ?>
</div>

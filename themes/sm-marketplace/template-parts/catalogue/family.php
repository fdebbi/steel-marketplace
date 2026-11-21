<?php
/**
 * Catalogue: one family, table of its sizes (0.59.0).
 *
 * @var array $args { data: { code, name, standard, count, rows, dims_keys, density, url } }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$d     = $args['data'];
$name  = sm_t( $d['name'] );
$keys  = array_values( array_filter( (array) $d['dims_keys'], static fn( $k ) => ! in_array( $k, array( 'r', 'r1', 'r2' ), true ) ) );
$sym   = array( 'd' => 'D' );
$title = array(
	'h'  => __( 'Height', 'sm-marketplace' ),
	'b'  => __( 'Width', 'sm-marketplace' ),
	'd'  => __( 'Outside diameter', 'sm-marketplace' ),
	'a'  => __( 'Side', 'sm-marketplace' ),
	'tw' => __( 'Web thickness', 'sm-marketplace' ),
	'tf' => __( 'Flange thickness', 'sm-marketplace' ),
	't'  => __( 'Thickness', 'sm-marketplace' ),
);
?>
<section class="sm-pagehead">
	<div class="sm-container">
		<?php sm_breadcrumb( array( array( __( 'Catalogue of sections', 'sm-marketplace' ), sm_page_url( 'catalogue' ) ), array( $name, '' ) ) ); ?>
		<h1 class="sm-pagehead__title"><?php echo esc_html( sprintf( /* translators: %s: family of sections, e.g. IPE beams. */ __( '%s: weight and dimensions', 'sm-marketplace' ), $name ) ); ?></h1>
		<p class="sm-pagehead__intro"><?php echo esc_html( sprintf( /* translators: 1: number of sizes, 2: family, 3: first size, 4: last size. */ __( 'Weight per metre and dimensions of the %1$d sizes of %2$s, from %3$s to %4$s. Choose a size for its drawing, its calculator and the listings in progress.', 'sm-marketplace' ), $d['count'], $name, $d['first'], $d['last'] ) ); ?></p>
	</div>
</section>
<div class="sm-container sm-section smk">
	<div class="smk-tablewrap">
		<table class="smk-table">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Section', 'sm-marketplace' ); ?></th>
					<th scope="col" class="is-num">kg/m</th>
					<th scope="col" class="is-num"><?php esc_html_e( 'Bar of 12 m (kg)', 'sm-marketplace' ); ?></th>
					<?php foreach ( $keys as $k ) : ?>
						<th scope="col" class="is-num" title="<?php echo esc_attr( $title[ $k ] ?? $k ); ?>"><?php echo esc_html( ( $sym[ $k ] ?? $k ) . ' (mm)' ); ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $d['rows'] as $r ) : ?>
					<tr>
						<th scope="row"><a href="<?php echo esc_url( $r['url'] ); ?>"><?php echo sm_tech( $r['designation'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a></th>
						<td class="is-num is-key"><?php echo esc_html( sm_number( $r['kg_m'], 2 ) ); ?></td>
						<td class="is-num"><?php echo esc_html( sm_number( $r['kg_12'], 1 ) ); ?></td>
						<?php foreach ( $keys as $k ) : ?>
							<?php $v = (float) ( $r['dims'][ $k ] ?? 0 ); ?>
							<td class="is-num"><?php echo esc_html( $v > 0 ? sm_number( $v, fmod( $v, 1 ) ? 1 : 0 ) : '–' ); ?></td>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<p class="smk-note">
		<?php echo esc_html( sprintf( /* translators: %s: density of steel. */ __( 'Theoretical weight computed from the standard dimensions, steel at %s kg/m³. The real weight can vary with the rolling tolerances.', 'sm-marketplace' ), sm_number( $d['density'] ) ) ); ?>
		<?php if ( '' !== (string) $d['standard'] ) : ?>
			<?php echo esc_html( sprintf( /* translators: %s: standard, e.g. EN 10365. */ __( 'Standard values: %s', 'sm-marketplace' ), $d['standard'] ) ); ?>
		<?php endif; ?>
	</p>
	<p class="smk-more"><a class="sm-btn sm-btn--ghost" href="<?php echo esc_url( sm_page_url( 'catalogue' ) ); ?>"><?php esc_html_e( 'Other families of sections', 'sm-marketplace' ); ?></a></p>
</div>

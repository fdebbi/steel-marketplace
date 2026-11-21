<?php
/**
 * Catalogue: the families of sections (0.59.0).
 *
 * @var array $args { data: { groups, density } }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$d      = $args['data'];
$titles = array(
	'beams'    => __( 'Beams', 'sm-marketplace' ),
	'channels' => __( 'Channels', 'sm-marketplace' ),
	'angles'   => __( 'Angles', 'sm-marketplace' ),
	'tubes'    => __( 'Hollow sections', 'sm-marketplace' ),
	'bars'     => __( 'Bars', 'sm-marketplace' ),
);
?>
<section class="sm-pagehead">
	<div class="sm-container">
		<?php sm_breadcrumb( array( array( __( 'Catalogue of sections', 'sm-marketplace' ), '' ) ) ); ?>
		<h1 class="sm-pagehead__title"><?php esc_html_e( 'Catalogue of steel sections: weight and dimensions', 'sm-marketplace' ); ?></h1>
		<p class="sm-pagehead__intro"><?php esc_html_e( 'Weight per metre and dimensions of the standard sections: beams, channels, angles, tubes and bars. Choose a family, then a size for its drawing, its calculator and the listings in progress.', 'sm-marketplace' ); ?></p>
	</div>
</section>
<div class="sm-container sm-section smk">
	<?php foreach ( $d['groups'] as $group => $families ) : ?>
		<h2 class="smk-group"><?php echo esc_html( $titles[ $group ] ?? $group ); ?></h2>
		<div class="smk-families">
			<?php foreach ( $families as $f ) : ?>
				<a class="smk-family" href="<?php echo esc_url( $f['url'] ); ?>">
					<span class="smk-family__icon" aria-hidden="true"><?php echo $f['icon']; // phpcs:ignore WordPress.Security.EscapeOutput -- SVG built by the weight engine. ?></span>
					<span class="smk-family__body">
						<strong><?php echo esc_html( sm_t( $f['name'] ) ); ?></strong>
						<small><?php echo sm_tech( $f['first'] . ' – ' . $f['last'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?> · <?php echo esc_html( sprintf( /* translators: %d: number of sizes. */ _n( '%d size', '%d sizes', $f['count'], 'sm-marketplace' ), $f['count'] ) ); ?></small>
						<small><?php echo sm_tech( sm_number( $f['min'], 2 ) . ' – ' . sm_number( $f['max'], 2 ) . ' kg/m' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></small>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endforeach; ?>
	<p class="smk-note"><?php echo esc_html( sprintf( /* translators: %s: density of steel. */ __( 'Theoretical weight computed from the standard dimensions, steel at %s kg/m³. The real weight can vary with the rolling tolerances.', 'sm-marketplace' ), sm_number( $d['density'] ) ) ); ?> <a href="<?php echo esc_url( sm_page_url( 'weight' ) ); ?>"><?php esc_html_e( 'Plates and other sizes: weight calculator', 'sm-marketplace' ); ?></a></p>
</div>

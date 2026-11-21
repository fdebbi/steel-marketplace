<?php
/**
 * Catalogue: one size (0.59.0) — weight per metre, bars of 6 and 12 m, drawing with
 * its dimensions, a small calculator, the listings in progress (new steel and reuse)
 * and the neighbouring sizes. Values of the weight engine only.
 *
 * @var array $args { data: see SM\WeightEngine\CataloguePages::section() }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$d      = $args['data'];
$name   = (string) $d['designation'];
$family = sm_t( $d['family']['name'] );
$kgm    = (float) $d['kg_m'];
$angle  = 'angle' === $d['shape'];
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
$sym      = array( 'd' => 'D' );
$listings = sm_theme_data(
	'listings',
	array(
		'market'      => array( 'products', 'reuse' ),
		'family'      => $d['family']['code'],
		'designation' => $name,
		'per_page'    => 3,
		'silent'      => 1, // Not a search: not counted in the Business report.
	)
);
$items    = is_array( $listings ) ? (array) ( $listings['items'] ?? array() ) : array();
$total    = is_array( $listings ) ? (int) ( $listings['total'] ?? 0 ) : 0;
$see_all  = add_query_arg( array( 'family' => $d['family']['code'], 'designation' => $name ), sm_page_url( 'search' ) ); // All the markets (new steel and reuse).
$rfq      = sm_page_url( 'account', array( 'view' => 'rfq', 'new' => 1 ) );
$publish  = sm_page_url( 'account', array( 'view' => 'publish' ) );
$kg       = static fn( $v, $dec = 1 ) => sm_number( $v, $dec ) . "\u{00A0}kg";
wp_enqueue_script( 'sm-catalogue', SM_THEME_URI . '/assets/js/catalogue.js', array(), sm_theme_asset_version( 'assets/js/catalogue.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
?>
<section class="sm-pagehead">
	<div class="sm-container">
		<?php sm_breadcrumb( array( array( __( 'Catalogue of sections', 'sm-marketplace' ), sm_page_url( 'catalogue' ) ), array( $family, $d['family']['url'] ), array( $name, '' ) ) ); ?>
		<h1 class="sm-pagehead__title"><?php echo wp_kses( sprintf( /* translators: %s: family of sections, e.g. IPE beams. */ __( '%s: weight and dimensions', 'sm-marketplace' ), sm_tech( $name ) ), array( 'bdi' => array( 'class' => array(), 'dir' => array() ) ) ); ?></h1>
		<p class="sm-pagehead__intro">
			<?php
			echo wp_kses(
				sprintf(
					/* translators: 1: section, 2: weight per metre, 3: weight of a bar of 12 m. */
					__( '%1$s: <strong>%2$s per metre</strong>, that is %3$s for a bar of 12 m.', 'sm-marketplace' ),
					sm_tech( $name ),
					'<bdi>' . esc_html( sm_number( $kgm, 2 ) . "\u{00A0}kg" ) . '</bdi>',
					'<bdi>' . esc_html( $kg( $d['kg_12'] ) ) . '</bdi>'
				),
				array( 'strong' => array(), 'bdi' => array( 'class' => array(), 'dir' => array() ) )
			);
			?>
		</p>
	</div>
</section>
<div class="sm-container sm-section smk smk-size">
	<div class="smk-kpis">
		<div class="smk-kpi is-key"><small><?php esc_html_e( 'Weight per metre', 'sm-marketplace' ); ?></small><b><?php echo sm_tech( sm_number( $kgm, 2 ) . "\u{00A0}kg/m" ); // phpcs:ignore WordPress.Security.EscapeOutput ?></b></div>
		<div class="smk-kpi"><small><?php esc_html_e( 'Bar of 6 m', 'sm-marketplace' ); ?></small><b><?php echo sm_tech( $kg( $d['kg_6'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></b></div>
		<div class="smk-kpi"><small><?php esc_html_e( 'Bar of 12 m', 'sm-marketplace' ); ?></small><b><?php echo sm_tech( $kg( $d['kg_12'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></b></div>
		<div class="smk-kpi"><small><?php esc_html_e( 'Cross-section area', 'sm-marketplace' ); ?></small><b><?php echo sm_tech( sm_number( $d['area_mm2'] / 100, 2 ) . "\u{00A0}cm²" ); // phpcs:ignore WordPress.Security.EscapeOutput ?></b></div>
	</div>

	<div class="smk-grid">
		<div class="smk-card smk-drawing">
			<h2><?php esc_html_e( 'Drawing with dimensions', 'sm-marketplace' ); ?></h2>
			<?php echo $d['drawing']; // phpcs:ignore WordPress.Security.EscapeOutput -- SVG built by the weight engine. ?>
			<dl class="smk-dims">
				<?php foreach ( (array) $d['dims'] as $k => $v ) : ?>
					<?php if ( ! isset( $labels[ $k ] ) || (float) $v <= 0 ) { continue; } ?>
					<div><dt><?php echo esc_html( $labels[ $k ] . ' ' . ( $sym[ $k ] ?? $k ) ); ?></dt><dd><?php echo sm_tech( sm_number( $v, fmod( (float) $v, 1 ) ? 1 : 0 ) . "\u{00A0}mm" ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd></div>
				<?php endforeach; ?>
				<div><dt><?php esc_html_e( 'Cross-section area', 'sm-marketplace' ); ?></dt><dd><?php echo sm_tech( sm_number( $d['area_mm2'] / 100, 2 ) . "\u{00A0}cm²" ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd></div>
			</dl>
			<?php if ( '' !== (string) $d['family']['standard'] ) : ?>
				<p class="smk-std"><?php echo esc_html( sprintf( /* translators: %s: standard, e.g. EN 10365. */ __( 'Standard values: %s', 'sm-marketplace' ), $d['family']['standard'] ) ); ?></p>
			<?php endif; ?>
		</div>

		<div class="smk-side">
			<div class="smk-card">
				<h2><?php esc_html_e( 'Compute the weight', 'sm-marketplace' ); ?></h2>
				<form class="smk-calc" data-smk-calc data-kgm="<?php echo esc_attr( $kgm ); ?>" onsubmit="return false">
					<p><label for="smk-l"><?php esc_html_e( 'Length (m)', 'sm-marketplace' ); ?></label><input id="smk-l" name="l" type="number" min="0" step="0.01" value="12" inputmode="decimal"></p>
					<p><label for="smk-n"><?php esc_html_e( 'Number of bars', 'sm-marketplace' ); ?></label><input id="smk-n" name="n" type="number" min="0" step="1" value="1" inputmode="numeric"></p>
					<output class="smk-calc__out" for="smk-l smk-n" aria-live="polite"><small><?php esc_html_e( 'Total weight', 'sm-marketplace' ); ?></small><b data-smk-kg><?php echo esc_html( $kg( $d['kg_12'] ) ); ?></b></output>
				</form>
				<a class="sm-btn sm-btn--ghost sm-btn--sm" href="<?php echo esc_url( $d['calc_url'] ); ?>"><?php sm_icon( 'scale' ); ?><span><?php esc_html_e( 'Full calculator (cutting, several sections)', 'sm-marketplace' ); ?></span></a>
			</div>

			<div class="smk-card smk-offers">
				<h2><?php echo esc_html( sprintf( /* translators: %s: section, e.g. IPE 200. */ __( 'Listings of %s in progress', 'sm-marketplace' ), $name ) ); ?></h2>
				<?php if ( $items ) : ?>
					<div class="sm-grid sm-grid--grid">
						<?php foreach ( $items as $item ) : ?>
							<?php sm_component( 'card-listing', array( 'item' => $item ) ); ?>
						<?php endforeach; ?>
					</div>
					<p class="smk-actions">
						<a class="sm-btn sm-btn--primary-outline sm-btn--sm" href="<?php echo esc_url( $see_all ); ?>"><?php echo esc_html( sprintf( /* translators: %d: number of listings. */ _n( 'See the %d listing', 'See the %d listings', $total, 'sm-marketplace' ), $total ) ); ?></a>
						<a class="sm-btn sm-btn--ghost sm-btn--sm" href="<?php echo esc_url( $rfq ); ?>"><?php sm_icon( 'rfq' ); ?><span><?php echo esc_html( sprintf( /* translators: %s: section. */ __( 'Request a quote for %s', 'sm-marketplace' ), $name ) ); ?></span></a>
					</p>
				<?php else : ?>
					<div class="smk-empty">
						<p><strong><?php echo esc_html( sprintf( /* translators: %s: section. */ __( 'No listing of %s for the moment.', 'sm-marketplace' ), $name ) ); ?></strong><br><?php esc_html_e( 'Ask the sellers for a quote, or publish your stock.', 'sm-marketplace' ); ?></p>
						<p class="smk-actions">
							<a class="sm-btn sm-btn--accent sm-btn--sm" href="<?php echo esc_url( $rfq ); ?>"><?php sm_icon( 'rfq' ); ?><span><?php echo esc_html( sprintf( /* translators: %s: section. */ __( 'Request a quote for %s', 'sm-marketplace' ), $name ) ); ?></span></a>
							<a class="sm-btn sm-btn--ghost sm-btn--sm" href="<?php echo esc_url( $publish ); ?>"><?php sm_icon( 'plus' ); ?><span><?php esc_html_e( 'Publish a listing', 'sm-marketplace' ); ?></span></a>
						</p>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<nav class="smk-near" aria-label="<?php esc_attr_e( 'Neighbouring sizes', 'sm-marketplace' ); ?>">
		<?php if ( $d['prev'] ) : ?>
			<a class="smk-near__prev" href="<?php echo esc_url( $d['prev']['url'] ); ?>"><small><?php esc_html_e( 'Smaller', 'sm-marketplace' ); ?></small><span><?php echo sm_tech( $d['prev']['designation'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?> · <?php echo sm_tech( sm_number( $d['prev']['kg_m'], 2 ) . "\u{00A0}kg/m" ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span></a>
		<?php endif; ?>
		<a class="smk-near__all" href="<?php echo esc_url( $d['family']['url'] ); ?>"><small><?php echo esc_html( sprintf( /* translators: %d: number of sizes. */ _n( '%d size', '%d sizes', $d['family']['count'], 'sm-marketplace' ), $d['family']['count'] ) ); ?></small><span><?php echo esc_html( $family ); ?></span></a>
		<?php if ( $d['next'] ) : ?>
			<a class="smk-near__next" href="<?php echo esc_url( $d['next']['url'] ); ?>"><small><?php esc_html_e( 'Larger', 'sm-marketplace' ); ?></small><span><?php echo sm_tech( $d['next']['designation'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?> · <?php echo sm_tech( sm_number( $d['next']['kg_m'], 2 ) . "\u{00A0}kg/m" ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span></a>
		<?php endif; ?>
	</nav>

	<div class="smk-faq">
		<details open>
			<summary><?php echo esc_html( sprintf( /* translators: %s: section. */ __( 'How much does a bar of %s of 12 m weigh?', 'sm-marketplace' ), $name ) ); ?></summary>
			<p><?php echo esc_html( sprintf( /* translators: 1: weight of the bar, 2: weight per metre. */ __( 'About %1$s (%2$s kg/m × 12 m), theoretical weight from the standard dimensions.', 'sm-marketplace' ), $kg( $d['kg_12'] ), sm_number( $kgm, 2 ) ) ); ?></p>
		</details>
		<details>
			<summary><?php echo esc_html( sprintf( /* translators: %s: section. */ __( 'How many metres of %s in one tonne?', 'sm-marketplace' ), $name ) ); ?></summary>
			<p><?php echo esc_html( sprintf( /* translators: 1: metres, 2: weight per metre. */ __( 'About %1$s m (1 000 kg ÷ %2$s kg/m).', 'sm-marketplace' ), sm_number( $d['m_per_t'], 1 ), sm_number( $kgm, 2 ) ) ); ?></p>
		</details>
	</div>
	<p class="smk-note"><?php echo esc_html( sprintf( /* translators: %s: density of steel. */ __( 'Theoretical weight computed from the standard dimensions, steel at %s kg/m³. The real weight can vary with the rolling tolerances.', 'sm-marketplace' ), sm_number( $d['density'] ) ) ); ?></p>
</div>

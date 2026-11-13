<?php
/**
 * Types of a results page as framed tiles (0.26.0): a small drawing of the type, its
 * name and its number of listings online. No "All" tile: clicking the chosen tile again
 * (it shows a ×) goes back to every listing; "All types" is also in the filter panel.
 *
 * @var array $args { tabs: list of { slug, label, icon, count }, current: string, action: string }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$tabs    = array_values( array_filter( (array) $args['tabs'], static fn( $t ) => '' !== (string) $t['slug'] ) );
$current = (string) $args['current'];
if ( ! $tabs ) {
	return;
}
// Small drawings of steel shapes (presentation only); other types use the icon of their category.
$shapes = array(
	'beam'  => '<path d="M5 5h14M5 19h14M12 5v14" stroke-width="2.4"/>',
	'tube'  => '<rect x="5" y="5" width="9" height="9" rx="1" stroke-width="2"/><circle cx="16" cy="16" r="4.2" stroke-width="2"/>',
	'plate' => '<path d="M3 15l6-5h12l-6 5z" stroke-width="1.8"/><path d="M3 15v2l12 0 6-5v-2" stroke-width="1.8"/>',
	'bar'   => '<rect x="3" y="6" width="18" height="4" rx="1" stroke-width="1.8"/><circle cx="7" cy="17" r="2.6" stroke-width="1.8"/><rect x="13" y="14.4" width="5.2" height="5.2" rx=".6" stroke-width="1.8"/>',
	'roof'  => '<path d="M3 15c1.5-3 3-3 4.5 0s3 3 4.5 0 3-3 4.5 0 3 3 4.5 0" stroke-width="1.8"/><path d="M3 10c1.5-3 3-3 4.5 0s3 3 4.5 0 3-3 4.5 0 3 3 4.5 0" stroke-width="1.8"/>',
);
$by_slug = array(
	'profiles'         => 'beam',
	'reuse-offcuts'    => 'beam',
	'tubes'            => 'tube',
	'reuse-tubes'      => 'tube',
	'plates'           => 'plate',
	'reuse-plates'     => 'plate',
	'bars'             => 'bar',
	'roofing'          => 'roof',
);
?>
<nav class="sm-typetiles" aria-label="<?php esc_attr_e( 'Types', 'sm-marketplace' ); ?>">
	<ul>
		<?php foreach ( $tabs as $t ) : ?>
			<?php
			$is   = (string) $t['slug'] === $current;
			$href = sm_theme_tab_url( $args['action'], $is ? '' : $t['slug'] );
			$n    = (int) $t['count'];
			?>
			<li>
				<a class="sm-typetile<?php echo $is ? ' is-current' : ''; ?><?php echo 0 === $n && ! $is ? ' is-empty' : ''; ?>" href="<?php echo esc_url( $href ); ?>" <?php echo $is ? 'aria-current="page" title="' . esc_attr__( 'Show every type', 'sm-marketplace' ) . '"' : ''; ?>>
					<span class="sm-typetile__icon" aria-hidden="true">
						<?php if ( isset( $by_slug[ $t['slug'] ] ) ) : ?>
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" focusable="false"><?php echo $shapes[ $by_slug[ $t['slug'] ] ]; // phpcs:ignore WordPress.Security.EscapeOutput -- fixed markup above. ?></svg>
						<?php else : ?>
							<?php sm_icon( $t['icon'] ? $t['icon'] : 'layers' ); ?>
						<?php endif; ?>
					</span>
					<span class="sm-typetile__text">
						<strong><?php echo esc_html( $t['label'] ); ?></strong>
						<small>
							<?php
							/* translators: %s: number of listings. */
							echo esc_html( 0 === $n ? __( 'No listing yet', 'sm-marketplace' ) : sprintf( _n( '%s listing', '%s listings', $n, 'sm-marketplace' ), sm_number( $n ) ) );
							?>
						</small>
					</span>
					<?php if ( $is ) : ?>
						<span class="sm-typetile__x" aria-hidden="true"><?php sm_icon( 'close' ); ?></span><span class="screen-reader-text"><?php esc_html_e( 'Remove this type: show every listing', 'sm-marketplace' ); ?></span>
					<?php endif; ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>

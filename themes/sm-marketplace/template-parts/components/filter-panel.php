<?php
/**
 * Filter panel. Side column on desktop, slide-in panel on tablet, full-screen sheet on mobile.
 * Filters are plain GET parameters so results pages are shareable and cacheable.
 *
 * 0.25.0: the types of the market are the tabs at the top of the page (not repeated
 * here); the panel shows the filters of the chosen type (from sm-products, only values
 * used by listings online), then the filters common to every type.
 *
 * @var array $args { market: string, action: string, total: int, facets: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$market   = $args['market'];
$q        = 'sm_theme_query_arg';
$facets   = (array) ( $args['facets']['facets'] ?? array() );
$category = (string) ( $args['facets']['category'] ?? '' );
$spec     = sm_theme_query_spec();
$tubular  = in_array( $category, array( 'tubes', 'bars', 'reuse-tubes' ), true );
$labels   = array(
	'family'      => $tubular ? __( 'Shape', 'sm-marketplace' ) : __( 'Family', 'sm-marketplace' ),
	'designation' => $tubular ? __( 'Size', 'sm-marketplace' ) : __( 'Designation', 'sm-marketplace' ),
	'thickness'   => __( 'Thickness', 'sm-marketplace' ),
	'length_min'  => __( 'Min. length', 'sm-marketplace' ),
	'weight_min'  => __( 'Min. weight', 'sm-marketplace' ),
	'area_min'    => __( 'Min. area', 'sm-marketplace' ),
	'height_min'  => __( 'Min. eaves height', 'sm-marketplace' ),
	'condition'   => __( 'Condition', 'sm-marketplace' ),
	'variant'     => __( 'Assembled or dismantled', 'sm-marketplace' ),
);
$variants = array(
	'assembled'  => __( 'Assembled', 'sm-marketplace' ),
	'dismantled' => __( 'Dismantled', 'sm-marketplace' ),
);
$conds    = sm_condition_labels();
// Current value of a facet ("spec[roof_type]" reads from the spec array).
$current = static function ( $key ) use ( $q, $spec ) {
	return preg_match( '/^spec\[([a-z0-9_]+)\]$/', $key, $m ) ? ( $spec[ $m[1] ] ?? '' ) : $q( $key );
};
$fid = static fn( $key ) => 'f-' . trim( preg_replace( '/[^a-z0-9]+/', '-', $key ), '-' );
?>
<aside class="sm-filters" id="sm-filters" data-sm-filters aria-label="<?php esc_attr_e( 'Filters', 'sm-marketplace' ); ?>">
	<form class="sm-filters__form" method="get" action="<?php echo esc_url( $args['action'] ); ?>" data-sm-count="<?php echo esc_url( add_query_arg( array( 'action' => 'sm_count', 'base' => $market, 'sm_lang' => sm_theme_current_language() ), admin_url( 'admin-ajax.php' ) ) ); ?>">
		<?php if ( 'ar' === sm_theme_current_language() ) : ?><input type="hidden" name="lang" value="ar"><?php endif; ?>
		<?php if ( '' !== $category ) : ?><input type="hidden" name="category" value="<?php echo esc_attr( $category ); ?>"><?php endif; ?>
		<?php foreach ( array( 'sort', 'display' ) as $keep ) : ?>
			<?php if ( '' !== $q( $keep ) ) : ?><input type="hidden" name="<?php echo esc_attr( $keep ); ?>" value="<?php echo esc_attr( $q( $keep ) ); ?>"><?php endif; ?>
		<?php endforeach; ?>
		<div class="sm-filters__head">
			<p class="sm-filters__title"><?php sm_icon( 'filter' ); ?><?php esc_html_e( 'Filters', 'sm-marketplace' ); ?></p>
			<button class="sm-icon-btn sm-filters__close" type="button" data-sm-filters-close><?php sm_icon( 'close' ); ?><span class="screen-reader-text"><?php esc_html_e( 'Close filters', 'sm-marketplace' ); ?></span></button>
		</div>

		<div class="sm-filters__body">
			<div class="sm-field">
				<label for="f-q"><?php esc_html_e( 'Keyword', 'sm-marketplace' ); ?></label>
				<input id="f-q" type="search" name="q" value="<?php echo esc_attr( $q( 'q' ) ); ?>" placeholder="<?php echo esc_attr( 'buildings' === $market ? __( 'hangar, poultry house…', 'sm-marketplace' ) : 'IPE 200' ); ?>" dir="auto">
			</div>

			<?php if ( 'search' === $market ) : ?>
				<fieldset class="sm-field">
					<legend><?php esc_html_e( 'Market', 'sm-marketplace' ); ?></legend>
					<?php foreach ( array_slice( sm_market_labels(), 0, 3, true ) as $key => $label ) : ?>
						<label class="sm-check"><input type="radio" name="market" value="<?php echo esc_attr( $key ); ?>" <?php checked( $q( 'market' ), $key ); ?>><span><?php echo esc_html( $label ); ?></span></label>
					<?php endforeach; ?>
					<label class="sm-check"><input type="radio" name="market" value="" <?php checked( $q( 'market' ), '' ); ?>><span><?php esc_html_e( 'All markets', 'sm-marketplace' ); ?></span></label>
				</fieldset>
			<?php endif; ?>

			<?php
			// Filters of the chosen type.
			$numbers = array();
			foreach ( $facets as $f ) :
				$key   = (string) $f['key'];
				$label = ! empty( $f['custom'] ) ? (string) $f['label'] : ( $labels[ $f['label'] ] ?? $f['label'] );
				$cur   = (string) $current( $key );
				if ( 'number' === $f['type'] ) {
					$numbers[] = array( $f, $label );
					continue;
				}
				$opt_label = static function ( $o ) use ( $f, $variants, $conds ) {
					if ( 'variant' === $f['label'] ) {
						return $variants[ $o['value'] ] ?? $o['value'];
					}
					if ( 'condition' === $f['label'] ) {
						return $conds[ $o['value'] ] ?? $o['value'];
					}
					return (string) ( $o['label'] ?? $o['value'] );
				};
				?>
				<?php if ( 'chips' === $f['type'] ) : ?>
					<fieldset class="sm-field sm-fchips"<?php echo 'family' === $f['label'] ? ' data-sm-family' : ''; ?>>
						<legend><?php echo esc_html( $label ); ?></legend>
						<div class="sm-fchips__list">
							<label><input type="radio" name="<?php echo esc_attr( $key ); ?>" value="" <?php checked( $cur, '' ); ?>><span><?php esc_html_e( 'All', 'sm-marketplace' ); ?></span></label>
							<?php foreach ( $f['options'] as $o ) : ?>
								<label><input type="radio" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $o['value'] ); ?>" <?php checked( $cur, (string) $o['value'] ); ?>><span><?php echo esc_html( $opt_label( $o ) ); ?> <small dir="ltr"><?php echo esc_html( sm_number( $o['count'] ) ); ?></small></span></label>
							<?php endforeach; ?>
						</div>
					</fieldset>
				<?php else : ?>
					<div class="sm-field">
						<label for="<?php echo esc_attr( $fid( $key ) ); ?>"><?php echo esc_html( $label ); ?></label>
						<select id="<?php echo esc_attr( $fid( $key ) ); ?>" name="<?php echo esc_attr( $key ); ?>"<?php echo 'designation' === $key ? ' data-sm-designation' : ''; ?>>
							<option value=""><?php esc_html_e( 'All', 'sm-marketplace' ); ?></option>
							<?php foreach ( $f['options'] as $o ) : ?>
								<option value="<?php echo esc_attr( $o['value'] ); ?>" <?php selected( $cur, (string) $o['value'] ); ?>><?php echo esc_html( $opt_label( $o ) . ' (' . sm_number( $o['count'] ) . ')' ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>

			<?php if ( $numbers ) : ?>
				<div class="sm-field sm-field--row sm-fnumbers">
					<?php foreach ( $numbers as list( $f, $label ) ) : ?>
						<div>
							<label for="<?php echo esc_attr( $fid( $f['key'] ) ); ?>"><?php echo esc_html( $label . ' (' . $f['unit'] . ')' ); ?></label>
							<input id="<?php echo esc_attr( $fid( $f['key'] ) ); ?>" type="number" inputmode="decimal" min="0" step="<?php echo esc_attr( $f['step'] ); ?>" name="<?php echo esc_attr( $f['key'] ); ?>" value="<?php echo esc_attr( $q( $f['key'] ) ); ?>" dir="ltr">
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php // Filters common to every type. ?>
			<div class="sm-field">
				<label for="f-wilaya"><?php esc_html_e( 'Wilaya', 'sm-marketplace' ); ?></label>
				<select id="f-wilaya" name="wilaya">
					<option value=""><?php esc_html_e( 'All wilayas', 'sm-marketplace' ); ?></option>
					<?php foreach ( sm_theme_wilayas() as $code => $name ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $q( 'wilaya' ), (string) $code ); ?>><?php echo esc_html( $code . ' — ' . sm_t( $name ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<?php if ( 'buildings' !== $market ) : ?>
				<div class="sm-field sm-field--row">
					<div><label for="f-pmin"><?php esc_html_e( 'Min. DA/kg', 'sm-marketplace' ); ?></label><input id="f-pmin" type="number" inputmode="numeric" min="0" name="price_min" value="<?php echo esc_attr( $q( 'price_min' ) ); ?>" dir="ltr"></div>
					<div><label for="f-pmax"><?php esc_html_e( 'Max. DA/kg', 'sm-marketplace' ); ?></label><input id="f-pmax" type="number" inputmode="numeric" min="0" name="price_max" value="<?php echo esc_attr( $q( 'price_max' ) ); ?>" dir="ltr"></div>
				</div>
			<?php endif; ?>

			<fieldset class="sm-field sm-fchips">
				<legend><?php esc_html_e( 'Sale', 'sm-marketplace' ); ?></legend>
				<div class="sm-fchips__list">
					<?php foreach ( array( '' => __( 'All', 'sm-marketplace' ), 'detail' => __( 'By quantity', 'sm-marketplace' ), 'lot' => __( 'Whole lot', 'sm-marketplace' ) ) as $k => $l ) : ?>
						<label><input type="radio" name="sale" value="<?php echo esc_attr( $k ); ?>" <?php checked( $q( 'sale' ), $k ); ?>><span><?php echo esc_html( $l ); ?></span></label>
					<?php endforeach; ?>
				</div>
			</fieldset>

			<label class="sm-check sm-check--switch"><input type="checkbox" name="verified" value="1" <?php checked( $q( 'verified' ), '1' ); ?>><span><?php esc_html_e( 'Verified sellers only', 'sm-marketplace' ); ?></span></label>
		</div>

		<div class="sm-filters__foot">
			<a class="sm-btn sm-btn--ghost" href="<?php echo esc_url( '' !== $category ? add_query_arg( 'category', $category, $args['action'] ) : $args['action'] ); ?>"><?php esc_html_e( 'Reset', 'sm-marketplace' ); ?></a>
			<button class="sm-btn sm-btn--primary" type="submit" data-sm-count-label aria-live="polite">
				<?php
				/* translators: %s: number of results. */
				echo esc_html( sprintf( _n( 'Show %s result', 'Show %s results', (int) $args['total'], 'sm-marketplace' ), sm_number( $args['total'] ) ) );
				?>
			</button>
		</div>
	</form>
</aside>

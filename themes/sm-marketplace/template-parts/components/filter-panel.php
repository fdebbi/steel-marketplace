<?php
/**
 * Filter panel. Side column on desktop, slide-in panel on tablet, full-screen sheet on mobile.
 * Filters are plain GET parameters so results pages are shareable and cacheable.
 *
 * @var array $args { market: string, action: string, total: int }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$market     = $args['market'];
$categories = 'search' === $market ? array() : sm_theme_categories( $market );
$q          = 'sm_theme_query_arg';
?>
<aside class="sm-filters" id="sm-filters" data-sm-filters aria-label="<?php esc_attr_e( 'Filters', 'sm-marketplace' ); ?>">
	<form class="sm-filters__form" method="get" action="<?php echo esc_url( $args['action'] ); ?>">
		<?php if ( 'ar' === sm_theme_current_language() ) : ?><input type="hidden" name="lang" value="ar"><?php endif; ?>
		<div class="sm-filters__head">
			<p class="sm-filters__title"><?php sm_icon( 'filter' ); ?><?php esc_html_e( 'Filters', 'sm-marketplace' ); ?></p>
			<button class="sm-icon-btn sm-filters__close" type="button" data-sm-filters-close><?php sm_icon( 'close' ); ?><span class="screen-reader-text"><?php esc_html_e( 'Close filters', 'sm-marketplace' ); ?></span></button>
		</div>

		<div class="sm-filters__body">
			<div class="sm-field">
				<label for="f-q"><?php esc_html_e( 'Profile designation or keyword', 'sm-marketplace' ); ?></label>
				<input id="f-q" type="search" name="q" value="<?php echo esc_attr( $q( 'q' ) ); ?>" placeholder="IPE 200" dir="auto">
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

			<?php if ( $categories ) : ?>
				<fieldset class="sm-field">
					<legend><?php esc_html_e( 'Category', 'sm-marketplace' ); ?></legend>
					<label class="sm-check"><input type="radio" name="category" value="" <?php checked( $q( 'category' ), '' ); ?>><span><?php esc_html_e( 'All categories', 'sm-marketplace' ); ?></span></label>
					<?php foreach ( $categories as $slug => $label ) : ?>
						<label class="sm-check"><input type="radio" name="category" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $q( 'category' ), $slug ); ?>><span><?php echo esc_html( sm_t( $label ) ); ?></span></label>
					<?php endforeach; ?>
				</fieldset>
			<?php endif; ?>

			<?php if ( 'reuse' === $market ) : ?>
				<fieldset class="sm-field">
					<legend><?php esc_html_e( 'Condition', 'sm-marketplace' ); ?></legend>
					<label class="sm-check"><input type="radio" name="condition" value="" <?php checked( $q( 'condition' ), '' ); ?>><span><?php esc_html_e( 'Any condition', 'sm-marketplace' ); ?></span></label>
					<?php foreach ( sm_condition_labels() as $code => $label ) : ?>
						<label class="sm-check"><input type="radio" name="condition" value="<?php echo esc_attr( $code ); ?>" <?php checked( $q( 'condition' ), $code ); ?>><span><?php sm_component( 'condition-badge', array( 'condition' => $code ) ); ?></span></label>
					<?php endforeach; ?>
				</fieldset>
				<div class="sm-field sm-field--row">
					<div><label for="f-lmin"><?php esc_html_e( 'Min. length (m)', 'sm-marketplace' ); ?></label><input id="f-lmin" type="number" inputmode="decimal" step="0.1" min="0" name="length_min" value="<?php echo esc_attr( $q( 'length_min' ) ); ?>"></div>
					<div><label for="f-qmin"><?php esc_html_e( 'Min. pieces', 'sm-marketplace' ); ?></label><input id="f-qmin" type="number" inputmode="numeric" min="0" name="qty_min" value="<?php echo esc_attr( $q( 'qty_min' ) ); ?>"></div>
				</div>
			<?php endif; ?>

			<?php if ( 'buildings' === $market ) : ?>
				<fieldset class="sm-field">
					<legend><?php esc_html_e( 'State', 'sm-marketplace' ); ?></legend>
					<label class="sm-check"><input type="radio" name="mode" value="" <?php checked( $q( 'mode' ), '' ); ?>><span><?php esc_html_e( 'All', 'sm-marketplace' ); ?></span></label>
					<label class="sm-check"><input type="radio" name="mode" value="assembled" <?php checked( $q( 'mode' ), 'assembled' ); ?>><span><?php esc_html_e( 'Assembled', 'sm-marketplace' ); ?></span></label>
					<label class="sm-check"><input type="radio" name="mode" value="dismantled" <?php checked( $q( 'mode' ), 'dismantled' ); ?>><span><?php esc_html_e( 'Dismantled', 'sm-marketplace' ); ?></span></label>
				</fieldset>
				<div class="sm-field sm-field--row">
					<div><label for="f-amin"><?php esc_html_e( 'Min. area (m²)', 'sm-marketplace' ); ?></label><input id="f-amin" type="number" inputmode="numeric" min="0" name="area_min" value="<?php echo esc_attr( $q( 'area_min' ) ); ?>"></div>
					<div><label for="f-hmin"><?php esc_html_e( 'Min. height (m)', 'sm-marketplace' ); ?></label><input id="f-hmin" type="number" inputmode="decimal" min="0" step="0.5" name="height_min" value="<?php echo esc_attr( $q( 'height_min' ) ); ?>"></div>
				</div>
			<?php endif; ?>

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
					<div><label for="f-pmin"><?php esc_html_e( 'Min. DA/kg', 'sm-marketplace' ); ?></label><input id="f-pmin" type="number" inputmode="numeric" min="0" name="price_min" value="<?php echo esc_attr( $q( 'price_min' ) ); ?>"></div>
					<div><label for="f-pmax"><?php esc_html_e( 'Max. DA/kg', 'sm-marketplace' ); ?></label><input id="f-pmax" type="number" inputmode="numeric" min="0" name="price_max" value="<?php echo esc_attr( $q( 'price_max' ) ); ?>"></div>
				</div>
				<label class="sm-check sm-check--switch"><input type="checkbox" name="verified" value="1" <?php checked( $q( 'verified' ), '1' ); ?>><span><?php esc_html_e( 'Verified sellers only', 'sm-marketplace' ); ?></span></label>
			<?php endif; ?>
		</div>

		<div class="sm-filters__foot">
			<a class="sm-btn sm-btn--ghost" href="<?php echo esc_url( $args['action'] ); ?>"><?php esc_html_e( 'Reset', 'sm-marketplace' ); ?></a>
			<button class="sm-btn sm-btn--primary" type="submit">
				<?php
				/* translators: %s: number of results. */
				echo esc_html( sprintf( _n( 'Show %s result', 'Show %s results', (int) $args['total'], 'sm-marketplace' ), sm_number( $args['total'] ) ) );
				?>
			</button>
		</div>
	</form>
</aside>

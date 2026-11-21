<?php
/**
 * Shared marketplace results layout (products, reuse, buildings, search).
 *
 * @var array $args { market: string, title: string, intro: string, icon: string }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$market   = $args['market'];
$action   = sm_page_url( 'search' === $market ? 'search' : $market );
$page_num = max( 1, (int) sm_theme_query_arg( 'pg', 1 ) );
$query    = sm_theme_listing_query( $market, $page_num );
$result   = sm_theme_listings( $query );
$layout   = 'list' === sm_theme_query_arg( 'display' ) ? 'list' : 'grid';
$category = sm_theme_query_arg( 'category' );
$cats     = 'search' === $market ? array() : sm_theme_categories( $market );
// Types as tabs with their number of listings, and the filters of the chosen type (0.25.0).
$facets   = 'search' === $market ? array() : sm_theme_data( 'listing_facets', array( 'market' => $market, 'category' => $category, 'family' => sm_theme_query_arg( 'family' ) ) );
$tabs     = (array) ( $facets['tabs'] ?? array() );

/*
 * 0.54.0: « Explore the marketplace » — the page of every market shows the three
 * markets and the types that have listings (each opens its market page, already
 * filtered), and the panel offers the type of product grouped by market.
 */
$explore  = array();
$browsing = false;
if ( 'search' === $market ) {
	$explore  = sm_theme_explore();
	$browsing = '' === (string) sm_theme_query_arg( 'q' ) && '' === (string) sm_theme_query_arg( 'market' );
	if ( $browsing ) {
		$args['title'] = __( 'Explore the marketplace', 'sm-marketplace' );
		$args['intro'] = __( 'New steel, reuse and steel buildings from all over Algeria: choose a market or a type, or search directly.', 'sm-marketplace' );
	}
}

$crumbs = array( array( $args['title'], $category ? $action : '' ) );
if ( $category && isset( $cats[ $category ] ) ) {
	$crumbs[] = array( sm_t( $cats[ $category ] ), '' );
}
?>
<section class="sm-pagehead sm-pagehead--<?php echo esc_attr( $market ); ?>">
	<div class="sm-container">
		<?php sm_breadcrumb( $crumbs ); ?>
		<div class="sm-pagehead__row">
			<div>
				<h1 class="sm-pagehead__title"><?php echo esc_html( $category && isset( $cats[ $category ] ) ? sm_t( $cats[ $category ] ) : $args['title'] ); ?></h1>
				<p class="sm-pagehead__intro"><?php echo esc_html( $args['intro'] ); ?></p>
			</div>
			<?php if ( 'search' !== $market ) : ?>
				<a class="sm-btn sm-btn--accent" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'publish', 'market' => $market ) ) ); ?>"><?php sm_icon( 'plus' ); ?><?php esc_html_e( 'Publish here', 'sm-marketplace' ); ?></a>
			<?php endif; ?>
		</div>
		<?php if ( $explore && $browsing ) : // After a search by keyword, the results come first (the types stay in the panel). ?>
			<ul class="sm-xmarkets">
				<?php foreach ( $explore['markets'] as $key => $m ) : ?>
					<li>
						<a class="sm-xmarket sm-xmarket--<?php echo esc_attr( $key ); ?>" href="<?php echo esc_url( $m['url'] ); ?>">
							<span class="sm-xmarket__icon" aria-hidden="true"><?php sm_icon( $m['icon'] ); ?></span>
							<span class="sm-xmarket__text">
								<strong><?php echo esc_html( $m['label'] ); ?></strong>
								<small><?php echo esc_html( $m['text'] ); ?></small>
								<b><?php echo esc_html( 0 === $m['count'] ? __( 'No listing yet', 'sm-marketplace' ) : sprintf( /* translators: %s: number of listings. */ _n( '%s listing', '%s listings', $m['count'], 'sm-marketplace' ), sm_number( $m['count'] ) ) ); ?></b>
							</span>
							<span class="sm-xmarket__go"><?php esc_html_e( 'See the market', 'sm-marketplace' ); ?><?php sm_icon( 'arrow-end' ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if ( $explore['types'] ) : ?>
				<p class="sm-xmarkets__sub"><?php esc_html_e( 'Types with listings', 'sm-marketplace' ); ?></p>
				<?php sm_component( 'type-tiles', array( 'tabs' => $explore['types'], 'current' => '', 'action' => $action ) ); ?>
			<?php endif; ?>
		<?php elseif ( $tabs ) : ?>
			<?php sm_component( 'type-tiles', array( 'tabs' => $tabs, 'current' => (string) ( $facets['category'] ?? '' ), 'action' => $action ) ); ?>
		<?php elseif ( $cats ) : ?>
			<ul class="sm-catpills" aria-label="<?php esc_attr_e( 'Categories', 'sm-marketplace' ); ?>">
				<li><a href="<?php echo esc_url( $action ); ?>" <?php echo $category ? '' : 'aria-current="true"'; ?>><?php esc_html_e( 'All', 'sm-marketplace' ); ?></a></li>
				<?php foreach ( $cats as $slug => $label ) : ?>
					<li><a href="<?php echo esc_url( add_query_arg( 'category', $slug, $action ) ); ?>" <?php echo $slug === $category ? 'aria-current="true"' : ''; ?>><?php echo esc_html( sm_t( $label ) ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>

<div class="sm-container">
	<?php sm_component( 'ai-search' ); ?>
</div>

<div class="sm-container sm-results">
	<?php sm_component( 'filter-panel', array( 'market' => $market, 'action' => $action, 'total' => $result['total'], 'facets' => $facets, 'explore' => $explore ) ); ?>

	<div class="sm-results__main">
		<div class="sm-toolbar">
			<p class="sm-toolbar__count">
				<?php
				/* translators: %s: number of listings. */
				echo wp_kses_post( sprintf( _n( '<strong>%s</strong> listing', '<strong>%s</strong> listings', (int) $result['total'], 'sm-marketplace' ), sm_number( $result['total'] ) ) );
				?>
			</p>
			<div class="sm-toolbar__controls">
				<button class="sm-btn sm-btn--ghost sm-toolbar__filters" type="button" aria-controls="sm-filters" aria-expanded="false" data-sm-filters-open><?php sm_icon( 'filter' ); ?><?php esc_html_e( 'Filters', 'sm-marketplace' ); ?></button>
				<form method="get" class="sm-sort">
					<?php foreach ( array( 'category', 'condition', 'mode', 'wilaya', 'q', 'display', 'market', 'length_min', 'qty_min', 'family', 'designation', 'weight_min', 'area_min', 'height_min', 'price_min', 'price_max', 'sale', 'verified', 'lang' ) as $keep ) : ?>
						<?php if ( '' !== sm_theme_query_arg( $keep ) ) : ?><input type="hidden" name="<?php echo esc_attr( $keep ); ?>" value="<?php echo esc_attr( sm_theme_query_arg( $keep ) ); ?>"><?php endif; ?>
					<?php endforeach; ?>
					<?php foreach ( sm_theme_query_spec() as $sk => $sv ) : ?><input type="hidden" name="spec[<?php echo esc_attr( $sk ); ?>]" value="<?php echo esc_attr( $sv ); ?>"><?php endforeach; ?>
					<label class="screen-reader-text" for="sm-sort"><?php esc_html_e( 'Sort by', 'sm-marketplace' ); ?></label>
					<?php sm_icon( 'sort' ); ?>
					<select id="sm-sort" name="sort" data-sm-autosubmit>
						<option value="recent" <?php selected( $query['sort'], 'recent' ); ?>><?php esc_html_e( 'Most recent', 'sm-marketplace' ); ?></option>
						<option value="price_asc" <?php selected( $query['sort'], 'price_asc' ); ?>><?php esc_html_e( 'Price per kg: low to high', 'sm-marketplace' ); ?></option>
						<option value="price_desc" <?php selected( $query['sort'], 'price_desc' ); ?>><?php esc_html_e( 'Price per kg: high to low', 'sm-marketplace' ); ?></option>
					</select>
				</form>
				<div class="sm-viewtoggle" role="group" aria-label="<?php esc_attr_e( 'Display', 'sm-marketplace' ); ?>">
					<a href="<?php echo esc_url( remove_query_arg( 'display' ) ); ?>" <?php echo 'grid' === $layout ? 'aria-current="true"' : ''; ?>><?php sm_icon( 'grid', '', __( 'Grid', 'sm-marketplace' ) ); ?></a>
					<a href="<?php echo esc_url( add_query_arg( 'display', 'list' ) ); ?>" <?php echo 'list' === $layout ? 'aria-current="true"' : ''; ?>><?php sm_icon( 'list', '', __( 'List', 'sm-marketplace' ) ); ?></a>
				</div>
			</div>
		</div>

		<?php if ( $result['items'] ) : ?>
			<div class="sm-grid sm-grid--<?php echo esc_attr( $layout ); ?>">
				<?php foreach ( $result['items'] as $item ) : ?>
					<?php sm_component( 'card-listing', array( 'item' => $item, 'layout' => $layout ) ); ?>
				<?php endforeach; ?>
			</div>
			<?php if ( $result['pages'] > 1 ) : ?>
				<nav class="sm-pagination" aria-label="<?php esc_attr_e( 'Pagination', 'sm-marketplace' ); ?>">
					<?php for ( $i = 1; $i <= $result['pages']; $i++ ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'pg', $i ) ); ?>" <?php echo $i === $page_num ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $i ); ?></a>
					<?php endfor; ?>
				</nav>
			<?php endif; ?>
		<?php else : ?>
			<?php
			sm_component(
				'empty-state',
				array(
					'title'        => __( 'No listing matches these filters', 'sm-marketplace' ),
					'text'         => __( 'Widen your search, or publish a request for quotation so suppliers come to you.', 'sm-marketplace' ),
					'action_label' => __( 'Request a quotation', 'sm-marketplace' ),
					'action_url'   => sm_page_url( 'rfq' ),
				)
			);
			?>
		<?php endif; ?>
	</div>
</div>

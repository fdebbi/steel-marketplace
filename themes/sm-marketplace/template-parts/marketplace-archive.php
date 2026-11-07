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
$query    = array(
	'market'    => 'search' === $market ? sm_theme_query_arg( 'market' ) : $market,
	'category'  => sm_theme_query_arg( 'category' ),
	'condition' => sm_theme_query_arg( 'condition' ),
	'mode'      => sm_theme_query_arg( 'mode' ),
	'wilaya'    => sm_theme_query_arg( 'wilaya' ),
	'q'         => sm_theme_query_arg( 'q' ),
	'sort'      => sm_theme_query_arg( 'sort', 'recent' ),
	'per_page'  => 12,
	'page'      => $page_num,
);
if ( 'search' === $market && '' === $query['market'] ) {
	$query['market'] = array( 'products', 'reuse', 'buildings' );
}
$result   = sm_theme_listings( $query );
$layout   = 'list' === sm_theme_query_arg( 'display' ) ? 'list' : 'grid';
$category = sm_theme_query_arg( 'category' );
$cats     = 'search' === $market ? array() : sm_theme_categories( $market );

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
		<?php if ( $cats ) : ?>
			<ul class="sm-catpills" aria-label="<?php esc_attr_e( 'Categories', 'sm-marketplace' ); ?>">
				<li><a href="<?php echo esc_url( $action ); ?>" <?php echo $category ? '' : 'aria-current="true"'; ?>><?php esc_html_e( 'All', 'sm-marketplace' ); ?></a></li>
				<?php foreach ( $cats as $slug => $label ) : ?>
					<li><a href="<?php echo esc_url( add_query_arg( 'category', $slug, $action ) ); ?>" <?php echo $slug === $category ? 'aria-current="true"' : ''; ?>><?php echo esc_html( sm_t( $label ) ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>

<div class="sm-container sm-results">
	<?php sm_component( 'filter-panel', array( 'market' => $market, 'action' => $action, 'total' => $result['total'] ) ); ?>

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
					<?php foreach ( array( 'category', 'condition', 'mode', 'wilaya', 'q', 'display', 'market' ) as $keep ) : ?>
						<?php if ( '' !== sm_theme_query_arg( $keep ) ) : ?><input type="hidden" name="<?php echo esc_attr( $keep ); ?>" value="<?php echo esc_attr( sm_theme_query_arg( $keep ) ); ?>"><?php endif; ?>
					<?php endforeach; ?>
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

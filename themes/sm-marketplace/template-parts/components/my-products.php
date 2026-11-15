<?php
/**
 * "My listings" for sm-products: status filter, one row per product with its
 * state and the actions that state allows.
 *
 * @var array $args { data: array{items, counts, total, pages} }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$data    = $args['data'];
$counts  = (array) $data['counts'];
$current = sm_theme_query_arg( 'status' );
$market  = (string) ( $data['market'] ?? '' );
$by_mkt  = (array) ( $data['markets'] ?? array() );
$root    = sm_page_url( 'account', array( 'view' => 'listings' ) );
$base    = '' !== $market ? add_query_arg( 'market', $market, $root ) : $root;
// 0.38.0: one tab per market, then the states of that market.
$tabs    = array(
	''          => __( 'All', 'sm-marketplace' ),
	'products'  => __( 'Brand new', 'sm-marketplace' ),
	'reuse'     => __( 'Reuse', 'sm-marketplace' ),
	'buildings' => __( 'Buildings', 'sm-marketplace' ),
);
$labels  = array(
	'published' => __( 'Published', 'sm-marketplace' ),
	'pending'   => __( 'Waiting for review', 'sm-marketplace' ),
	'rejected'  => __( 'Changes requested', 'sm-marketplace' ),
	'draft'     => __( 'Drafts', 'sm-marketplace' ),
	'sold'      => __( 'Sold', 'sm-marketplace' ),
	'expired'   => __( 'Expired', 'sm-marketplace' ),
	'paused'    => __( 'Paused (plan)', 'sm-marketplace' ),
	'grouped'   => __( 'In a lot', 'sm-marketplace' ),
);
$one = array(
	'published' => __( 'Published', 'sm-marketplace' ),
	'pending'   => __( 'Waiting for review', 'sm-marketplace' ),
	'rejected'  => __( 'Changes requested', 'sm-marketplace' ),
	'draft'     => __( 'Draft', 'sm-marketplace' ),
	'sold'      => __( 'Sold', 'sm-marketplace' ),
	'expired'   => __( 'Expired', 'sm-marketplace' ),
	'paused'    => __( 'Paused (plan)', 'sm-marketplace' ),
	'grouped'   => __( 'In a lot', 'sm-marketplace' ),
);
// 0.37.0: published reuse listings can be grouped into one lot (not scrap, not a lot).
$groupable = static fn( $item ) => 'reuse' === $market && 'published' === $item['status'] && 'reuse' === ( $item['market'] ?? '' ) && ! isset( $item['group_count'] ) && 'reuse-scrap' !== ( $item['category'] ?? '' );
$n_group   = count( array_filter( $data['items'], $groupable ) );
// Paid options (sm-subscriptions): back to the top, "Urgent" badge, featured listing.
$sub     = sm_theme_data( 'subscription' );
$options = is_array( $sub ) && ! empty( $sub['can_order'] ) ? array_filter( (array) ( $sub['options'] ?? array() ), static fn( $o ) => $o['price'] > 0 ) : array();
$wilayas = sm_theme_wilayas();
$action  = static function ( $item, $do, $label, $style = 'ghost', $confirm = '' ) {
	sm_theme_form_open( 'product_action', array( 'class' => 'smp-act' ) );
	echo '<input type="hidden" name="product_id" value="' . esc_attr( $item['product_id'] ) . '"><input type="hidden" name="do" value="' . esc_attr( $do ) . '">';
	echo '<button type="submit" class="sm-btn sm-btn--' . esc_attr( $style ) . ' sm-btn--sm"' . ( $confirm ? ' data-sm-confirm="' . esc_attr( $confirm ) . '"' : '' ) . '>' . esc_html( $label ) . '</button></form>';
};
?>
<nav class="smp-tabs" aria-label="<?php esc_attr_e( 'Listings by market', 'sm-marketplace' ); ?>">
	<?php foreach ( $tabs as $k => $l ) : ?>
		<?php $n = '' === $k ? array_sum( $by_mkt ) : (int) ( $by_mkt[ $k ] ?? 0 ); ?>
		<a href="<?php echo esc_url( '' === $k ? $root : add_query_arg( 'market', $k, $root ) ); ?>"<?php echo $market === $k ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $l ); ?> <span class="sm-count"><?php echo esc_html( sm_number( $n ) ); ?></span></a>
	<?php endforeach; ?>
</nav>
<div class="sm-toolbar">
	<nav class="smp-filter" aria-label="<?php esc_attr_e( 'Filter by status', 'sm-marketplace' ); ?>">
		<a href="<?php echo esc_url( $base ); ?>"<?php echo '' === $current ? ' aria-current="page"' : ''; ?>><?php esc_html_e( 'All', 'sm-marketplace' ); ?> (<?php echo esc_html( array_sum( $counts ) ); ?>)</a>
		<?php foreach ( $labels as $k => $l ) : ?>
			<?php if ( empty( $counts[ $k ] ) && $current !== $k ) { continue; } ?>
			<a href="<?php echo esc_url( add_query_arg( 'status', $k, $base ) ); ?>"<?php echo $current === $k ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $l ); ?> (<?php echo esc_html( (int) ( $counts[ $k ] ?? 0 ) ); ?>)</a>
		<?php endforeach; ?>
	</nav>
	<a class="sm-btn sm-btn--accent" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'publish' ) ) ); ?>"><?php sm_icon( 'plus' ); ?><?php esc_html_e( 'Publish a listing', 'sm-marketplace' ); ?></a>
</div>

<?php if ( ! $data['items'] ) : ?>
	<?php sm_component( 'empty-state', array( 'icon' => 'package', 'title' => __( 'No product here yet', 'sm-marketplace' ), 'text' => __( 'Publish your first product: profiles, tubes, plates, roofing…', 'sm-marketplace' ), 'action_label' => __( 'Publish a listing', 'sm-marketplace' ), 'action_url' => sm_page_url( 'account', array( 'view' => 'publish' ) ) ) ); ?>
	<?php return; ?>
<?php endif; ?>

<?php if ( $n_group >= 2 ) : ?>
	<p class="sm-notice sm-notice--info smr-group-hint"><?php sm_icon( 'package' ); ?><span><?php esc_html_e( 'Reuse: tick several listings of the same wilaya to sell them together as one lot.', 'sm-marketplace' ); ?></span></p>
<?php endif; ?>
<ul class="smp-list" role="list">
	<?php foreach ( $data['items'] as $item ) : ?>
		<?php $edit = sm_page_url( 'account', array( 'view' => 'publish', 'edit' => $item['product_id'] ) ); ?>
		<li class="smp-row<?php echo $n_group >= 2 && $groupable( $item ) ? ' smp-row--pick' : ''; ?>">
			<?php if ( $n_group >= 2 && $groupable( $item ) ) : ?>
				<label class="smr-pick"><input type="checkbox" name="products[]" value="<?php echo esc_attr( $item['product_id'] ); ?>" form="sm-reuse-group" data-smr-pick data-price="<?php echo esc_attr( (string) ( $item['lot_price'] > 0 && 'detail' !== ( $item['sale_mode'] ?? '' ) ? $item['lot_price'] : $item['total_price'] ) ); ?>" data-kg="<?php echo esc_attr( (string) $item['weight_kg'] ); ?>" data-wilaya="<?php echo esc_attr( (string) $item['wilaya'] ); ?>"><span class="screen-reader-text"><?php echo esc_html( sprintf( /* translators: %s: listing title. */ __( 'Put "%s" in a lot', 'sm-marketplace' ), sm_t( $item['title'] ) ) ); ?></span></label>
			<?php endif; ?>
			<img class="smp-row__img" src="<?php echo esc_url( sm_image_url( $item['image'] ) ); ?>" alt="" width="96" height="96" loading="lazy">
			<div>
				<p class="smp-row__title"><a href="<?php echo esc_url( sm_page_url( 'listing', array( 'id' => $item['id'] ) ) ); ?>"><?php sm_e( $item['title'] ); ?></a></p>
				<p class="smp-row__meta">
					<span class="smp-status smp-status--<?php echo esc_attr( $item['status'] ); ?>"><?php echo esc_html( $one[ $item['status'] ] ?? $item['status'] ); ?></span>
					<?php if ( ! empty( $item['revision'] ) ) : ?><span class="smp-status smp-status--pending"><?php esc_html_e( 'Changes being checked', 'sm-marketplace' ); ?></span><?php endif; ?>
					<span><?php echo sm_tech( $item['designation'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<?php if ( $item['weight_kg'] ) : ?><span><?php echo sm_kg( $item['weight_kg'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php endif; ?>
					<span><?php echo sm_listing_price( $item ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<?php if ( $item['total_price'] ) : ?><span><?php echo esc_html( sm_money( $item['total_price'] ) ); ?></span><?php endif; ?>
					<span><?php echo esc_html( sm_t( $wilayas[ $item['wilaya'] ] ?? '' ) ); ?></span>
					<?php if ( ! empty( $item['is_featured'] ) ) : ?>
						<span class="smp-status smp-status--featured"><?php /* translators: %s: date. */ echo esc_html( sprintf( __( 'Featured until %s', 'sm-marketplace' ), sm_date( $item['featured_until'] ) ) ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $item['is_urgent'] ) ) : ?>
						<span class="smp-status smp-status--urgent"><?php /* translators: %s: date. */ echo esc_html( sprintf( __( 'Urgent until %s', 'sm-marketplace' ), sm_date( $item['urgent_until'] ) ) ); ?></span>
					<?php endif; ?>
					<?php if ( 'published' === $item['status'] ) : ?>
						<span><?php sm_icon( 'eye' ); ?> <?php echo esc_html( sm_number( $item['views'] ) ); ?></span>
					<?php endif; ?>
					<?php if ( isset( $item['group_count'] ) ) : ?>
						<span class="smp-status smp-status--lot"><?php echo esc_html( sprintf( /* translators: %d: number of products. */ _n( 'Lot of %d product', 'Lot of %d products', (int) $item['group_count'], 'sm-marketplace' ), (int) $item['group_count'] ) ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $item['in_group'] ) ) : ?>
						<span><?php esc_html_e( 'Sold in:', 'sm-marketplace' ); ?> <a href="<?php echo esc_url( sm_page_url( 'listing', array( 'id' => $item['in_group']['id'] ) ) ); ?>"><?php echo esc_html( $item['in_group']['title'] ); ?></a></span>
					<?php endif; ?>
				</p>
			</div>
			<div class="smp-row__actions">
				<?php if ( ! isset( $item['group_count'] ) && 'grouped' !== $item['status'] ) : ?>
				<a class="sm-btn sm-btn--primary-outline sm-btn--sm" href="<?php echo esc_url( $edit ); ?>"><?php sm_icon( 'edit' ); ?><?php esc_html_e( 'Edit', 'sm-marketplace' ); ?></a>
				<?php endif; ?>
				<?php
				if ( isset( $item['group_count'] ) && in_array( $item['status'], array( 'published', 'draft', 'expired', 'paused' ), true ) ) {
					sm_theme_form_open( 'reuse_ungroup', array( 'class' => 'smp-act' ) );
					echo '<input type="hidden" name="product_id" value="' . esc_attr( $item['product_id'] ) . '"><button type="submit" class="sm-btn sm-btn--ghost sm-btn--sm" data-sm-confirm="' . esc_attr__( 'Undo this lot? Its products go back on sale one by one.', 'sm-marketplace' ) . '">' . esc_html__( 'Undo the lot', 'sm-marketplace' ) . '</button></form>';
				}
				if ( 'published' === $item['status'] ) {
					$action( $item, 'sold', __( 'Mark as sold', 'sm-marketplace' ) );
					$action( $item, 'withdraw', __( 'Withdraw', 'sm-marketplace' ) );
				} elseif ( in_array( $item['status'], array( 'sold', 'expired', 'paused' ), true ) ) {
					$action( $item, 'republish', __( 'Publish again', 'sm-marketplace' ) );
				} elseif ( 'pending' === $item['status'] ) {
					$action( $item, 'withdraw', __( 'Withdraw', 'sm-marketplace' ) );
				}
				if ( in_array( $item['status'], array( 'draft', 'rejected', 'sold', 'expired', 'paused' ), true ) && ! ( isset( $item['group_count'] ) && 'sold' !== $item['status'] ) ) {
					$action( $item, 'delete', __( 'Delete', 'sm-marketplace' ), 'ghost', __( 'Delete this product for good?', 'sm-marketplace' ) );
				}
				?>
			</div>
			<?php if ( 'published' === $item['status'] && $options ) : ?>
				<div class="smp-row__boost">
					<span class="smp-row__boost-label"><?php esc_html_e( 'More visibility:', 'sm-marketplace' ); ?></span>
					<?php
					$names = array(
						'bump'     => array( 'sort', __( 'Back to the top', 'sm-marketplace' ) ),
						/* translators: %d: days. */
						'urgent'   => array( 'alert', __( 'Urgent badge, %d days', 'sm-marketplace' ) ),
						/* translators: %d: days. */
						'featured' => array( 'star', __( 'Featured, %d days', 'sm-marketplace' ) ),
					);
					foreach ( $options as $key => $opt ) {
						if ( ! isset( $names[ $key ] ) || ( 'urgent' === $key && ! empty( $item['is_urgent'] ) ) || ( 'featured' === $key && ! empty( $item['is_featured'] ) ) ) {
							continue;
						}
						sm_theme_form_open( 'sub_order', array( 'class' => 'smp-act' ) );
						echo '<input type="hidden" name="kind" value="option"><input type="hidden" name="item" value="' . esc_attr( $key ) . '"><input type="hidden" name="target" value="' . esc_attr( $item['product_id'] ) . '">';
						echo '<button type="submit" class="sm-btn sm-btn--ghost sm-btn--sm">' . sm_get_icon( $names[ $key ][0] ) . '<span>' . esc_html( sprintf( $names[ $key ][1], (int) $opt['days'] ) . ' · ' . sm_money( $opt['price'] ) ) . '</span></button></form>'; // phpcs:ignore WordPress.Security.EscapeOutput
					}
					?>
				</div>
			<?php endif; ?>
			<?php if ( 'paused' === $item['status'] ) : ?>
				<p class="smp-row__reason sm-notice sm-notice--info"><?php sm_icon( 'info' ); ?><span><?php esc_html_e( 'Paused because it went beyond your plan. Nothing was deleted: publish it again when there is room, or choose a higher plan.', 'sm-marketplace' ); ?> <a href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'plan' ) ) ); ?>"><?php esc_html_e( 'See the plans', 'sm-marketplace' ); ?></a></span></p>
			<?php endif; ?>
			<?php if ( 'rejected' === $item['status'] && $item['reject_reason'] ) : ?>
				<p class="smp-row__reason sm-notice sm-notice--warn"><?php sm_icon( 'alert' ); ?><span><?php echo esc_html( $item['reject_reason'] ); ?></span></p>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
</ul>

<?php if ( $n_group >= 2 ) : ?>
	<?php sm_theme_form_open( 'reuse_group', array( 'class' => 'sm-form smr-group', 'id' => 'sm-reuse-group', 'data-smr-group' => '' ) ); ?>
		<div class="smr-group__bar">
			<p class="smr-group__count" data-smr-count aria-live="polite"><?php esc_html_e( 'Tick the listings to group.', 'sm-marketplace' ); ?></p>
		</div>
		<div class="smr-group__fields">
			<div class="sm-field">
				<label for="smr-lot-title"><?php esc_html_e( 'Title of the lot', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label>
				<input id="smr-lot-title" name="lot_title" maxlength="140" dir="auto" placeholder="<?php esc_attr_e( 'e.g. Lot from a dismantled hangar', 'sm-marketplace' ); ?>">
			</div>
			<div class="sm-field">
				<label for="smr-lot-price"><?php esc_html_e( 'Price of the lot', 'sm-marketplace' ); ?> <small>(<?php echo esc_html( sm_currency_label() ); ?>)</small> <small class="sm-optional"><?php esc_html_e( 'empty = sum of the prices', 'sm-marketplace' ); ?></small></label>
				<input id="smr-lot-price" name="lot_price" inputmode="decimal" dir="ltr" data-smr-price>
			</div>
		</div>
		<p class="sm-help"><?php esc_html_e( 'The ticked products are no longer sold separately while the lot exists. "Undo the lot" puts them back on sale one by one.', 'sm-marketplace' ); ?></p>
		<button type="submit" class="sm-btn sm-btn--accent" data-smr-submit><?php sm_icon( 'package' ); ?><span><?php esc_html_e( 'Group into a lot', 'sm-marketplace' ); ?></span></button>
	</form>
	<script>
	( function () {
		var f = document.querySelector( '[data-smr-group]' );
		if ( ! f ) { return; }
		f.classList.add( 'is-js' );
		var picks = document.querySelectorAll( '[data-smr-pick]' );
		var out = f.querySelector( '[data-smr-count]' );
		var price = f.querySelector( '[data-smr-price]' );
		var fmt = function ( n ) { return Math.round( n ).toLocaleString( document.documentElement.lang || 'fr' ).replace( /,/g, ' ' ); };
		var tpl = <?php echo wp_json_encode( array( 'none' => __( 'Tick the listings to group.', 'sm-marketplace' ), 'one' => __( '1 listing ticked: tick at least one more.', 'sm-marketplace' ), /* translators: 1: number of listings, 2: weight in kg. */ 'many' => __( '%1$s listings ticked · %2$s kg', 'sm-marketplace' ), 'wilaya' => __( 'The products of a lot must be in the same wilaya.', 'sm-marketplace' ) ) ); ?>;
		function update() {
			var n = 0, kg = 0, sum = 0, w = {};
			picks.forEach( function ( c ) {
				var row = c.closest( '.smp-row' );
				if ( row ) { row.classList.toggle( 'is-picked', c.checked ); }
				if ( ! c.checked ) { return; }
				n++; kg += parseFloat( c.dataset.kg || 0 ); sum += parseFloat( c.dataset.price || 0 ); w[ c.dataset.wilaya ] = 1;
			} );
			var many = Object.keys( w ).length > 1;
			out.textContent = 0 === n ? tpl.none : ( 1 === n ? tpl.one : ( many ? tpl.wilaya : tpl.many.replace( '%1$s', n ).replace( '%2$s', fmt( kg ) ) ) );
			f.classList.toggle( 'is-ready', n >= 2 && ! many );
			if ( price ) { price.placeholder = sum > 0 ? fmt( sum ) : ''; }
		}
		picks.forEach( function ( c ) { c.addEventListener( 'change', update ); } );
		update();
	}() );
	</script>
<?php endif; ?>

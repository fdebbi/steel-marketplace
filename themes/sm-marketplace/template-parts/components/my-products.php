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
$base    = sm_page_url( 'account', array( 'view' => 'listings' ) );
$labels  = array(
	'published' => __( 'Published', 'sm-marketplace' ),
	'pending'   => __( 'Waiting for review', 'sm-marketplace' ),
	'rejected'  => __( 'Changes requested', 'sm-marketplace' ),
	'draft'     => __( 'Drafts', 'sm-marketplace' ),
	'sold'      => __( 'Sold', 'sm-marketplace' ),
	'expired'   => __( 'Expired', 'sm-marketplace' ),
	'paused'    => __( 'Paused (plan)', 'sm-marketplace' ),
);
$one = array(
	'published' => __( 'Published', 'sm-marketplace' ),
	'pending'   => __( 'Waiting for review', 'sm-marketplace' ),
	'rejected'  => __( 'Changes requested', 'sm-marketplace' ),
	'draft'     => __( 'Draft', 'sm-marketplace' ),
	'sold'      => __( 'Sold', 'sm-marketplace' ),
	'expired'   => __( 'Expired', 'sm-marketplace' ),
	'paused'    => __( 'Paused (plan)', 'sm-marketplace' ),
);
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

<ul class="smp-list" role="list">
	<?php foreach ( $data['items'] as $item ) : ?>
		<?php $edit = sm_page_url( 'account', array( 'view' => 'publish', 'edit' => $item['product_id'] ) ); ?>
		<li class="smp-row">
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
				</p>
			</div>
			<div class="smp-row__actions">
				<a class="sm-btn sm-btn--primary-outline sm-btn--sm" href="<?php echo esc_url( $edit ); ?>"><?php sm_icon( 'edit' ); ?><?php esc_html_e( 'Edit', 'sm-marketplace' ); ?></a>
				<?php
				if ( 'published' === $item['status'] ) {
					$action( $item, 'sold', __( 'Mark as sold', 'sm-marketplace' ) );
					$action( $item, 'withdraw', __( 'Withdraw', 'sm-marketplace' ) );
				} elseif ( in_array( $item['status'], array( 'sold', 'expired', 'paused' ), true ) ) {
					$action( $item, 'republish', __( 'Publish again', 'sm-marketplace' ) );
				} elseif ( 'pending' === $item['status'] ) {
					$action( $item, 'withdraw', __( 'Withdraw', 'sm-marketplace' ) );
				}
				if ( in_array( $item['status'], array( 'draft', 'rejected', 'sold', 'expired', 'paused' ), true ) ) {
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

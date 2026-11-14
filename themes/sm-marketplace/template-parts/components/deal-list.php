<?php
/**
 * Orders and offers of the account (0.24.0, sm-trade): as a buyer ("My purchases
 * and offers") or as a seller ("Orders and offers received").
 *
 * @var array $args { role: buyer|seller }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$role   = 'seller' === ( $args['role'] ?? '' ) ? 'seller' : 'buyer';
$state  = sm_theme_query_arg( 'etat' );
$state  = in_array( $state, array( 'open', 'closed' ), true ) ? $state : '';
$page   = max( 1, (int) sm_theme_query_arg( 'pg', 1 ) );
$data   = sm_theme_data( 'deals', array( 'role' => $role, 'status' => $state, 'page' => $page ) );
$items  = (array) ( $data['items'] ?? array() );
$view   = 'seller' === $role ? 'sales' : 'purchases';
$tone   = array( 'open' => 'warn', 'accepted' => 'ok', 'refused' => 'muted', 'cancelled' => 'muted', 'expired' => 'muted' );
$filter = array(
	''       => __( 'All', 'sm-marketplace' ),
	'open'   => __( 'In progress', 'sm-marketplace' ),
	'closed' => __( 'Closed', 'sm-marketplace' ),
);
?>
<nav class="sm-chips sm-deals__filter" aria-label="<?php esc_attr_e( 'Filter', 'sm-marketplace' ); ?>">
	<?php foreach ( $filter as $k => $l ) : ?>
		<a class="sm-chip<?php echo $k === $state ? ' is-active' : ''; ?>" href="<?php echo esc_url( sm_page_url( 'account', array_filter( array( 'view' => $view, 'etat' => $k ) ) ) ); ?>" <?php echo $k === $state ? 'aria-current="true"' : ''; ?>><?php echo esc_html( $l ); ?></a>
	<?php endforeach; ?>
</nav>

<?php if ( ! $items ) : ?>
	<?php
	sm_component(
		'empty-state',
		'seller' === $role
			? array( 'icon' => 'cart', 'title' => __( 'No order or offer received yet', 'sm-marketplace' ), 'text' => __( 'Buyers order or make offers from your listings; you are told by e-mail and answer here.', 'sm-marketplace' ), 'action_label' => __( 'My listings', 'sm-marketplace' ), 'action_url' => sm_page_url( 'account', array( 'view' => 'listings' ) ) )
			: array( 'icon' => 'cart', 'title' => __( 'No purchase or offer yet', 'sm-marketplace' ), 'text' => __( 'On a listing, choose "Buy" or "Make an offer": your orders and offers are followed here.', 'sm-marketplace' ), 'action_label' => __( 'Browse the marketplace', 'sm-marketplace' ), 'action_url' => sm_page_url( 'search' ) )
	);
	?>
	<?php return; ?>
<?php endif; ?>

<ul class="sm-deals">
	<?php foreach ( $items as $d ) : ?>
		<li class="sm-deal<?php echo $d['my_turn'] ? ' is-turn' : ''; ?>">
			<a class="sm-deal__link" href="<?php echo esc_url( $d['url'] ); ?>">
				<span class="sm-deal__top">
					<span class="sm-deal__ref" dir="ltr"><?php echo esc_html( $d['ref'] ); ?></span>
					<span class="sm-chip sm-chip--quiet"><?php echo esc_html( $d['kind_label'] ); ?></span>
					<span class="sm-status sm-status--<?php echo esc_attr( $tone[ $d['status'] ] ?? 'muted' ); ?>"><?php echo esc_html( $d['status_label'] ); ?></span>
					<?php if ( $d['my_turn'] ) : ?><span class="sm-deal__turn"><?php esc_html_e( 'Your answer is awaited', 'sm-marketplace' ); ?></span><?php endif; ?>
				</span>
				<strong class="sm-deal__title"><?php echo esc_html( $d['title'] ); ?></strong>
				<span class="sm-deal__meta">
					<span><?php echo esc_html( 'lot' === $d['mode'] ? ( 'buildings' === $d['market'] ? __( 'Complete building', 'sm-marketplace' ) : __( 'Whole lot', 'sm-marketplace' ) ) : sm_number( $d['quantity'], fmod( $d['quantity'], 1 ) ? 2 : 0 ) . ' ' . $d['unit_label'] ); ?></span>
					<span><strong><?php echo esc_html( sm_money( $d['amount'] ) ); ?></strong></span>
					<span><?php echo esc_html( 'seller' === $role ? $d['buyer']['name'] : $d['seller']['name'] ); ?></span>
					<span><?php echo esc_html( sm_ago( $d['updated'] ) ); ?></span>
				</span>
			</a>
		</li>
	<?php endforeach; ?>
</ul>
<?php if ( (int) ( $data['pages'] ?? 1 ) > 1 ) : ?>
	<nav class="sm-pagination" aria-label="<?php esc_attr_e( 'Pagination', 'sm-marketplace' ); ?>">
		<?php for ( $i = 1; $i <= (int) $data['pages']; $i++ ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'pg', $i ) ); ?>" <?php echo $i === $page ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $i ); ?></a>
		<?php endfor; ?>
	</nav>
<?php endif; ?>

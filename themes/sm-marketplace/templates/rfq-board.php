<?php
/**
 * Template Name: SM — Requests for quotation
 *
 * Public board of open requests (sm-rfq), or one request when the address
 * carries its number (/demandes-de-devis/123/).
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$rfq_id = sm_theme_current_rfq_id();
if ( $rfq_id ) {
	$item = sm_theme_data( 'rfq', array( 'id' => $rfq_id, 'sort' => sm_theme_query_arg( 'sort', 'total' ) ) );
	if ( ! $item ) {
		status_header( 404 );
		get_header();
		echo '<div class="sm-container sm-section">';
		sm_component( 'empty-state', array( 'icon' => 'rfq', 'title' => __( 'This request is not available', 'sm-marketplace' ), 'text' => __( 'It may have been removed, or it is waiting for review.', 'sm-marketplace' ), 'action_label' => __( 'See the requests for quotation', 'sm-marketplace' ), 'action_url' => sm_page_url( 'rfq' ) ) );
		echo '</div>';
		get_footer();
		return;
	}
	get_header();
	sm_component( 'rfq-single', array( 'item' => $item ) );
	get_footer();
	return;
}

get_header();
$wilayas  = sm_theme_wilayas();
$settings = (array) sm_theme_data( 'rfq_settings' );
$markets  = (array) ( $settings['markets'] ?? array() );
$page_num = max( 1, (int) sm_theme_query_arg( 'pg', 1 ) );
$result   = wp_parse_args(
	(array) sm_theme_data(
		'rfqs',
		array(
			'market'   => sm_theme_query_arg( 'market' ),
			'wilaya'   => sm_theme_query_arg( 'wilaya' ),
			'q'        => sm_theme_query_arg( 'q' ),
			'sort'     => sm_theme_query_arg( 'sort', 'recent' ),
			'per_page' => 12,
			'page'     => $page_num,
		)
	),
	array( 'items' => array(), 'total' => 0, 'pages' => 0 )
);
$new_url = is_user_logged_in() ? sm_page_url( 'account', array( 'view' => 'rfq', 'new' => 1 ) ) : sm_page_url( 'login' );
?>
<section class="sm-pagehead">
	<div class="sm-container">
		<?php sm_breadcrumb( array( array( __( 'Requests for quotation', 'sm-marketplace' ), '' ) ) ); ?>
		<div class="sm-pagehead__row">
			<div>
				<h1 class="sm-pagehead__title"><?php esc_html_e( 'Requests for quotation', 'sm-marketplace' ); ?></h1>
				<p class="sm-pagehead__intro"><?php esc_html_e( 'Buyers describe what they need; suppliers answer with a price per kg and a delivery time.', 'sm-marketplace' ); ?></p>
			</div>
			<a class="sm-btn sm-btn--accent" href="<?php echo esc_url( $new_url ); ?>"><?php sm_icon( 'plus' ); ?><?php esc_html_e( 'New request', 'sm-marketplace' ); ?></a>
		</div>
	</div>
</section>
<div class="sm-container sm-section sm-rfq sm-rfq--board">
	<form class="smq-filters" method="get" role="search" aria-label="<?php esc_attr_e( 'Filter the requests', 'sm-marketplace' ); ?>">
		<div class="sm-field">
			<label for="smq-q"><?php esc_html_e( 'Profile or keyword', 'sm-marketplace' ); ?></label>
			<input id="smq-q" name="q" type="search" value="<?php echo esc_attr( sm_theme_query_arg( 'q' ) ); ?>" placeholder="IPE 270, hangar…">
		</div>
		<?php if ( $markets ) : ?>
			<div class="sm-field">
				<label for="smq-market"><?php esc_html_e( 'Market', 'sm-marketplace' ); ?></label>
				<select id="smq-market" name="market"><option value=""><?php esc_html_e( 'All markets', 'sm-marketplace' ); ?></option><?php foreach ( $markets as $k => $l ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( sm_theme_query_arg( 'market' ), $k ); ?>><?php echo esc_html( sm_t( $l ) ); ?></option><?php endforeach; ?></select>
			</div>
		<?php endif; ?>
		<div class="sm-field">
			<label for="smq-wilaya"><?php esc_html_e( 'Delivery wilaya', 'sm-marketplace' ); ?></label>
			<select id="smq-wilaya" name="wilaya"><option value=""><?php esc_html_e( 'All wilayas', 'sm-marketplace' ); ?></option><?php foreach ( $wilayas as $c => $n ) : ?><option value="<?php echo esc_attr( $c ); ?>" <?php selected( sm_theme_query_arg( 'wilaya' ), (string) $c ); ?>><?php echo esc_html( $c . ' — ' . sm_t( $n ) ); ?></option><?php endforeach; ?></select>
		</div>
		<div class="sm-field">
			<label for="smq-sort"><?php esc_html_e( 'Sort by', 'sm-marketplace' ); ?></label>
			<select id="smq-sort" name="sort"><option value="recent"><?php esc_html_e( 'Most recent', 'sm-marketplace' ); ?></option><option value="deadline" <?php selected( sm_theme_query_arg( 'sort' ), 'deadline' ); ?>><?php esc_html_e( 'Deadline soonest', 'sm-marketplace' ); ?></option></select>
		</div>
		<button class="sm-btn sm-btn--primary" type="submit"><?php sm_icon( 'search' ); ?><?php esc_html_e( 'Filter', 'sm-marketplace' ); ?></button>
	</form>

	<div class="sm-rfq__list">
		<p class="sm-toolbar__count">
			<?php
			/* translators: %s: number of requests. */
			echo wp_kses_post( sprintf( _n( '<strong>%s</strong> open request', '<strong>%s</strong> open requests', (int) $result['total'], 'sm-marketplace' ), sm_number( $result['total'] ) ) );
			?>
		</p>
		<?php if ( ! $result['items'] ) : ?>
			<?php sm_component( 'empty-state', array( 'icon' => 'rfq', 'title' => __( 'No open request here', 'sm-marketplace' ), 'text' => __( 'Publish yours: suppliers answer with their prices.', 'sm-marketplace' ), 'action_label' => __( 'New request', 'sm-marketplace' ), 'action_url' => $new_url ) ); ?>
		<?php endif; ?>
		<?php foreach ( $result['items'] as $r ) : ?>
			<?php sm_component( 'card-rfq', array( 'item' => $r ) ); ?>
		<?php endforeach; ?>
		<?php if ( $result['pages'] > 1 ) : ?>
			<nav class="sm-pagination" aria-label="<?php esc_attr_e( 'Pagination', 'sm-marketplace' ); ?>">
				<?php for ( $i = 1; $i <= $result['pages']; $i++ ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'pg', $i ) ); ?>" <?php echo $i === $page_num ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $i ); ?></a>
				<?php endfor; ?>
			</nav>
		<?php endif; ?>
	</div>

	<aside class="sm-panel smq-how">
		<h2 class="sm-panel__title"><?php esc_html_e( 'How it works', 'sm-marketplace' ); ?></h2>
		<ol class="smq-steps">
			<li><strong><?php esc_html_e( 'Describe your need', 'sm-marketplace' ); ?></strong><span><?php esc_html_e( 'Profiles and lengths (the weight is calculated), or a free description, delivery place and date, drawings.', 'sm-marketplace' ); ?></span></li>
			<li><strong><?php esc_html_e( 'Receive offers', 'sm-marketplace' ); ?></strong><span><?php esc_html_e( 'Suppliers and manufacturers answer with a price, a price per kg and a delivery time. Only you see all the offers.', 'sm-marketplace' ); ?></span></li>
			<li><strong><?php esc_html_e( 'Compare and choose', 'sm-marketplace' ); ?></strong><span><?php esc_html_e( 'Side by side: total, price per kg, delivery, supplier verification. Contact them, then accept the offer you choose.', 'sm-marketplace' ); ?></span></li>
		</ol>
		<a class="sm-btn sm-btn--accent sm-btn--block" href="<?php echo esc_url( $new_url ); ?>"><?php sm_icon( 'plus' ); ?><?php esc_html_e( 'New request', 'sm-marketplace' ); ?></a>
	</aside>
</div>
<?php
get_footer();

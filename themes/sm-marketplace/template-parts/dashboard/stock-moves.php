<?php
/**
 * Warehouse: all the movements (0.48.0, sm-stock).
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$page = max( 1, (int) sm_theme_query_arg( 'pg', 1 ) );
$d    = sm_theme_data( 'stock_moves', array( 'page' => $page ) );
if ( ! is_array( $d ) || ! $d['items'] ) {
	sm_component( 'empty-state', array( 'icon' => 'warehouse', 'title' => __( 'No movement yet', 'sm-marketplace' ), 'text' => __( 'Additions, sales accepted on ReuSteel, corrections and price changes appear here.', 'sm-marketplace' ) ) );
	return;
}
?>
<section class="sm-panel smst-moves">
	<?php sm_component( 'stock-moves-list', array( 'moves' => $d['items'] ) ); ?>
	<?php if ( $d['total'] > $d['per'] ) : ?>
		<nav class="smst-pager">
			<?php if ( $page > 1 ) : ?><a class="sm-btn sm-btn--ghost sm-btn--sm" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'stock-moves', 'pg' => $page - 1 ) ) ); ?>"><?php esc_html_e( 'Newer', 'sm-marketplace' ); ?></a><?php endif; ?>
			<?php if ( $page * $d['per'] < $d['total'] ) : ?><a class="sm-btn sm-btn--ghost sm-btn--sm" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'stock-moves', 'pg' => $page + 1 ) ) ); ?>"><?php esc_html_e( 'Older', 'sm-marketplace' ); ?></a><?php endif; ?>
		</nav>
	<?php endif; ?>
</section>

<?php
/**
 * My listings (inventory table). Rows become cards on mobile.
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$items = sm_theme_listings( array( 'company' => $args['account']['company']['slug'], 'per_page' => 50 ) )['items'];
?>
<div class="sm-toolbar">
	<ul class="sm-catpills sm-catpills--tight">
		<li><a href="#" aria-current="true"><?php esc_html_e( 'All', 'sm-marketplace' ); ?> <span class="sm-count"><?php echo esc_html( count( $items ) ); ?></span></a></li>
		<li><a href="#"><?php esc_html_e( 'Published', 'sm-marketplace' ); ?></a></li>
		<li><a href="#"><?php esc_html_e( 'Drafts', 'sm-marketplace' ); ?></a></li>
		<li><a href="#"><?php esc_html_e( 'Sold', 'sm-marketplace' ); ?></a></li>
	</ul>
	<a class="sm-btn sm-btn--accent" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'publish' ) ) ); ?>"><?php sm_icon( 'plus' ); ?><?php esc_html_e( 'Publish a listing', 'sm-marketplace' ); ?></a>
</div>
<div class="sm-table-wrap" tabindex="0" role="region" aria-label="<?php esc_attr_e( 'My listings', 'sm-marketplace' ); ?>">
	<table class="sm-table sm-table--stack sm-table--listings">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Listing', 'sm-marketplace' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Market', 'sm-marketplace' ); ?></th>
				<th scope="col" class="num"><?php esc_html_e( 'Weight', 'sm-marketplace' ); ?></th>
				<th scope="col" class="num"><?php esc_html_e( 'DA/kg', 'sm-marketplace' ); ?></th>
				<th scope="col" class="num"><?php esc_html_e( 'Total price', 'sm-marketplace' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Status', 'sm-marketplace' ); ?></th>
				<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'sm-marketplace' ); ?></span></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $items as $item ) : ?>
				<tr>
					<th scope="row" data-label="<?php esc_attr_e( 'Listing', 'sm-marketplace' ); ?>">
						<span class="sm-table__item"><img src="<?php echo esc_url( sm_image_url( $item['image'] ) ); ?>" alt="" width="56" height="42" loading="lazy"><a href="<?php echo esc_url( sm_page_url( 'listing', array( 'id' => $item['id'] ) ) ); ?>"><?php sm_e( $item['title'] ); ?></a></span>
					</th>
					<td data-label="<?php esc_attr_e( 'Market', 'sm-marketplace' ); ?>"><?php echo esc_html( sm_market_labels()[ $item['market'] ] ); ?></td>
					<td class="num" data-label="<?php esc_attr_e( 'Weight', 'sm-marketplace' ); ?>"><?php echo sm_kg( $item['weight_kg'] ); // phpcs:ignore ?></td>
					<td class="num" data-label="<?php esc_attr_e( 'DA/kg', 'sm-marketplace' ); ?>"><?php echo esc_html( sm_number( $item['price_per_kg'] ) ); ?></td>
					<td class="num" data-label="<?php esc_attr_e( 'Total price', 'sm-marketplace' ); ?>"><?php echo esc_html( sm_money( $item['total_price'] ) ); ?></td>
					<td data-label="<?php esc_attr_e( 'Status', 'sm-marketplace' ); ?>"><span class="sm-status sm-status--ok"><?php esc_html_e( 'Published', 'sm-marketplace' ); ?></span></td>
					<td class="sm-table__actions">
						<a class="sm-icon-btn" href="#"><?php sm_icon( 'edit', '', __( 'Edit', 'sm-marketplace' ) ); ?></a>
						<a class="sm-icon-btn" href="#"><?php sm_icon( 'star', '', __( 'Feature', 'sm-marketplace' ) ); ?></a>
						<a class="sm-icon-btn" href="#"><?php sm_icon( 'trash', '', __( 'Delete', 'sm-marketplace' ) ); ?></a>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

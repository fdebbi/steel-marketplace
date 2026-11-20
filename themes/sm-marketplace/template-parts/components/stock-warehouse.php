<?php
/**
 * Warehouse of the account (0.48.0, sm-stock): one tab (new or reuse) with its
 * figures, filters, search, items and variants (reference, location,
 * quantity, weight, price), « Published / In stock only », corrections and the
 * last movements. Data: stock (sm-stock).
 *
 * @var array $args { tab: new|reuse }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$tab  = 'reuse' === ( $args['tab'] ?? '' ) ? 'reuse' : 'new';
$view = 'reuse' === $tab ? 'stock-reuse' : 'stock';
$d    = sm_theme_data( 'stock', array( 'tab' => $tab, 'filter' => sm_theme_query_arg( 'filter', '' ), 'q' => sm_theme_query_arg( 'q', '' ) ) );
if ( ! is_array( $d ) ) {
	sm_component( 'empty-state', array( 'icon' => 'warehouse', 'title' => __( 'Warehouse', 'sm-marketplace' ), 'text' => __( 'This module is not active yet.', 'sm-marketplace' ) ) );
	return;
}
$base   = sm_page_url( 'account', array( 'view' => $view ) );
$t      = $d['totals'];
$conds  = array( 'new' => __( 'New', 'sm-marketplace' ) ) + sm_condition_labels();
$m      = static fn( $mm ) => sm_number( $mm / 1000, 2 );
$hl     = sanitize_text_field( (string) sm_theme_query_arg( 'ref', '' ) );
$qty    = static function ( $v, $family = '' ) use ( $m ) {
	if ( 'kg' === $v['unit'] ) {
		return sm_number( $v['qty'], 1 ) . "\u{00A0}kg";
	}
	$n = (int) $v['qty'];
	if ( 'PLATE' === $family ) {
		/* translators: 1: number of plates, 2: length. */
		$s = sprintf( _n( '%1$s plate × %2$s m', '%1$s plates × %2$s m', $n, 'sm-marketplace' ), sm_number( $n ), $m( $v['length_mm'] ) );
	} elseif ( 'bar' === $v['unit'] ) {
		/* translators: 1: number of bars, 2: length. */
		$s = sprintf( _n( '%1$s bar × %2$s m', '%1$s bars × %2$s m', $n, 'sm-marketplace' ), sm_number( $n ), $m( $v['length_mm'] ) );
	} else {
		/* translators: 1: number of pieces, 2: length. */
		$s = $v['length_mm'] ? sprintf( _n( '%1$s piece × %2$s m', '%1$s pieces × %2$s m', $n, 'sm-marketplace' ), sm_number( $n ), $m( $v['length_mm'] ) ) : sprintf( /* translators: %s: number of pieces. */ _n( '%s piece', '%s pieces', $n, 'sm-marketplace' ), sm_number( $n ) );
	}
	return $s;
};
$published = static fn( $st ) => in_array( $st, array( 'published', 'pending', 'paused' ), true );
$status_tx = array(
	'published' => __( 'Published', 'sm-marketplace' ),
	'pending'   => __( 'Waiting for review', 'sm-marketplace' ),
	'paused'    => __( 'Paused (plan)', 'sm-marketplace' ),
	'stock'     => __( 'In stock only', 'sm-marketplace' ),
	'expired'   => __( 'Expired · in stock', 'sm-marketplace' ),
	'sold'      => __( 'Sold', 'sm-marketplace' ),
);

/* « Published / In stock only » switch: the listing engine moves the item. */
$switch = static function ( $item ) use ( $published, $status_tx ) {
	if ( 'sold' === $item['status'] ) {
		echo '<span class="smst-state">' . esc_html( $status_tx['sold'] ) . '</span>';
		return;
	}
	$on = $published( $item['status'] );
	sm_theme_form_open( 'product_action', array( 'class' => 'smst-switch-form' ) );
	echo '<input type="hidden" name="product_id" value="' . esc_attr( $item['id'] ) . '"><input type="hidden" name="do" value="' . esc_attr( $on ? 'to_stock' : 'republish' ) . '">';
	printf(
		'<button type="submit" class="smst-switch%1$s" role="switch" aria-checked="%2$s" data-sm-confirm="%3$s" data-sm-confirm-ok="%4$s"><i aria-hidden="true"></i><span>%5$s</span></button>',
		$on ? ' is-on' : '',
		$on ? 'true' : 'false',
		esc_attr( $on ? __( 'Take this item off the market? It stays in your warehouse; buyers no longer see it.', 'sm-marketplace' ) : __( 'Publish this item on the market? Buyers will see it at once.', 'sm-marketplace' ) ),
		esc_attr( $on ? __( 'Keep in stock only', 'sm-marketplace' ) : __( 'Publish', 'sm-marketplace' ) ),
		esc_html( $status_tx[ $item['status'] ] ?? $item['status'] )
	);
	echo '</form>';
};

/* Reference + location, and the correction of the quantity, in a folding box. */
$tools = static function ( $item, $v ) use ( $qty ) {
	?>
	<details class="smst-edit">
		<summary><?php esc_html_e( 'Reference, location, quantity', 'sm-marketplace' ); ?></summary>
		<div class="smst-edit__body">
			<?php sm_theme_form_open( 'stock_ref', array( 'class' => 'sm-form smst-mini' ) ); ?>
				<input type="hidden" name="product_id" value="<?php echo esc_attr( $item['id'] ); ?>"><input type="hidden" name="line_key" value="<?php echo esc_attr( $v['key'] ); ?>">
				<label><span><?php esc_html_e( 'Reference', 'sm-marketplace' ); ?></span><input name="ref" value="<?php echo esc_attr( $v['ref'] ); ?>" maxlength="20" dir="ltr"></label>
				<label><span><?php esc_html_e( 'Location', 'sm-marketplace' ); ?></span><input name="location" value="<?php echo esc_attr( $v['location'] ); ?>" maxlength="120" placeholder="<?php esc_attr_e( 'e.g. Hangar 2 – rack B', 'sm-marketplace' ); ?>"></label>
				<button class="sm-btn sm-btn--ghost sm-btn--sm" type="submit"><?php esc_html_e( 'Save', 'sm-marketplace' ); ?></button>
			</form>
			<?php if ( 'sold' !== $item['status'] ) : ?>
				<?php sm_theme_form_open( 'stock_adjust', array( 'class' => 'sm-form smst-mini' ) ); ?>
					<input type="hidden" name="product_id" value="<?php echo esc_attr( $item['id'] ); ?>"><input type="hidden" name="line_key" value="<?php echo esc_attr( $v['key'] ); ?>">
					<label><span><?php echo esc_html( 'kg' === $v['unit'] ? __( 'New weight (kg)', 'sm-marketplace' ) : __( 'New quantity', 'sm-marketplace' ) ); ?></span><input name="qty" inputmode="decimal" value="<?php echo esc_attr( 'kg' === $v['unit'] ? $v['qty'] : (int) $v['qty'] ); ?>" required></label>
					<label><span><?php esc_html_e( 'Reason', 'sm-marketplace' ); ?></span><input name="note" maxlength="160" placeholder="<?php esc_attr_e( 'Inventory, damaged bar, sale outside the platform…', 'sm-marketplace' ); ?>" required></label>
					<button class="sm-btn sm-btn--accent sm-btn--sm" type="submit"><?php esc_html_e( 'Correct the quantity', 'sm-marketplace' ); ?></button>
				</form>
				<p class="smst-hint"><?php esc_html_e( 'Sales accepted on ReuSteel are taken off automatically.', 'sm-marketplace' ); ?></p>
			<?php endif; ?>
		</div>
	</details>
	<?php
};
?>
<div class="smst" data-tab="<?php echo esc_attr( $tab ); ?>">
	<div class="smst-totals">
		<div class="smst-k"><small><?php esc_html_e( 'Items', 'sm-marketplace' ); ?></small><b><?php echo esc_html( sm_number( $d['counts']['all'] ) ); ?></b><small><?php echo esc_html( sprintf( /* translators: 1: published, 2: in stock only. */ __( '%1$s published · %2$s in stock only', 'sm-marketplace' ), sm_number( $d['counts']['published'] ), sm_number( $d['counts']['stock'] ) ) ); ?></small></div>
		<div class="smst-k"><small><?php esc_html_e( 'Weight in stock', 'sm-marketplace' ); ?></small><b><?php echo sm_kg( $t['kg'], 1 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></b></div>
		<div class="smst-k is-pub"><small><?php esc_html_e( 'Published on the market', 'sm-marketplace' ); ?></small><b><?php echo sm_kg( $t['published_kg'], 1 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></b><?php if ( $t['published_value'] > 0 ) : ?><small><?php echo esc_html( sprintf( /* translators: %s: amount. */ __( 'value shown ≈ %s', 'sm-marketplace' ), sm_money( $t['published_value'] ) ) ); ?></small><?php endif; ?></div>
		<div class="smst-k"><small><?php esc_html_e( 'Not published', 'sm-marketplace' ); ?></small><b><?php echo sm_kg( $t['stock_kg'], 1 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></b><small><?php esc_html_e( 'seen by you only', 'sm-marketplace' ); ?></small></div>
	</div>
	<?php if ( null !== $d['capacity']['max_kg'] ) : ?>
		<?php $pct = $d['capacity']['max_kg'] > 0 ? min( 100, round( 100 * $d['capacity']['used_kg'] / $d['capacity']['max_kg'] ) ) : 100; ?>
		<div class="sm-meter smst-cap">
			<div class="sm-meter__label"><span><?php esc_html_e( 'Capacity of your warehouse (new and reuse)', 'sm-marketplace' ); ?></span><span><bdi dir="ltr"><?php echo esc_html( sm_number( $d['capacity']['used_kg'] / 1000, 1 ) . ' / ' . sm_number( $d['capacity']['max_kg'] / 1000, 0 ) . ' t' ); ?></bdi></span></div>
			<div class="sm-meter__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( $pct ); ?>"><span style="inline-size:<?php echo esc_attr( $pct ); ?>%"></span></div>
		</div>
	<?php endif; ?>

	<div class="sm-toolbar smst-bar">
		<nav class="smp-filter" aria-label="<?php esc_attr_e( 'Filter by status', 'sm-marketplace' ); ?>">
			<?php
			$pills = array(
				''          => sprintf( '%s (%s)', __( 'All', 'sm-marketplace' ), sm_number( $d['counts']['all'] ) ),
				'published' => sprintf( '%s (%s)', __( 'Published', 'sm-marketplace' ), sm_number( $d['counts']['published'] ) ),
				'stock'     => sprintf( '%s (%s)', __( 'In stock only', 'sm-marketplace' ), sm_number( $d['counts']['stock'] ) ),
				'sold'      => __( 'Sold', 'sm-marketplace' ),
			);
			foreach ( $pills as $k => $l ) :
				?>
				<a href="<?php echo esc_url( '' === $k ? $base : add_query_arg( 'filter', $k, $base ) ); ?>"<?php echo $d['filter'] === $k ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $l ); ?></a>
			<?php endforeach; ?>
		</nav>
		<form class="smst-search" method="get" action="<?php echo esc_url( $base ); ?>" role="search">
			<?php if ( $d['filter'] ) : ?><input type="hidden" name="filter" value="<?php echo esc_attr( $d['filter'] ); ?>"><?php endif; ?>
			<label class="screen-reader-text" for="smst-q"><?php esc_html_e( 'Search the warehouse', 'sm-marketplace' ); ?></label>
			<input id="smst-q" name="q" type="search" value="<?php echo esc_attr( (string) sm_theme_query_arg( 'q', '' ) ); ?>" placeholder="<?php esc_attr_e( 'Reference, profile, location…', 'sm-marketplace' ); ?>">
			<button class="sm-btn sm-btn--ghost sm-btn--sm" type="submit"><?php sm_icon( 'search' ); ?><span class="screen-reader-text"><?php esc_html_e( 'Search', 'sm-marketplace' ); ?></span></button>
		</form>
		<div class="smst-actions">
			<?php if ( 'new' === $tab ) : ?>
				<a class="sm-btn sm-btn--ghost sm-btn--sm<?php echo $d['tools'] ? '' : ' is-locked'; ?>" href="<?php echo esc_url( $d['tools'] ? sm_page_url( 'account', array( 'view' => 'stock-prices' ) ) : sm_page_url( 'account', array( 'view' => 'plan' ) ) ); ?>"<?php echo $d['tools'] ? '' : ' title="' . esc_attr__( 'With the paid plans', 'sm-marketplace' ) . '"'; ?>><?php sm_icon( $d['tools'] ? 'tag' : 'lock' ); ?><span><?php esc_html_e( 'Price for a whole type', 'sm-marketplace' ); ?></span></a>
			<?php endif; ?>
			<a class="sm-btn sm-btn--ghost sm-btn--sm<?php echo $d['tools'] ? '' : ' is-locked'; ?>" href="<?php echo esc_url( $d['tools'] ? add_query_arg( array( 'action' => 'sm_stock_labels', 'tab' => $tab ), admin_url( 'admin-post.php' ) ) : sm_page_url( 'account', array( 'view' => 'plan' ) ) ); ?>"<?php echo $d['tools'] ? '' : ' title="' . esc_attr__( 'With the paid plans', 'sm-marketplace' ) . '"'; ?>><?php sm_icon( $d['tools'] ? 'file' : 'lock' ); ?><span><?php esc_html_e( 'Labels (PDF)', 'sm-marketplace' ); ?></span></a>
			<a class="sm-btn sm-btn--ai-outline sm-btn--sm" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'ai', 'tool' => 'document', 'for' => 'stock' ) ) ); ?>"><?php sm_icon( 'ai' ); ?><span><?php esc_html_e( 'Add from a document', 'sm-marketplace' ); ?></span></a>
			<a class="sm-btn sm-btn--accent sm-btn--sm" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'publish', 'market' => 'reuse' === $tab ? 'reuse' : 'products' ) ) ); ?>"><?php sm_icon( 'plus' ); ?><span><?php esc_html_e( 'Add to the stock', 'sm-marketplace' ); ?></span></a>
		</div>
	</div>

	<?php if ( ! $d['items'] ) : ?>
		<?php sm_component( 'empty-state', array( 'icon' => 'warehouse', 'title' => __( 'Nothing here yet', 'sm-marketplace' ), 'text' => 'reuse' === $tab ? __( 'Add your offcuts and reused steel: publish them or keep them in stock only.', 'sm-marketplace' ) : __( 'Add your new steel: publish it on the market or keep it in stock only.', 'sm-marketplace' ) ) ); ?>
	<?php elseif ( 'new' === $tab ) : ?>
		<div class="sm-table-wrap" tabindex="0" role="region" aria-label="<?php esc_attr_e( 'Warehouse', 'sm-marketplace' ); ?>">
			<table class="sm-table sm-table--stack smst-table">
				<thead><tr>
					<th scope="col"><?php esc_html_e( 'Reference', 'sm-marketplace' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Item', 'sm-marketplace' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Quantity', 'sm-marketplace' ); ?></th>
					<th scope="col" class="num"><?php esc_html_e( 'Weight', 'sm-marketplace' ); ?></th>
					<th scope="col" class="num"><?php esc_html_e( 'Price', 'sm-marketplace' ); ?></th>
					<th scope="col"><?php esc_html_e( 'On the market', 'sm-marketplace' ); ?></th>
				</tr></thead>
				<tbody>
					<?php foreach ( $d['items'] as $it ) : ?>
						<?php $v = $it['variants'][0]; ?>
						<tr class="<?php echo $hl && $hl === $v['ref'] ? 'is-hl' : ''; ?>" id="<?php echo esc_attr( 'ref-' . $v['ref'] ); ?>">
							<td data-label="<?php esc_attr_e( 'Reference', 'sm-marketplace' ); ?>"><b class="smst-ref" dir="ltr"><?php echo esc_html( $v['ref'] ); ?></b><?php if ( $v['location'] ) : ?><small class="smst-loc"><?php sm_icon( 'map-pin' ); ?><?php echo esc_html( $v['location'] ); ?></small><?php endif; ?></td>
							<td data-label="<?php esc_attr_e( 'Item', 'sm-marketplace' ); ?>"><b><?php echo sm_tech( trim( $it['designation'] . ( $it['grade'] ? ' · ' . $it['grade'] : '' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></b><?php if ( $v['kg_m'] ) : ?><small><?php echo esc_html( sm_number( $v['kg_m'], 2 ) . ' kg/m' ); ?></small><?php endif; ?></td>
							<td data-label="<?php esc_attr_e( 'Quantity', 'sm-marketplace' ); ?>"><?php echo esc_html( $qty( $v, $it['family'] ) ); ?></td>
							<td class="num" data-label="<?php esc_attr_e( 'Weight', 'sm-marketplace' ); ?>"><?php echo esc_html( sm_number( $v['kg'], 1 ) . "\u{00A0}kg" ); ?></td>
							<td class="num" data-label="<?php esc_attr_e( 'Price', 'sm-marketplace' ); ?>"><?php echo $it['price'] > 0 && 'kg' === $it['price_unit'] ? sm_per_unit( $it['price'], 'kg' ) : esc_html( $it['price'] > 0 ? sm_money( $it['price'] ) : '—' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
							<td data-label="<?php esc_attr_e( 'On the market', 'sm-marketplace' ); ?>">
								<?php $switch( $it ); ?>
								<div class="smst-links"><a href="<?php echo esc_url( $it['edit'] ); ?>"><?php esc_html_e( 'Edit', 'sm-marketplace' ); ?></a><?php if ( $published( $it['status'] ) ) : ?> · <a href="<?php echo esc_url( $it['url'] ); ?>"><?php esc_html_e( 'See the listing', 'sm-marketplace' ); ?></a><?php endif; ?></div>
								<?php $tools( $it, $v ); ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php else : ?>
		<div class="smst-lots">
			<?php foreach ( $d['items'] as $it ) : ?>
				<section class="smst-lot">
					<div class="smst-lot__head">
						<div>
							<h3><?php echo esc_html( $it['title'] ); ?></h3>
							<p><span class="smst-cond"><?php echo esc_html( $conds[ $it['condition'] ] ?? $it['condition'] ); ?></span> · <?php echo esc_html( sm_number( $it['kg'], 1 ) . ' kg' ); ?><?php if ( $it['price'] > 0 && 'kg' === $it['price_unit'] ) : ?> · <?php echo sm_per_unit( $it['price'], 'kg' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php endif; ?></p>
						</div>
						<div class="smst-lot__acts"><?php $switch( $it ); ?><div class="smst-links"><a href="<?php echo esc_url( $it['edit'] ); ?>"><?php esc_html_e( 'Edit', 'sm-marketplace' ); ?></a><?php if ( $published( $it['status'] ) ) : ?> · <a href="<?php echo esc_url( $it['url'] ); ?>"><?php esc_html_e( 'See the listing', 'sm-marketplace' ); ?></a><?php endif; ?></div></div>
					</div>
					<ul class="smst-vars">
						<?php foreach ( $it['variants'] as $v ) : ?>
							<li class="<?php echo $hl && $hl === $v['ref'] ? 'is-hl' : ''; ?>" id="<?php echo esc_attr( 'ref-' . $v['ref'] ); ?>">
								<b class="smst-ref" dir="ltr"><?php echo esc_html( $v['ref'] ); ?></b>
								<span class="smst-var"><?php echo sm_tech( $v['designation'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?> · <?php echo esc_html( $qty( $v ) ); ?> · <?php echo esc_html( sm_number( $v['kg'], 1 ) . ' kg' ); ?></span>
								<?php if ( $v['location'] ) : ?><small class="smst-loc"><?php sm_icon( 'map-pin' ); ?><?php echo esc_html( $v['location'] ); ?></small><?php endif; ?>
								<?php $tools( $it, $v ); ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php if ( $d['moves'] ) : ?>
		<section class="sm-panel smst-moves">
			<div class="smst-moves__head"><h2 class="sm-panel__title"><?php esc_html_e( 'Last movements', 'sm-marketplace' ); ?></h2><a class="sm-link" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'stock-moves' ) ) ); ?>"><?php esc_html_e( 'All the movements', 'sm-marketplace' ); ?><?php sm_icon( 'arrow-end' ); ?></a></div>
			<?php sm_component( 'stock-moves-list', array( 'moves' => $d['moves'] ) ); ?>
		</section>
	<?php endif; ?>
</div>

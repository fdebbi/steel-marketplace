<?php
/**
 * Overview of a buyer or seller account (0.35.0): the first page after signing in.
 *
 * What was sold and bought on the platform (accepted orders and offers, from
 * sm-trade), the steel on sale (sm-products), a chart per period, what waits for
 * an answer, recent listings and the state of the account. Numbers are drawn by
 * assets/js/overview.js (animated curves, count-up, period and measure switches);
 * the page reads complete without it.
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$account = $args['account'];
$usage   = (array) ( $account['usage'] ?? array() );
$badges  = (array) ( $account['badges'] ?? array() );
$level   = (int) $account['company']['verification'];
$is_demo = empty( $account['real'] );
$stats   = $is_demo ? null : sm_theme_data( 'account_stats' );
$stock   = $is_demo ? null : sm_theme_data( 'account_stock' );
$stock   = is_array( $stock ) ? $stock : array( 'count' => (int) ( $account['kpis']['active'] ?? 0 ), 'kg' => 0, 'value' => 0, 'views' => 0, 'markets' => array() );
$mine    = $is_demo ? null : sm_theme_data( 'my_products', array( 'status' => 'published' ) );
$recent  = is_array( $mine ) && isset( $mine['items'] ) ? array_slice( (array) $mine['items'], 0, 5 ) : sm_theme_listings( array( 'company' => $account['company']['slug'], 'per_page' => 5 ) )['items'];
$period  = '12m';
$cur     = is_array( $stats ) ? (array) $stats['periods'][ $period ] : null;
$tot     = $cur ? (array) $cur['totals'] : array( 'sold' => 0, 'bought' => 0, 'sold_kg' => 0, 'bought_kg' => 0, 'sold_n' => 0, 'bought_n' => 0, 'no_weight' => 0 );
$markets = sm_market_labels();
$first   = trim( strtok( (string) ( $account['user'] ?? '' ), ' ' ) );

// Short amounts (20,6 M DA) and weights (86,4 t), the same as overview.js.
$money_short = static function ( $v ) {
	$v = (float) $v;
	if ( $v >= 1000000 ) {
		return sm_number( $v / 1000000, 1 ) . ' M';
	}
	return sm_number( round( $v ) );
};
$weight_short = static function ( $kg ) {
	$kg = (float) $kg;
	return $kg >= 1000 ? array( sm_number( $kg / 1000, 1 ), 't' ) : array( sm_number( round( $kg ) ), 'kg' );
};
$delta = static function ( $now, $before ) {
	if ( (float) $before <= 0 ) {
		return '';
	}
	$pct = round( ( (float) $now - (float) $before ) / (float) $before * 100 );
	return '<span class="smo-delta' . ( $pct < 0 ? ' is-down' : '' ) . '" data-smo-delta>' . esc_html( ( $pct > 0 ? '+' : ( $pct < 0 ? '−' : '' ) ) . sm_number( abs( $pct ) ) . ' %' ) . '</span>';
};

// What waits for an answer: label, count, view.
$waiting = (array) ( $stats['waiting'] ?? array() );
$todo    = array(
	array( __( 'Orders and offers waiting for your answer', 'sm-marketplace' ), (int) ( $waiting['seller'] ?? 0 ) + (int) ( $waiting['buyer'] ?? 0 ), (int) ( $waiting['seller'] ?? 0 ) > 0 || empty( $waiting['buyer'] ) ? 'sales' : 'purchases', true ),
	array( __( 'Visit requests', 'sm-marketplace' ), (int) ( $badges['visits'] ?? 0 ), 'visits', false ),
	array( __( 'New enquiries', 'sm-marketplace' ), (int) ( $badges['messages'] ?? 0 ), 'messages', false ),
	array( __( 'Service requests received', 'sm-marketplace' ), (int) ( $badges['service-requests'] ?? 0 ), 'service-requests', false ),
);
if ( ! isset( $badges['service-requests'] ) ) {
	array_pop( $todo );
}
$todo_n = array_sum( array_column( $todo, 1 ) );

// Steel on sale by market, for the ring.
$ring_colors = array( 'products' => 'var(--sm-accent)', 'buildings' => 'var(--sm-primary)', 'reuse' => '#3B82F6' );
$ring        = array();
$offset      = 25; // Start at the top.
foreach ( array( 'products', 'buildings', 'reuse' ) as $m ) {
	$kg = (float) ( $stock['markets'][ $m ]['kg'] ?? 0 );
	if ( $kg <= 0 || $stock['kg'] <= 0 ) {
		continue;
	}
	$share  = $kg / $stock['kg'] * 100;
	$ring[] = array( $m, $share, $offset, $kg, (int) $stock['markets'][ $m ]['count'] );
	$offset = $offset - $share;
}
?>
<div class="smo" data-smo data-period="<?php echo esc_attr( $period ); ?>">
	<section class="smo-hero">
		<div>
			<h2 class="smo-hero__title">
				<?php
				/* translators: %s: first name. */
				echo esc_html( '' !== $first ? sprintf( __( 'Hello, %s', 'sm-marketplace' ), $first ) : __( 'Hello', 'sm-marketplace' ) );
				?>
			</h2>
			<p class="smo-hero__sub">
				<?php echo esc_html( is_array( $account['company']['name'] ) ? sm_t( $account['company']['name'] ) : (string) $account['company']['name'] ); ?>
				<?php if ( $level >= 2 ) : ?> · <span class="smo-hero__ok"><?php sm_icon( 'verified' ); ?><?php esc_html_e( 'Verified', 'sm-marketplace' ); ?></span><?php endif; ?>
			</p>
		</div>
		<?php if ( $stats ) : ?>
			<div class="smo-period" role="group" aria-label="<?php esc_attr_e( 'Period', 'sm-marketplace' ); ?>">
				<?php
				foreach ( array(
					'30d' => __( '30 days', 'sm-marketplace' ),
					'3m'  => __( '3 months', 'sm-marketplace' ),
					'12m' => __( '12 months', 'sm-marketplace' ),
					'all' => __( 'All time', 'sm-marketplace' ),
				) as $key => $label ) :
					?>
					<button type="button" data-smo-period="<?php echo esc_attr( $key ); ?>" aria-pressed="<?php echo $key === $period ? 'true' : 'false'; ?>"><?php echo esc_html( $label ); ?></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</section>

	<ul class="smo-kpis" role="list">
		<li class="smo-kpi">
			<p class="smo-kpi__label"><span class="smo-ico smo-ico--up" aria-hidden="true"><?php sm_icon( 'arrow-up-right' ); ?></span><?php esc_html_e( 'Amount sold', 'sm-marketplace' ); ?></p>
			<p class="smo-kpi__value"><span data-smo-v="sold" data-money><?php echo esc_html( $money_short( $tot['sold'] ) ); ?></span> <small><?php echo esc_html( sm_currency_label() ); ?></small></p>
			<p class="smo-kpi__sub"><?php echo $cur ? $delta( $tot['sold'], $cur['prev']['sold'] ?? 0 ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?><span data-smo-n="sold" data-one="<?php esc_attr_e( '%s accepted sale', 'sm-marketplace' ); ?>" data-many="<?php esc_attr_e( '%s accepted sales', 'sm-marketplace' ); ?>"><?php echo esc_html( sprintf( _n( '%s accepted sale', '%s accepted sales', (int) $tot['sold_n'], 'sm-marketplace' ), sm_number( (int) $tot['sold_n'] ) ) ); ?></span></p>
			<svg class="smo-spark smo-spark--sold" viewBox="0 0 120 36" aria-hidden="true" data-smo-spark="sold"></svg>
		</li>
		<li class="smo-kpi">
			<p class="smo-kpi__label"><span class="smo-ico smo-ico--down" aria-hidden="true"><?php sm_icon( 'arrow-down-left' ); ?></span><?php esc_html_e( 'Amount bought', 'sm-marketplace' ); ?></p>
			<p class="smo-kpi__value"><span data-smo-v="bought" data-money><?php echo esc_html( $money_short( $tot['bought'] ) ); ?></span> <small><?php echo esc_html( sm_currency_label() ); ?></small></p>
			<p class="smo-kpi__sub"><?php echo $cur ? $delta( $tot['bought'], $cur['prev']['bought'] ?? 0 ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?><span data-smo-n="bought" data-one="<?php esc_attr_e( '%s accepted purchase', 'sm-marketplace' ); ?>" data-many="<?php esc_attr_e( '%s accepted purchases', 'sm-marketplace' ); ?>"><?php echo esc_html( sprintf( _n( '%s accepted purchase', '%s accepted purchases', (int) $tot['bought_n'], 'sm-marketplace' ), sm_number( (int) $tot['bought_n'] ) ) ); ?></span></p>
			<svg class="smo-spark smo-spark--bought" viewBox="0 0 120 36" aria-hidden="true" data-smo-spark="bought"></svg>
		</li>
		<li class="smo-kpi">
			<?php list( $sv, $su ) = $weight_short( $stock['kg'] ); ?>
			<p class="smo-kpi__label"><span class="smo-ico smo-ico--dark" aria-hidden="true"><?php sm_icon( 'layers' ); ?></span><?php esc_html_e( 'Steel on sale', 'sm-marketplace' ); ?></p>
			<p class="smo-kpi__value"><span data-smo-count="<?php echo esc_attr( $stock['kg'] >= 1000 ? round( $stock['kg'] / 1000, 1 ) : round( $stock['kg'] ) ); ?>" data-dec="<?php echo $stock['kg'] >= 1000 ? 1 : 0; ?>"><?php echo esc_html( $sv ); ?></span> <small><?php echo esc_html( $su ); ?></small></p>
			<p class="smo-kpi__sub">
				<?php
				/* translators: 1: number of listings, 2: their value. */
				echo esc_html( sprintf( _n( '%1$s listing online · %2$s', '%1$s listings online · %2$s', (int) $stock['count'], 'sm-marketplace' ), sm_number( (int) $stock['count'] ), $money_short( $stock['value'] ) . ' ' . sm_currency_label() ) );
				?>
			</p>
		</li>
		<li class="smo-kpi">
			<?php list( $xv, $xu ) = $weight_short( $tot['sold_kg'] + $tot['bought_kg'] ); ?>
			<p class="smo-kpi__label"><span class="smo-ico smo-ico--warm" aria-hidden="true"><?php sm_icon( 'scale' ); ?></span><?php esc_html_e( 'Steel traded', 'sm-marketplace' ); ?></p>
			<p class="smo-kpi__value"><span data-smo-v="kg" data-weight><?php echo esc_html( $xv ); ?></span> <small data-smo-unit><?php echo esc_html( $xu ); ?></small></p>
			<p class="smo-kpi__sub" data-smo-kgsplit data-tpl="<?php esc_attr_e( '%1$s sold · %2$s bought', 'sm-marketplace' ); ?>">
				<?php
				$a = $weight_short( $tot['sold_kg'] );
				$b = $weight_short( $tot['bought_kg'] );
				/* translators: 1: weight sold, 2: weight bought. */
				echo esc_html( sprintf( __( '%1$s sold · %2$s bought', 'sm-marketplace' ), $a[0] . ' ' . $a[1], $b[0] . ' ' . $b[1] ) );
				?>
			</p>
		</li>
	</ul>

	<?php if ( $stats ) : ?>
		<section class="sm-panel smo-chart-card">
			<div class="smo-chart-head">
				<h2 class="sm-panel__title"><?php esc_html_e( 'Sales and purchases', 'sm-marketplace' ); ?></h2>
				<div class="smo-tabs" role="group" aria-label="<?php esc_attr_e( 'Measure', 'sm-marketplace' ); ?>">
					<button type="button" data-smo-measure="amount" aria-pressed="true"><?php echo esc_html( sprintf( /* translators: %s: currency. */ __( 'Amount (%s)', 'sm-marketplace' ), sm_currency_label() ) ); ?></button>
					<button type="button" data-smo-measure="kg" aria-pressed="false"><?php esc_html_e( 'Weight (t)', 'sm-marketplace' ); ?></button>
					<button type="button" data-smo-measure="n" aria-pressed="false"><?php esc_html_e( 'Number of orders', 'sm-marketplace' ); ?></button>
				</div>
			</div>
			<p class="smo-legend"><span><i class="is-sold"></i><?php esc_html_e( 'Total sold', 'sm-marketplace' ); ?></span><span><i class="is-bought"></i><?php esc_html_e( 'Total bought', 'sm-marketplace' ); ?></span></p>
			<div class="smo-chart" data-smo-chart>
				<svg viewBox="0 0 820 260" role="img" aria-label="<?php esc_attr_e( 'Sales and purchases per period', 'sm-marketplace' ); ?>"></svg>
				<div class="smo-tip" data-smo-tip hidden></div>
				<p class="smo-empty" data-smo-empty hidden><?php esc_html_e( 'No accepted sale or purchase in this period yet.', 'sm-marketplace' ); ?></p>
			</div>
			<p class="sm-help smo-note">
				<?php esc_html_e( 'Orders and offers accepted on ReuSteel. Payment is made outside the platform: these are the amounts agreed, not money received.', 'sm-marketplace' ); ?>
				<span data-smo-noweight data-tpl="<?php esc_attr_e( '%s orders without a steel weight (sold per m² or per piece) count in amounts only.', 'sm-marketplace' ); ?>"<?php echo $tot['no_weight'] ? '' : ' hidden'; ?>><?php echo esc_html( sprintf( __( '%s orders without a steel weight (sold per m² or per piece) count in amounts only.', 'sm-marketplace' ), sm_number( (int) $tot['no_weight'] ) ) ); ?></span>
			</p>
			<script type="application/json" data-smo-data><?php echo wp_json_encode( array( 'periods' => $stats['periods'], 'currency' => sm_currency_label(), 'lang' => sm_theme_current_language(), 'i18n' => array( 'sold' => __( 'Total sold', 'sm-marketplace' ), 'bought' => __( 'Total bought', 'sm-marketplace' ), 'sales' => __( 'sales', 'sm-marketplace' ), 'purchases' => __( 'purchases', 'sm-marketplace' ), 'sale' => __( 'sale', 'sm-marketplace' ), 'purchase' => __( 'purchase', 'sm-marketplace' ) ) ) ); ?></script>
		</section>
	<?php endif; ?>

	<div class="smo-two">
		<section class="sm-panel smo-stock">
			<div class="smo-chart-head">
				<h2 class="sm-panel__title"><?php esc_html_e( 'My steel on sale', 'sm-marketplace' ); ?></h2>
				<?php if ( $stock['kg'] > 0 ) : ?><span class="sm-muted"><?php echo sm_kg( $stock['kg'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php endif; ?>
			</div>
			<?php if ( $ring ) : ?>
				<div class="smo-ring">
					<svg viewBox="0 0 42 42" aria-hidden="true">
						<circle cx="21" cy="21" r="15.915" class="smo-ring__bg"/>
						<?php foreach ( $ring as $n => $r ) : ?>
							<circle cx="21" cy="21" r="15.915" class="smo-ring__part" style="stroke:<?php echo esc_attr( $ring_colors[ $r[0] ] ); ?>;stroke-dasharray:<?php echo esc_attr( round( $r[1], 2 ) . ' ' . round( 100 - $r[1], 2 ) ); ?>;stroke-dashoffset:<?php echo esc_attr( round( $r[2], 2 ) ); ?>;--smo-delay:<?php echo (int) $n * 120; ?>ms"/>
						<?php endforeach; ?>
						<text x="21" y="22.6" text-anchor="middle"><?php echo esc_html( sm_number( (int) $stock['count'] ) ); ?></text>
					</svg>
					<dl class="smo-rows">
						<?php foreach ( $ring as $r ) : ?>
							<div><dt><i style="background:<?php echo esc_attr( $ring_colors[ $r[0] ] ); ?>"></i><?php echo esc_html( $markets[ $r[0] ] ?? $r[0] ); ?> <small>(<?php echo esc_html( sm_number( $r[4] ) ); ?>)</small></dt><dd><?php echo esc_html( implode( ' ', $weight_short( $r[3] ) ) ); ?></dd></div>
						<?php endforeach; ?>
					</dl>
				</div>
			<?php else : ?>
				<p class="sm-muted"><?php esc_html_e( 'No steel on sale at the moment.', 'sm-marketplace' ); ?></p>
				<a class="sm-btn sm-btn--accent" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'publish' ) ) ); ?>"><?php sm_icon( 'plus' ); ?><?php esc_html_e( 'Publish a listing', 'sm-marketplace' ); ?></a>
			<?php endif; ?>
			<?php if ( $stock['count'] ) : ?>
				<p class="smo-views"><span><?php esc_html_e( 'Views of your listings online', 'sm-marketplace' ); ?></span><strong><?php echo esc_html( sm_number( (int) $stock['views'] ) ); ?></strong></p>
			<?php endif; ?>
		</section>

		<section class="sm-panel smo-todo">
			<h2 class="sm-panel__title"><?php esc_html_e( 'To do', 'sm-marketplace' ); ?></h2>
			<?php if ( ! $todo_n ) : ?>
				<p class="smo-calm"><?php sm_icon( 'check' ); ?><?php esc_html_e( 'Nothing waits for you. Well done.', 'sm-marketplace' ); ?></p>
			<?php endif; ?>
			<ul role="list">
				<?php foreach ( $todo as list( $label, $n, $view, $hot ) ) : ?>
					<li><a href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => $view ) ) ); ?>" class="<?php echo $n ? 'is-on' : ''; ?>"><span><?php echo esc_html( $label ); ?></span><b class="<?php echo $n && $hot ? 'is-hot' : ''; ?>"><?php echo esc_html( sm_number( $n ) ); ?></b></a></li>
				<?php endforeach; ?>
			</ul>
		</section>
	</div>

	<div class="smo-two">
		<section class="sm-panel">
			<div class="sm-panel__head">
				<h2 class="sm-panel__title"><?php esc_html_e( 'Recent listings', 'sm-marketplace' ); ?></h2>
				<?php if ( $recent ) : ?><a class="sm-link" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'listings' ) ) ); ?>"><?php esc_html_e( 'Manage', 'sm-marketplace' ); ?><?php sm_icon( 'arrow-end' ); ?></a><?php endif; ?>
			</div>
			<?php if ( $recent ) : ?>
				<ul class="sm-rowlist">
					<?php foreach ( $recent as $item ) : ?>
						<li>
							<img src="<?php echo esc_url( sm_image_url( $item['image'] ) ); ?>" alt="" width="64" height="48" loading="lazy">
							<div><a href="<?php echo esc_url( sm_page_url( 'listing', array( 'id' => $item['id'] ) ) ); ?>"><?php sm_e( $item['title'] ); ?></a><small><?php echo esc_html( $markets[ $item['market'] ] ?? '' ); ?> · <?php echo esc_html( sm_ago( $item['date'] ) ); ?><?php if ( isset( $item['views'] ) ) : ?> · <?php echo esc_html( sprintf( _n( '%s view', '%s views', (int) $item['views'], 'sm-marketplace' ), sm_number( (int) $item['views'] ) ) ); ?><?php endif; ?></small></div>
							<strong><?php echo ! empty( $item['building_sale']['lot_total'] ) ? esc_html( sm_money( $item['building_sale']['lot_total'] ) ) : sm_listing_price( $item, false ); // phpcs:ignore WordPress.Security.EscapeOutput ?></strong>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="sm-muted"><?php esc_html_e( 'You have no listing yet.', 'sm-marketplace' ); ?></p>
			<?php endif; ?>
		</section>

		<section class="sm-panel">
			<h2 class="sm-panel__title"><?php esc_html_e( 'Account and plan', 'sm-marketplace' ); ?></h2>
			<?php sm_component( 'verification-steps', array( 'account' => $account ) ); ?>
			<?php
			$meters = array(
				'listings'   => __( 'Listings', 'sm-marketplace' ),
				'featured'   => __( 'Featured slots', 'sm-marketplace' ),
				'ai_credits' => __( 'AI credits used', 'sm-marketplace' ),
			);
			foreach ( $meters as $key => $label ) :
				if ( empty( $usage[ $key ] ) ) {
					continue;
				}
				list( $used, $max ) = $usage[ $key ];
				?>
				<div class="sm-meter">
					<div class="sm-meter__label"><span><?php echo esc_html( $label ); ?></span><span><bdi dir="ltr"><?php echo esc_html( $used . ' / ' . $max ); ?></bdi></span></div>
					<div class="sm-meter__bar" role="progressbar" aria-valuemin="0" aria-valuemax="<?php echo esc_attr( $max ); ?>" aria-valuenow="<?php echo esc_attr( $used ); ?>" aria-label="<?php echo esc_attr( $label ); ?>"><span style="inline-size:<?php echo esc_attr( min( 100, round( $used / max( 1, $max ) * 100 ) ) ); ?>%"></span></div>
				</div>
			<?php endforeach; ?>
			<?php if ( $usage ) : ?>
				<a class="sm-btn sm-btn--ghost sm-btn--block" href="<?php echo esc_url( sm_page_url( 'pricing' ) ); ?>"><?php esc_html_e( 'Change plan', 'sm-marketplace' ); ?></a>
			<?php endif; ?>
		</section>
	</div>

	<section class="sm-panel">
		<h2 class="sm-panel__title"><?php esc_html_e( 'Quick actions', 'sm-marketplace' ); ?></h2>
		<ul class="sm-quick">
			<li><a href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'publish', 'market' => 'products' ) ) ); ?>"><?php sm_icon( 'layers' ); ?><?php esc_html_e( 'Sell new steel', 'sm-marketplace' ); ?></a></li>
			<li><a href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'publish', 'market' => 'reuse' ) ) ); ?>"><?php sm_icon( 'recycle' ); ?><?php esc_html_e( 'Sell offcuts or surplus', 'sm-marketplace' ); ?></a></li>
			<li><a href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'publish', 'market' => 'buildings' ) ) ); ?>"><?php sm_icon( 'warehouse' ); ?><?php esc_html_e( 'Sell a building', 'sm-marketplace' ); ?></a></li>
			<li><a href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'company' ) ) ); ?>"><?php sm_icon( 'company' ); ?><?php esc_html_e( 'Complete the company profile', 'sm-marketplace' ); ?></a></li>
		</ul>
	</section>
</div>

<?php
/**
 * Business report of the steel market (0.53.0, sm-insights 0.1.0): filters (period,
 * market, region), « What to remember » written by the AI next to the circle of
 * the steel on sale, figures, average asked prices with their curve, best-selling
 * (ranking only) and most searched profiles, what is asked and little offered,
 * figures per region. « Download as PDF » prints the page (any language).
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$in = array(
	'period' => sanitize_key( (string) sm_theme_query_arg( 'period', '30' ) ),
	'market' => sanitize_key( (string) sm_theme_query_arg( 'market', 'all' ) ),
	'region' => sanitize_key( (string) sm_theme_query_arg( 'region', '' ) ),
);
$d = sm_theme_data( 'insights_report', $in );
if ( ! is_array( $d ) || 'none' === ( $d['state'] ?? '' ) ) {
	sm_component( 'empty-state', array( 'icon' => 'chart', 'title' => __( 'Business report', 'sm-marketplace' ), 'text' => __( 'This module is not active yet.', 'sm-marketplace' ) ) );
	return;
}
if ( 'ok' !== $d['state'] ) : ?>
	<section class="sm-panel smbr-upgrade">
		<span class="smbr-upgrade__icon"><?php sm_icon( 'chart' ); ?></span>
		<h2><?php esc_html_e( 'The Business report is included in the Business plan', 'sm-marketplace' ); ?></h2>
		<p><?php esc_html_e( 'Every day: the average asked prices of new and reused steel, the steel on sale (new, reuse, buildings), the most searched and best-selling profiles, what is asked and little offered, by region — with a summary written by the AI.', 'sm-marketplace' ); ?></p>
		<a class="sm-btn sm-btn--primary" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'plan' ) ) ); ?>"><?php esc_html_e( 'Move to the Business plan', 'sm-marketplace' ); ?><?php sm_icon( 'arrow-end' ); ?></a>
	</section>
	<?php
	return;
endif;

$a    = $d['args'];
$r    = $d['report'];
$mine = $d['mine'];
$opt  = $d['options'];
$cur  = sm_currency_label();
$url  = static fn( $k, $v ) => sm_page_url( 'account', array_filter( array_merge( $a, array( 'view' => 'report', $k => $v ) ), static fn( $x ) => '' !== $x && null !== $x ) );
$num  = static fn( $v, $dec = 2 ) => null === $v ? '' : sm_number( $v, $dec );
$chg  = static function ( $v, $unit = '%' ) {
	if ( null === $v ) {
		return '<span class="smbr-chg is-none">—</span>';
	}
	$cls  = abs( $v ) < 0.5 ? 'is-eq' : ( $v > 0 ? 'is-up' : 'is-down' );
	$sign = 'is-eq' === $cls ? '=' : ( $v > 0 ? '▲' : '▼' );
	return '<span class="smbr-chg ' . $cls . '">' . $sign . "\u{00A0}" . esc_html( sm_number( abs( $v ), '%' === $unit ? 1 : 0 ) . ( '%' === $unit ? "\u{00A0}%" : '' ) ) . '</span>';
};
$show_new   = 'reuse' !== $a['market'];
$show_reuse = 'new' !== $a['market'];
$not_enough = '<span class="smbr-na">' . esc_html__( 'not enough listings', 'sm-marketplace' ) . '</span>';
$w          = $r['weights'];
wp_enqueue_script( 'sm-report', SM_THEME_URI . '/assets/js/report.js', array(), sm_theme_asset_version( 'assets/js/report.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
?>
<div class="smbr">
	<p class="smbr-sub">
		<span class="smbr-badge"><?php esc_html_e( 'Business plan', 'sm-marketplace' ); ?></span>
		<span><?php echo esc_html( sprintf( /* translators: 1: start date, 2: end date. */ __( 'From %1$s to %2$s', 'sm-marketplace' ), sm_date( $r['from'] ), sm_date( $r['to'] ) ) ); ?> · <?php echo esc_html( sprintf( /* translators: %s: date. */ __( 'updated on %s', 'sm-marketplace' ), sm_date( gmdate( 'Y-m-d', (int) $r['at'] ) ) ) ); ?> · <?php esc_html_e( 'prices asked by the sellers on ReuSteel, not sale prices.', 'sm-marketplace' ); ?></span>
	</p>

	<nav class="smbr-bar" aria-label="<?php esc_attr_e( 'Report filters', 'sm-marketplace' ); ?>">
		<?php
		foreach ( array(
			'period' => __( 'Period', 'sm-marketplace' ),
			'market' => __( 'Market', 'sm-marketplace' ),
			'region' => __( 'Region', 'sm-marketplace' ),
		) as $k => $label ) :
			?>
			<div class="smbr-f"><span class="smbr-f__l"><?php echo esc_html( $label ); ?></span>
				<div class="smbr-tags">
					<?php foreach ( $opt[ $k ] as $v => $text ) : ?>
						<a class="smbr-tag<?php echo (string) $a[ $k ] === (string) $v ? ' is-on' : ''; ?>" href="<?php echo esc_url( $url( $k, (string) $v ) ); ?>"<?php echo (string) $a[ $k ] === (string) $v ? ' aria-current="true"' : ''; ?>><?php echo esc_html( $text ); ?></a>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endforeach; ?>
		<button type="button" class="sm-btn sm-btn--ghost sm-btn--sm smbr-pdf" data-smbr-print><?php sm_icon( 'download' ); ?><span><?php esc_html_e( 'Download as PDF', 'sm-marketplace' ); ?></span></button>
	</nav>

	<div class="smbr-hero<?php echo $d['summary'] || $mine['note'] ? '' : ' is-single'; ?>">
		<?php if ( $d['summary'] || $mine['note'] ) : ?>
			<section class="sm-panel smbr-sum">
				<h2 class="sm-panel__title"><?php if ( $d['summary'] ) { sm_ai_badge(); } ?> <?php esc_html_e( 'What to remember', 'sm-marketplace' ); ?></h2>
				<ul>
					<?php foreach ( $d['summary'] as $p ) : ?><li><?php echo esc_html( $p ); ?></li><?php endforeach; ?>
					<?php if ( $mine['note'] ) : ?><li class="smbr-sum__mine"><?php echo esc_html( $mine['note'] ); ?></li><?php endif; ?>
				</ul>
			</section>
		<?php endif; ?>
		<section class="sm-panel smbr-donut">
			<h2 class="sm-panel__title"><?php esc_html_e( 'Steel on the market', 'sm-marketplace' ); ?></h2>
			<?php
			$parts = array(
				'products'  => array( __( 'New steel', 'sm-marketplace' ), '#06CE77' ),
				'reuse'     => array( __( 'Reuse', 'sm-marketplace' ), '#3B82F6' ),
				'buildings' => array( __( 'Buildings', 'sm-marketplace' ), '#F59E0B' ),
			);
			$off = 25; // Starts at the top.
			?>
			<div class="smbr-donut__in">
				<svg class="smbr-donut__svg" viewBox="0 0 42 42" role="img" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: tonnes. */ __( '%s t of steel on sale', 'sm-marketplace' ), sm_number( $w['total_t'], 1 ) ) ); ?>">
					<circle cx="21" cy="21" r="15.915" fill="none" stroke="var(--smbr-ring)" stroke-width="6"/>
					<?php
					foreach ( $parts as $k => $p ) :
						$pct = (float) $w[ $k . '_pct' ];
						if ( $pct <= 0 ) {
							continue;
						}
						?>
						<circle class="smbr-donut__arc" cx="21" cy="21" r="15.915" fill="none" stroke="<?php echo esc_attr( $p[1] ); ?>" stroke-width="6" stroke-dasharray="<?php echo esc_attr( round( $pct, 2 ) . ' ' . round( 100 - $pct, 2 ) ); ?>" stroke-dashoffset="<?php echo esc_attr( (string) round( $off, 2 ) ); ?>" pathLength="100" style="--smbr-len:<?php echo esc_attr( (string) round( $pct, 2 ) ); ?>"/>
						<?php
						$off -= $pct;
					endforeach;
					?>
				</svg>
				<p class="smbr-donut__total"><b dir="ltr"><?php echo esc_html( sm_number( $w['total_t'], $w['total_t'] < 100 ? 1 : 0 ) . "\u{00A0}t" ); ?></b><span><?php esc_html_e( 'in all', 'sm-marketplace' ); ?></span></p>
				<ul class="smbr-donut__lg">
					<?php foreach ( $parts as $k => $p ) : ?>
						<li><i style="background:<?php echo esc_attr( $p[1] ); ?>"></i><span><?php echo esc_html( $p[0] ); ?></span><b><bdi><?php echo esc_html( sm_number( $w[ $k . '_t' ], $w[ $k . '_t' ] < 100 ? 1 : 0 ) . "\u{00A0}t" ); ?></bdi> · <bdi><?php echo esc_html( sm_number( $w[ $k . '_pct' ], 0 ) . "\u{00A0}%" ); ?></bdi></b></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	</div>

	<div class="smbr-kpis">
		<?php
		foreach ( array(
			'listings'  => __( 'Listings online', 'sm-marketplace' ),
			'searches'  => __( 'Searches', 'sm-marketplace' ),
			'rfqs'      => __( 'Requests for quotation', 'sm-marketplace' ),
			'buildings' => __( 'Buildings on sale', 'sm-marketplace' ),
		) as $k => $label ) :
			$x = $r['kpis'][ $k ];
			?>
			<div class="smbr-kpi"><small><?php echo esc_html( $label ); ?></small><b><?php echo esc_html( sm_number( $x['value'] ) ); ?></b> <?php echo 'buildings' === $k ? $chg( $x['before'] ? $x['diff'] : null, '' ) : $chg( $x['change'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		<?php endforeach; ?>
	</div>

	<section class="sm-panel smbr-prices">
		<h2 class="sm-panel__title"><?php echo esc_html( sprintf( /* translators: %s: currency. */ __( 'Average asked prices (%s/kg)', 'sm-marketplace' ), $cur ) ); ?></h2>
		<?php if ( ! $r['prices'] ) : ?>
			<p class="sm-muted"><?php esc_html_e( 'Not enough listings yet for an average on this choice.', 'sm-marketplace' ); ?></p>
		<?php else : ?>
			<div class="smbr-table-wrap"><table class="smbr-table">
				<thead><tr>
					<th><?php esc_html_e( 'Profile', 'sm-marketplace' ); ?></th>
					<?php if ( $show_new ) : ?><th class="n"><?php esc_html_e( 'New steel', 'sm-marketplace' ); ?></th><th class="n"><?php esc_html_e( 'Change over the period', 'sm-marketplace' ); ?></th><?php endif; ?>
					<?php if ( $show_reuse ) : ?><th class="n"><?php esc_html_e( 'Reuse', 'sm-marketplace' ); ?></th><?php endif; ?>
					<?php if ( $show_new && $show_reuse ) : ?><th class="n"><?php esc_html_e( 'Reuse gap', 'sm-marketplace' ); ?></th><?php endif; ?>
					<?php if ( ! $show_new ) : ?><th class="n"><?php esc_html_e( 'Change over the period', 'sm-marketplace' ); ?></th><?php endif; ?>
					<th class="n"><?php esc_html_e( 'Your price', 'sm-marketplace' ); ?></th>
				</tr></thead>
				<tbody>
				<?php
				foreach ( $r['prices'] as $i => $row ) :
					$own   = $mine['prices'][ $row['family'] ] ?? array();
					$own_c = $own['new'] ?? ( $own['reuse'] ?? null );
					$more  = $row['details'] || $row['series'];
					?>
					<tr class="smbr-row">
						<td><?php if ( $more ) : ?><button type="button" class="smbr-more" aria-expanded="false" aria-controls="smbr-d<?php echo (int) $i; ?>"><?php sm_icon( 'chevron-down' ); ?><span><?php echo esc_html( $row['label'] ); ?></span></button><?php else : ?><?php echo esc_html( $row['label'] ); ?><?php endif; ?></td>
						<?php if ( $show_new ) : ?>
							<td class="n"><?php echo null === $row['new'] ? $not_enough : esc_html( $num( $row['new'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
							<td class="n"><?php echo $chg( $row['new_change'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
						<?php endif; ?>
						<?php if ( $show_reuse ) : ?><td class="n"><?php echo null === $row['reuse'] ? $not_enough : esc_html( $num( $row['reuse'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td><?php endif; ?>
						<?php if ( $show_new && $show_reuse ) : ?><td class="n"><?php echo null === $row['gap'] ? '<span class="smbr-na">—</span>' : esc_html( ( $row['gap'] > 0 ? '+' : '−' ) . sm_number( abs( $row['gap'] ), 0 ) . "\u{00A0}%" ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td><?php endif; ?>
						<?php if ( ! $show_new ) : ?><td class="n"><?php echo $chg( $row['reuse_change'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td><?php endif; ?>
						<td class="n"><?php if ( $own_c ) : ?><span class="smbr-mine"><?php echo esc_html( $num( $own_c['price'] ) ); ?><?php if ( null !== $own_c['diff'] ) : ?> (<?php echo esc_html( ( $own_c['diff'] >= 0 ? '+' : '−' ) . sm_number( abs( $own_c['diff'] ), 0 ) . "\u{00A0}%" ); ?>)<?php endif; ?></span><?php else : ?><span class="smbr-na">—</span><?php endif; ?></td>
					</tr>
					<?php if ( $more ) : ?>
						<tr class="smbr-detail" id="smbr-d<?php echo (int) $i; ?>" hidden><td colspan="6">
							<?php
							if ( $row['series'] ) :
								$vals = array_filter( array_column( $row['series'], 'price' ), static fn( $v ) => null !== $v );
								$lo   = min( $vals );
								$hi   = max( $vals );
								$span = max( 1, $hi - $lo );
								$n    = count( $row['series'] );
								$pts  = array();
								foreach ( $row['series'] as $j => $s ) {
									if ( null !== $s['price'] ) {
										$pts[] = round( 10 + $j * 280 / max( 1, $n - 1 ), 1 ) . ',' . round( 70 - ( $s['price'] - $lo ) / $span * 55, 1 );
									}
								}
								?>
								<figure class="smbr-curve">
									<figcaption><?php echo esc_html( sprintf( /* translators: 1: type, 2: new or reuse. */ __( '%1$s, %2$s: average asked price over 12 months', 'sm-marketplace' ), $row['label'], 'new' === $row['series_market'] ? __( 'new', 'sm-marketplace' ) : __( 'reuse', 'sm-marketplace' ) ) ); ?></figcaption>
									<svg viewBox="0 0 300 80" role="img" aria-label="<?php echo esc_attr( sm_number( $lo ) . ' – ' . sm_number( $hi ) . ' ' . $cur . '/kg' ); ?>"><polyline points="<?php echo esc_attr( implode( ' ', $pts ) ); ?>" fill="none" stroke="#06CE77" stroke-width="2.5" stroke-linejoin="round"/></svg>
									<p class="smbr-curve__axis"><span><?php echo esc_html( sm_number( $lo ) ); ?></span><span><?php echo esc_html( sm_number( $hi ) . ' ' . $cur . '/kg' ); ?></span></p>
								</figure>
							<?php endif; ?>
							<?php if ( $row['details'] ) : ?>
								<ul class="smbr-sizes">
									<?php foreach ( $row['details'] as $x ) : ?>
										<li><bdi><?php echo esc_html( $x['designation'] ); ?></bdi><?php if ( null !== $x['new'] ) : ?><span><?php esc_html_e( 'New steel', 'sm-marketplace' ); ?> <b><?php echo esc_html( $num( $x['new'] ) ); ?></b></span><?php endif; ?><?php if ( null !== $x['reuse'] ) : ?><span><?php esc_html_e( 'Reuse', 'sm-marketplace' ); ?> <b><?php echo esc_html( $num( $x['reuse'] ) ); ?></b></span><?php endif; ?></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</td></tr>
					<?php endif; ?>
				<?php endforeach; ?>
				</tbody>
			</table></div>
			<p class="smbr-foot"><?php esc_html_e( 'Click on a type to see its sizes and its curve over 12 months. An average is shown only when there are enough listings.', 'sm-marketplace' ); ?></p>
		<?php endif; ?>
	</section>

	<div class="smbr-two">
		<section class="sm-panel">
			<h2 class="sm-panel__title"><?php esc_html_e( 'Best-selling', 'sm-marketplace' ); ?></h2>
			<?php if ( $r['top_sold'] ) : ?>
				<ol class="smbr-rank"><?php foreach ( $r['top_sold'] as $x ) : ?><li><bdi><?php echo esc_html( $x ); ?></bdi></li><?php endforeach; ?></ol>
			<?php else : ?>
				<p class="sm-muted"><?php esc_html_e( 'No sale accepted on this choice yet.', 'sm-marketplace' ); ?></p>
			<?php endif; ?>
		</section>
		<section class="sm-panel">
			<h2 class="sm-panel__title"><?php esc_html_e( 'Most searched', 'sm-marketplace' ); ?></h2>
			<?php if ( $r['top_searched'] ) : ?>
				<?php $max = max( 1, $r['top_searched'][0]['count'] ); ?>
				<ol class="smbr-rank is-bars">
					<?php foreach ( $r['top_searched'] as $x ) : ?>
						<li><bdi><?php echo esc_html( $x['label'] ); ?></bdi><b><?php echo esc_html( sm_number( $x['count'] ) ); ?></b><span class="smbr-meter" aria-hidden="true"><i style="width:<?php echo esc_attr( (string) round( $x['count'] / $max * 100 ) ); ?>%"></i></span></li>
					<?php endforeach; ?>
				</ol>
				<p class="smbr-foot"><?php esc_html_e( 'Searches of the catalogue and lines of the requests for quotation.', 'sm-marketplace' ); ?></p>
			<?php else : ?>
				<p class="sm-muted"><?php esc_html_e( 'The searches are counted from now: this list fills up with time.', 'sm-marketplace' ); ?></p>
			<?php endif; ?>
		</section>
	</div>

	<div class="smbr-two">
		<section class="sm-panel">
			<h2 class="sm-panel__title"><?php esc_html_e( 'Asked, little offered', 'sm-marketplace' ); ?></h2>
			<?php if ( $r['opportunities'] ) : ?>
				<?php foreach ( $r['opportunities'] as $o ) : ?>
					<div class="smbr-opp">
						<span><b><bdi><?php echo esc_html( $o['label'] ); ?></bdi></b> · <?php echo esc_html( sprintf( /* translators: 1: searches and requests, 2: listings. */ __( '%1$s searches and requests, %2$s listings', 'sm-marketplace' ), sm_number( $o['count'] ), sm_number( $o['listings'] ) ) ); ?></span>
						<?php if ( isset( $mine['stock'][ $o['term'] ] ) ) : ?>
							<a href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'stock' ) ) ); ?>"><?php echo esc_html( sprintf( /* translators: %s: tonnes. */ __( 'You have %s t of it', 'sm-marketplace' ), sm_number( $mine['stock'][ $o['term'] ], 1 ) ) ); ?></a>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<p class="sm-muted"><?php esc_html_e( 'Nothing stands out on this choice for now.', 'sm-marketplace' ); ?></p>
			<?php endif; ?>
		</section>
		<section class="sm-panel">
			<h2 class="sm-panel__title"><?php esc_html_e( 'By region (listings · requests)', 'sm-marketplace' ); ?></h2>
			<div class="smbr-regions">
				<?php foreach ( $r['regions'] as $k => $x ) : ?>
					<a class="smbr-region<?php echo $a['region'] === $k ? ' is-on' : ''; ?>" href="<?php echo esc_url( $url( 'region', $k ) ); ?>"><span><?php echo esc_html( $opt['region'][ $k ] ); ?></span><b><?php echo esc_html( sm_number( $x['listings'] ) . ' · ' . sm_number( $x['rfqs'] ) ); ?></b><?php if ( null !== $x['reuse_pct'] ) : ?><small><?php echo esc_html( sprintf( /* translators: %s: percentage. */ __( 'Reuse: %s %% of the weight', 'sm-marketplace' ), sm_number( $x['reuse_pct'] ) ) ); ?></small><?php endif; ?></a>
				<?php endforeach; ?>
			</div>
		</section>
	</div>

	<p class="smbr-foot"><?php esc_html_e( 'No company name, no quantity or amount of sale: only asked prices, weights on sale and numbers of listings, searches and requests. « Your price » and « You have » are seen by you only.', 'sm-marketplace' ); ?></p>
</div>

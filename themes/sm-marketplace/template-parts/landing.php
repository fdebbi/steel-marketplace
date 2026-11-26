<?php
/**
 * Page « type × wilaya » (0.60.0, sm-products 0.24.0): /produits/profiles/msila/ or
 * /reemploi/msila/. Real figures of the published listings only (number, weight,
 * range of the asked prices from 3 listings with a price per kilogram, last
 * update), the sizes offered (pages of the catalogue), the listings, the same type
 * in the other wilayas and the other types of the wilaya. Maquette m063.
 *
 * @var array $args { data: see SM\Products\Landing::data(), title: market title }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$d       = $args['data'];
$markets = sm_market_labels();
$wname   = sm_t( $d['w_name'] );
$what    = $d['category'] ? sm_t( $d['cat_name'] ) : ( $markets[ $d['market'] ] ?? $args['title'] );
/* translators: 1: type of product or market, 2: wilaya (e.g. « Profilés à M'Sila »). */
$title   = sprintf( __( '%1$s in %2$s', 'sm-marketplace' ), $what, $wname );
$market_url = sm_page_url( $d['market'] );
$crumbs  = array( array( $markets[ $d['market'] ] ?? $args['title'], $market_url ) );
if ( $d['category'] ) {
	$crumbs[] = array( sm_t( $d['cat_name'] ), add_query_arg( 'category', $d['category'], $market_url ) );
}
$crumbs[] = array( $wname, '' );
$rfq      = sm_page_url( 'account', array( 'view' => 'rfq', 'new' => 1 ) );
// « dans la wilaya de M'Sila », « dans la wilaya d’Alger » (elision before a vowel, 0.60.1).
$in_w     = preg_match( '/^[aeiouy]/i', remove_accents( $wname ) )
	/* translators: %s: wilaya whose name starts with a vowel (French: « dans la wilaya d’Alger »). */
	? sprintf( _x( 'in the wilaya of %s', 'before a vowel', 'sm-marketplace' ), $wname )
	/* translators: %s: wilaya. */
	: sprintf( __( 'in the wilaya of %s', 'sm-marketplace' ), $wname );
$t        = (float) $d['kg'] >= 1000 ? sm_number( $d['kg'] / 1000, 1 ) . "\u{00A0}" . ( 'ar' === sm_theme_current_language() ? 'طن' : 't' ) : sm_number( $d['kg'] ) . "\u{00A0}kg"; // 0.60.1: « طن » in Arabic.
?>
<section class="sm-pagehead sm-pagehead--<?php echo esc_attr( $d['market'] ); ?>">
	<div class="sm-container">
		<?php sm_breadcrumb( $crumbs ); ?>
		<div class="sm-pagehead__row">
			<div>
				<h1 class="sm-pagehead__title"><?php echo esc_html( $title ); ?></h1>
				<p class="sm-pagehead__intro">
					<?php
					if ( $d['count'] ) {
						echo esc_html(
							sprintf(
								/* translators: 1: number of listings, 2: type or market, 3: « in the wilaya of … », 4: weight. */
								_n( '%1$s listing of %2$s %3$s, about %4$s offered.', '%1$s listings of %2$s %3$s, about %4$s offered.', $d['count'], 'sm-marketplace' ),
								sm_number( $d['count'] ),
								mb_strtolower( $what ),
								$in_w,
								$t
							)
						);
						if ( $d['prices'] ) {
							echo ' ' . esc_html(
								$d['prices']['min'] < $d['prices']['max']
									/* translators: 1: lowest asked price, 2: highest asked price. */
									? sprintf( __( 'Prices asked by the sellers: from %1$s to %2$s DA/kg.', 'sm-marketplace' ), sm_number( $d['prices']['min'] ), sm_number( $d['prices']['max'] ) )
									/* translators: %s: asked price, the same for all the sellers (0.60.1). */
									: sprintf( __( 'Price asked by the sellers: %s DA/kg.', 'sm-marketplace' ), sm_number( $d['prices']['min'] ) )
							);
						}
					} else {
						echo esc_html( sprintf( /* translators: 1: type or market, 2: wilaya. */ __( 'No listing of %1$s %2$s for the moment.', 'sm-marketplace' ), mb_strtolower( $what ), $in_w ) );
					}
					?>
				</p>
			</div>
			<a class="sm-btn sm-btn--accent" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'publish', 'market' => $d['market'] ) ) ); ?>"><?php sm_icon( 'plus' ); ?><?php esc_html_e( 'Publish here', 'sm-marketplace' ); ?></a>
		</div>
	</div>
</section>
<div class="sm-container sm-section smk sml">
	<?php if ( $d['count'] ) : ?>
		<div class="smk-kpis">
			<div class="smk-kpi is-key"><small><?php esc_html_e( 'Listings online', 'sm-marketplace' ); ?></small><b><?php echo esc_html( sm_number( $d['count'] ) ); ?></b></div>
			<div class="smk-kpi"><small><?php esc_html_e( 'Weight offered', 'sm-marketplace' ); ?></small><b><?php echo sm_tech( $t ); // phpcs:ignore WordPress.Security.EscapeOutput ?></b></div>
			<?php if ( $d['prices'] ) : ?>
				<div class="smk-kpi"><small><?php esc_html_e( 'Asked prices', 'sm-marketplace' ); ?></small><b><?php echo sm_tech( sm_number( $d['prices']['min'] ) . ( $d['prices']['min'] < $d['prices']['max'] ? ' – ' . sm_number( $d['prices']['max'] ) : '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></b><small><?php echo sm_tech( 'DA/kg' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></small></div>
			<?php endif; ?>
			<?php if ( $d['updated'] ) : ?>
				<div class="smk-kpi"><small><?php esc_html_e( 'Last update', 'sm-marketplace' ); ?></small><b><?php echo esc_html( sm_date( $d['updated'] ) ); ?></b></div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( $d['sizes'] ) : ?>
		<h2 class="sml-h"><?php esc_html_e( 'Sizes offered', 'sm-marketplace' ); ?></h2>
		<ul class="sml-chips">
			<?php foreach ( $d['sizes'] as $s ) : ?>
				<li><a href="<?php echo esc_url( $s['url'] ); ?>"><b><?php echo sm_tech( $s['designation'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></b> <small><?php echo esc_html( sm_number( $s['count'] ) ); ?></small></a></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<h2 class="sml-h"><?php esc_html_e( 'Listings', 'sm-marketplace' ); ?></h2>
	<?php if ( $d['items'] ) : ?>
		<div class="sm-grid sm-grid--grid">
			<?php foreach ( array_values( $d['items'] ) as $i => $item ) : ?>
				<?php sm_component( 'card-listing', array( 'item' => $item, 'eager' => $i < 3 ) ); ?>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<p class="sml-empty"><?php esc_html_e( 'Ask the sellers for a quote, or publish your stock.', 'sm-marketplace' ); ?></p>
	<?php endif; ?>
	<p class="smk-actions">
		<?php if ( $d['count'] ) : ?>
			<a class="sm-btn sm-btn--primary-outline" href="<?php echo esc_url( $d['see_all'] ); ?>"><?php echo esc_html( sprintf( /* translators: %s: number of listings. */ _n( 'See the %s listing with the filters', 'See the %s listings with the filters', $d['count'], 'sm-marketplace' ), sm_number( $d['count'] ) ) ); ?></a>
		<?php endif; ?>
		<a class="sm-btn sm-btn--accent" href="<?php echo esc_url( $rfq ); ?>"><?php sm_icon( 'rfq' ); ?><span><?php esc_html_e( 'Request a quote', 'sm-marketplace' ); ?></span></a>
	</p>

	<?php if ( $d['wilayas'] ) : ?>
		<h2 class="sml-h"><?php echo esc_html( sprintf( /* translators: %s: type or market. */ __( '%s in other wilayas', 'sm-marketplace' ), $what ) ); ?></h2>
		<ul class="sml-chips">
			<?php foreach ( $d['wilayas'] as $w ) : ?>
				<li><a href="<?php echo esc_url( $w['url'] ); ?>"><?php echo esc_html( sm_t( $w['name'] ) ); ?> <small><?php echo esc_html( sm_number( $w['count'] ) ); ?></small></a></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( $d['others'] ) : ?>
		<h2 class="sml-h"><?php echo esc_html( sprintf( /* translators: %s: wilaya. */ __( 'Other products in %s', 'sm-marketplace' ), $wname ) ); ?></h2>
		<ul class="sml-chips">
			<?php foreach ( $d['others'] as $o ) : ?>
				<li><a href="<?php echo esc_url( $o['url'] ); ?>"><?php echo esc_html( sm_t( $o['label'] ) ); ?><?php echo $o['market'] !== $d['market'] ? ' <em>(' . esc_html( $markets[ $o['market'] ] ?? '' ) . ')</em>' : ''; ?> <small><?php echo esc_html( sm_number( $o['count'] ) ); ?></small></a></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<p class="smk-note"><?php esc_html_e( 'Prices asked by the sellers in their listings, not sale prices. The range is shown from 3 listings with a price.', 'sm-marketplace' ); ?></p>
</div>

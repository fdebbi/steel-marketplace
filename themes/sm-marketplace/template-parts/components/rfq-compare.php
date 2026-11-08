<?php
/**
 * Comparison of the offers received, for the buyer only (sm-rfq): sortable,
 * best values flagged, supplier information and contact, decisions.
 *
 * @var array $args { item: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$r       = $args['item'];
$offers  = (array) ( $r['offers'] ?? array() );
$v       = (array) ( $r['viewer'] ?? array() );
$wilayas = sm_theme_wilayas();
$labels  = sm_offer_status_labels();
$sort    = sm_theme_query_arg( 'sort', 'total' );
$base    = sm_page_url( 'rfq', array( 'id' => $r['id'] ) );
$sorts   = array(
	'total' => __( 'Total price', 'sm-marketplace' ),
	'kg'    => __( 'Price per kg', 'sm-marketplace' ),
	'days'  => __( 'Delivery time', 'sm-marketplace' ),
	'trust' => __( 'Verified suppliers first', 'sm-marketplace' ),
);
$decide  = static function ( $o, $do, $label, $style, $confirm = '' ) {
	sm_theme_form_open( 'rfq_offer_action', array( 'class' => 'smp-act' ) );
	echo '<input type="hidden" name="offer_id" value="' . esc_attr( $o['id'] ) . '"><input type="hidden" name="do" value="' . esc_attr( $do ) . '">';
	echo '<button type="submit" class="sm-btn sm-btn--' . esc_attr( $style ) . ' sm-btn--sm"' . ( $confirm ? ' data-sm-confirm="' . esc_attr( $confirm ) . '"' : '' ) . '>' . esc_html( $label ) . '</button></form>';
};
?>
<section class="smq-compare" id="smq-offers" aria-labelledby="smq-offers-title">
	<div class="smq-compare__head">
		<h2 class="smq-compare__title" id="smq-offers-title"><?php esc_html_e( 'Offers received', 'sm-marketplace' ); ?> <span class="sm-count"><?php echo esc_html( count( array_filter( $offers, static fn( $o ) => 'withdrawn' !== $o['status'] ) ) ); ?></span></h2>
		<?php if ( count( $offers ) > 1 ) : ?>
			<nav class="smp-filter" aria-label="<?php esc_attr_e( 'Sort the offers', 'sm-marketplace' ); ?>">
				<?php foreach ( $sorts as $k => $l ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'sort', $k, $base ) . '#smq-offers' ); ?>"<?php echo $sort === $k ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $l ); ?></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>
	</div>
	<?php if ( ! $offers ) : ?>
		<?php sm_component( 'empty-state', array( 'icon' => 'rfq', 'title' => __( 'No offer yet', 'sm-marketplace' ), 'text' => __( 'You will be told by notification and e-mail as soon as a supplier answers.', 'sm-marketplace' ) ) ); ?>
	<?php else : ?>
		<ol class="smq-offers" role="list">
			<?php foreach ( $offers as $o ) : ?>
				<?php
				$s       = (array) $o['supplier'];
				$active  = in_array( $o['status'], array( 'submitted', 'shortlisted', 'accepted' ), true );
				$classes = 'smq-o smq-o--' . $o['status'];
				?>
				<li class="<?php echo esc_attr( $classes ); ?>" data-offer="<?php echo esc_attr( $o['id'] ); ?>">
					<header class="smq-o__head">
						<div class="smq-o__who">
							<a class="smq-o__name" href="<?php echo esc_url( sm_page_url( 'company', array( 'c' => $s['slug'] ?? '' ) ) ); ?>"><?php echo esc_html( sm_t( (array) ( $s['name'] ?? array() ) ) ); ?></a>
							<span class="smq-o__sub">
								<?php if ( ( $s['verification'] ?? 0 ) >= 2 ) : ?><?php sm_icon( 'verified', 'sm-verified' ); ?><?php endif; ?>
								<?php echo esc_html( sm_verification_label( (int) ( $s['verification'] ?? 0 ) ) ); ?> · <?php echo esc_html( sm_t( $wilayas[ $s['wilaya'] ?? '' ] ?? '' ) ); ?>
							</span>
						</div>
						<span class="smq-ostatus smq-ostatus--<?php echo esc_attr( $o['status'] ); ?>"><?php echo esc_html( $labels[ $o['status'] ] ?? $o['status'] ); ?></span>
					</header>
					<dl class="smq-o__figs">
						<div class="<?php echo ! empty( $o['best']['total'] ) ? 'is-best' : ''; ?>"><dt><?php esc_html_e( 'Total', 'sm-marketplace' ); ?></dt><dd><?php echo esc_html( sm_money( $o['total_price'] ) ); ?></dd><?php if ( ! empty( $o['best']['total'] ) ) : ?><span class="smq-best"><?php esc_html_e( 'Lowest', 'sm-marketplace' ); ?></span><?php endif; ?></div>
						<div class="<?php echo ! empty( $o['best']['kg'] ) ? 'is-best' : ''; ?>"><dt><?php esc_html_e( 'Per kg', 'sm-marketplace' ); ?></dt><dd><?php echo $o['price_per_kg'] > 0 ? sm_per_unit( $o['price_per_kg'], 'kg', true, 2 ) : '—'; // phpcs:ignore WordPress.Security.EscapeOutput ?></dd><?php if ( ! empty( $o['best']['kg'] ) ) : ?><span class="smq-best"><?php esc_html_e( 'Best per kg', 'sm-marketplace' ); ?></span><?php endif; ?></div>
						<div class="<?php echo ! empty( $o['best']['days'] ) ? 'is-best' : ''; ?>"><dt><?php esc_html_e( 'Delivery', 'sm-marketplace' ); ?></dt><dd><?php echo esc_html( sprintf( /* translators: %d: days. */ _n( '%d day', '%d days', $o['delivery_days'], 'sm-marketplace' ), $o['delivery_days'] ) ); ?></dd><?php if ( ! empty( $o['best']['days'] ) ) : ?><span class="smq-best"><?php esc_html_e( 'Fastest', 'sm-marketplace' ); ?></span><?php endif; ?></div>
						<div><dt><?php esc_html_e( 'Valid until', 'sm-marketplace' ); ?></dt><dd><?php echo esc_html( sm_date( $o['valid_until'] ) ); ?></dd></div>
					</dl>
					<ul class="smq-o__facts" role="list">
						<li class="<?php echo $o['delivery_included'] ? 'is-yes' : 'is-no'; ?>"><?php sm_icon( $o['delivery_included'] ? 'check' : 'close' ); ?><?php echo esc_html( $o['delivery_included'] ? __( 'Delivery included', 'sm-marketplace' ) : __( 'Delivery not included', 'sm-marketplace' ) ); ?></li>
						<?php if ( $o['weight_kg'] > 0 ) : ?><li><?php sm_icon( 'weight' ); ?><?php echo esc_html( sprintf( /* translators: %s: weight. */ __( 'For %s kg', 'sm-marketplace' ), sm_number( $o['weight_kg'] ) ) ); ?><?php echo 'supplier' === $o['weight_from'] ? ' <small>(' . esc_html__( 'supplier\'s weight', 'sm-marketplace' ) . ')</small>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></li><?php endif; ?>
						<?php if ( $o['payment_terms'] ) : ?><li><?php sm_icon( 'card' ); ?><bdi><?php echo esc_html( $o['payment_terms'] ); ?></bdi></li><?php endif; ?>
					</ul>
					<?php if ( $o['message'] || $o['files'] ) : ?>
						<details class="smq-o__more">
							<summary><?php esc_html_e( 'Message and documents', 'sm-marketplace' ); ?></summary>
							<?php if ( $o['message'] ) : ?><p class="sm-prewrap" dir="auto"><?php echo esc_html( $o['message'] ); ?></p><?php endif; ?>
							<?php foreach ( $o['files'] as $f ) : ?>
								<a class="smq-o__file" href="<?php echo esc_url( $f['url'] ); ?>" target="_blank" rel="noopener" download><?php sm_icon( 'file' ); ?><?php echo esc_html( $f['name'] ); ?></a>
							<?php endforeach; ?>
						</details>
					<?php endif; ?>
					<footer class="smq-o__foot">
						<div class="smq-o__contact">
							<?php if ( ! empty( $s['phone'] ) ) : ?><a class="sm-btn sm-btn--ghost sm-btn--sm" href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $s['phone'] ) ); ?>"><?php sm_icon( 'phone' ); ?><bdi dir="ltr"><?php echo esc_html( $s['phone'] ); ?></bdi></a><?php endif; ?>
							<?php if ( ! empty( $s['email'] ) ) : ?><a class="sm-btn sm-btn--ghost sm-btn--sm" href="mailto:<?php echo esc_attr( $s['email'] ); ?>?subject=<?php echo rawurlencode( sprintf( /* translators: %d: request number. */ __( 'Request for quotation no. %d', 'sm-marketplace' ), $r['id'] ) ); ?>"><?php sm_icon( 'mail' ); ?><?php esc_html_e( 'E-mail', 'sm-marketplace' ); ?></a><?php endif; ?>
						</div>
						<?php if ( ! empty( $v['can_decide'] ) && in_array( $o['status'], array( 'submitted', 'shortlisted' ), true ) ) : ?>
							<div class="smq-o__decide">
								<?php $decide( $o, 'shortlist', 'shortlisted' === $o['status'] ? __( 'Remove from shortlist', 'sm-marketplace' ) : __( 'Shortlist', 'sm-marketplace' ), 'ghost' ); ?>
								<?php $decide( $o, 'decline', __( 'Decline', 'sm-marketplace' ), 'ghost', __( 'Decline this offer? The supplier is told.', 'sm-marketplace' ) ); ?>
								<?php $decide( $o, 'accept', __( 'Accept this offer', 'sm-marketplace' ), 'accent', __( 'Accept this offer? The request is closed and the other suppliers are told.', 'sm-marketplace' ) ); ?>
							</div>
						<?php endif; ?>
					</footer>
				</li>
			<?php endforeach; ?>
		</ol>
		<p class="sm-help"><?php esc_html_e( 'Prices are given by the suppliers. The price per kg is calculated from each offer\'s total and weight. Check the conditions with the supplier before ordering.', 'sm-marketplace' ); ?></p>
	<?php endif; ?>
</section>

<?php
/**
 * Request for quotation on the public board.
 *
 * @var array $args { item: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$r       = $args['item'];
$wilayas = sm_theme_wilayas();
$url     = empty( $r['demo'] ) ? sm_page_url( 'rfq', array( 'id' => $r['id'] ) ) : '#';
$days    = $r['days_left'];
?>
<article class="sm-card sm-card--rfq">
	<div class="sm-card__body">
		<p class="sm-card__meta">
			<span class="sm-chip"><?php echo esc_html( sm_t( $r['category_label'] ? $r['category_label'] : $r['market_label'] ) ); ?></span>
			<?php if ( null !== $days && $days <= 3 && $r['is_open'] ) : ?>
				<span class="sm-chip sm-chip--warn"><?php sm_icon( 'calendar' ); ?><?php echo esc_html( 0 >= $days ? __( 'Last day', 'sm-marketplace' ) : sprintf( /* translators: %d: days. */ _n( '%d day left', '%d days left', $days, 'sm-marketplace' ), $days ) ); ?></span>
			<?php endif; ?>
		</p>
		<h2 class="sm-card__title"><a href="<?php echo esc_url( $url ); ?>"><?php sm_e( $r['title'] ); ?></a></h2>
		<p class="sm-card__facts-inline">
			<?php if ( $r['quantity'] ) : ?><span><?php sm_icon( 'package' ); ?><?php echo sm_tech( sm_number( $r['quantity'], fmod( (float) $r['quantity'], 1 ) ? 1 : 0 ) . ' ' . sm_rfq_unit_label( $r['unit'], $r['quantity'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php endif; ?>
			<?php if ( $r['weight_kg'] ) : ?><span><?php sm_icon( 'weight' ); ?><?php echo sm_kg( $r['weight_kg'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php endif; ?>
			<span><?php sm_icon( 'map-pin' ); ?><?php echo esc_html( sm_t( $wilayas[ $r['wilaya'] ] ?? '' ) ); ?></span>
			<?php if ( $r['deadline'] ) : ?><span><?php sm_icon( 'calendar' ); ?><?php echo esc_html( sprintf( /* translators: %s: date. */ __( 'Offers until %s', 'sm-marketplace' ), sm_date( $r['deadline'] ) ) ); ?></span><?php endif; ?>
		</p>
	</div>
	<div class="sm-card--rfq__side">
		<span class="sm-count sm-count--lg"><?php echo esc_html( (int) $r['offers_count'] ); ?></span>
		<small><?php echo esc_html( _n( 'offer', 'offers', (int) $r['offers_count'], 'sm-marketplace' ) ); ?></small>
		<a class="sm-btn sm-btn--primary-outline" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'See and answer', 'sm-marketplace' ); ?></a>
	</div>
</article>

<?php
/**
 * AI credit packs on the public plans page (0.23.0).
 *
 * @var array $args { packs: list of { credits, price }, url: where to buy, lead: bool (first block of the page) }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$lead = ! empty( $args['lead'] );
?>
<section class="sm-panel<?php echo $lead ? ' sm-panel--lead' : ''; ?>">
	<h2 class="sm-panel__title"><?php esc_html_e( 'AI credit packs', 'sm-marketplace' ); ?></h2>
	<p class="sm-muted"><?php esc_html_e( 'For every account, clients and partner engineers included. Bought credits do not expire.', 'sm-marketplace' ); ?></p>
	<ul class="sm-pricelist">
		<?php foreach ( (array) $args['packs'] as $pack ) : ?>
			<li><span><?php /* translators: %s: number of credits. */ echo esc_html( sprintf( __( '%s AI credits', 'sm-marketplace' ), sm_number( $pack['credits'] ) ) ); ?></span><strong><?php echo esc_html( sm_money( $pack['price'] ) ); ?></strong></li>
		<?php endforeach; ?>
	</ul>
	<?php if ( $lead ) : ?>
		<a class="sm-btn sm-btn--accent" href="<?php echo esc_url( $args['url'] ); ?>"><?php sm_icon( 'ai' ); ?><span><?php esc_html_e( 'Buy AI credits', 'sm-marketplace' ); ?></span></a>
	<?php endif; ?>
</section>

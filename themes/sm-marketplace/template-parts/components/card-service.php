<?php
/**
 * Service card (fabrication & site work of companies and professionals).
 *
 * @var array $args { service: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$service = $args['service'] ?? null;
if ( ! $service ) {
	return;
}
$company = sm_theme_company( $service['company'] );
$wilayas = sm_theme_wilayas();
$real    = isset( $service['status'] );
$url     = $real ? sm_page_url( 'service', array( 'id' => $service['id'] ) ) : sm_page_url( 'company', array( 'c' => $service['company'] ) );
?>
<article class="sm-card sm-card--service sm-card--<?php echo esc_attr( $service['kind'] ); ?>">
	<a class="sm-card__media" href="<?php echo esc_url( $url ); ?>" tabindex="-1" aria-hidden="true">
		<img src="<?php echo esc_url( sm_image_url( $service['image'] ) ); ?>" alt="" loading="lazy" decoding="async" width="640" height="480">
		<span class="sm-chip sm-chip--kind"><?php sm_icon( 'wrench' ); ?><?php esc_html_e( 'Fabrication & installation', 'sm-marketplace' ); ?></span>
	</a>
	<div class="sm-card__body">
		<h3 class="sm-card__title"><a href="<?php echo esc_url( $url ); ?>"><?php sm_e( $service['title'] ); ?></a></h3>
		<?php if ( sm_service_tags( $service ) ) : ?>
			<ul class="sm-tags">
				<?php foreach ( array_slice( sm_service_tags( $service ), 0, 4 ) as $tag ) : ?><li><?php sm_e( $tag ); ?></li><?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php if ( $real ) : ?>
			<p class="sm-card__facts-inline">
				<span><?php sm_icon( 'map-pin' ); ?><?php echo esc_html( $service['all_algeria'] ? __( 'All of Algeria', 'sm-marketplace' ) : sprintf( /* translators: %d: number of wilayas. */ _n( '%d wilaya served', '%d wilayas served', count( $service['wilayas'] ), 'sm-marketplace' ), count( $service['wilayas'] ) ) ); ?></span>
				<span class="sm-card__price-hint"><?php echo wp_kses_post( sm_service_price( $service ) ); ?></span>
			</p>
		<?php endif; ?>
		<footer class="sm-card__foot">
			<span class="sm-card__place"><?php sm_icon( 'company' ); ?><?php echo esc_html( sm_t( $wilayas[ $company['wilaya'] ?? '' ] ?? '' ) ); ?></span>
			<?php if ( $company ) : ?>
				<span class="sm-card__seller"><?php sm_e( $company['name'] ); ?><?php if ( $company['verification'] >= 2 ) : ?><?php sm_icon( 'verified', 'sm-verified', sm_verification_label( $company['verification'], $company['class'] ?? '' ) ); ?><?php endif; ?></span>
			<?php endif; ?>
		</footer>
	</div>
</article>

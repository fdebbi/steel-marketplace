<?php
/**
 * Service card (fabrication / installation / engineering).
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
$url     = sm_page_url( 'company', array( 'c' => $service['company'] ) );
?>
<article class="sm-card sm-card--service">
	<a class="sm-card__media" href="<?php echo esc_url( $url ); ?>" tabindex="-1" aria-hidden="true">
		<img src="<?php echo esc_url( sm_image_url( $service['image'] ) ); ?>" alt="" loading="lazy" decoding="async" width="640" height="480">
		<span class="sm-chip sm-chip--kind"><?php echo esc_html( 'engineering' === $service['kind'] ? __( 'Engineering', 'sm-marketplace' ) : __( 'Fabrication & installation', 'sm-marketplace' ) ); ?></span>
	</a>
	<div class="sm-card__body">
		<h3 class="sm-card__title"><a href="<?php echo esc_url( $url ); ?>"><?php sm_e( $service['title'] ); ?></a></h3>
		<ul class="sm-tags">
			<?php foreach ( $service['tags'] as $tag ) : ?><li><?php sm_e( $tag ); ?></li><?php endforeach; ?>
		</ul>
		<footer class="sm-card__foot">
			<span class="sm-card__place"><?php sm_icon( 'map-pin' ); ?><?php echo esc_html( sm_t( $wilayas[ $company['wilaya'] ] ?? '' ) ); ?></span>
			<span class="sm-card__seller"><?php sm_e( $company['name'] ); ?><?php if ( $company['verification'] >= 2 ) : ?><?php sm_icon( 'verified', 'sm-verified', sm_verification_label( $company['verification'] ) ); ?><?php endif; ?></span>
		</footer>
	</div>
</article>

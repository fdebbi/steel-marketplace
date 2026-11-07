<?php
/**
 * Company card (directory, home, engineer list).
 *
 * @var array $args { company: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$company = $args['company'] ?? null;
if ( ! $company ) {
	return;
}
$wilayas    = sm_theme_wilayas();
$activities = sm_activity_labels();
?>
<article class="sm-card sm-card--company">
	<div class="sm-avatar sm-avatar--<?php echo esc_attr( $company['class'] ); ?>" aria-hidden="true"><?php echo esc_html( $company['initials'] ); ?></div>
	<div class="sm-card__body">
		<h3 class="sm-card__title">
			<a href="<?php echo esc_url( sm_page_url( 'company', array( 'c' => $company['slug'] ) ) ); ?>"><?php sm_e( $company['name'] ); ?></a>
			<?php if ( $company['verification'] >= 2 ) : ?><?php sm_icon( 'verified', 'sm-verified', sm_verification_label( $company['verification'] ) ); ?><?php endif; ?>
		</h3>
		<p class="sm-card__place"><?php sm_icon( 'map-pin' ); ?><?php echo esc_html( sm_t( $wilayas[ $company['wilaya'] ] ?? '' ) ); ?></p>
		<ul class="sm-tags">
			<?php foreach ( $company['activities'] as $activity ) : ?>
				<li><?php echo esc_html( $activities[ $activity ] ?? $activity ); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
</article>

<?php
/**
 * Home page card of a featured company or professional (0.15.0).
 *
 * @var array $args { company: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$c = $args['company'] ?? null;
if ( ! $c ) {
	return;
}
$class   = sm_account_class( $c );
$url     = sm_page_url( 'company', array( 'c' => $c['slug'] ) );
$wilayas = sm_theme_wilayas();
$labels  = sm_activity_labels();
$acts    = array_map( static fn( $a ) => $labels[ $a ] ?? $a, (array) $c['activities'] );
$sub     = 'professional' === $class && ! empty( $c['title'] ) ? sm_t( $c['title'] ) : implode( ' · ', array_slice( $acts, 0, 2 ) );
$level   = (int) $c['verification'];
?>
<article class="smf-card smf-card--<?php echo esc_attr( $class ); ?>">
	<?php if ( 'company' === $class ) : ?><div class="smf-card__band" aria-hidden="true"></div><?php endif; ?>
	<?php if ( ! empty( $c['featured'] ) ) : ?><span class="smf-card__flag"><?php echo esc_html( $c['featured'] ); ?></span><?php endif; ?>
	<div class="smf-card__body">
		<span class="smf-card__logo">
			<?php if ( $c['logo'] ) : ?>
				<img src="<?php echo esc_url( $c['logo'] ); ?>" alt="" loading="lazy" decoding="async">
			<?php else : ?>
				<?php echo esc_html( $c['initials'] ); ?>
			<?php endif; ?>
		</span>
		<ul class="smf-card__badges" role="list">
			<?php if ( $level >= 1 ) : ?><li class="smc-badge smc-badge--account"><?php sm_icon( 'check' ); ?><?php esc_html_e( 'Account verified', 'sm-marketplace' ); ?></li><?php endif; ?>
			<?php if ( $level >= 2 ) : ?><li class="smc-badge smc-badge--quality"><?php sm_icon( 'verified' ); ?><?php echo esc_html( sm_verified_label( $class ) ); ?></li><?php endif; ?>
		</ul>
		<h3 class="smf-card__name"><a href="<?php echo esc_url( $url ); ?>"><?php sm_e( $c['name'] ); ?></a></h3>
		<?php if ( $sub ) : ?><p class="smf-card__sub"><?php echo esc_html( $sub ); ?></p><?php endif; ?>
		<p class="smf-card__place"><?php sm_icon( 'map-pin' ); ?><?php echo esc_html( sm_t( $wilayas[ $c['wilaya'] ] ?? '' ) ); ?><?php if ( 'professional' === $class && $c['experience'] ) : ?> · <?php echo esc_html( sprintf( /* translators: %d: years. */ _n( '%d year of experience', '%d years of experience', $c['experience'], 'sm-marketplace' ), $c['experience'] ) ); ?><?php endif; ?></p>
		<a class="sm-btn <?php echo 'professional' === $class ? 'sm-btn--accent' : 'sm-btn--ghost'; ?> sm-btn--block smf-card__cta" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( 'professional' === $class ? __( 'Request a quote', 'sm-marketplace' ) : __( 'See the profile', 'sm-marketplace' ) ); ?></a>
	</div>
</article>

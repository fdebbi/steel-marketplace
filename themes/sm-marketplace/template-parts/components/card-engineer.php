<?php
/**
 * Card of a partner engineer on the public list. No contact details: the
 * "Ask for a study" button sends the request to the platform with this
 * engineer as the client's preference.
 *
 * @var array $args { engineer: array, preview?: bool }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
$e       = $args['engineer'];
$preview = ! empty( $args['preview'] );
$wilayas = sm_theme_wilayas();
?>
<article class="sme-card">
	<header class="sme-card__head">
		<?php sm_avatar( array( 'class' => 'engineer', 'logo' => $e['photo'], 'initials' => $e['initials'] ), 'lg' ); ?>
		<div>
			<h3 class="sme-card__name" dir="auto"><?php echo esc_html( $e['name'] ); ?></h3>
			<p class="sme-card__title" dir="auto"><?php echo esc_html( $e['title'] ); ?></p>
			<p class="sme-card__badge"><?php sm_icon( 'verified' ); ?><?php esc_html_e( 'Partner engineer verified by the platform', 'sm-marketplace' ); ?></p>
		</div>
	</header>
	<ul class="sme-card__facts" role="list">
		<li><?php sm_icon( 'calendar' ); ?><?php echo esc_html( sprintf( /* translators: %d: years. */ _n( '%d year of experience', '%d years of experience', (int) $e['experience'], 'sm-marketplace' ), (int) $e['experience'] ) ); ?></li>
		<li><?php sm_icon( 'map-pin' ); ?><?php echo esc_html( sm_t( $wilayas[ $e['wilaya'] ] ?? '' ) ); ?></li>
		<?php if ( null !== $e['missions'] ) : ?><li><?php sm_icon( 'check' ); ?><?php echo esc_html( sprintf( /* translators: %d: missions. */ _n( '%d mission completed', '%d missions completed', (int) $e['missions'], 'sm-marketplace' ), (int) $e['missions'] ) ); ?></li><?php endif; ?>
	</ul>
	<?php if ( $e['specialties'] ) : ?>
		<ul class="sme-card__tags" role="list">
			<?php foreach ( $e['specialties'] as $s ) : ?><li><?php echo esc_html( $s ); ?></li><?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<?php if ( $e['bio'] ) : ?><p class="sme-card__bio" dir="auto"><?php echo esc_html( wp_trim_words( $e['bio'], 40 ) ); ?></p><?php endif; ?>
	<footer class="sme-card__foot">
		<?php if ( ! $e['available'] ) : ?><span class="sm-status sm-status--muted"><?php esc_html_e( 'Not available at the moment', 'sm-marketplace' ); ?></span><?php endif; ?>
		<?php if ( $preview ) : ?>
			<span class="sm-btn sm-btn--accent sm-btn--sm" aria-disabled="true"><?php sm_icon( 'hard-hat' ); ?><?php esc_html_e( 'Ask for a study', 'sm-marketplace' ); ?></span>
		<?php else : ?>
			<a class="sm-btn sm-btn--accent sm-btn--sm" href="<?php echo esc_url( $e['request_url'] ); ?>"><?php sm_icon( 'hard-hat' ); ?><?php esc_html_e( 'Ask for a study', 'sm-marketplace' ); ?></a>
		<?php endif; ?>
	</footer>
</article>

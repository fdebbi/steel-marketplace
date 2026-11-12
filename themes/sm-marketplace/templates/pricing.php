<?php
/**
 * Template Name: SM — Plans
 *
 * Public plans (Phase 10): companies and professionals, AI credit packs, listing
 * options and the founding members offer. Prices and limits are never
 * hardcoded: they come from sm-subscriptions (resource plans), as set by the
 * administrator. Without the extension the page says that the platform is free
 * during the launch.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
get_header();
$data    = sm_theme_data( 'plans' );
$has     = is_array( $data ) && ! empty( $data['classes'] );
$classes = array(
	'company'      => __( 'Companies', 'sm-marketplace' ),
	'professional' => __( 'Professionals and craftsmen', 'sm-marketplace' ),
);
$tab = sm_theme_query_arg( 'for' );
$tab = isset( $classes[ $tab ] ) ? $tab : 'company';
?>
<section class="sm-pagehead">
	<div class="sm-container">
		<?php sm_breadcrumb( array( array( __( 'Plans', 'sm-marketplace' ), '' ) ) ); ?>
		<h1 class="sm-pagehead__title"><?php esc_html_e( 'Plans', 'sm-marketplace' ); ?></h1>
		<p class="sm-pagehead__intro"><?php esc_html_e( 'Start free. Upgrade when you need more listings, more tonnage online, visible contacts and more AI credits. Clients and partner engineers use the platform free.', 'sm-marketplace' ); ?></p>
	</div>
</section>
<div class="sm-container sm-section sm-pricing">
	<?php if ( ! $has ) : ?>
		<?php sm_component( 'empty-state', array( 'icon' => 'card', 'title' => __( 'Free during the launch', 'sm-marketplace' ), 'text' => __( 'All the features of the platform are open. The plans will be published here.', 'sm-marketplace' ) ) ); ?>
	<?php else : ?>
		<?php if ( ! empty( $data['founders']['on'] ) ) : ?>
			<section class="sm-founders">
				<?php sm_icon( 'star' ); ?>
				<div>
					<h2><?php esc_html_e( 'Founding members offer', 'sm-marketplace' ); ?></h2>
					<p>
						<?php
						printf(
							/* translators: 1: number of months, 2: places left. */
							esc_html( _n( 'The first verified accounts get a paid plan free for %1$d months, then keep its price. %2$d place left.', 'The first verified accounts get a paid plan free for %1$d months, then keep its price. %2$d places left.', (int) $data['founders']['places_left'], 'sm-marketplace' ) ),
							(int) $data['founders']['months'],
							(int) $data['founders']['places_left']
						);
						?>
					</p>
				</div>
			</section>
		<?php endif; ?>

		<div class="sm-pricing__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Type of account', 'sm-marketplace' ); ?>">
			<?php foreach ( $classes as $key => $label ) : ?>
				<button type="button" role="tab" id="tab-<?php echo esc_attr( $key ); ?>" aria-controls="plans-<?php echo esc_attr( $key ); ?>" aria-selected="<?php echo $tab === $key ? 'true' : 'false'; ?>" data-sm-tab="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></button>
			<?php endforeach; ?>
		</div>
		<?php foreach ( $classes as $key => $label ) : ?>
			<div class="sm-plans" role="tabpanel" id="plans-<?php echo esc_attr( $key ); ?>" aria-labelledby="tab-<?php echo esc_attr( $key ); ?>"<?php echo $tab === $key ? '' : ' hidden'; ?>>
				<?php foreach ( (array) ( $data['classes'][ $key ] ?? array() ) as $i => $p ) : ?>
					<?php sm_component( 'plan-card', array( 'plan' => $p, 'labels' => $data['labels'], 'class' => $key, 'mode' => 'public', 'highlight' => 1 === $i ) ); ?>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
		<p class="sm-help sm-pricing__note"><?php esc_html_e( 'Prices include TVA. Buildings are counted by number, apart from the tonnage. When a paid period ends, what goes beyond the free plan is paused, the oldest first; nothing is deleted.', 'sm-marketplace' ); ?></p>

		<div class="sm-pricing__extras">
			<?php if ( ! empty( $data['packs'] ) ) : ?>
				<section class="sm-panel">
					<h2 class="sm-panel__title"><?php esc_html_e( 'AI credit packs', 'sm-marketplace' ); ?></h2>
					<p class="sm-muted"><?php esc_html_e( 'For every account, clients and partner engineers included. Bought credits do not expire.', 'sm-marketplace' ); ?></p>
					<ul class="sm-pricelist">
						<?php foreach ( $data['packs'] as $pack ) : ?>
							<li><span><?php /* translators: %s: number of credits. */ echo esc_html( sprintf( __( '%s AI credits', 'sm-marketplace' ), sm_number( $pack['credits'] ) ) ); ?></span><strong><?php echo esc_html( sm_money( $pack['price'] ) ); ?></strong></li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>
			<?php $opts = array_filter( (array) $data['options'], static fn( $o ) => $o['price'] > 0 ); ?>
			<?php if ( $opts ) : ?>
				<section class="sm-panel">
					<h2 class="sm-panel__title"><?php esc_html_e( 'Options for one listing', 'sm-marketplace' ); ?></h2>
					<p class="sm-muted"><?php esc_html_e( 'With any plan, from My listings.', 'sm-marketplace' ); ?></p>
					<ul class="sm-pricelist">
						<?php if ( isset( $opts['bump'] ) ) : ?>
							<li><span><?php esc_html_e( 'Back to the top of the list', 'sm-marketplace' ); ?></span><strong><?php echo esc_html( sm_money( $opts['bump']['price'] ) ); ?></strong></li>
						<?php endif; ?>
						<?php if ( isset( $opts['urgent'] ) ) : ?>
							<li><span><?php /* translators: %d: days. */ echo esc_html( sprintf( __( '"Urgent" badge, %d days', 'sm-marketplace' ), $opts['urgent']['days'] ) ); ?></span><strong><?php echo esc_html( sm_money( $opts['urgent']['price'] ) ); ?></strong></li>
						<?php endif; ?>
						<?php if ( isset( $opts['featured'] ) ) : ?>
							<li><span><?php /* translators: %d: days. */ echo esc_html( sprintf( __( 'Featured listing, first in the results, %d days', 'sm-marketplace' ), $opts['featured']['days'] ) ); ?></span><strong><?php echo esc_html( sm_money( $opts['featured']['price'] ) ); ?></strong></li>
						<?php endif; ?>
					</ul>
				</section>
			<?php endif; ?>
			<?php if ( ! empty( $data['methods'] ) ) : ?>
				<section class="sm-panel">
					<h2 class="sm-panel__title"><?php esc_html_e( 'How to pay', 'sm-marketplace' ); ?></h2>
					<ul class="sm-checklist"><?php foreach ( $data['methods'] as $m ) : ?><li><?php sm_icon( 'check' ); ?><?php echo esc_html( $m ); ?></li><?php endforeach; ?></ul>
					<p class="sm-muted"><?php esc_html_e( 'Order from your account, pay, then send the proof of payment. The plan starts when our team has checked it, usually within one working day. Online card payment will come later.', 'sm-marketplace' ); ?></p>
				</section>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
<?php
get_footer();

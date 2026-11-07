<?php
/**
 * Commercial dashboard overview.
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$account = $args['account'];
$kpis    = $account['kpis'];
$usage   = (array) ( $account['usage'] ?? array() );
$level   = (int) $account['company']['verification'];
$recent  = sm_theme_listings( array( 'company' => $account['company']['slug'], 'per_page' => 5 ) )['items'];
?>
<ul class="sm-kpis">
	<li><span><?php esc_html_e( 'Active listings', 'sm-marketplace' ); ?></span><strong><?php echo esc_html( sm_number( $kpis['active'] ) ); ?></strong></li>
	<li><span><?php esc_html_e( 'Views (30 days)', 'sm-marketplace' ); ?></span><strong><?php echo esc_html( sm_number( $kpis['views'] ) ); ?></strong></li>
	<li><span><?php esc_html_e( 'New enquiries', 'sm-marketplace' ); ?></span><strong><?php echo esc_html( sm_number( $kpis['enquiries'] ) ); ?></strong></li>
	<li><span><?php esc_html_e( 'Open quotations', 'sm-marketplace' ); ?></span><strong><?php echo esc_html( sm_number( $kpis['offers'] ) ); ?></strong></li>
</ul>

<div class="sm-dash__cols">
	<section class="sm-panel">
		<div class="sm-panel__head">
			<h2 class="sm-panel__title"><?php esc_html_e( 'Recent listings', 'sm-marketplace' ); ?></h2>
			<?php if ( $recent ) : ?><a class="sm-link" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'listings' ) ) ); ?>"><?php esc_html_e( 'Manage', 'sm-marketplace' ); ?><?php sm_icon( 'arrow-end' ); ?></a><?php endif; ?>
		</div>
		<?php if ( $recent ) : ?>
			<ul class="sm-rowlist">
				<?php foreach ( $recent as $item ) : ?>
					<li>
						<img src="<?php echo esc_url( sm_image_url( $item['image'] ) ); ?>" alt="" width="64" height="48" loading="lazy">
						<div><a href="<?php echo esc_url( sm_page_url( 'listing', array( 'id' => $item['id'] ) ) ); ?>"><?php sm_e( $item['title'] ); ?></a><small><?php echo esc_html( sm_market_labels()[ $item['market'] ] ); ?> · <?php echo esc_html( sm_ago( $item['date'] ) ); ?></small></div>
						<strong><?php echo sm_per_unit( $item['price_per_kg'] ); // phpcs:ignore ?></strong>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="sm-muted"><?php esc_html_e( 'You have no listing yet. Publishing opens with the marketplace modules (products, reuse, buildings).', 'sm-marketplace' ); ?></p>
		<?php endif; ?>
	</section>

	<section class="sm-panel">
		<h2 class="sm-panel__title"><?php esc_html_e( 'Account status', 'sm-marketplace' ); ?></h2>
		<?php sm_component( 'verification-steps', array( 'account' => $account ) ); ?>
		<?php
		$meters = array(
			'listings'   => __( 'Listings', 'sm-marketplace' ),
			'featured'   => __( 'Featured slots', 'sm-marketplace' ),
			'ai_credits' => __( 'AI credits used', 'sm-marketplace' ),
		);
		foreach ( $meters as $key => $label ) :
			if ( empty( $usage[ $key ] ) ) {
				continue;
			}
			list( $used, $max ) = $usage[ $key ];
			?>
			<div class="sm-meter">
				<div class="sm-meter__label"><span><?php echo esc_html( $label ); ?></span><span><bdi dir="ltr"><?php echo esc_html( $used . ' / ' . $max ); ?></bdi></span></div>
				<div class="sm-meter__bar" role="progressbar" aria-valuemin="0" aria-valuemax="<?php echo esc_attr( $max ); ?>" aria-valuenow="<?php echo esc_attr( $used ); ?>" aria-label="<?php echo esc_attr( $label ); ?>"><span style="inline-size:<?php echo esc_attr( min( 100, round( $used / max( 1, $max ) * 100 ) ) ); ?>%"></span></div>
			</div>
		<?php endforeach; ?>
		<?php if ( $usage ) : ?>
			<a class="sm-btn sm-btn--ghost sm-btn--block" href="<?php echo esc_url( sm_page_url( 'pricing' ) ); ?>"><?php esc_html_e( 'Change plan', 'sm-marketplace' ); ?></a>
		<?php endif; ?>
	</section>
</div>

<?php get_template_part( 'template-parts/dashboard/notifications', null, array( 'account' => $account ) ); ?>

<section class="sm-panel">
	<h2 class="sm-panel__title"><?php esc_html_e( 'Quick actions', 'sm-marketplace' ); ?></h2>
	<ul class="sm-quick">
		<li><a href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'publish', 'market' => 'products' ) ) ); ?>"><?php sm_icon( 'layers' ); ?><?php esc_html_e( 'Sell new steel', 'sm-marketplace' ); ?></a></li>
		<li><a href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'publish', 'market' => 'reuse' ) ) ); ?>"><?php sm_icon( 'recycle' ); ?><?php esc_html_e( 'Sell offcuts or surplus', 'sm-marketplace' ); ?></a></li>
		<li><a href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'publish', 'market' => 'buildings' ) ) ); ?>"><?php sm_icon( 'warehouse' ); ?><?php esc_html_e( 'Sell a building', 'sm-marketplace' ); ?></a></li>
		<li><a href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'company' ) ) ); ?>"><?php sm_icon( 'company' ); ?><?php esc_html_e( 'Complete the company profile', 'sm-marketplace' ); ?></a></li>
	</ul>
</section>

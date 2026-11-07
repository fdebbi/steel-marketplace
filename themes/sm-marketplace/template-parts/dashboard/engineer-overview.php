<?php
/**
 * Engineering dashboard overview. No buying/selling features by design.
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$account  = $args['account'];
$kpis     = $account['kpis'];
$services = sm_theme_services( array( 'company' => $account['company']['slug'] ) );
$requests = array(
	array( 'fr' => 'Expertise d’un hangar d’occasion 40 × 20 m avant achat', 'ar' => 'خبرة هنغار مستعمل 40 × 20 م قبل الشراء', 'w' => '31', 'd' => 1, 's' => 'new' ),
	array( 'fr' => 'Note de calcul — bâtiment avicole 60 × 12 m', 'ar' => 'مذكرة حساب — مبنى دواجن 60 × 12 م', 'w' => '34', 'd' => 3, 's' => 'quoted' ),
	array( 'fr' => 'Plans d’exécution d’un auvent métallique', 'ar' => 'مخططات تنفيذ مظلة معدنية', 'w' => '25', 'd' => 6, 's' => 'won' ),
);
$status = array(
	'new'    => array( 'info', __( 'New', 'sm-marketplace' ) ),
	'quoted' => array( 'warn', __( 'Quote sent', 'sm-marketplace' ) ),
	'won'    => array( 'ok', __( 'Accepted', 'sm-marketplace' ) ),
);
$wilayas = sm_theme_wilayas();
?>
<ul class="sm-kpis">
	<li><span><?php esc_html_e( 'New requests', 'sm-marketplace' ); ?></span><strong><?php echo esc_html( $kpis['requests'] ); ?></strong></li>
	<li><span><?php esc_html_e( 'Profile views (30 days)', 'sm-marketplace' ); ?></span><strong><?php echo esc_html( sm_number( $kpis['views'] ) ); ?></strong></li>
	<li><span><?php esc_html_e( 'Quotes sent', 'sm-marketplace' ); ?></span><strong><?php echo esc_html( $kpis['quotes'] ); ?></strong></li>
	<li><span><?php esc_html_e( 'Verification', 'sm-marketplace' ); ?></span><strong class="sm-kpis__badge"><?php sm_icon( 'verified' ); ?><?php echo esc_html( sm_verification_label( $account['company']['verification'] ) ); ?></strong></li>
</ul>

<div class="sm-dash__cols">
	<section class="sm-panel">
		<div class="sm-panel__head"><h2 class="sm-panel__title"><?php esc_html_e( 'Client requests', 'sm-marketplace' ); ?></h2></div>
		<ul class="sm-rowlist">
			<?php foreach ( $requests as $r ) : ?>
				<li>
					<span class="sm-rowlist__icon"><?php sm_icon( 'file' ); ?></span>
					<div><a href="#"><?php sm_e( array( 'fr' => $r['fr'], 'ar' => $r['ar'] ) ); ?></a><small><?php echo esc_html( sm_t( $wilayas[ $r['w'] ] ) ); ?> · <?php echo esc_html( sm_ago( gmdate( 'Y-m-d H:i:s', time() - $r['d'] * DAY_IN_SECONDS ) ) ); ?></small></div>
					<span class="sm-status sm-status--<?php echo esc_attr( $status[ $r['s'] ][0] ); ?>"><?php echo esc_html( $status[ $r['s'] ][1] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
	<section class="sm-panel">
		<h2 class="sm-panel__title"><?php esc_html_e( 'My services', 'sm-marketplace' ); ?></h2>
		<ul class="sm-rowlist">
			<?php foreach ( $services as $s ) : ?>
				<li><span class="sm-rowlist__icon"><?php sm_icon( 'hard-hat' ); ?></span><div><?php sm_e( $s['title'] ); ?></div></li>
			<?php endforeach; ?>
		</ul>
		<a class="sm-btn sm-btn--primary sm-btn--block" href="<?php echo esc_url( sm_page_url( 'account', array( 'account' => 'engineer', 'view' => 'services' ) ) ); ?>"><?php sm_icon( 'plus' ); ?><?php esc_html_e( 'Add a service', 'sm-marketplace' ); ?></a>
	</section>
</div>
<p class="sm-callout"><?php sm_icon( 'info' ); ?><span><?php esc_html_e( 'Engineering accounts publish services only. To trade steel, open a separate business account with its own verification.', 'sm-marketplace' ); ?></span></p>

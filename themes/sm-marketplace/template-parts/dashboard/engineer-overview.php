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
$is_demo  = empty( $account['real'] );
$mine     = $is_demo ? null : sm_theme_data( 'my_services' );
$services = is_array( $mine ) && isset( $mine['items'] ) ? $mine['items'] : sm_theme_services( array( 'company' => $account['company']['slug'] ) );
$svc_set  = $is_demo ? array() : (array) sm_theme_data( 'services_settings' );
$requests = $is_demo ? array() : array_slice( (array) sm_theme_data( 'enquiries', array( 'box' => 'received' ) ), 0, 5 );
$status   = array(
	'new'      => array( 'info', __( 'New', 'sm-marketplace' ) ),
	'read'     => array( 'ok', __( 'Read', 'sm-marketplace' ) ),
	'archived' => array( 'muted', __( 'Archived', 'sm-marketplace' ) ),
);
?>
<ul class="sm-kpis">
	<li><span><?php esc_html_e( 'New requests', 'sm-marketplace' ); ?></span><strong><?php echo esc_html( $kpis['requests'] ); ?></strong></li>
	<li><span><?php esc_html_e( 'Profile views (30 days)', 'sm-marketplace' ); ?></span><strong><?php echo esc_html( sm_number( $kpis['views'] ) ); ?></strong></li>
	<li><span><?php esc_html_e( 'New service requests', 'sm-marketplace' ); ?></span><strong><?php echo esc_html( (int) ( $svc_set['new_requests'] ?? $kpis['quotes'] ) ); ?></strong></li>
	<li><span><?php esc_html_e( 'Verification', 'sm-marketplace' ); ?></span><strong class="sm-kpis__badge"><?php sm_icon( $account['company']['verification'] >= 1 ? 'verified' : 'shield' ); ?><?php echo esc_html( sm_verification_label( $account['company']['verification'] ) ); ?></strong></li>
</ul>

<div class="sm-dash__cols">
	<section class="sm-panel">
		<div class="sm-panel__head">
			<h2 class="sm-panel__title"><?php esc_html_e( 'Client requests', 'sm-marketplace' ); ?></h2>
			<?php if ( $requests ) : ?><a class="sm-link" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'requests' ) ) ); ?>"><?php esc_html_e( 'See all', 'sm-marketplace' ); ?><?php sm_icon( 'arrow-end' ); ?></a><?php endif; ?>
		</div>
		<?php if ( $requests ) : ?>
			<ul class="sm-rowlist">
				<?php foreach ( $requests as $r ) : ?>
					<li>
						<span class="sm-rowlist__icon"><?php sm_icon( 'file' ); ?></span>
						<div><a href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'requests' ) ) ); ?>"><?php echo esc_html( $r['subject'] ? $r['subject'] : wp_trim_words( $r['message'], 8 ) ); ?></a><small><?php echo esc_html( $r['name'] ); ?> · <?php echo esc_html( sm_ago( $r['date'] ) ); ?></small></div>
						<span class="sm-status sm-status--<?php echo esc_attr( $status[ $r['status'] ][0] ?? 'info' ); ?>"><?php echo esc_html( $status[ $r['status'] ][1] ?? $r['status'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="sm-muted"><?php esc_html_e( 'Requests sent from your profile page appear here. You are also notified by e-mail.', 'sm-marketplace' ); ?></p>
		<?php endif; ?>
	</section>
	<section class="sm-panel">
		<h2 class="sm-panel__title"><?php esc_html_e( 'Account status', 'sm-marketplace' ); ?></h2>
		<?php sm_component( 'verification-steps', array( 'account' => $account ) ); ?>
		<?php if ( $services ) : ?>
			<h3 class="sm-panel__subtitle"><?php esc_html_e( 'My services', 'sm-marketplace' ); ?></h3>
			<ul class="sm-rowlist">
				<?php foreach ( $services as $s ) : ?>
					<li><span class="sm-rowlist__icon"><?php sm_icon( 'hard-hat' ); ?></span><div><?php if ( isset( $s['status'] ) ) : ?><a href="<?php echo esc_url( sm_page_url( 'service', array( 'id' => $s['id'] ) ) ); ?>"><?php sm_e( $s['title'] ); ?></a><?php else : ?><?php sm_e( $s['title'] ); ?><?php endif; ?></div></li>
				<?php endforeach; ?>
			</ul>
		<?php elseif ( ! $is_demo ) : ?>
			<a class="sm-btn sm-btn--accent sm-btn--block" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'services', 'new' => 1 ) ) ); ?>"><?php sm_icon( 'plus' ); ?><?php esc_html_e( 'Publish my first service', 'sm-marketplace' ); ?></a>
		<?php endif; ?>
		<a class="sm-btn sm-btn--primary sm-btn--block" href="<?php echo esc_url( sm_page_url( 'account', array_merge( $is_demo ? array( 'account' => 'engineer' ) : array(), array( 'view' => 'profile' ) ) ) ); ?>"><?php sm_icon( 'user' ); ?><?php esc_html_e( 'Complete my professional profile', 'sm-marketplace' ); ?></a>
	</section>
</div>
<?php get_template_part( 'template-parts/dashboard/notifications', null, array( 'account' => $account ) ); ?>
<p class="sm-callout"><?php sm_icon( 'info' ); ?><span><?php esc_html_e( 'Engineering accounts publish services only. To trade steel, open a separate business account with its own verification.', 'sm-marketplace' ); ?></span></p>

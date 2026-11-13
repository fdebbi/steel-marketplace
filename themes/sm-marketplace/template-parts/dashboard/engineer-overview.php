<?php
/**
 * Partner engineer: overview. Application status until approval, then the
 * missions offered and in progress, and the fees. No trading by design.
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$account = $args['account'];
$d       = sm_theme_data( 'engineer_account' );
if ( ! is_array( $d ) ) {
	sm_component( 'empty-state', array( 'icon' => 'hard-hat', 'title' => __( 'Partner engineer area', 'sm-marketplace' ), 'text' => __( 'Missions, invoicing and files arrive with the SM Engineering plugin.', 'sm-marketplace' ) ) );
	return;
}
$status = $d['status'];
if ( 'approved' !== $status ) :
	$texts = array(
		'pending'   => array( 'info', 'shield', __( 'Your application is being reviewed', 'sm-marketplace' ), __( 'The platform team checks your diploma and your documents, usually within 3 working days. You receive the decision by e-mail.', 'sm-marketplace' ) ),
		'rejected'  => array( 'warn', 'alert', __( 'Your application needs changes', 'sm-marketplace' ), $d['reason'] ),
		'suspended' => array( 'warn', 'alert', __( 'Your partner account is suspended', 'sm-marketplace' ), $d['reason'] ),
	);
	list( $tone, $icon, $title, $text ) = $texts[ $status ] ?? $texts['pending'];
	?>
	<section class="sm-panel sme-status sme-status--<?php echo esc_attr( $tone ); ?>">
		<?php sm_icon( $icon ); ?>
		<div>
			<h2 class="sm-panel__title"><?php echo esc_html( $title ); ?></h2>
			<?php if ( $text ) : ?><p dir="auto"><?php echo esc_html( $text ); ?></p><?php endif; ?>
			<?php if ( 'rejected' === $status ) : ?>
				<a class="sm-btn sm-btn--accent" href="<?php echo esc_url( $d['apply_url'] ); ?>"><?php esc_html_e( 'Correct and send my application again', 'sm-marketplace' ); ?></a>
			<?php else : ?>
				<a class="sm-btn sm-btn--ghost" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'partner' ) ) ); ?>"><?php esc_html_e( 'Check my profile', 'sm-marketplace' ); ?></a>
			<?php endif; ?>
		</div>
	</section>
	<?php
	get_template_part( 'template-parts/dashboard/notifications', null, array( 'account' => $account ) );
	return;
endif;

$money = $d['money'];
?>
<ul class="sm-kpis">
	<li><span><?php esc_html_e( 'Missions offered', 'sm-marketplace' ); ?></span><strong><?php echo esc_html( (string) $d['counts']['offered'] ); ?></strong></li>
	<li><span><?php esc_html_e( 'In progress', 'sm-marketplace' ); ?></span><strong><?php echo esc_html( (string) $d['counts']['current'] ); ?></strong></li>
	<li><span><?php esc_html_e( 'Fees due', 'sm-marketplace' ); ?></span><strong><?php echo esc_html( sm_money( $money['due'] ) ); ?></strong></li>
	<li><span><?php esc_html_e( 'Fees paid', 'sm-marketplace' ); ?></span><strong><?php echo esc_html( sm_money( $money['paid'] ) ); ?></strong></li>
</ul>
<div class="sm-dash__cols">
	<section class="sm-panel">
		<div class="sm-panel__head">
			<h2 class="sm-panel__title"><?php esc_html_e( 'Missions to answer and in progress', 'sm-marketplace' ); ?></h2>
			<a class="sm-link" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'missions' ) ) ); ?>"><?php esc_html_e( 'All my missions', 'sm-marketplace' ); ?><?php sm_icon( 'arrow-end' ); ?></a>
		</div>
		<?php $open = array_merge( $d['missions']['offered'], $d['missions']['current'] ); ?>
		<?php if ( $open ) : ?>
			<ul class="sm-rowlist">
				<?php foreach ( array_slice( $open, 0, 6 ) as $m ) : ?>
					<li>
						<span class="sm-rowlist__icon"><?php sm_icon( 'hard-hat' ); ?></span>
						<div><a href="<?php echo esc_url( $m['url'] ); ?>" dir="auto"><?php echo esc_html( $m['title'] ); ?></a><small><?php echo esc_html( $m['type'] ); ?> · <?php echo esc_html( sm_money( $m['fee'] ) ); ?></small></div>
						<?php sm_mission_status( $m ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="sm-muted"><?php esc_html_e( 'No mission at the moment. The platform offers you missions matching your services; you are told by e-mail.', 'sm-marketplace' ); ?></p>
		<?php endif; ?>
	</section>
	<section class="sm-panel">
		<h2 class="sm-panel__title"><?php esc_html_e( 'Your card on the list of partner engineers', 'sm-marketplace' ); ?></h2>
		<?php if ( $d['public'] ) : ?>
			<?php sm_component( 'card-engineer', array( 'engineer' => $d['public'], 'preview' => true ) ); ?>
		<?php endif; ?>
		<p class="sm-help"><?php esc_html_e( 'Clients never see your contact details: their requests go to the platform.', 'sm-marketplace' ); ?></p>
		<p class="sme-available"><?php sm_icon( $d['available'] ? 'check' : 'info' ); ?><?php echo esc_html( $d['available'] ? __( 'You are available for new missions.', 'sm-marketplace' ) : __( 'You are marked as not available.', 'sm-marketplace' ) ); ?></p>
	</section>
</div>
<?php get_template_part( 'template-parts/dashboard/notifications', null, array( 'account' => $account ) ); ?>

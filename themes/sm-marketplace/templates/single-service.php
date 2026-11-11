<?php
/**
 * Template Name: SM — Service page
 *
 * One service page (sm-services): /service/123/. Photos, services offered,
 * description, capabilities, equipment, coverage, indicative price, provider,
 * references, and the request form (or the provider's actions).
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$sid  = absint( get_query_var( 'sm_id' ) ? get_query_var( 'sm_id' ) : sm_theme_query_arg( 'id', 0 ) );
$item = $sid ? sm_theme_data( 'service', array( 'id' => $sid ) ) : null;
get_header();
if ( ! $item ) {
	status_header( 404 );
	echo '<div class="sm-container sm-section">';
	sm_component( 'empty-state', array( 'icon' => 'wrench', 'title' => __( 'This service is not available', 'sm-marketplace' ), 'text' => __( 'It may have been paused or removed by the provider.', 'sm-marketplace' ), 'action_label' => __( 'See the services', 'sm-marketplace' ), 'action_url' => sm_page_url( 'services' ) ) );
	echo '</div>';
	get_footer();
	return;
}
$page_key = 'services';
$company  = sm_theme_company( $item['company'] );
$wilayas  = sm_theme_wilayas();
$v        = (array) $item['viewer'];
$caps     = sm_lines( sm_field( $item['capabilities'] )['text'] );
$equip    = sm_lines( sm_field( $item['equipment'] )['text'] );
$desc     = sm_field( $item['description'] );
$gallery  = $item['photos'] ? $item['photos'] : array( $item['image'] );
$status   = array(
	'pending'  => __( 'Waiting for review: only you see this page.', 'sm-marketplace' ),
	'paused'   => __( 'Paused: clients do not see this page.', 'sm-marketplace' ),
	'rejected' => __( 'Changes requested by the platform team:', 'sm-marketplace' ),
);
?>
<div class="sm-container sm-detail sms-detail">
	<?php sm_theme_notices(); ?>
	<?php if ( isset( $status[ $item['status'] ] ) ) : ?>
		<div class="sm-notice sm-notice--warn"><?php sm_icon( 'info' ); ?><p><?php echo esc_html( $status[ $item['status'] ] ); ?> <?php echo esc_html( 'rejected' === $item['status'] ? $item['reject_reason'] : '' ); ?></p></div>
	<?php endif; ?>
	<?php sm_breadcrumb( array( array( __( 'Fabrication & installation', 'sm-marketplace' ), sm_page_url( $page_key ) ), array( sm_t( $item['title'] ), '' ) ) ); ?>

	<div class="sm-detail__top">
		<div class="sm-gallery" data-sm-gallery>
			<figure class="sm-gallery__main">
				<img src="<?php echo esc_url( sm_image_url( $gallery[0] ) ); ?>" alt="<?php echo esc_attr( sm_t( $item['title'] ) ); ?>" width="1280" height="960" data-sm-gallery-main>
			</figure>
			<?php if ( count( $gallery ) > 1 ) : ?>
				<div class="sm-gallery__thumbs">
					<?php foreach ( $gallery as $n => $img ) : ?>
						<button type="button" class="sm-gallery__thumb" aria-pressed="<?php echo 0 === $n ? 'true' : 'false'; ?>" data-src="<?php echo esc_url( sm_image_url( $img ) ); ?>">
							<img src="<?php echo esc_url( sm_image_url( $img ) ); ?>" alt="" width="160" height="120" loading="lazy">
							<span class="screen-reader-text"><?php echo esc_html( sprintf( /* translators: %d: photo number. */ __( 'Photo %d', 'sm-marketplace' ), $n + 1 ) ); ?></span>
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<aside class="sm-detail__aside">
			<p class="sm-card__meta"><span class="sm-chip sm-chip--kind"><?php sm_icon( 'wrench' ); ?><?php echo esc_html( sm_t( $item['kind_label'] ) ); ?></span></p>
			<h1 class="sm-detail__title"><?php sm_e( $item['title'] ); ?></h1>
			<ul class="sm-tags"><?php foreach ( $item['tags'] as $tag ) : ?><li><?php sm_e( $tag ); ?></li><?php endforeach; ?></ul>
			<dl class="sm-spec sm-spec--compact sms-facts">
				<div><dt><?php esc_html_e( 'Price', 'sm-marketplace' ); ?></dt><dd><?php echo wp_kses_post( sm_service_price( $item ) ); ?></dd></div>
				<?php if ( $item['lead_days'] ) : ?><div><dt><?php esc_html_e( 'Usual lead time', 'sm-marketplace' ); ?></dt><dd><?php echo esc_html( sprintf( /* translators: %d: days. */ _n( '%d day', '%d days', $item['lead_days'], 'sm-marketplace' ), $item['lead_days'] ) ); ?></dd></div><?php endif; ?>
				<div><dt><?php esc_html_e( 'Area served', 'sm-marketplace' ); ?></dt><dd><?php echo esc_html( $item['all_algeria'] ? __( 'All of Algeria', 'sm-marketplace' ) : implode( ', ', array_map( static fn( $w ) => sm_t( $wilayas[ $w ] ?? $w ), $item['wilayas'] ) ) ); ?></dd></div>
			</dl>
			<?php if ( $company ) : ?>
				<a class="sms-provider" href="<?php echo esc_url( sm_page_url( 'company', array( 'c' => $company['slug'] ) ) ); ?>">
					<?php sm_avatar( $company ); ?>
					<span class="sms-provider__text">
						<strong><?php sm_e( $company['name'] ); ?><?php if ( $company['verification'] >= 2 ) : ?><?php sm_icon( 'verified', 'sm-verified', sm_verification_label( $company['verification'] ) ); ?><?php endif; ?></strong>
						<small>
							<?php if ( ! empty( $company['title'] ) ) : ?><?php sm_e( $company['title'] ); ?> · <?php endif; ?>
							<?php echo esc_html( sm_t( $wilayas[ $company['wilaya'] ] ?? '' ) ); ?>
							<?php if ( ! empty( $company['experience'] ) ) : ?> · <?php echo esc_html( sprintf( /* translators: %d: years. */ _n( '%d year of experience', '%d years of experience', (int) $company['experience'], 'sm-marketplace' ), (int) $company['experience'] ) ); ?><?php endif; ?>
						</small>
						<small><?php echo esc_html( sm_verification_label( $company['verification'] ) ); ?></small>
					</span>
				</a>
			<?php endif; ?>
			<?php if ( ! empty( $v['is_owner'] ) ) : ?>
				<div class="smq-actions">
					<a class="sm-btn sm-btn--primary-outline" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'services', 'edit' => $item['id'] ) ) ); ?>"><?php sm_icon( 'edit' ); ?><?php esc_html_e( 'Edit', 'sm-marketplace' ); ?></a>
					<a class="sm-btn sm-btn--ghost" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'service-requests' ) ) ); ?>"><?php esc_html_e( 'Requests received', 'sm-marketplace' ); ?></a>
				</div>
			<?php elseif ( ! empty( $v['can_request'] ) ) : ?>
				<a class="sm-btn sm-btn--accent sm-btn--block" href="#sms-request"><?php sm_icon( 'rfq' ); ?><?php esc_html_e( 'Request a quotation', 'sm-marketplace' ); ?></a>
			<?php endif; ?>
		</aside>
	</div>

	<div class="sm-detail__body">
		<section class="sm-panel">
			<h2 class="sm-panel__title"><?php esc_html_e( 'Description', 'sm-marketplace' ); ?></h2>
			<p class="sm-prewrap"<?php echo $desc['fallback'] ? ' lang="' . esc_attr( $desc['lang'] ) . '" dir="auto"' : ''; ?>><?php echo esc_html( $desc['text'] ); ?></p>
		</section>
		<?php if ( $caps || $equip ) : ?>
			<section class="sm-panel sms-lists">
				<?php if ( $caps ) : ?>
					<div>
						<h2 class="sm-panel__title"><?php esc_html_e( 'Technical capabilities', 'sm-marketplace' ); ?></h2>
						<ul class="sm-checklist"><?php foreach ( $caps as $c ) : ?><li><?php sm_icon( 'check' ); ?><bdi><?php echo esc_html( $c ); ?></bdi></li><?php endforeach; ?></ul>
					</div>
				<?php endif; ?>
				<?php if ( $equip ) : ?>
					<div>
						<h2 class="sm-panel__title"><?php esc_html_e( 'Equipment', 'sm-marketplace' ); ?></h2>
						<ul class="sm-checklist"><?php foreach ( $equip as $c ) : ?><li><?php sm_icon( 'wrench' ); ?><bdi><?php echo esc_html( $c ); ?></bdi></li><?php endforeach; ?></ul>
					</div>
				<?php endif; ?>
			</section>
		<?php endif; ?>

		<?php if ( empty( $v['is_owner'] ) ) : ?>
			<section class="sm-panel sms-request" id="sms-request">
				<h2 class="sm-panel__title"><?php esc_html_e( 'Request a quotation', 'sm-marketplace' ); ?></h2>
				<?php if ( ! empty( $v['can_request'] ) ) : ?>
					<?php sm_component( 'service-request-form', array( 'item' => $item ) ); ?>
				<?php elseif ( ! empty( $v['open_request'] ) ) : ?>
					<p><?php esc_html_e( 'You already sent a request for this service.', 'sm-marketplace' ); ?> <a href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'my-service-requests' ) ) ); ?>"><?php esc_html_e( 'Follow it in "My service requests"', 'sm-marketplace' ); ?></a></p>
				<?php else : ?>
					<p><?php echo esc_html( $v['request_block'] ?? '' ); ?></p>
					<?php if ( empty( $v['signed_in'] ) ) : ?>
						<div class="smq-actions">
							<a class="sm-btn sm-btn--primary" href="<?php echo esc_url( add_query_arg( 'redirect_to', rawurlencode( sm_page_url( 'service', array( 'id' => $item['id'] ) ) ), sm_page_url( 'login' ) ) ); ?>"><?php sm_icon( 'user' ); ?><?php esc_html_e( 'Sign in', 'sm-marketplace' ); ?></a>
							<a class="sm-btn sm-btn--ghost" href="<?php echo esc_url( sm_page_url( 'register' ) ); ?>"><?php esc_html_e( 'Create an account', 'sm-marketplace' ); ?></a>
						</div>
					<?php endif; ?>
				<?php endif; ?>
			</section>
		<?php endif; ?>
	</div>

	<?php if ( $item['portfolio'] ) : ?>
		<section class="sm-section">
			<div class="sm-section__head"><h2><?php esc_html_e( 'Completed projects', 'sm-marketplace' ); ?></h2></div>
			<?php sm_component( 'portfolio-grid', array( 'items' => $item['portfolio'] ) ); ?>
		</section>
	<?php endif; ?>
</div>
<?php
get_footer();

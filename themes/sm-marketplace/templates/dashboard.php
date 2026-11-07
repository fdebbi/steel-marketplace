<?php
/**
 * Template Name: SM — Dashboard
 *
 * Account dashboard shell. The menu depends on the account class:
 * - commercial: buying area + selling area (one account buys and sells);
 * - engineering: profile, services and client requests only, no trading features.
 *
 * Views (?view=): overview, listings, publish, saved, rfq, offers, messages, company, plan,
 * and for engineers: profile, services, requests, verification. Demo: ?account=engineer.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$account = sm_theme_account();
$is_eng  = 'engineering' === ( $account['class'] ?? 'commercial' );
$view    = sanitize_key( sm_theme_query_arg( 'view', 'overview' ) );
$extra   = $is_eng ? array( 'account' => 'engineer' ) : array();

if ( $is_eng ) {
	$menu = array(
		'' => array(
			'overview'     => array( 'home', __( 'Overview', 'sm-marketplace' ) ),
			'profile'      => array( 'user', __( 'Professional profile', 'sm-marketplace' ) ),
			'services'     => array( 'wrench', __( 'My services', 'sm-marketplace' ) ),
			'requests'     => array( 'message', __( 'Client requests', 'sm-marketplace' ) ),
			'portfolio'    => array( 'image', __( 'Portfolio', 'sm-marketplace' ) ),
			'verification' => array( 'shield', __( 'Qualifications & verification', 'sm-marketplace' ) ),
		),
	);
} else {
	$menu = array(
		''                                  => array( 'overview' => array( 'home', __( 'Overview', 'sm-marketplace' ) ) ),
		__( 'Selling', 'sm-marketplace' )   => array(
			'publish'  => array( 'plus', __( 'Publish a listing', 'sm-marketplace' ) ),
			'listings' => array( 'package', __( 'My listings', 'sm-marketplace' ) ),
			'offers'   => array( 'rfq', __( 'Quotations sent', 'sm-marketplace' ) ),
			'messages' => array( 'message', __( 'Enquiries received', 'sm-marketplace' ) ),
			'stats'    => array( 'chart', __( 'Listing statistics', 'sm-marketplace' ) ),
		),
		__( 'Buying', 'sm-marketplace' )    => array(
			'saved'    => array( 'heart', __( 'Saved listings', 'sm-marketplace' ) ),
			'rfq'      => array( 'file', __( 'My requests for quotation', 'sm-marketplace' ) ),
			'history'  => array( 'calendar', __( 'Purchase history', 'sm-marketplace' ) ),
		),
		__( 'Company', 'sm-marketplace' )   => array(
			'company'  => array( 'company', __( 'Company profile', 'sm-marketplace' ) ),
			'plan'     => array( 'card', __( 'Plan & AI credits', 'sm-marketplace' ) ),
		),
	);
}
$titles = array();
foreach ( $menu as $group ) {
	foreach ( $group as $key => $entry ) {
		$titles[ $key ] = $entry[1];
	}
}
if ( ! isset( $titles[ $view ] ) ) {
	$view = 'overview';
}

get_header();
?>
<div class="sm-dash">
	<aside class="sm-dash__side" id="sm-dash-nav">
		<div class="sm-dash__who">
			<div class="sm-avatar sm-avatar--<?php echo esc_attr( $account['class'] ); ?>" aria-hidden="true"><?php echo esc_html( $account['company']['initials'] ); ?></div>
			<div>
				<p class="sm-dash__company"><?php sm_e( $account['company']['name'] ); ?></p>
				<p class="sm-dash__class"><?php echo esc_html( $is_eng ? __( 'Engineering account', 'sm-marketplace' ) : __( 'Business account', 'sm-marketplace' ) ); ?> · <?php sm_e( $account['plan'] ); ?></p>
			</div>
		</div>
		<nav aria-label="<?php esc_attr_e( 'Dashboard', 'sm-marketplace' ); ?>">
			<?php foreach ( $menu as $group => $entries ) : ?>
				<?php if ( $group ) : ?><p class="sm-dash__group"><?php echo esc_html( $group ); ?></p><?php endif; ?>
				<ul>
					<?php foreach ( $entries as $key => list( $icon, $label ) ) : ?>
						<li><a href="<?php echo esc_url( sm_page_url( 'account', array_merge( $extra, array( 'view' => $key ) ) ) ); ?>" <?php echo $key === $view ? 'aria-current="page"' : ''; ?>><?php sm_icon( $icon ); ?><span><?php echo esc_html( $label ); ?></span></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endforeach; ?>
			<ul class="sm-dash__end">
				<li><a href="<?php echo esc_url( sm_page_url( 'account', array_merge( $extra, array( 'view' => 'settings' ) ) ) ); ?>"><?php sm_icon( 'settings' ); ?><span><?php esc_html_e( 'Settings', 'sm-marketplace' ); ?></span></a></li>
				<li><a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php sm_icon( 'logout' ); ?><span><?php esc_html_e( 'Sign out', 'sm-marketplace' ); ?></span></a></li>
			</ul>
		</nav>
	</aside>

	<div class="sm-dash__main">
		<div class="sm-dash__head">
			<details class="sm-dash__mobilenav">
				<summary><?php sm_icon( 'menu' ); ?><span><?php echo esc_html( $titles[ $view ] ); ?></span><?php sm_icon( 'chevron-down' ); ?></summary>
				<ul>
					<?php foreach ( $titles as $key => $label ) : ?>
						<li><a href="<?php echo esc_url( sm_page_url( 'account', array_merge( $extra, array( 'view' => $key ) ) ) ); ?>" <?php echo $key === $view ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</details>
			<h1 class="sm-dash__title"><?php echo esc_html( $titles[ $view ] ); ?></h1>
			<p class="sm-demo-note"><?php sm_icon( 'info' ); ?><?php echo $is_eng ? esc_html__( 'Demo engineering account.', 'sm-marketplace' ) : esc_html__( 'Demo business account.', 'sm-marketplace' ); ?> <a href="<?php echo esc_url( sm_page_url( 'account', $is_eng ? array() : array( 'account' => 'engineer' ) ) ); ?>"><?php echo $is_eng ? esc_html__( 'Switch to the business dashboard', 'sm-marketplace' ) : esc_html__( 'Switch to the engineer dashboard', 'sm-marketplace' ); ?></a></p>
		</div>

		<?php
		$part = 'template-parts/dashboard/' . ( $is_eng ? 'engineer-' : '' ) . $view;
		if ( locate_template( $part . '.php' ) ) {
			get_template_part( $part, null, array( 'account' => $account ) );
		} else {
			do_action( 'sm_dashboard_view', $view, $account );
			if ( ! did_action( 'sm_dashboard_view_rendered' ) ) {
				sm_component(
					'empty-state',
					array(
						'icon'  => 'layers',
						'title' => __( 'This section arrives with its plugin', 'sm-marketplace' ),
						'text'  => __( 'The layout is ready; the content will be provided by the corresponding marketplace plugin.', 'sm-marketplace' ),
					)
				);
			}
		}
		?>
	</div>
</div>
<?php
get_footer();

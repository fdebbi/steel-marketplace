<?php
/**
 * Template Name: SM — Dashboard
 *
 * Account dashboard shell. The menu depends on the account class (0.13.0):
 * - company, professional, client: buying + selling (+ services for companies
 *   and professionals, engineering studies requested from the platform);
 * - engineer (partner engineer): missions, invoicing, files and plans, profile;
 *   no trading features.
 *
 * Views come from /compte/{view}/ (sm-core) or ?view=. Each view is a part in
 * template-parts/dashboard/: "engineer-{view}" first for partner engineers,
 * then "{view}". Plugins can render other views on the sm_dashboard_view action.
 *
 * Without sm-core the dashboard shows a demo account (?account=engineer).
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

// Live refresh of the technical part of the product form (assets/js/product-form.js).
if ( sm_theme_query_arg( 'smp_partial' ) && is_user_logged_in() ) {
	$sm_cats = (array) sm_theme_data( 'product_categories' );
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only preview, nothing is saved.
	$sm_state = map_deep( wp_unslash( $_GET ), 'sanitize_text_field' );
	$sm_cat   = $sm_cats[ sanitize_key( $sm_state['category'] ?? '' ) ] ?? null;
	if ( $sm_cat ) {
		$sm_markets = (array) sm_theme_data( 'product_markets' );
		sm_component( 'product-tech', array( 'state' => $sm_state, 'category' => $sm_cat, 'market' => $sm_markets[ $sm_cat['market'] ?? 'products' ] ?? array() ) );
	}
	return;
}

// Live refresh of the quantity part of the request-for-quotation form.
if ( sm_theme_query_arg( 'smq_partial' ) && is_user_logged_in() ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only preview, nothing is saved.
	sm_component( 'rfq-tech', array( 'state' => map_deep( wp_unslash( $_GET ), 'sanitize_text_field' ) ) );
	return;
}

$account = sm_theme_account();
if ( ! $account ) {
	// Signed-in user without a company (e.g. an administrator).
	get_header();
	echo '<div class="sm-container sm-section">';
	sm_component( 'empty-state', array( 'icon' => 'company', 'title' => __( 'This user has no company account', 'sm-marketplace' ), 'text' => __( 'Create an account to use the dashboard.', 'sm-marketplace' ), 'action_label' => __( 'Create an account', 'sm-marketplace' ), 'action_url' => sm_page_url( 'register' ) ) );
	echo '</div>';
	get_footer();
	return;
}

$class   = sm_account_class( $account );
$is_eng  = 'engineer' === $class;
$is_demo = empty( $account['real'] );
$view    = sanitize_key( sm_theme_route( 'view', 'overview' ) );
$extra   = ( $is_eng && $is_demo ) ? array( 'account' => 'engineer' ) : array();
$unread  = (int) ( $account['unread'] ?? 0 );

$has_ai = ! $is_demo && is_array( sm_theme_data( 'ai_tools' ) );
if ( $is_eng ) {
	// Partner engineer: missions given by the platform, never trading.
	$approved = $is_demo || 'approved' === ( $account['engineer']['status'] ?? '' );
	$menu     = array(
		'' => array(
			'overview' => array( 'home', __( 'Overview', 'sm-marketplace' ) ),
			'missions' => $approved ? array( 'hard-hat', __( 'My missions', 'sm-marketplace' ) ) : null,
			'billing'  => $approved ? array( 'card', __( 'Invoicing', 'sm-marketplace' ) ) : null,
			'files'    => $approved ? array( 'file', __( 'Files and plans', 'sm-marketplace' ) ) : null,
			'ai'       => $approved && $has_ai ? array( 'ai', __( 'AI assistant', 'sm-marketplace' ) ) : null,
			'partner'  => array( 'user', __( 'Partner profile', 'sm-marketplace' ) ),
		),
	);
} else {
	$svc_set   = (array) sm_theme_data( 'services_settings' );
	$can_fab   = ! empty( $svc_set['kinds']['fabrication']['allowed'] );
	$has_eng   = is_array( sm_theme_data( 'engineers' ) );
	$own_label = array(
		'company'      => __( 'Company profile', 'sm-marketplace' ),
		'professional' => __( 'Professional profile', 'sm-marketplace' ),
		'client'       => __( 'My profile', 'sm-marketplace' ),
	);
	$menu      = array(
		''                                  => array_filter(
			array(
				'overview' => array( 'home', __( 'Overview', 'sm-marketplace' ) ),
				'ai'       => $has_ai ? array( 'ai', __( 'AI assistant', 'sm-marketplace' ) ) : null,
			)
		),
		__( 'Selling', 'sm-marketplace' )   => array(
			'publish'  => array( 'plus', __( 'Publish a listing', 'sm-marketplace' ) ),
			'listings' => array( 'package', __( 'My listings', 'sm-marketplace' ) ),
			'offers'   => 'client' === $class ? null : array( 'rfq', __( 'Quotations sent', 'sm-marketplace' ) ),
			'messages' => array( 'message', __( 'Enquiries received', 'sm-marketplace' ) ),
			'stats'    => array( 'chart', __( 'Listing statistics', 'sm-marketplace' ) ),
		),
		__( 'Buying', 'sm-marketplace' )    => array(
			'saved'    => array( 'heart', __( 'Saved listings', 'sm-marketplace' ) ),
			'rfq'      => array( 'file', __( 'My requests for quotation', 'sm-marketplace' ) ),
			'history'  => array( 'calendar', __( 'Purchase history', 'sm-marketplace' ) ),
		),
		__( 'Services', 'sm-marketplace' )  => array_merge(
			$can_fab ? array(
				'services'         => array( 'wrench', __( 'My services', 'sm-marketplace' ) ),
				'service-requests' => array( 'rfq', __( 'Service requests received', 'sm-marketplace' ) ),
				'portfolio'        => array( 'image', __( 'Completed projects', 'sm-marketplace' ) ),
			) : array(),
			$svc_set ? array( 'my-service-requests' => array( 'wrench', __( 'My service requests', 'sm-marketplace' ) ) ) : array(),
			$has_eng ? array( 'studies' => array( 'hard-hat', __( 'Engineering studies', 'sm-marketplace' ) ) ) : array()
		),
		( 'client' === $class ? __( 'My account', 'sm-marketplace' ) : __( 'Account', 'sm-marketplace' ) ) => array(
			'company'      => array( 'client' === $class ? 'user' : 'company', $own_label[ $class ] ?? __( 'Company profile', 'sm-marketplace' ) ),
			'verification' => array( 'shield', __( 'Verification', 'sm-marketplace' ) ),
			'plan'         => 'client' === $class ? null : array( 'card', __( 'Plan & AI credits', 'sm-marketplace' ) ),
		),
	);
}
$menu   = array_filter( array_map( 'array_filter', $menu ) );
$titles = array();
foreach ( $menu as $group ) {
	foreach ( $group as $key => $entry ) {
		$titles[ $key ] = $entry[1];
	}
}
$titles['settings']     = __( 'Settings', 'sm-marketplace' );
$titles['verify-phone'] = __( 'Verify your account', 'sm-marketplace' );
if ( ! isset( $titles[ $view ] ) ) {
	$view = 'overview';
}

get_header();
?>
<div class="sm-dash">
	<aside class="sm-dash__side" id="sm-dash-nav">
		<div class="sm-dash__who">
			<?php sm_avatar( array_merge( $account['company'], array( 'class' => $class ) ) ); ?>
			<div>
				<p class="sm-dash__company"><?php sm_e( $account['company']['name'] ); ?></p>
				<p class="sm-dash__class"><?php echo esc_html( sm_class_label( $class ) ); ?><?php if ( ! empty( $account['plan'] ) ) : ?> · <?php sm_e( $account['plan'] ); ?><?php endif; ?></p>
			</div>
		</div>
		<nav aria-label="<?php esc_attr_e( 'Dashboard', 'sm-marketplace' ); ?>">
			<?php foreach ( $menu as $group => $entries ) : ?>
				<?php if ( $group ) : ?><p class="sm-dash__group"><?php echo esc_html( $group ); ?></p><?php endif; ?>
				<ul>
					<?php foreach ( $entries as $key => list( $icon, $label ) ) : ?>
						<li><a href="<?php echo esc_url( sm_page_url( 'account', array_merge( $extra, array( 'view' => $key ) ) ) ); ?>" <?php echo $key === $view ? 'aria-current="page"' : ''; ?>><?php sm_icon( $icon ); ?><span><?php echo esc_html( $label ); ?></span>
							<?php if ( 'overview' === $key && $unread ) : ?><span class="sm-count"><?php echo esc_html( $unread ); ?></span><?php endif; ?>
						</a></li>
					<?php endforeach; ?>
				</ul>
			<?php endforeach; ?>
			<ul class="sm-dash__end">
				<li><a href="<?php echo esc_url( sm_page_url( 'account', array_merge( $extra, array( 'view' => 'settings' ) ) ) ); ?>" <?php echo 'settings' === $view ? 'aria-current="page"' : ''; ?>><?php sm_icon( 'settings' ); ?><span><?php esc_html_e( 'Settings', 'sm-marketplace' ); ?></span></a></li>
				<li><a href="<?php echo esc_url( wp_logout_url() ); ?>"><?php sm_icon( 'logout' ); ?><span><?php esc_html_e( 'Sign out', 'sm-marketplace' ); ?></span></a></li>
			</ul>
		</nav>
	</aside>

	<div class="sm-dash__main">
		<div class="sm-dash__head">
			<details class="sm-dash__mobilenav">
				<summary><?php sm_icon( 'menu' ); ?><span><?php echo esc_html( $titles[ $view ] ); ?></span><?php sm_icon( 'chevron-down' ); ?></summary>
				<ul>
					<?php foreach ( $titles as $key => $label ) : ?>
						<?php if ( 'verify-phone' === $key ) { continue; } ?>
						<li><a href="<?php echo esc_url( sm_page_url( 'account', array_merge( $extra, array( 'view' => $key ) ) ) ); ?>" <?php echo $key === $view ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a></li>
					<?php endforeach; ?>
					<li><a href="<?php echo esc_url( wp_logout_url() ); ?>"><?php esc_html_e( 'Sign out', 'sm-marketplace' ); ?></a></li>
				</ul>
			</details>
			<h1 class="sm-dash__title"><?php echo esc_html( $titles[ $view ] ); ?></h1>
			<?php if ( $is_demo ) : ?>
				<p class="sm-demo-note"><?php sm_icon( 'info' ); ?><?php echo $is_eng ? esc_html__( 'Demo partner engineer account.', 'sm-marketplace' ) : esc_html__( 'Demo company account.', 'sm-marketplace' ); ?> <a href="<?php echo esc_url( sm_page_url( 'account', $is_eng ? array() : array( 'account' => 'engineer' ) ) ); ?>"><?php echo $is_eng ? esc_html__( 'Switch to the company dashboard', 'sm-marketplace' ) : esc_html__( 'Switch to the engineer dashboard', 'sm-marketplace' ); ?></a></p>
			<?php endif; ?>
			<?php sm_theme_notices( true ); ?>
			<?php if ( ! $is_demo && (int) $account['company']['verification'] < 1 && ! in_array( $view, array( 'verify-phone', 'verification' ), true ) ) : ?>
				<div class="sm-notice sm-notice--warn"><?php sm_icon( 'shield' ); ?><p><?php esc_html_e( 'Verify your account with the 6-digit code to publish and answer requests.', 'sm-marketplace' ); ?> <a href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'verify-phone' ) ) ); ?>"><?php esc_html_e( 'Enter the code', 'sm-marketplace' ); ?></a></p></div>
			<?php endif; ?>
		</div>

		<?php
		// Views shared by both account classes, under engineer-specific names.
		$aliases = array( 'profile' => 'company', 'requests' => 'messages' );
		$parts   = array();
		if ( $is_eng ) {
			$parts[] = 'template-parts/dashboard/engineer-' . $view;
		}
		$parts[] = 'template-parts/dashboard/' . ( $aliases[ $view ] ?? $view );
		$found   = false;
		foreach ( $parts as $part ) {
			if ( locate_template( $part . '.php' ) ) {
				get_template_part( $part, null, array( 'account' => $account, 'view' => $view ) );
				$found = true;
				break;
			}
		}
		if ( ! $found ) {
			ob_start();
			do_action( 'sm_dashboard_view', $view, $account );
			$plugin_view = ob_get_clean();
			if ( $plugin_view ) {
				echo $plugin_view; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the plugin.
			} else {
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

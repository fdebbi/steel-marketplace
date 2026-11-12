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
$badges = (array) ( $account['badges'] ?? array() );

/*
 * Menu (0.17.0): a short list of groups. A click on a group opens its first
 * page; the pages of the group are tabs at the top of the page. Each page keeps
 * its own address (/compte/{view}/). "hidden" views belong to a group without
 * having a tab (the publication form belongs to Selling).
 */
if ( $is_eng ) {
	// Partner engineer: missions given by the platform, never trading.
	$approved = $is_demo || 'approved' === ( $account['engineer']['status'] ?? '' );
	$groups   = array(
		'overview' => array( 'home', __( 'Overview', 'sm-marketplace' ), array( 'overview' => __( 'Overview', 'sm-marketplace' ) ) ),
		'missions' => $approved ? array( 'hard-hat', __( 'My missions', 'sm-marketplace' ), array( 'missions' => __( 'My missions', 'sm-marketplace' ) ) ) : null,
		'billing'  => $approved ? array( 'card', __( 'Invoicing', 'sm-marketplace' ), array( 'billing' => __( 'Invoicing', 'sm-marketplace' ) ) ) : null,
		'files'    => $approved ? array( 'file', __( 'Files and plans', 'sm-marketplace' ), array( 'files' => __( 'Files and plans', 'sm-marketplace' ) ) ) : null,
		'ai'       => $approved && $has_ai ? array( 'ai', __( 'AI assistant', 'sm-marketplace' ), array( 'ai' => __( 'AI assistant', 'sm-marketplace' ) ) ) : null,
		'account'  => array(
			'user',
			__( 'Account', 'sm-marketplace' ),
			array(
				'partner'  => __( 'Partner profile', 'sm-marketplace' ),
				'settings' => __( 'Settings', 'sm-marketplace' ),
			),
		),
	);
	$hidden   = array();
} else {
	$svc_set   = (array) sm_theme_data( 'services_settings' );
	$can_fab   = ! empty( $svc_set['kinds']['fabrication']['allowed'] );
	$has_eng   = is_array( sm_theme_data( 'engineers' ) );
	$own_label = array(
		'company'      => __( 'Company profile', 'sm-marketplace' ),
		'professional' => __( 'Professional profile', 'sm-marketplace' ),
		'client'       => __( 'My profile', 'sm-marketplace' ),
	);
	$groups    = array(
		'overview' => array( 'home', __( 'Overview', 'sm-marketplace' ), array( 'overview' => __( 'Overview', 'sm-marketplace' ) ) ),
		'selling'  => array(
			'package',
			__( 'Selling', 'sm-marketplace' ),
			array(
				'listings' => __( 'My listings', 'sm-marketplace' ),
				'offers'   => 'client' === $class ? null : __( 'Quotations sent', 'sm-marketplace' ),
				'messages' => __( 'Enquiries received', 'sm-marketplace' ),
				'stats'    => __( 'Statistics', 'sm-marketplace' ),
			),
		),
		'buying'   => array(
			'rfq',
			__( 'Buying', 'sm-marketplace' ),
			array(
				'saved'   => __( 'Saved listings', 'sm-marketplace' ),
				'rfq'     => __( 'My requests for quotation', 'sm-marketplace' ),
				'history' => __( 'Purchase history', 'sm-marketplace' ),
			),
		),
		'services' => array(
			'wrench',
			__( 'Services', 'sm-marketplace' ),
			array(
				'services'            => $can_fab ? __( 'My services', 'sm-marketplace' ) : null,
				'service-requests'    => $can_fab ? __( 'Requests received', 'sm-marketplace' ) : null,
				'portfolio'           => $can_fab ? __( 'Completed projects', 'sm-marketplace' ) : null,
				'my-service-requests' => $svc_set ? __( 'My service requests', 'sm-marketplace' ) : null,
				'studies'             => $has_eng ? __( 'Engineering studies', 'sm-marketplace' ) : null,
			),
		),
		'ai'       => $has_ai ? array( 'ai', __( 'AI assistant', 'sm-marketplace' ), array( 'ai' => __( 'AI assistant', 'sm-marketplace' ) ) ) : null,
		'account'  => array(
			'client' === $class ? 'user' : 'company',
			'client' === $class ? __( 'My account', 'sm-marketplace' ) : __( 'Account', 'sm-marketplace' ),
			array(
				'company'      => $own_label[ $class ] ?? __( 'Company profile', 'sm-marketplace' ),
				'verification' => __( 'Verification', 'sm-marketplace' ),
				'plan'         => 'client' === $class ? null : __( 'Plan & AI credits', 'sm-marketplace' ),
				'settings'     => __( 'Settings', 'sm-marketplace' ),
			),
		),
	);
	$hidden    = array( 'publish' => array( 'selling', __( 'Publish a listing', 'sm-marketplace' ) ) );
}
$hidden['verify-phone']               = array( 'account', __( 'Verify your account', 'sm-marketplace' ) );
$hidden['notifications']              = array( '', __( 'Notifications', 'sm-marketplace' ) );

// Drop empty pages and empty groups; find the current page and its group.
$titles = array();
$owner  = array();
foreach ( $groups as $gkey => $group ) {
	if ( ! $group ) {
		unset( $groups[ $gkey ] );
		continue;
	}
	$groups[ $gkey ][2] = array_filter( $group[2] );
	if ( ! $groups[ $gkey ][2] ) {
		unset( $groups[ $gkey ] );
		continue;
	}
	foreach ( $groups[ $gkey ][2] as $key => $label ) {
		$titles[ $key ] = $label;
		$owner[ $key ]  = $gkey;
	}
}
foreach ( $hidden as $key => list( $gkey, $label ) ) {
	if ( '' === $gkey || isset( $groups[ $gkey ] ) ) {
		$titles[ $key ] = $label;
		$owner[ $key ]  = $gkey;
	}
}
if ( ! isset( $titles[ $view ] ) ) {
	$view = 'overview';
}
$group_key = $owner[ $view ] ?? '';
$tabs      = ( $group_key && count( $groups[ $group_key ][2] ) > 1 ) ? $groups[ $group_key ][2] : array();
// A page with tabs is headed by its group; a single page by its own name.
$heading   = ( $tabs && isset( $tabs[ $view ] ) ) ? $groups[ $group_key ][1] : $titles[ $view ];
$link      = static fn( $key ) => sm_page_url( 'account', array_merge( $extra, array( 'view' => $key ) ) );
$group_cnt = static function ( $gkey ) use ( $groups, $badges, $unread ) {
	if ( 'overview' === $gkey ) {
		return $unread;
	}
	return array_sum( array_intersect_key( $badges, $groups[ $gkey ][2] ) );
};

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
		<?php if ( isset( $titles['publish'] ) ) : ?>
			<a class="sm-btn sm-btn--accent sm-btn--block sm-dash__publish" href="<?php echo esc_url( $link( 'publish' ) ); ?>" <?php echo 'publish' === $view ? 'aria-current="page"' : ''; ?>><?php sm_icon( 'plus' ); ?><span><?php esc_html_e( 'Publish a listing', 'sm-marketplace' ); ?></span></a>
		<?php endif; ?>
		<nav aria-label="<?php esc_attr_e( 'Dashboard', 'sm-marketplace' ); ?>">
			<ul>
				<?php foreach ( $groups as $gkey => list( $icon, $label, $pages ) ) : ?>
					<?php $cnt = $group_cnt( $gkey ); ?>
					<li><a href="<?php echo esc_url( $link( array_key_first( $pages ) ) ); ?>" <?php echo $gkey === $group_key && 'publish' !== $view ? 'aria-current="page"' : ''; ?>><?php sm_icon( $icon ); ?><span><?php echo esc_html( $label ); ?></span>
						<?php if ( $cnt ) : ?><span class="sm-count"><?php echo esc_html( $cnt ); ?></span><?php endif; ?>
					</a></li>
				<?php endforeach; ?>
			</ul>
			<ul class="sm-dash__end">
				<li><a href="<?php echo esc_url( wp_logout_url() ); ?>"><?php sm_icon( 'logout' ); ?><span><?php esc_html_e( 'Sign out', 'sm-marketplace' ); ?></span></a></li>
			</ul>
		</nav>
	</aside>

	<div class="sm-dash__main">
		<div class="sm-dash__head">
			<details class="sm-dash__mobilenav">
				<summary><?php sm_icon( 'menu' ); ?><span><?php esc_html_e( 'Menu', 'sm-marketplace' ); ?></span><?php sm_icon( 'chevron-down' ); ?></summary>
				<ul>
					<?php if ( isset( $titles['publish'] ) ) : ?>
						<li><a href="<?php echo esc_url( $link( 'publish' ) ); ?>" <?php echo 'publish' === $view ? 'aria-current="page"' : ''; ?>><?php esc_html_e( 'Publish a listing', 'sm-marketplace' ); ?></a></li>
					<?php endif; ?>
					<?php foreach ( $groups as $gkey => list( $icon, $label, $pages ) ) : ?>
						<?php $cnt = $group_cnt( $gkey ); ?>
						<li><a href="<?php echo esc_url( $link( array_key_first( $pages ) ) ); ?>" <?php echo $gkey === $group_key && 'publish' !== $view ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?><?php if ( $cnt ) : ?> <span class="sm-count"><?php echo esc_html( $cnt ); ?></span><?php endif; ?></a></li>
					<?php endforeach; ?>
					<li><a href="<?php echo esc_url( wp_logout_url() ); ?>"><?php esc_html_e( 'Sign out', 'sm-marketplace' ); ?></a></li>
				</ul>
			</details>
			<h1 class="sm-dash__title"><?php echo esc_html( $heading ); ?></h1>
			<?php if ( $tabs ) : ?>
				<nav class="sm-dash__tabs" aria-label="<?php echo esc_attr( $groups[ $group_key ][1] ); ?>">
					<?php foreach ( $tabs as $key => $label ) : ?>
						<a href="<?php echo esc_url( $link( $key ) ); ?>" <?php echo $key === $view ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?><?php if ( ! empty( $badges[ $key ] ) ) : ?> <span class="sm-count"><?php echo esc_html( $badges[ $key ] ); ?></span><?php endif; ?></a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>
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

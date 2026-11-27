<?php
/**
 * Site footer and mobile bottom tab bar.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
?>
</main>

<footer class="sm-footer">
	<div class="sm-container sm-footer__grid">
		<div class="sm-footer__brand">
			<?php sm_brand(); ?>
			<p><?php esc_html_e( 'Buy and sell steel by the kilogram, trade used structures and find fabricators and engineers across Algeria.', 'sm-marketplace' ); ?></p>
			<?php sm_theme_language_switcher( 'footer' ); ?>
		</div>
		<?php
		// 0.64.0: a link is shown only when its page exists and is published (never the home page instead);
		// a column without any link is not shown.
		$sm_cols = array(
			__( 'Marketplaces', 'sm-marketplace' ) => array(
				'products'  => __( 'Steel products', 'sm-marketplace' ),
				'reuse'     => __( 'Steel reuse', 'sm-marketplace' ),
				'buildings' => __( 'Steel buildings', 'sm-marketplace' ),
				'rfq'       => __( 'Requests for quotation', 'sm-marketplace' ),
			),
			__( 'Services', 'sm-marketplace' )     => array(
				'services'      => __( 'Fabrication & installation', 'sm-marketplace' ),
				'engineers'     => __( 'Engineering', 'sm-marketplace' ),
				'companies'     => __( 'Company directory', 'sm-marketplace' ),
				'professionals' => __( 'Professionals directory', 'sm-marketplace' ),
				'weight'        => __( 'Weight calculator', 'sm-marketplace' ),
				'catalogue'     => __( 'Catalogue of sections', 'sm-marketplace' ),
				'cutting'       => __( 'Cutting optimiser', 'sm-marketplace' ),
				'price-index'   => __( 'Price index', 'sm-marketplace' ),
			),
			__( 'Platform', 'sm-marketplace' )     => array(
				'pricing'  => __( 'Plans', 'sm-marketplace' ),
				'guides'   => __( 'Guides', 'sm-marketplace' ),
				'faq'      => __( 'Frequently asked questions', 'sm-marketplace' ),
				'terms'    => __( 'General terms', 'sm-marketplace' ),
				'privacy'  => __( 'Privacy', 'sm-marketplace' ),
				'register' => __( 'Create an account', 'sm-marketplace' ),
				'contact'  => __( 'Contact', 'sm-marketplace' ),
			),
		);
		foreach ( $sm_cols as $sm_title => $sm_links ) :
			$sm_links = array_filter( $sm_links, static fn( $sm_label, $sm_key ) => sm_theme_page_ready( $sm_key ), ARRAY_FILTER_USE_BOTH );
			if ( ! $sm_links ) {
				continue;
			}
			?>
		<div>
			<p class="sm-footer__title"><?php echo esc_html( $sm_title ); ?></p>
			<ul>
				<?php foreach ( $sm_links as $sm_key => $sm_label ) : ?>
					<li><a href="<?php echo esc_url( sm_page_url( $sm_key ) ); ?>"><?php echo esc_html( $sm_label ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php endforeach; ?>
	</div>
	<div class="sm-footer__bottom">
		<div class="sm-container">
			<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( sm_theme_brand_name() ); ?>. <?php esc_html_e( 'Advertised prices are set by sellers and are not official market prices.', 'sm-marketplace' ); ?></p>
		</div>
	</div>
</footer>

<nav class="sm-tabbar" aria-label="<?php esc_attr_e( 'Quick navigation', 'sm-marketplace' ); ?>">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" <?php echo is_front_page() ? 'aria-current="page"' : ''; ?>><?php sm_icon( 'home' ); ?><span><?php esc_html_e( 'Home', 'sm-marketplace' ); ?></span></a>
	<a href="<?php echo esc_url( sm_page_url( 'search' ) ); ?>"><?php sm_icon( 'search' ); ?><span><?php esc_html_e( 'Search', 'sm-marketplace' ); ?></span></a>
	<?php $sm_tab_eng = is_user_logged_in() && 'engineer' === sm_account_class( (array) sm_theme_account() ); ?>
	<?php if ( $sm_tab_eng ) : // Partner engineers never publish listings: missions and invoicing instead. ?>
		<a class="sm-tabbar__publish" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'missions' ) ) ); ?>"><?php sm_icon( 'hard-hat' ); ?><span><?php esc_html_e( 'My missions', 'sm-marketplace' ); ?></span></a>
		<a href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'billing' ) ) ); ?>"><?php sm_icon( 'card' ); ?><span><?php esc_html_e( 'Invoicing', 'sm-marketplace' ); ?></span></a>
	<?php else : ?>
		<a class="sm-tabbar__publish" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'publish' ) ) ); ?>"><?php sm_icon( 'plus' ); ?><span><?php esc_html_e( 'Publish', 'sm-marketplace' ); ?></span></a>
		<a href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'messages' ) ) ); ?>"><?php sm_icon( 'message' ); ?><span><?php esc_html_e( 'Enquiries', 'sm-marketplace' ); ?></span></a>
	<?php endif; ?>
	<a href="<?php echo esc_url( sm_page_url( 'account' ) ); ?>"><?php sm_icon( 'user' ); ?><span><?php esc_html_e( 'Account', 'sm-marketplace' ); ?></span></a>
</nav>

<div class="sm-toast" role="status" aria-live="polite" hidden data-sm-toast></div>
<?php wp_footer(); ?>
</body>
</html>

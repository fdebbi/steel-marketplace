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
		<div>
			<p class="sm-footer__title"><?php esc_html_e( 'Marketplaces', 'sm-marketplace' ); ?></p>
			<ul>
				<li><a href="<?php echo esc_url( sm_page_url( 'products' ) ); ?>"><?php esc_html_e( 'Steel products', 'sm-marketplace' ); ?></a></li>
				<li><a href="<?php echo esc_url( sm_page_url( 'reuse' ) ); ?>"><?php esc_html_e( 'Steel reuse', 'sm-marketplace' ); ?></a></li>
				<li><a href="<?php echo esc_url( sm_page_url( 'buildings' ) ); ?>"><?php esc_html_e( 'Steel buildings', 'sm-marketplace' ); ?></a></li>
				<li><a href="<?php echo esc_url( sm_page_url( 'rfq' ) ); ?>"><?php esc_html_e( 'Requests for quotation', 'sm-marketplace' ); ?></a></li>
			</ul>
		</div>
		<div>
			<p class="sm-footer__title"><?php esc_html_e( 'Services', 'sm-marketplace' ); ?></p>
			<ul>
				<li><a href="<?php echo esc_url( sm_page_url( 'services' ) ); ?>"><?php esc_html_e( 'Fabrication & installation', 'sm-marketplace' ); ?></a></li>
				<li><a href="<?php echo esc_url( sm_page_url( 'engineers' ) ); ?>"><?php esc_html_e( 'Engineering', 'sm-marketplace' ); ?></a></li>
				<li><a href="<?php echo esc_url( sm_page_url( 'companies' ) ); ?>"><?php esc_html_e( 'Company directory', 'sm-marketplace' ); ?></a></li>
				<li><a href="<?php echo esc_url( sm_page_url( 'price-index' ) ); ?>"><?php esc_html_e( 'Price index', 'sm-marketplace' ); ?></a></li>
			</ul>
		</div>
		<div>
			<p class="sm-footer__title"><?php esc_html_e( 'Platform', 'sm-marketplace' ); ?></p>
			<ul>
				<li><a href="<?php echo esc_url( sm_page_url( 'pricing' ) ); ?>"><?php esc_html_e( 'Plans', 'sm-marketplace' ); ?></a></li>
				<li><a href="<?php echo esc_url( sm_page_url( 'register' ) ); ?>"><?php esc_html_e( 'Create an account', 'sm-marketplace' ); ?></a></li>
				<li><a href="<?php echo esc_url( sm_page_url( 'contact' ) ); ?>"><?php esc_html_e( 'Contact', 'sm-marketplace' ); ?></a></li>
			</ul>
		</div>
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
	<a class="sm-tabbar__publish" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'publish' ) ) ); ?>"><?php sm_icon( 'plus' ); ?><span><?php esc_html_e( 'Publish', 'sm-marketplace' ); ?></span></a>
	<a href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'messages' ) ) ); ?>"><?php sm_icon( 'message' ); ?><span><?php esc_html_e( 'Enquiries', 'sm-marketplace' ); ?></span></a>
	<a href="<?php echo esc_url( sm_page_url( 'account' ) ); ?>"><?php sm_icon( 'user' ); ?><span><?php esc_html_e( 'Account', 'sm-marketplace' ); ?></span></a>
</nav>

<div class="sm-toast" role="status" aria-live="polite" hidden data-sm-toast></div>
<?php wp_footer(); ?>
</body>
</html>

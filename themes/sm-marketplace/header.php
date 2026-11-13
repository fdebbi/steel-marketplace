<?php
/**
 * Site header: utility bar, brand + search + actions, mega menu, mobile drawer.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
$sm_nav     = sm_theme_nav_items();
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="<?php echo esc_attr( sm_theme_color( 'primary' ) ); ?>">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?> data-sm-user="<?php echo is_user_logged_in() ? '1' : '0'; ?>">
<?php wp_body_open(); ?>
<a class="sm-skip" href="#sm-main"><?php esc_html_e( 'Skip to content', 'sm-marketplace' ); ?></a>

<header class="sm-header" data-sm-header>
	<div class="sm-header__utility">
		<div class="sm-container sm-header__utility-inner">
			<p class="sm-header__tagline"><?php esc_html_e( 'The industrial marketplace for steel in Algeria', 'sm-marketplace' ); ?></p>
			<nav class="sm-header__utility-links" aria-label="<?php esc_attr_e( 'Utility', 'sm-marketplace' ); ?>">
				<a href="<?php echo esc_url( sm_page_url( 'weight' ) ); ?>"><?php sm_icon( 'scale' ); ?><?php esc_html_e( 'Weight calculator', 'sm-marketplace' ); ?></a>
				<a href="<?php echo esc_url( sm_page_url( 'price-index' ) ); ?>"><?php sm_icon( 'chart' ); ?><?php esc_html_e( 'Price index', 'sm-marketplace' ); ?></a>
				<a href="<?php echo esc_url( sm_page_url( 'pricing' ) ); ?>"><?php esc_html_e( 'Plans', 'sm-marketplace' ); ?></a>
				<a href="<?php echo esc_url( sm_page_url( 'contact' ) ); ?>"><?php esc_html_e( 'Contact', 'sm-marketplace' ); ?></a>
			</nav>
			<?php sm_theme_language_switcher( 'header' ); ?>
		</div>
	</div>

	<div class="sm-header__main">
		<div class="sm-container sm-header__main-inner">
			<button class="sm-icon-btn sm-header__burger" type="button" aria-controls="sm-drawer" aria-expanded="false" data-sm-drawer-open>
				<?php sm_icon( 'menu' ); ?><span class="screen-reader-text"><?php esc_html_e( 'Open menu', 'sm-marketplace' ); ?></span>
			</button>

			<?php sm_brand(); ?>

			<form class="sm-search" role="search" method="get" action="<?php echo esc_url( sm_page_url( 'search' ) ); ?>">
				<label class="screen-reader-text" for="sm-search-q"><?php esc_html_e( 'Search the marketplace', 'sm-marketplace' ); ?></label>
				<select name="market" class="sm-search__market" aria-label="<?php esc_attr_e( 'Market', 'sm-marketplace' ); ?>">
					<option value=""><?php esc_html_e( 'All markets', 'sm-marketplace' ); ?></option>
					<?php foreach ( array_slice( sm_market_labels(), 0, 3, true ) as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( sm_theme_query_arg( 'market' ), $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<input id="sm-search-q" class="sm-search__input" type="search" name="q" value="<?php echo esc_attr( sm_theme_query_arg( 'q' ) ); ?>" placeholder="<?php esc_attr_e( 'IPE 200, HEA, tube 100×50, hangar…', 'sm-marketplace' ); ?>" autocomplete="off">
				<button class="sm-search__submit" type="submit"><?php sm_icon( 'search' ); ?><span class="screen-reader-text"><?php esc_html_e( 'Search', 'sm-marketplace' ); ?></span></button>
			</form>

			<div class="sm-header__actions" data-sm-usernav="head">
				<?php get_template_part( 'template-parts/header-actions' ); ?>
			</div>
		</div>
	</div>

	<nav class="sm-nav" aria-label="<?php esc_attr_e( 'Marketplaces', 'sm-marketplace' ); ?>">
		<div class="sm-container">
			<ul class="sm-nav__list">
				<?php foreach ( $sm_nav as $item ) : ?>
					<li class="sm-nav__item<?php echo ! empty( $item['mega'] ) ? ' has-mega' : ''; ?>">
						<?php if ( ! empty( $item['mega'] ) ) : ?>
							<button class="sm-nav__link" type="button" aria-expanded="false" aria-controls="sm-mega-<?php echo esc_attr( $item['key'] ); ?>" data-sm-mega>
								<?php sm_icon( $item['icon'] ); ?><span><?php echo esc_html( $item['label'] ); ?></span><?php sm_icon( 'chevron-down', 'sm-nav__caret' ); ?>
							</button>
							<?php sm_theme_mega_panel( $item ); ?>
						<?php else : ?>
							<a class="sm-nav__link" href="<?php echo esc_url( $item['url'] ); ?>"><?php sm_icon( $item['icon'] ); ?><span><?php echo esc_html( $item['label'] ); ?></span></a>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
				<li class="sm-nav__item sm-nav__item--end"><a class="sm-nav__link" href="<?php echo esc_url( sm_page_url( 'engineers' ) ); ?>"><?php sm_icon( 'hard-hat' ); ?><span><?php esc_html_e( 'Engineering', 'sm-marketplace' ); ?></span></a></li>
			</ul>
		</div>
	</nav>
</header>

<div class="sm-drawer" id="sm-drawer" hidden data-sm-drawer>
	<div class="sm-drawer__backdrop" data-sm-drawer-close></div>
	<div class="sm-drawer__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Menu', 'sm-marketplace' ); ?>">
		<div class="sm-drawer__head">
			<?php sm_brand(); ?>
			<button class="sm-icon-btn" type="button" data-sm-drawer-close><?php sm_icon( 'close' ); ?><span class="screen-reader-text"><?php esc_html_e( 'Close menu', 'sm-marketplace' ); ?></span></button>
		</div>
		<?php sm_theme_language_switcher( 'drawer' ); ?>
		<nav class="sm-drawer__nav" aria-label="<?php esc_attr_e( 'Marketplaces', 'sm-marketplace' ); ?>">
			<?php foreach ( $sm_nav as $item ) : ?>
				<?php if ( ! empty( $item['mega'] ) && 'services' !== $item['mega'] ) : ?>
					<details class="sm-drawer__group">
						<summary><?php sm_icon( $item['icon'] ); ?><span><?php echo esc_html( $item['label'] ); ?></span><?php sm_icon( 'chevron-down', 'sm-drawer__caret' ); ?></summary>
						<ul>
							<li><a href="<?php echo esc_url( $item['url'] ); ?>"><strong><?php esc_html_e( 'View all listings', 'sm-marketplace' ); ?></strong></a></li>
							<?php foreach ( sm_theme_categories( $item['mega'] ) as $slug => $label ) : ?>
								<li><a href="<?php echo esc_url( add_query_arg( 'category', $slug, $item['url'] ) ); ?>"><?php echo esc_html( sm_t( $label ) ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</details>
				<?php else : ?>
					<a class="sm-drawer__link" href="<?php echo esc_url( $item['url'] ); ?>"><?php sm_icon( $item['icon'] ); ?><span><?php echo esc_html( $item['label'] ); ?></span></a>
				<?php endif; ?>
			<?php endforeach; ?>
			<a class="sm-drawer__link" href="<?php echo esc_url( sm_page_url( 'engineers' ) ); ?>"><?php sm_icon( 'hard-hat' ); ?><span><?php esc_html_e( 'Engineering', 'sm-marketplace' ); ?></span></a>
			<a class="sm-drawer__link" href="<?php echo esc_url( sm_page_url( 'companies' ) ); ?>"><?php sm_icon( 'company' ); ?><span><?php esc_html_e( 'Company directory', 'sm-marketplace' ); ?></span></a>
			<a class="sm-drawer__link" href="<?php echo esc_url( sm_page_url( 'weight' ) ); ?>"><?php sm_icon( 'scale' ); ?><span><?php esc_html_e( 'Weight calculator', 'sm-marketplace' ); ?></span></a>
			<a class="sm-drawer__link" href="<?php echo esc_url( sm_page_url( 'price-index' ) ); ?>"><?php sm_icon( 'chart' ); ?><span><?php esc_html_e( 'Price index', 'sm-marketplace' ); ?></span></a>
		</nav>
		<div class="sm-drawer__foot" data-sm-usernav="drawer">
			<?php get_template_part( 'template-parts/drawer-foot' ); ?>
		</div>
	</div>
</div>

<main id="sm-main" class="sm-main">

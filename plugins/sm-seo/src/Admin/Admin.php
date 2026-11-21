<?php
/**
 * wp-admin: SM → SEO. AI bots, share image, verification codes, links to check.
 *
 * @package SM\Seo
 */

namespace SM\Seo\Admin;

use SM\Seo\Settings;

defined( 'ABSPATH' ) || exit;

final class Admin {

	const SLUG = 'sm-seo';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 40 );
		add_action( 'admin_post_smseo_settings', array( __CLASS__, 'save' ) );
	}

	public static function menu() {
		add_submenu_page( 'sm-core', __( 'SEO', 'sm-seo' ), __( 'SEO', 'sm-seo' ), 'sm_manage_settings', self::SLUG, array( __CLASS__, 'page' ) );
	}

	public static function page() {
		$s = Settings::all();
		echo '<div class="wrap"><h1>' . esc_html__( 'Search engines and AI', 'sm-seo' ) . '</h1>';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['saved'] ) ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'sm-seo' ) . '</p></div>';
		}
		if ( ! get_option( 'blog_public' ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'The search engines are asked not to index the site (Settings → Reading). Untick « Discourage search engines » when the site goes live.', 'sm-seo' ) . '</p></div>';
		}
		if ( file_exists( ABSPATH . 'robots.txt' ) ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'A robots.txt file exists at the root of the site: it replaces the one written by this plugin. Delete it to use the choice below.', 'sm-seo' ) . '</p></div>';
		}
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="smseo_settings">';
		wp_nonce_field( 'smseo_settings' );
		echo '<table class="form-table"><tr><th scope="row">' . esc_html__( 'AI bots', 'sm-seo' ) . '</th><td>';
		$choices = array(
			'search' => array( __( 'Allow the AI search engines (advised)', 'sm-seo' ), __( 'ChatGPT search, Perplexity, Claude… may read and quote the public pages. The bots that collect texts to train AI models are refused.', 'sm-seo' ) ),
			'all'    => array( __( 'Also allow the training of AI models', 'sm-seo' ), __( 'Every bot reads the public pages, including the collection of texts to train models.', 'sm-seo' ) ),
			'none'   => array( __( 'Refuse every AI bot', 'sm-seo' ), __( 'The AI assistants will not be able to quote the site.', 'sm-seo' ) ),
		);
		foreach ( $choices as $val => $c ) {
			printf( '<p><label><input type="radio" name="ai_bots" value="%s" %s> <strong>%s</strong></label><br><span class="description">%s</span></p>', esc_attr( $val ), checked( $s['ai_bots'], $val, false ), esc_html( $c[0] ), esc_html( $c[1] ) );
		}
		echo '<p class="description">' . esc_html__( 'Google and Bing search are not concerned: they always read the public pages. The account pages are closed to every bot.', 'sm-seo' ) . '</p></td></tr>';
		printf( '<tr><th scope="row"><label for="smseo-img">%s</label></th><td><input type="url" class="regular-text" id="smseo-img" name="default_image" value="%s"><p class="description">%s</p></td></tr>', esc_html__( 'Default share image', 'sm-seo' ), esc_attr( $s['default_image'] ), esc_html__( 'Address of an image of 1200 × 630 pixels, shown when a page without a photo is shared. Empty: the logo or the icon of the site.', 'sm-seo' ) );
		printf( '<tr><th scope="row"><label for="smseo-g">%s</label></th><td><input type="text" class="regular-text" id="smseo-g" name="google" value="%s"><p class="description">%s</p></td></tr>', esc_html__( 'Google Search Console', 'sm-seo' ), esc_attr( $s['google'] ), esc_html__( 'Verification code (method « HTML tag »): paste the code or the whole tag.', 'sm-seo' ) );
		printf( '<tr><th scope="row"><label for="smseo-b">%s</label></th><td><input type="text" class="regular-text" id="smseo-b" name="bing" value="%s"><p class="description">%s</p></td></tr>', esc_html__( 'Bing Webmaster Tools', 'sm-seo' ), esc_attr( $s['bing'] ), esc_html__( 'Verification code (meta tag msvalidate.01): paste the code or the whole tag.', 'sm-seo' ) );
		echo '</table>';
		submit_button();
		echo '</form><h2>' . esc_html__( 'Addresses to give to the search engines', 'sm-seo' ) . '</h2><ul>';
		foreach ( array( 'wp-sitemap.xml' => __( 'Sitemap (to submit in Search Console and Bing Webmaster Tools)', 'sm-seo' ), 'robots.txt' => 'robots.txt', 'llms.txt' => __( 'llms.txt (description of the site for the AI assistants)', 'sm-seo' ) ) as $path => $label ) {
			$url = home_url( '/' . $path );
			printf( '<li>%s : <a href="%s" target="_blank" rel="noopener">%s</a></li>', esc_html( $label ), esc_url( $url ), esc_html( $url ) );
		}
		echo '</ul></div>';
	}

	public static function save() {
		if ( ! current_user_can( 'sm_manage_settings' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'sm-seo' ) );
		}
		check_admin_referer( 'smseo_settings' );
		Settings::save( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitised in Settings::save().
		wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG, 'saved' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}
}

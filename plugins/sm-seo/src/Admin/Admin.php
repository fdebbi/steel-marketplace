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
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	/** Media library and the small script of the share images (0.6.0). */
	public static function assets( $hook ) {
		if ( false === strpos( (string) $hook, self::SLUG ) ) {
			return;
		}
		wp_enqueue_media();
		wp_add_inline_style( 'common', '.smseo-img{margin:0 0 14px}.smseo-img__row{display:flex;gap:12px;align-items:flex-start;margin-top:4px}.smseo-img__preview{flex:none;width:190px;height:100px;border:1px dashed #c3c4c7;border-radius:6px;background:#f6f7f7;display:grid;place-items:center;overflow:hidden}.smseo-img__preview img{width:100%;height:100%;object-fit:cover}' );
		wp_add_inline_script(
			'media-editor',
			"document.addEventListener('click',function(e){var b=e.target.closest('[data-smseo-pick],[data-smseo-default]');if(!b)return;e.preventDefault();var id=b.getAttribute('data-smseo-pick')||b.getAttribute('data-smseo-default'),input=document.getElementById(id),prev=input.closest('.smseo-img').querySelector('.smseo-img__preview');function set(u){input.value=u;prev.innerHTML=u?'<img src=\"'+u.replace(/\"/g,'')+'\" alt=\"\">':'';}if(b.hasAttribute('data-smseo-default')){set(b.getAttribute('data-url'));return;}var f=wp.media({title:b.textContent,library:{type:'image'},multiple:false});f.on('select',function(){set(f.state().get('selection').first().toJSON().url);});f.open();});"
		);
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
		// 0.6.0: one share image per language, chosen in the media library.
		$langs = array( 'fr' => __( 'French', 'sm-seo' ), 'ar' => __( 'Arabic', 'sm-seo' ), 'en' => __( 'English', 'sm-seo' ) );
		echo '<tr><th scope="row">' . esc_html__( 'Share images', 'sm-seo' ) . '</th><td>';
		foreach ( $langs as $lang => $label ) {
			$k   = Settings::image_key( $lang );
			$url = (string) $s[ $k ];
			printf(
				'<div class="smseo-img"><label for="smseo-%1$s"><strong>%2$s</strong></label><div class="smseo-img__row"><span class="smseo-img__preview">%3$s</span><span><input type="url" class="regular-text" id="smseo-%1$s" name="%1$s" value="%4$s"><br><button type="button" class="button" data-smseo-pick="smseo-%1$s">%5$s</button> <button type="button" class="button-link" data-smseo-default="smseo-%1$s" data-url="%6$s">%7$s</button></span></div></div>',
				esc_attr( $k ),
				esc_html( $label ),
				'' !== $url ? '<img src="' . esc_url( $url ) . '" alt="">' : '',
				esc_attr( $url ),
				esc_html__( 'Choose an image', 'sm-seo' ),
				esc_url( Settings::bundled_image( $lang ) ),
				esc_html__( 'Image of ReuSteel', 'sm-seo' )
			);
		}
		echo '<p class="description">' . esc_html__( 'Image of 1200 × 630 pixels (JPEG under 300 KB for WhatsApp), shown when a page without a photo is shared, in the language of the page. Empty: the French image, then the logo of the site. Listings, companies and services keep their own photo.', 'sm-seo' ) . '</p></td></tr>';
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

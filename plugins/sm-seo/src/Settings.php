<?php
/**
 * Settings (SM → SEO).
 *
 *  - ai_bots: 'search' (default, choice A of the owner: the AI search engines may read
 *    and quote the public pages, the bots that collect texts to train models are
 *    refused), 'all' or 'none';
 *  - default_image, default_image_ar, default_image_en: share image of the pages without
 *    a photo, in French, Arabic and English (URL, 1200 × 630 advised; 0.6.0: one per
 *    language, the images of assets/share/ written once at installation);
 *  - google / bing: verification codes of Search Console and Bing Webmaster Tools.
 *
 * @package SM\Seo
 */

namespace SM\Seo;

defined( 'ABSPATH' ) || exit;

final class Settings {

	const OPTION = 'sm_seo_settings';

	const DEFAULTS = array(
		'ai_bots'       => 'search',
		'default_image' => '',
		'default_image_ar' => '',
		'default_image_en' => '',
		'google'        => '',
		'bing'          => '',
	);

	public static function all() {
		return array_merge( self::DEFAULTS, array_intersect_key( (array) get_option( self::OPTION, array() ), self::DEFAULTS ) );
	}

	public static function get( $key ) {
		return self::all()[ $key ] ?? null;
	}

	public static function save( array $in ) {
		$s = array(
			'ai_bots'       => in_array( $in['ai_bots'] ?? '', array( 'search', 'all', 'none' ), true ) ? $in['ai_bots'] : 'search',
			'default_image' => esc_url_raw( trim( (string) ( $in['default_image'] ?? '' ) ) ),
			'default_image_ar' => esc_url_raw( trim( (string) ( $in['default_image_ar'] ?? '' ) ) ),
			'default_image_en' => esc_url_raw( trim( (string) ( $in['default_image_en'] ?? '' ) ) ),
			'google'        => self::code( $in['google'] ?? '' ),
			'bing'          => self::code( $in['bing'] ?? '' ),
		);
		update_option( self::OPTION, $s, false );
		return $s;
	}

	/** Field of the share image of a language. */
	public static function image_key( $lang ) {
		return 'fr' === $lang || ! in_array( $lang, array( 'ar', 'en' ), true ) ? 'default_image' : 'default_image_' . $lang;
	}

	/** Image shipped with the plugin for a language (1200 × 630, ReuSteel). */
	public static function bundled_image( $lang ) {
		$lang = in_array( $lang, array( 'fr', 'ar', 'en' ), true ) ? $lang : 'fr';
		return plugins_url( 'assets/share/reusteel-partage-' . $lang . '.jpg', SM_SEO_FILE );
	}

	/**
	 * 0.6.0: the empty share images get the images of the plugin, once (a field that
	 * is filled, or emptied later, is never written over).
	 */
	public static function seed_images() {
		if ( get_option( 'sm_seo_share_seed' ) ) {
			return false;
		}
		$s = self::all();
		foreach ( array( 'fr', 'ar', 'en' ) as $lang ) {
			$k = self::image_key( $lang );
			if ( '' === trim( (string) $s[ $k ] ) ) {
				$s[ $k ] = self::bundled_image( $lang );
			}
		}
		update_option( self::OPTION, $s, false );
		update_option( 'sm_seo_share_seed', 1, false );
		return true;
	}

	/** Verification code: the content="" value, also when the whole meta tag is pasted. */
	public static function code( $raw ) {
		$raw = trim( (string) $raw );
		if ( preg_match( '/content=["\']([^"\']+)["\']/', $raw, $m ) ) {
			$raw = $m[1];
		}
		return preg_replace( '/[^A-Za-z0-9_\-]/', '', $raw );
	}
}

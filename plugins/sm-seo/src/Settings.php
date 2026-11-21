<?php
/**
 * Settings (SM → SEO).
 *
 *  - ai_bots: 'search' (default, choice A of the owner: the AI search engines may read
 *    and quote the public pages, the bots that collect texts to train models are
 *    refused), 'all' or 'none';
 *  - default_image: share image of the pages without a photo (URL, 1200 × 630 advised);
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
			'google'        => self::code( $in['google'] ?? '' ),
			'bing'          => self::code( $in['bing'] ?? '' ),
		);
		update_option( self::OPTION, $s, false );
		return $s;
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

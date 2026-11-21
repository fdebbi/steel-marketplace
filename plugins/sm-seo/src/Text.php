<?php
/**
 * Small helpers: language, multilingual fields, pages, wilayas, numbers.
 *
 * @package SM\Seo
 */

namespace SM\Seo;

defined( 'ABSPATH' ) || exit;

final class Text {

	public static function lang() {
		return class_exists( '\SM\Core\I18n\Language' ) ? \SM\Core\I18n\Language::current() : 'fr';
	}

	/** Codes of the languages of the site (fr, ar, en). */
	public static function langs() {
		return class_exists( '\SM\Core\I18n\Language' ) ? array_keys( \SM\Core\I18n\Language::languages() ) : array( 'fr' );
	}

	/** Same address in another language. */
	public static function in_lang( $url, $code ) {
		return class_exists( '\SM\Core\I18n\Language' ) ? \SM\Core\I18n\Language::url_for( $url, $code ) : $url;
	}

	/** Text of a multilingual field { fr, ar, en } in the current language, or a fallback. */
	public static function pick( $field ) {
		if ( ! is_array( $field ) ) {
			return trim( (string) $field );
		}
		$lang = self::lang();
		foreach ( array( $lang, 'fr', 'en', 'ar' ) as $c ) {
			if ( isset( $field[ $c ] ) && '' !== trim( (string) $field[ $c ] ) ) {
				return trim( (string) $field[ $c ] );
			}
		}
		return '';
	}

	/** Data of the site, from the data contract the plugins fill (sm_theme_data_*). */
	public static function data( $resource, array $args = array() ) {
		return apply_filters( "sm_theme_data_{$resource}", null, $args );
	}

	public static function page_id( $key ) {
		$map = (array) get_option( 'sm_theme_pages', array() );
		return (int) ( $map[ $key ] ?? 0 );
	}

	/** Key of a page id ('' when the page is not one of the marketplace). */
	public static function page_key( $id ) {
		$key = array_search( (int) $id, array_map( 'intval', (array) get_option( 'sm_theme_pages', array() ) ), true );
		return false === $key ? '' : (string) $key;
	}

	/** Clean address of a page of the marketplace (the routing plugins add /id/ or /slug/). */
	public static function url( $key, array $args = array() ) {
		$url = (string) apply_filters( 'sm_page_url', '', $key, $args );
		if ( '' === $url ) {
			$id  = self::page_id( $key );
			$url = $id ? add_query_arg( $args, get_permalink( $id ) ) : home_url( '/' );
		}
		return $url;
	}

	public static function wilaya( $code ) {
		static $list = null;
		if ( null === $list ) {
			$list = (array) self::data( 'wilayas' );
		}
		return isset( $list[ (string) $code ] ) ? self::pick( $list[ (string) $code ] ) : '';
	}

	/** Number as the theme writes it: « 10,7 », « 2 300 000 » (1 decimal at most). */
	public static function number( $n, $decimals = null ) {
		$n = (float) $n;
		if ( null === $decimals ) {
			$decimals = fmod( $n, 1 ) ? 1 : 0;
		}
		return number_format( $n, $decimals, ',', "\u{202F}" );
	}

	/** Weight in t (from 1 000 kg) or kg. */
	public static function weight( $kg ) {
		$kg = (float) $kg;
		if ( $kg >= 1000 ) {
			/* translators: %s: weight in tonnes. */
			return sprintf( __( 'about %s t', 'sm-seo' ), self::number( round( $kg / 1000, 1 ) ) );
		}
		/* translators: %s: weight in kilograms. */
		return sprintf( __( 'about %s kg', 'sm-seo' ), self::number( round( $kg ) , 0 ) );
	}

	/** Cut a text at a word, at most $max characters. */
	public static function cut( $text, $max = 300 ) {
		$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $text ) ) );
		if ( mb_strlen( $text ) <= $max ) {
			return $text;
		}
		$cut = mb_substr( $text, 0, $max - 1 );
		$sp  = mb_strrpos( $cut, ' ' );
		return rtrim( false !== $sp && $sp > $max * 0.6 ? mb_substr( $cut, 0, $sp ) : $cut, " ,;:.-" ) . '…';
	}

	public static function market_label( $market ) {
		$labels = array(
			'products'  => __( 'New steel', 'sm-seo' ),
			'reuse'     => __( 'Steel reuse', 'sm-seo' ),
			'buildings' => __( 'Steel buildings', 'sm-seo' ),
		);
		return $labels[ $market ] ?? '';
	}

	public static function condition_label( $code ) {
		$labels = array(
			'new_surplus' => __( 'New unused surplus', 'sm-seo' ),
			'excellent'   => __( 'Excellent used condition', 'sm-seo' ),
			'good'        => __( 'Good used condition', 'sm-seo' ),
			'inspection'  => __( 'Requires inspection or treatment', 'sm-seo' ),
			'scrap'       => __( 'Scrap / recycling only', 'sm-seo' ),
		);
		return $labels[ $code ] ?? '';
	}

	/** Image URL of an attachment id ('' when none). */
	public static function image( $id, $size = 'large' ) {
		return is_numeric( $id ) && (int) $id > 0 ? (string) wp_get_attachment_image_url( (int) $id, $size ) : '';
	}
}

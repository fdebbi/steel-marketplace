<?php
/**
 * Lighter photos (0.9.0): a JPEG photo sent through the photo fields of the site
 * (listings, services, references, profile photo) is saved in WebP with all its sizes (cards,
 * gallery, thumbnails, share image), about a quarter to a third lighter for the same
 * quality; WordPress keeps the file as sent next to it. Not changed: the older
 * photos, PNG files, attached files and technical sheets (sent as they are), logos
 * (they go into e-mails, and some mail programs cannot show WebP) and what is
 * uploaded in wp-admin. Only when the image library of the server
 * can write WebP; filter sm_core_webp (false = off).
 *
 * @package SM\Core
 */

namespace SM\Core\Content;

defined( 'ABSPATH' ) || exit;

final class ImageFormat {

	/** @var bool Inside an upload of photos by a form of the site. */
	private static $active = false;

	public static function init() {
		add_filter( 'image_editor_output_format', array( __CLASS__, 'output_format' ) );
	}

	/** Called by Media::upload() around the files of one field: only the photos (not the attached files, sheets, logos). */
	public static function begin( $field ) {
		self::$active = in_array( (string) $field, array( 'photo', 'photos' ), true );
	}

	public static function end() {
		self::$active = false;
	}

	public static function enabled() {
		return (bool) apply_filters( 'sm_core_webp', true ) && wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) );
	}

	public static function output_format( $formats ) {
		if ( self::$active && self::enabled() ) {
			$formats['image/jpeg'] = 'image/webp';
		}
		return $formats;
	}
}

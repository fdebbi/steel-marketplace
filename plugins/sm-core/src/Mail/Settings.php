<?php
/**
 * E-mail and activity settings (option sm_mail_settings): sender, admin
 * recipients, language of admin e-mails, daily summary hour, retention, and
 * for each event the admin mode (now, digest, off) and the user e-mail switch.
 *
 * @package SM\Core
 */

namespace SM\Core\Mail;

defined( 'ABSPATH' ) || exit;

final class Settings {

	const OPTION = 'sm_mail_settings';
	const MODES  = array( 'now', 'digest', 'off' );

	public static function defaults() {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		$host = $host ? preg_replace( '/^www\./', '', $host ) : 'reusteel.com';
		return array(
			'from_name'   => sm_brand_name(),
			'from_email'  => 'contact@reusteel.com',
			'admins'      => 'contact@reusteel.com',
			'admin_lang'  => 'fr',
			'digest_hour' => 18,
			'retention'   => 365,
			'log_bodies'  => 1,
			'events'      => array(),
			'notices'     => array(),
			'host'        => $host,
		);
	}

	public static function all() {
		$s = wp_parse_args( (array) get_option( self::OPTION, array() ), self::defaults() );
		$s['events']  = (array) $s['events'];
		$s['notices'] = (array) $s['notices'];
		return $s;
	}

	public static function get( $key ) {
		return self::all()[ $key ] ?? null;
	}

	public static function save( array $values ) {
		$s = array_merge( self::all(), $values );
		update_option( self::OPTION, $s, false );
		return $s;
	}

	/** Valid admin addresses (comma, semicolon or new line separated). */
	public static function admin_emails() {
		$list = preg_split( '/[\s,;]+/', (string) self::get( 'admins' ) );
		return array_values( array_unique( array_filter( array_map( 'sanitize_email', $list ), 'is_email' ) ) );
	}

	/** Admin mode of an event: now, digest or off. */
	public static function admin_mode( $key ) {
		$ev   = Events::get( $key );
		$set  = self::all()['events'][ $key ]['admin'] ?? null;
		$mode = in_array( $set, self::MODES, true ) ? $set : ( $ev['admin'] ?? 'off' );
		return $ev && null === $ev['admin'] ? 'off' : $mode;
	}

	/** Whether the user e-mail of an event is on. */
	public static function user_on( $key ) {
		$ev = Events::get( $key );
		if ( ! $ev || null === $ev['user'] ) {
			return false;
		}
		$set = self::all()['events'][ $key ]['user'] ?? null;
		return null === $set ? (bool) $ev['user'] : (bool) $set;
	}

	/** Whether the e-mail of a user notification type is on (in-app notification is always kept). */
	public static function notice_on( $type ) {
		$set = self::all()['notices'][ $type ] ?? null;
		return null === $set ? true : (bool) $set;
	}

	public static function retention_days() {
		return max( 30, min( 3650, (int) self::get( 'retention' ) ) );
	}
}

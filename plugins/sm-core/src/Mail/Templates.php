<?php
/**
 * E-mail templates: default text (translated, per language) and the
 * administrator's edited version (option sm_mail_templates), with
 * {variable} placeholders.
 *
 * Template ids: "event:{event}:admin", "event:{event}:user" and
 * "notice:{notification type}" (e-mails of user notifications).
 *
 * @package SM\Core
 */

namespace SM\Core\Mail;

use SM\Core\I18n\Language;
use SM\Core\Notifications\Notifications;

defined( 'ABSPATH' ) || exit;

final class Templates {

	const OPTION = 'sm_mail_templates';
	const LANGS  = array( 'fr', 'ar' );

	/** Variables available in every template. */
	public static function common_vars() {
		return array(
			'site'      => sm_brand_name(),
			'lien_site' => home_url( '/' ),
			'date'      => wp_date( 'Y-m-d H:i' ),
		);
	}

	/** Default [subject, body] of a template in a language, or null when the template does not exist. */
	public static function default_text( $id, $lang ) {
		$parts = explode( ':', $id );
		if ( 'event' === $parts[0] && 3 === count( $parts ) ) {
			$ev = Events::get( $parts[1] );
			$tp = $ev['templates'][ $parts[2] ] ?? null;
			if ( ! $tp ) {
				return null;
			}
			return Language::with_language( $lang, static fn() => array( (string) call_user_func( $tp[0] ), (string) call_user_func( $tp[1] ) ) );
		}
		if ( 'notice' === $parts[0] && 2 === count( $parts ) && isset( Notifications::types()[ $parts[1] ] ) ) {
			return array( '{titre}', '{message}' );
		}
		return null;
	}

	public static function stored_all() {
		return (array) get_option( self::OPTION, array() );
	}

	/** The administrator's version, or null. */
	public static function stored( $id, $lang ) {
		$t = self::stored_all()[ $id ][ $lang ] ?? null;
		return is_array( $t ) && ( '' !== trim( (string) ( $t['subject'] ?? '' ) ) || '' !== trim( (string) ( $t['body'] ?? '' ) ) ) ? array( (string) $t['subject'], (string) $t['body'] ) : null;
	}

	public static function is_custom( $id ) {
		foreach ( self::LANGS as $l ) {
			if ( self::stored( $id, $l ) ) {
				return true;
			}
		}
		return false;
	}

	/** [subject, body] to use: the edited version (each part falls back to the default when empty). */
	public static function get( $id, $lang ) {
		$lang = in_array( $lang, self::LANGS, true ) ? $lang : 'fr';
		$def  = self::default_text( $id, $lang );
		$own  = self::stored( $id, $lang );
		if ( ! $def ) {
			return null;
		}
		if ( ! $own ) {
			return $def;
		}
		return array( '' !== trim( $own[0] ) ? $own[0] : $def[0], '' !== trim( $own[1] ) ? $own[1] : $def[1] );
	}

	public static function save( $id, $lang, $subject, $body ) {
		$all = self::stored_all();
		$all[ $id ][ $lang ] = array(
			'subject' => sanitize_text_field( $subject ),
			'body'    => sanitize_textarea_field( $body ),
		);
		update_option( self::OPTION, $all, false );
	}

	public static function reset( $id ) {
		$all = self::stored_all();
		unset( $all[ $id ] );
		update_option( self::OPTION, $all, false );
	}

	/** Subject: plain text with variables replaced. */
	public static function subject( $text, array $vars ) {
		return trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( self::replace( $text, $vars ) ) ) );
	}

	/** Body: plain text → safe HTML (paragraphs, line breaks, links). */
	public static function body_html( $text, array $vars ) {
		$text  = str_replace( "\r\n", "\n", self::replace( $text, $vars ) );
		$paras = preg_split( '/\n{2,}/', trim( $text ) );
		$out   = '';
		foreach ( $paras as $p ) {
			if ( preg_match( '/^\d{6}$/', trim( $p ) ) ) { // A verification code: shown large.
				$out .= '<p style="margin:0 0 14px;text-align:center"><span style="display:inline-block;background:#E7F6EF;border:1px dashed #16A36A;border-radius:10px;padding:10px 22px;font-size:30px;font-weight:700;letter-spacing:6px;color:#0B5A3A">' . esc_html( trim( $p ) ) . '</span></p>';
				continue;
			}
			$p = esc_html( $p );
			$p = preg_replace_callback(
				'#\bhttps?://[^\s<]+#u',
				static function ( $m ) {
					$url = rtrim( $m[0], '.,;:)' );
					$end = substr( $m[0], strlen( $url ) );
					return '<a href="' . esc_url( html_entity_decode( $url ) ) . '" style="color:#16A36A;word-break:break-all">' . $url . '</a>' . $end;
				},
				$p
			);
			$out .= '<p style="margin:0 0 14px">' . nl2br( $p, false ) . '</p>';
		}
		return $out;
	}

	/** Plain-text body (for the e-mail log and previews). */
	public static function body_text( $text, array $vars ) {
		return trim( self::replace( $text, $vars ) );
	}

	/** Replace {name} by values; unknown names are left empty. */
	public static function replace( $text, array $vars ) {
		$vars = array_merge( self::common_vars(), $vars );
		return preg_replace_callback(
			'/\{([a-z_]+)\}/',
			static function ( $m ) use ( $vars ) {
				$v = $vars[ $m[1] ] ?? '';
				return is_scalar( $v ) ? (string) $v : '';
			},
			(string) $text
		);
	}

	/** Example values for previews and test e-mails. */
	public static function sample_vars( array $names ) {
		$samples = array(
			'nom'                   => 'Karim Benali',
			'email'                 => 'karim.benali@example.dz',
			'telephone'             => '0550 12 34 56',
			'entreprise'            => 'Acier Hodna SARL',
			'email_entreprise'      => 'contact@acier-hodna.dz',
			'wilaya'                => '28 ' . ( Language::current() === 'ar' ? 'المسيلة' : 'M’Sila' ),
			'type'                  => __( 'Business account', 'sm-core' ),
			'activites'             => __( 'Supplier / distributor', 'sm-core' ),
			'titre'                 => 'IPE 270 — S275JR — 12 m',
			'message'               => __( 'Hello, we need 40 bars delivered to Rouiba next month.', 'sm-core' ),
			'sujet'                 => __( 'Price request', 'sm-core' ),
			'destinataire'          => 'Métal Est SPA',
			'identifiant'           => 'karim.benali',
			'ip'                    => '41.104.12.8',
			'montant'               => '850 000 DA',
			'delai'                 => '20',
			'quantite'              => '12 t',
			'marche'                => __( 'Steel products', 'sm-core' ),
			'prestataire'           => 'Atelier Oran Charpente',
			'client'                => 'BTP Rouiba',
			'statut'                => __( 'Published', 'sm-core' ),
			'code'                  => '482913',
		);
		$out = array();
		foreach ( array_diff( $names, array_keys( self::common_vars() ) ) as $n ) {
			$out[ $n ] = $samples[ $n ] ?? ( 0 === strpos( $n, 'lien' ) ? home_url( '/' ) : '…' );
		}
		return $out;
	}
}

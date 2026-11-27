<?php
/**
 * Terms of use and privacy policy (0.10.0): the information of the company
 * (SM → Legal information), the texts of the two pages in French, Arabic and
 * English (Markdown, in the editor of each page), the acceptance kept in each
 * account (version and date).
 *
 * The pages themselves are created by the theme (keys "terms" and "privacy").
 * The texts of data/legal/ are written once into each page; a text changed in
 * the administration is never written over.
 *
 * In a text: {card} (the box with the company information), {company},
 * {contact_email}, {court}, {dpo_line}, {dpo_email}, {anpdp}, {transfer},
 * {host}; [text](page:key) links to a page of the site.
 *
 * @package SM\Core
 */

namespace SM\Core\Legal;

use SM\Core\Content\Markdown;

defined( 'ABSPATH' ) || exit;

final class Legal {

	const OPTION = 'sm_legal';
	const META   = '_sm_legal_text';
	const USER   = 'sm_terms';
	const SEED   = 'sm_legal_seeded';
	const KEYS   = array( 'terms', 'privacy' );
	const LANGS  = array( 'fr', 'ar', 'en' );

	/** Fields of SM → Legal information. */
	const FIELDS = array( 'company', 'legal_form', 'rc', 'nif', 'address', 'court_city', 'publisher', 'contact_email', 'dpo', 'dpo_email', 'anpdp', 'transfer', 'host', 'version', 'date' );

	const DEFAULT_COMPANY = 'ReuSteel';
	const DEFAULT_EMAIL   = 'contact@reusteel.com';

	public static function init() {
		add_filter( 'sm_theme_data_legal', array( __CLASS__, 'data' ), 10, 2 );
		add_action( 'admin_init', array( __CLASS__, 'seed' ) );
		add_action( 'show_user_profile', array( __CLASS__, 'profile' ) );
		add_action( 'edit_user_profile', array( __CLASS__, 'profile' ) );
	}

	/** Saved information, with the defaults. */
	public static function info() {
		$saved = (array) get_option( self::OPTION, array() );
		$out   = array();
		foreach ( self::FIELDS as $f ) {
			$out[ $f ] = trim( (string) ( $saved[ $f ] ?? '' ) );
		}
		if ( '' === $out['version'] ) {
			$out['version'] = '1';
		}
		if ( '' === $out['host'] && ! array_key_exists( 'host', $saved ) ) {
			$out['host'] = 'Hostinger';
		}
		return $out;
	}

	public static function save_info( array $in ) {
		$clean = array();
		foreach ( self::FIELDS as $f ) {
			$v           = (string) ( $in[ $f ] ?? '' );
			$clean[ $f ] = in_array( $f, array( 'contact_email', 'dpo_email' ), true ) ? sanitize_email( $v ) : sanitize_text_field( $v );
		}
		if ( '' !== $clean['date'] && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $clean['date'] ) ) {
			$clean['date'] = '';
		}
		$clean['version'] = '' === $clean['version'] ? '1' : $clean['version'];
		update_option( self::OPTION, $clean, false );
	}

	/** Version of the texts in force. */
	public static function version() {
		return self::info()['version'];
	}

	/** Id of the page of a key (0 when the theme has not created it). */
	public static function page_id( $key ) {
		$map = (array) get_option( 'sm_theme_pages', array() );
		$id  = (int) ( $map[ $key ] ?? 0 );
		return $id && get_post( $id ) ? $id : 0;
	}

	public static function key_of( $post_id ) {
		foreach ( self::KEYS as $k ) {
			if ( (int) $post_id && self::page_id( $k ) === (int) $post_id ) {
				return $k;
			}
		}
		return '';
	}

	public static function url( $key ) {
		$id = self::page_id( $key );
		return $id && 'publish' === get_post_status( $id ) ? (string) get_permalink( $id ) : '';
	}

	/** Title of a page in a language. */
	public static function title( $key, $lang, $short = false ) {
		$t = array(
			'terms'   => array(
				'fr' => array( "Conditions générales d'utilisation", 'Conditions générales' ),
				'ar' => array( 'الشروط العامة للاستخدام', 'الشروط العامة' ),
				'en' => array( 'Terms of use', 'Terms' ),
			),
			'privacy' => array(
				'fr' => array( 'Politique de confidentialité', 'Confidentialité' ),
				'ar' => array( 'سياسة الخصوصية', 'الخصوصية' ),
				'en' => array( 'Privacy policy', 'Privacy' ),
			),
		);
		$l = isset( $t[ $key ][ $lang ] ) ? $lang : 'fr';
		return $t[ $key ][ $l ][ $short ? 1 : 0 ] ?? '';
	}

	/** Texts of a page: [ lang => markdown ]. */
	public static function texts( $key ) {
		$id  = self::page_id( $key );
		$raw = $id ? (array) get_post_meta( $id, self::META, true ) : array();
		$out = array();
		foreach ( self::LANGS as $l ) {
			$out[ $l ] = (string) ( $raw[ $l ] ?? '' );
		}
		return $out;
	}

	public static function save_texts( $post_id, array $in ) {
		$clean = array();
		foreach ( self::LANGS as $l ) {
			$clean[ $l ] = trim( str_replace( "\r", '', sanitize_textarea_field( (string) ( $in[ $l ] ?? '' ) ) ) );
		}
		update_post_meta( $post_id, self::META, $clean );
	}

	/** Text shipped with the plugin. */
	public static function default_text( $key, $lang ) {
		$file = SM_CORE_DIR . '/data/legal/' . $key . '-' . $lang . '.md';
		return is_readable( $file ) ? trim( (string) file_get_contents( $file ) ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions
	}

	/** Write the shipped texts once into each page (never over a text). */
	public static function seed() {
		$done = (array) get_option( self::SEED, array() );
		if ( count( array_intersect( self::KEYS, array_keys( array_filter( $done ) ) ) ) === count( self::KEYS ) ) {
			return 0;
		}
		$n = 0;
		foreach ( self::KEYS as $k ) {
			$id = self::page_id( $k );
			if ( ! $id || ! empty( $done[ $k ] ) ) {
				continue;
			}
			$texts = self::texts( $k );
			if ( '' === implode( '', $texts ) ) {
				$new = array();
				foreach ( self::LANGS as $l ) {
					$new[ $l ] = self::default_text( $k, $l );
				}
				update_post_meta( $id, self::META, $new );
				++$n;
			}
			$done[ $k ] = $id;
		}
		update_option( self::SEED, $done, false );
		$info = (array) get_option( self::OPTION, array() );
		if ( empty( $info['date'] ) && $n ) {
			$info['date'] = current_time( 'Y-m-d' );
			update_option( self::OPTION, $info + array( 'version' => '1' ), false );
		}
		return $n;
	}

	/** Words that change with the language. */
	private static function words( $lang ) {
		$w = array(
			'fr' => array(
				'company'    => 'Société',
				'legal_form' => 'Forme juridique',
				'rc'         => 'Registre du commerce',
				'nif'        => 'NIF',
				'address'    => 'Siège',
				'publisher'  => 'Responsable de la publication',
				'contact'    => 'Contact',
				'court'      => 'les **tribunaux de %s**',
				'court0'     => "les **tribunaux du lieu du siège de l'éditeur**",
				'dpo'        => 'Délégué à la protection des données : **%1$s**, joignable à %2$s.',
				'dpo0'       => 'Pour toute question sur vos données : %s.',
				'anpdp'      => " Traitement déclaré à l'ANPDP sous le n° %s.",
				'transfer'   => " (autorisation de l'ANPDP : %s)",
			),
			'ar' => array(
				'company'    => 'الشركة',
				'legal_form' => 'الشكل القانوني',
				'rc'         => 'السجل التجاري',
				'nif'        => 'NIF',
				'address'    => 'المقر',
				'publisher'  => 'مسؤول النشر',
				'contact'    => 'الاتصال',
				'court'      => '**محاكم %s**',
				'court0'     => '**المحاكم التابعة لمقر الناشر**',
				'dpo'        => 'مندوب حماية المعطيات: **%1$s**، على العنوان %2$s.',
				'dpo0'       => 'لأي سؤال حول معطياتك: %s.',
				'anpdp'      => ' المعالجة مصرّح بها لدى السلطة الوطنية لحماية المعطيات ذات الطابع الشخصي (ANPDP) تحت رقم %s.',
				'transfer'   => ' (ترخيص ANPDP: %s)',
			),
			'en' => array(
				'company'    => 'Company',
				'legal_form' => 'Legal form',
				'rc'         => 'Trade register',
				'nif'        => 'NIF',
				'address'    => 'Registered office',
				'publisher'  => 'Publication director',
				'contact'    => 'Contact',
				'court'      => 'the **courts of %s**',
				'court0'     => "the **courts of the place of the publisher's registered office**",
				'dpo'        => 'Data protection officer: **%1$s**, at %2$s.',
				'dpo0'       => 'For any question about your data: %s.',
				'anpdp'      => ' Processing declared to the ANPDP under no. %s.',
				'transfer'   => ' (ANPDP authorisation: %s)',
			),
		);
		return $w[ $lang ] ?? $w['fr'];
	}

	/** The box with the company information (only the filled lines). */
	public static function card( $lang ) {
		$i    = self::info();
		$w    = self::words( $lang );
		$rows = array(
			'company'    => '' !== $i['company'] ? $i['company'] : self::DEFAULT_COMPANY,
			'legal_form' => $i['legal_form'],
			'rc'         => $i['rc'],
			'nif'        => $i['nif'],
			'address'    => $i['address'],
			'publisher'  => $i['publisher'],
			'contact'    => '' !== $i['contact_email'] ? $i['contact_email'] : self::DEFAULT_EMAIL,
		);
		$html = '<dl class="sm-legal__card">';
		foreach ( $rows as $k => $v ) {
			if ( '' !== $v ) {
				$html .= '<dt>' . esc_html( $w[ $k ] ) . '</dt><dd>' . esc_html( $v ) . '</dd>';
			}
		}
		return $html . '</dl>';
	}

	/** Markdown with the words of SM → Legal information. */
	public static function fill( $text, $lang ) {
		$i       = self::info();
		$w       = self::words( $lang );
		$company = '' !== $i['company'] ? $i['company'] : self::DEFAULT_COMPANY;
		$email   = '' !== $i['contact_email'] ? $i['contact_email'] : self::DEFAULT_EMAIL;
		$dpo_m   = '' !== $i['dpo_email'] ? $i['dpo_email'] : $email;
		$map     = array(
			'{company}'       => $company,
			'{contact_email}' => $email,
			'{dpo_email}'     => $dpo_m,
			'{host}'          => '' !== $i['host'] ? $i['host'] : '—',
			'{court}'         => '' !== $i['court_city'] ? self::court( $w, $i['court_city'], $lang ) : $w['court0'],
			'{dpo_line}'      => '' !== $i['dpo'] ? sprintf( $w['dpo'], $i['dpo'], $dpo_m ) : sprintf( $w['dpo0'], $dpo_m ),
			'{anpdp}'         => '' !== $i['anpdp'] ? sprintf( $w['anpdp'], $i['anpdp'] ) : '',
			'{transfer}'      => '' !== $i['transfer'] ? sprintf( $w['transfer'], $i['transfer'] ) : '',
		);
		$text = strtr( (string) $text, $map );
		// [text](page:key): a page of the site, in the language of the page.
		return (string) preg_replace_callback(
			'/\[([^\]]+)\]\(page:([a-z0-9-]+)\)/u',
			static function ( $m ) {
				$url = in_array( $m[2], self::KEYS, true ) ? self::url( $m[2] ) : self::page_link( $m[2] );
				return '' === $url ? $m[1] : '[' . $m[1] . '](' . $url . ')';
			},
			$text
		);
	}

	private static function page_link( $key ) {
		$map = (array) get_option( 'sm_theme_pages', array() );
		$url = (string) apply_filters( 'sm_page_url', '', $key, array() );
		if ( '' === $url || ( empty( $map[ $key ] ) && untrailingslashit( $url ) === untrailingslashit( home_url() ) ) ) {
			$url = ! empty( $map[ $key ] ) && 'publish' === get_post_status( (int) $map[ $key ] ) ? (string) get_permalink( (int) $map[ $key ] ) : '';
		}
		return $url;
	}

	/** « les tribunaux de Blida », « les tribunaux d'Alger » (elision before a vowel in French). */
	private static function court( array $w, $city, $lang ) {
		if ( 'fr' === $lang && preg_match( '/^[aeiouyhâàéèêîïôûAEIOUYHÂÀÉÈÊÎÏÔÛ]/u', $city ) ) {
			return sprintf( str_replace( 'tribunaux de %s', 'tribunaux d’%s', $w['court'] ), $city );
		}
		return sprintf( $w['court'], $city );
	}

	/** HTML of a page in a language: { html, toc, lang, fallback }. */
	public static function render( $key, $lang ) {
		$texts    = self::texts( $key );
		$fallback = '' === ( $texts[ $lang ] ?? '' );
		$show     = $fallback ? 'fr' : $lang;
		$text     = '' !== $texts[ $show ] ? $texts[ $show ] : self::default_text( $key, $show );
		$md       = Markdown::render( self::fill( $text, $show ) );
		$html     = preg_replace( '#<p>\{card\}</p>#u', self::card( $show ), $md['html'] );
		$html     = str_replace( '{card}', self::card( $show ), (string) $html );
		return array(
			'html'     => self::isolate( $html, $show ),
			'toc'      => array_map( static fn( $t ) => array( 'id' => $t['id'], 'title' => self::short_heading( $t['title'] ) ), $md['toc'] ),
			'lang'     => $show,
			'fallback' => $fallback,
		);
	}

	/** "Article 3 — Inscription et comptes" → "Inscription et comptes" (the table of contents is numbered). */
	private static function short_heading( $title ) {
		$parts = preg_split( '/\s+—\s+/u', (string) $title, 2 );
		return trim( (string) ( $parts[1] ?? $parts[0] ) );
	}

	/**
	 * Arabic: Latin words (NIF, HTTPS, ANPDP, ReuSteel, an address) and law numbers
	 * (18-07) inside <bdi>, so they stay in their place in the right-to-left text.
	 */
	public static function isolate( $html, $lang ) {
		if ( 'ar' !== $lang ) {
			return $html;
		}
		$parts = preg_split( '/(<[^>]+>)/u', (string) $html, -1, PREG_SPLIT_DELIM_CAPTURE );
		foreach ( $parts as $n => $part ) {
			if ( '' === $part || '<' === $part[0] ) {
				continue;
			}
			$parts[ $n ] = preg_replace( '/(?<![&#\w])(?:[A-Za-z](?:[A-Za-z0-9@.\/_-]*[A-Za-z0-9])?|\d+-\d+)/u', '<bdi>$0</bdi>', $part );
		}
		return implode( '', $parts );
	}

	/** sm_theme_data( 'legal', { key } ). */
	public static function data( $r, $args ) {
		if ( null !== $r ) {
			return $r;
		}
		$key = (string) ( $args['key'] ?? '' );
		if ( ! in_array( $key, self::KEYS, true ) || ! self::page_id( $key ) ) {
			return null;
		}
		$lang  = function_exists( 'sm_current_language' ) ? sm_current_language() : 'fr';
		$page  = self::render( $key, $lang );
		$other = 'terms' === $key ? 'privacy' : 'terms';
		$info  = self::info();
		return array(
			'key'      => $key,
			'title'    => self::title( $key, $lang ),
			'short'    => self::title( $key, $lang, true ),
			'html'     => $page['html'],
			'toc'      => $page['toc'],
			'fallback' => $page['fallback'],
			'version'  => $info['version'],
			'date'     => $info['date'],
			'url'      => self::url( $key ),
			'other'    => array( 'key' => $other, 'title' => self::title( $other, $lang, true ), 'url' => self::url( $other ) ),
			'contact'  => '' !== $info['contact_email'] ? $info['contact_email'] : self::DEFAULT_EMAIL,
		);
	}

	/** Keep the acceptance of the texts in force (sign-up). */
	public static function record_acceptance( $user_id ) {
		update_user_meta( (int) $user_id, self::USER, array( 'version' => self::version(), 'at' => gmdate( 'Y-m-d H:i:s' ) ) );
	}

	/** @return array|null { version, at } */
	public static function acceptance( $user_id ) {
		$a = get_user_meta( (int) $user_id, self::USER, true );
		return is_array( $a ) && ! empty( $a['at'] ) ? $a : null;
	}

	/** Line on the profile of a user in the administration. */
	public static function profile( $user ) {
		if ( ! current_user_can( 'list_users' ) && ! current_user_can( 'sm_moderate' ) ) {
			return;
		}
		$a = self::acceptance( $user->ID );
		echo '<h2>' . esc_html__( 'Terms and privacy policy', 'sm-core' ) . '</h2><table class="form-table"><tr><th>' . esc_html__( 'Accepted', 'sm-core' ) . '</th><td>';
		if ( $a ) {
			/* translators: 1: version of the texts, 2: date and time. */
			echo esc_html( sprintf( __( 'Version %1$s, on %2$s', 'sm-core' ), $a['version'], wp_date( 'j F Y H:i', strtotime( $a['at'] . ' UTC' ) ) ) );
		} else {
			esc_html_e( 'Not accepted yet (account created before the checkbox).', 'sm-core' );
		}
		echo '</td></tr></table>';
	}
}

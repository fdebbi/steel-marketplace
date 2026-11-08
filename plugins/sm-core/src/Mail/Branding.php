<?php
/**
 * Look of every e-mail: header (image or coloured band with the name),
 * signature (logo, name, address, phone, e-mail, website, closing words in
 * French and Arabic) and the platform's social links.
 *
 * Stored in the "brand" key of sm_mail_settings. Images are attachments of
 * the media library; e-mails use their public URL.
 *
 * @package SM\Core
 */

namespace SM\Core\Mail;

defined( 'ABSPATH' ) || exit;

final class Branding {

	/** Social networks offered, in display order: key => label. */
	public static function networks() {
		return array(
			'facebook'  => 'Facebook',
			'instagram' => 'Instagram',
			'linkedin'  => 'LinkedIn',
			'youtube'   => 'YouTube',
			'tiktok'    => 'TikTok',
			'x'         => 'X',
			'whatsapp'  => 'WhatsApp',
		);
	}

	public static function defaults() {
		return array(
			'header_image' => 0,
			'header_bg'    => '',
			'header_align' => 'center',
			'logo'         => 0,
			'name'         => '',
			'address'      => array( 'fr' => '', 'ar' => '' ),
			'phone'        => '',
			'email'        => '',
			'website'      => '',
			'closing'      => array( 'fr' => '', 'ar' => '' ),
			'signature'    => 1,
			'social'       => array(),
		);
	}

	public static function get() {
		$b = wp_parse_args( (array) ( Settings::all()['brand'] ?? array() ), self::defaults() );
		foreach ( array( 'address', 'closing' ) as $k ) {
			$b[ $k ] = wp_parse_args( (array) $b[ $k ], array( 'fr' => '', 'ar' => '' ) );
		}
		$b['social'] = (array) $b['social'];
		return $b;
	}

	/** Clean values posted from the settings screen. */
	public static function sanitize( array $in ) {
		$out = self::defaults();
		foreach ( array( 'header_image', 'logo' ) as $k ) {
			$id        = absint( $in[ $k ] ?? 0 );
			$out[ $k ] = $id && wp_attachment_is_image( $id ) ? $id : 0;
		}
		$out['header_bg']    = sanitize_hex_color( (string) ( $in['header_bg'] ?? '' ) ) ?: '';
		$out['header_align'] = in_array( $in['header_align'] ?? '', array( 'center', 'start' ), true ) ? $in['header_align'] : 'center';
		$out['name']         = mb_substr( sanitize_text_field( (string) ( $in['name'] ?? '' ) ), 0, 120 );
		$out['phone']        = mb_substr( sanitize_text_field( (string) ( $in['phone'] ?? '' ) ), 0, 40 );
		$mail                = sanitize_email( (string) ( $in['email'] ?? '' ) );
		$out['email']        = is_email( $mail ) ? $mail : '';
		$out['website']      = esc_url_raw( (string) ( $in['website'] ?? '' ), array( 'http', 'https' ) );
		$out['signature']    = empty( $in['signature'] ) ? 0 : 1;
		foreach ( array( 'address', 'closing' ) as $k ) {
			foreach ( array( 'fr', 'ar' ) as $l ) {
				$out[ $k ][ $l ] = mb_substr( sanitize_textarea_field( (string) ( $in[ $k ][ $l ] ?? '' ) ), 0, 400 );
			}
		}
		foreach ( self::networks() as $k => $label ) {
			$v = trim( (string) ( $in['social'][ $k ] ?? '' ) );
			if ( 'whatsapp' === $k ) {
				$digits = preg_replace( '/\D/', '', $v );
				// Algerian numbers: 0XXXXXXXXX → 213XXXXXXXXX.
				if ( preg_match( '/^0\d{9}$/', $digits ) ) {
					$digits = '213' . substr( $digits, 1 );
				}
				$v = strlen( $digits ) >= 9 ? $digits : '';
			} else {
				$v = esc_url_raw( $v, array( 'https', 'http' ) );
			}
			if ( '' !== $v ) {
				$out['social'][ $k ] = $v;
			}
		}
		return $out;
	}

	/** Public URL of an image attachment ('' when none). */
	private static function image( $id, $size = 'full' ) {
		$src = $id ? wp_get_attachment_image_src( (int) $id, $size ) : false;
		return $src ? array( 'url' => $src[0], 'w' => (int) $src[1], 'h' => (int) $src[2] ) : null;
	}

	/** Link of a social network. */
	public static function social_url( $key, $value ) {
		return 'whatsapp' === $key ? 'https://wa.me/' . $value : $value;
	}

	/** Header of the e-mail (HTML table-free markup, inline styles). */
	public static function header_html( $lang ) {
		$b     = self::get();
		$brand = sm_brand_name();
		$img   = self::image( $b['header_image'] );
		$bg    = $b['header_bg'] ?: ( sanitize_hex_color( get_theme_mod( 'sm_color_primary', '#172B3A' ) ) ?: '#172B3A' );
		$link  = esc_url( home_url( 'ar' === $lang ? '/ar/' : '/' ) );
		if ( $img ) {
			return '<div style="background:' . esc_attr( $bg ) . ';text-align:center;line-height:0"><a href="' . $link . '"><img src="' . esc_url( $img['url'] ) . '" alt="' . esc_attr( $brand ) . '" width="560" style="display:block;width:100%;max-width:560px;height:auto;border:0;margin:0 auto"></a></div>';
		}
		$align = 'center' === $b['header_align'] ? 'center' : ( 'ar' === $lang ? 'right' : 'left' );
		$logo  = self::image( $b['logo'], 'medium' );
		$inner = $logo ? '<img src="' . esc_url( $logo['url'] ) . '" alt="' . esc_attr( $brand ) . '" height="40" style="height:40px;width:auto;border:0;vertical-align:middle">' : esc_html( $brand );
		return '<div style="background:' . esc_attr( $bg ) . ';color:#fff;padding:16px 24px;font-weight:bold;font-size:18px;text-align:' . $align . '"><a href="' . $link . '" style="color:#fff;text-decoration:none">' . $inner . '</a></div>';
	}

	/** Signature and social links (empty when switched off and no network). */
	public static function signature_html( $lang ) {
		$b     = self::get();
		$align = 'ar' === $lang ? 'right' : 'left';
		$out   = '';
		if ( $b['signature'] ) {
			$lines = array();
			$name  = '' !== $b['name'] ? $b['name'] : sm_brand_name();
			if ( '' !== trim( $b['closing'][ $lang ] ) ) {
				$lines[] = '<div style="margin:0 0 8px;color:#0f172a">' . nl2br( esc_html( $b['closing'][ $lang ] ), false ) . '</div>';
			}
			$lines[] = '<div style="font-weight:bold;color:#0f172a;font-size:15px">' . esc_html( $name ) . '</div>';
			if ( '' !== trim( $b['address'][ $lang ] ) ) {
				$lines[] = '<div>' . nl2br( esc_html( $b['address'][ $lang ] ), false ) . '</div>';
			}
			$contact = array();
			if ( '' !== $b['phone'] ) {
				$contact[] = '<a href="tel:' . esc_attr( preg_replace( '/[^\d+]/', '', $b['phone'] ) ) . '" style="color:#334155;text-decoration:none" dir="ltr">' . esc_html( $b['phone'] ) . '</a>';
			}
			if ( '' !== $b['email'] ) {
				$contact[] = '<a href="mailto:' . esc_attr( $b['email'] ) . '" style="color:#334155;text-decoration:none" dir="ltr">' . esc_html( $b['email'] ) . '</a>';
			}
			if ( '' !== $b['website'] ) {
				$contact[] = '<a href="' . esc_url( $b['website'] ) . '" style="color:#16A36A;text-decoration:none" dir="ltr">' . esc_html( preg_replace( '#^https?://(www\.)?#', '', untrailingslashit( $b['website'] ) ) ) . '</a>';
			}
			if ( $contact ) {
				$lines[] = '<div style="margin-top:4px">' . implode( ' &nbsp;·&nbsp; ', $contact ) . '</div>';
			}
			$logo = self::image( $b['logo'], 'medium' );
			$text = '<div style="font-size:13px;line-height:1.6;color:#475569">' . implode( '', $lines ) . '</div>';
			if ( $logo ) {
				$pad  = 'ar' === $lang ? 'padding-left:16px' : 'padding-right:16px';
				$text = '<table role="presentation" cellpadding="0" cellspacing="0" border="0" dir="' . ( 'ar' === $lang ? 'rtl' : 'ltr' ) . '" style="border-collapse:collapse"><tr>'
					. '<td valign="top" style="' . $pad . '"><img src="' . esc_url( $logo['url'] ) . '" alt="" width="72" style="display:block;width:72px;height:auto;border:0;border-radius:8px"></td>'
					. '<td valign="top" style="text-align:' . $align . '">' . $text . '</td></tr></table>';
			}
			$out .= '<div style="padding:16px 24px;border-top:1px solid #e2e8f0;text-align:' . $align . '">' . $text . '</div>';
		}
		$links = array();
		foreach ( self::networks() as $k => $label ) {
			if ( ! empty( $b['social'][ $k ] ) ) {
				$links[] = '<a href="' . esc_url( self::social_url( $k, $b['social'][ $k ] ) ) . '" style="display:inline-block;margin:0 3px 6px;padding:5px 12px;border-radius:999px;background:#F1F5F9;border:1px solid #e2e8f0;color:#334155;font-size:12px;font-weight:bold;text-decoration:none">' . esc_html( $label ) . '</a>';
			}
		}
		if ( $links ) {
			$out .= '<div style="padding:4px 24px 12px;text-align:center">' . implode( '', $links ) . '</div>';
		}
		return $out;
	}
}

<?php
/**
 * The small part of Markdown the guides use, turned into safe HTML:
 * ## and ### headings, paragraphs, **bold**, *italic*, [links](url), lists (- and 1.),
 * check lists (- [ ]), tables (| a | b |) and quotes (>). Everything else is text:
 * the text is escaped first, so no HTML written in a guide reaches the page.
 *
 * The ## headings get an id (s1, s2…) and make the table of contents.
 *
 * @package SM\Guides
 */

namespace SM\Guides;

defined( 'ABSPATH' ) || exit;

final class Markdown {

	/** @return array { html, toc: [ { id, title } ] } */
	public static function render( $text ) {
		$lines = preg_split( '/\R/u', str_replace( "\t", '    ', (string) $text ) );
		$html  = '';
		$toc   = array();
		$n     = count( $lines );
		$i     = 0;
		while ( $i < $n ) {
			$line = rtrim( $lines[ $i ] );
			if ( '' === trim( $line ) ) {
				++$i;
				continue;
			}
			// Headings (# is the title of the guide: kept out of the body).
			if ( preg_match( '/^(#{1,3})\s+(.+)$/u', $line, $m ) ) {
				$level = strlen( $m[1] );
				if ( 2 === $level ) {
					$id    = 's' . ( count( $toc ) + 1 );
					$toc[] = array( 'id' => $id, 'title' => self::plain( $m[2] ) );
					$html .= '<h2 id="' . $id . '">' . self::inline( $m[2] ) . '</h2>';
				} elseif ( 3 === $level ) {
					$html .= '<h3>' . self::inline( $m[2] ) . '</h3>';
				}
				++$i;
				continue;
			}
			// Table: a row of cells followed by the line | --- |.
			if ( self::is_row( $line ) && isset( $lines[ $i + 1 ] ) && preg_match( '/^\s*\|?\s*:?-{3,}/u', $lines[ $i + 1 ] ) ) {
				$head = self::cells( $line );
				$i   += 2;
				$rows = array();
				while ( $i < $n && self::is_row( $lines[ $i ] ) ) {
					$rows[] = self::cells( $lines[ $i ] );
					++$i;
				}
				$html .= '<div class="sm-guide__table"><table><thead><tr>';
				foreach ( $head as $c ) {
					$html .= '<th scope="col">' . self::inline( $c ) . '</th>';
				}
				$html .= '</tr></thead><tbody>';
				foreach ( $rows as $r ) {
					$html .= '<tr>';
					foreach ( $head as $k => $unused ) {
						$html .= '<td>' . self::inline( $r[ $k ] ?? '' ) . '</td>';
					}
					$html .= '</tr>';
				}
				$html .= '</tbody></table></div>';
				continue;
			}
			// Lists.
			if ( preg_match( '/^\s*(-|\*|\d+\.)\s+/u', $line ) ) {
				$ordered = (bool) preg_match( '/^\s*\d+\.\s/u', $line );
				$items   = array();
				$check   = true;
				while ( $i < $n && preg_match( '/^\s*(-|\*|\d+\.)\s+(.*)$/u', rtrim( $lines[ $i ] ), $m ) ) {
					$item = $m[2];
					if ( preg_match( '/^\[( |x|X)\]\s+(.*)$/u', $item, $c ) ) {
						$item = $c[2];
					} else {
						$check = false;
					}
					$items[] = $item;
					++$i;
				}
				$tag   = $ordered ? 'ol' : 'ul';
				$class = $check && ! $ordered ? ' class="sm-guide__check"' : '';
				$html .= '<' . $tag . $class . '>';
				foreach ( $items as $item ) {
					$html .= '<li>' . self::inline( $item ) . '</li>';
				}
				$html .= '</' . $tag . '>';
				continue;
			}
			// Quote.
			if ( preg_match( '/^>\s?(.*)$/u', $line ) ) {
				$q = array();
				while ( $i < $n && preg_match( '/^>\s?(.*)$/u', rtrim( $lines[ $i ] ), $m ) ) {
					$q[] = $m[1];
					++$i;
				}
				$html .= '<blockquote><p>' . self::inline( implode( ' ', $q ) ) . '</p></blockquote>';
				continue;
			}
			// Paragraph: lines until an empty line or another block.
			$p = array();
			while ( $i < $n && '' !== trim( $lines[ $i ] ) && ! preg_match( '/^(#{1,3}\s|>|\s*(-|\*|\d+\.)\s)/u', $lines[ $i ] ) && ! self::is_row( $lines[ $i ] ) ) {
				$p[] = trim( $lines[ $i ] );
				++$i;
			}
			if ( ! $p ) { // A line no block takes: shown as text.
				$p[] = trim( $lines[ $i ] );
				++$i;
			}
			$html .= '<p>' . self::inline( implode( ' ', $p ) ) . '</p>';
		}
		return array( 'html' => $html, 'toc' => $toc );
	}

	/** The title (# …) and the first paragraph of a text, and what follows. */
	public static function split( $text ) {
		$text  = trim( str_replace( "\r", '', (string) $text ) );
		$title = '';
		if ( preg_match( '/^#\s+(.+)\n/u', $text . "\n", $m ) ) {
			$title = trim( $m[1] );
			$text  = trim( substr( $text, strlen( $m[0] ) ) );
		}
		$parts = preg_split( '/\n\s*\n/u', $text, 2 );
		return array(
			'title'   => $title,
			'summary' => trim( preg_replace( '/\s*\n\s*/u', ' ', (string) ( $parts[0] ?? '' ) ) ),
			'body'    => trim( (string) ( $parts[1] ?? '' ) ),
		);
	}

	/** Number of ## sections of a text. */
	public static function sections( $text ) {
		return (int) preg_match_all( '/^##\s+\S/mu', (string) $text );
	}

	/** Text without the Markdown marks (for a title, a description). */
	public static function plain( $text ) {
		$t = preg_replace( '/\[([^\]]+)\]\([^)]+\)/u', '$1', (string) $text );
		return trim( str_replace( array( '**', '*', '`' ), '', $t ) );
	}

	private static function is_row( $line ) {
		return (bool) preg_match( '/^\s*\|.*\|\s*$/u', (string) $line );
	}

	private static function cells( $line ) {
		$line = trim( trim( (string) $line ), '|' );
		return array_map( 'trim', explode( '|', $line ) );
	}

	public static function inline( $text ) {
		$t = esc_html( (string) $text );
		// Links: only http(s) addresses and addresses of the site.
		$t = preg_replace_callback(
			'/\[([^\]]+)\]\(([^)\s]+)\)/u',
			static function ( $m ) {
				$url = html_entity_decode( $m[2], ENT_QUOTES );
				if ( ! preg_match( '#^(https?://|/)#u', $url ) ) {
					return $m[1];
				}
				return '<a href="' . esc_url( $url ) . '">' . $m[1] . '</a>';
			},
			$t
		);
		$t = preg_replace( '/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $t );
		$t = preg_replace( '/(?<![\*\w])\*(?!\s)(.+?)(?<!\s)\*(?![\*\w])/u', '<em>$1</em>', $t );
		return $t;
	}
}

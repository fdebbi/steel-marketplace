<?php
/**
 * Installation: writes the guides of data/guides and the questions of data/faq once (never over a guide that
 * exists, even in the trash, so a guide edited or deleted by the administrator
 * stays as it is).
 *
 * @package SM\Guides
 */

namespace SM\Guides\Install;

use SM\Guides\Faq;
use SM\Guides\Guides;
use SM\Guides\Markdown;

defined( 'ABSPATH' ) || exit;

final class Installer {

	public static function activate() {
		Guides::register_type();
		Faq::register_type();
		self::seed();
		self::seed_faq();
		delete_option( 'sm_guides_rules' ); // Rules written again on the next load.
	}

	public static function maybe_upgrade() {
		if ( (int) get_option( 'sm_guides_seed' ) < SM_GUIDES_SEED ) {
			add_action( 'init', array( __CLASS__, 'seed' ), 30 );
		}
		if ( (int) get_option( 'sm_guides_faq_seed' ) < SM_GUIDES_FAQ_SEED ) {
			add_action( 'init', array( __CLASS__, 'seed_faq' ), 31 );
		}
	}

	/** @return int Number of guides written. */
	public static function seed() {
		$defs  = include SM_GUIDES_DIR . '/data/guides.php';
		$count = 0;
		foreach ( (array) $defs as $d ) {
			$exists = get_posts(
				array(
					'post_type'      => Guides::TYPE,
					'name'           => $d['slug'],
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'fields'         => 'ids',
				)
			);
			$trashed = get_posts(
				array(
					'post_type'      => Guides::TYPE,
					'name'           => $d['slug'] . '__trashed',
					'post_status'    => 'trash',
					'posts_per_page' => 1,
					'fields'         => 'ids',
				)
			);
			if ( $exists || $trashed ) {
				continue;
			}
			$f = array( 'tools' => $d['tools'], 'topic' => $d['topic'], 'excerpt' => $d['excerpt'] );
			foreach ( Guides::LANGS as $l ) {
				$file = SM_GUIDES_DIR . '/data/guides/' . $d['slug'] . '-' . $l . '.md';
				$part = is_readable( $file ) ? Markdown::split( (string) file_get_contents( $file ) ) : array( 'title' => '', 'summary' => '', 'body' => '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
				foreach ( $part as $k => $v ) {
					$f[ $k ][ $l ] = $v;
				}
			}
			$id = wp_insert_post(
				array(
					'post_type'   => Guides::TYPE,
					'post_status' => 'publish',
					'post_title'  => $f['title']['fr'],
					'post_name'   => $d['slug'],
					'menu_order'  => (int) $d['order'],
				),
				true
			);
			if ( is_wp_error( $id ) ) {
				continue;
			}
			Guides::save_fields( $id, $f );
			++$count;
		}
		update_option( 'sm_guides_seed', SM_GUIDES_SEED, false );
		return $count;
	}

	/**
	 * The questions of data/faq.php, their texts read from data/faq/{lang}.md.
	 *
	 * @return int Number of questions written.
	 */
	public static function seed_faq() {
		$defs  = (array) include SM_GUIDES_DIR . '/data/faq.php';
		$texts = array();
		foreach ( Faq::LANGS as $l ) {
			$file        = SM_GUIDES_DIR . '/data/faq/' . $l . '.md';
			$texts[ $l ] = is_readable( $file ) ? self::parse_faq( (string) file_get_contents( $file ) ) : array(); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		$count = 0;
		foreach ( $defs as $i => $d ) {
			if ( empty( $texts['fr'][ $i ] ) || self::exists( Faq::TYPE, $d['slug'] ) ) {
				continue;
			}
			$f = array( 'group' => $d['group'], 'pages' => $d['pages'] ?? array() );
			foreach ( Faq::LANGS as $l ) {
				$f['question'][ $l ] = $texts[ $l ][ $i ]['q'] ?? '';
				$f['answer'][ $l ]   = $texts[ $l ][ $i ]['a'] ?? '';
			}
			$id = wp_insert_post(
				array(
					'post_type'   => Faq::TYPE,
					'post_status' => 'publish',
					'post_title'  => $f['question']['fr'],
					'post_name'   => $d['slug'],
					'menu_order'  => ( $i + 1 ) * 10,
				),
				true
			);
			if ( is_wp_error( $id ) ) {
				continue;
			}
			Faq::save_fields( $id, $f );
			++$count;
		}
		update_option( 'sm_guides_faq_seed', SM_GUIDES_FAQ_SEED, false );
		return $count;
	}

	/** ### question / answer blocks of a text, in their order (## themes are skipped). */
	public static function parse_faq( $text ) {
		$out = array();
		foreach ( preg_split( '/^### /mu', str_replace( "\r", '', $text ) ) as $k => $block ) {
			if ( 0 === $k ) {
				continue; // Before the first question.
			}
			$block = preg_replace( '/^##\s.*$/mu', '', $block ); // Heading of the next theme.
			$parts = explode( "\n", $block, 2 );
			$out[] = array( 'q' => trim( $parts[0] ), 'a' => trim( (string) ( $parts[1] ?? '' ) ) );
		}
		return $out;
	}

	/** A post of this type with this name exists (even in the trash). */
	private static function exists( $type, $slug ) {
		return (bool) get_posts(
			array(
				'post_type'      => $type,
				'name'           => $slug,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		) || (bool) get_posts(
			array(
				'post_type'      => $type,
				'name'           => $slug . '__trashed',
				'post_status'    => 'trash',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
	}
}

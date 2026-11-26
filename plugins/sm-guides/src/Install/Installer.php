<?php
/**
 * Installation: writes the guides of data/guides once (never over a guide that
 * exists, even in the trash, so a guide edited or deleted by the administrator
 * stays as it is).
 *
 * @package SM\Guides
 */

namespace SM\Guides\Install;

use SM\Guides\Guides;
use SM\Guides\Markdown;

defined( 'ABSPATH' ) || exit;

final class Installer {

	public static function activate() {
		Guides::register_type();
		self::seed();
		delete_option( 'sm_guides_rules' ); // Rules written again on the next load.
	}

	public static function maybe_upgrade() {
		if ( (int) get_option( 'sm_guides_seed' ) < SM_GUIDES_SEED ) {
			add_action( 'init', array( __CLASS__, 'seed' ), 30 );
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
}

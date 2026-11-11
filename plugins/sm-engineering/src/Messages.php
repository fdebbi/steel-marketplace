<?php
/**
 * Messages of a mission. Two separate channels: client ↔ platform and
 * engineer ↔ platform. The client and the engineer never see each other's
 * channel; the platform relays what is needed.
 *
 * @package SM\Engineering
 */

namespace SM\Engineering;

use SM\Core\Audit\AuditLog;

defined( 'ABSPATH' ) || exit;

final class Messages {

	const CHANNELS = array( 'client', 'engineer' );

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sm_mission_messages';
	}

	public static function add( $mission_id, $channel, $body, $from_platform = false, $user_id = null ) {
		global $wpdb;
		if ( ! in_array( $channel, self::CHANNELS, true ) || '' === trim( (string) $body ) ) {
			return 0;
		}
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			array(
				'mission_id'    => (int) $mission_id,
				'channel'       => $channel,
				'user_id'       => (int) ( $user_id ?? get_current_user_id() ),
				'from_platform' => $from_platform ? 1 : 0,
				'body'          => mb_substr( (string) $body, 0, 5000 ),
				'created_at'    => current_time( 'mysql', true ),
			)
		);
		$id = (int) $wpdb->insert_id;
		AuditLog::add( 'mission.message', 'mission', (int) $mission_id, array( 'channel' => $channel, 'platform' => $from_platform ? 1 : 0 ) );
		return $id;
	}

	public static function for_mission( $mission_id, $channel ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE mission_id = %d AND channel = %s ORDER BY id ASC', $mission_id, $channel ) ); // phpcs:ignore WordPress.DB
	}

	/** Messages as shown on the site (the platform signs with the site name). */
	public static function shape( $rows, $viewer_user ) {
		$site = get_bloginfo( 'name' );
		return array_map(
			static function ( $r ) use ( $site, $viewer_user ) {
				$u = get_userdata( (int) $r->user_id );
				return array(
					'id'       => (int) $r->id,
					'mine'     => ! $r->from_platform && (int) $r->user_id === (int) $viewer_user,
					'platform' => (bool) $r->from_platform,
					'author'   => $r->from_platform ? $site : ( $u ? $u->display_name : '' ),
					'body'     => (string) $r->body,
					'date'     => $r->created_at,
				);
			},
			$rows
		);
	}
}

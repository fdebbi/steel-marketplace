<?php
/**
 * Notifications of partner engineers and missions (on the site and by e-mail,
 * in the recipient's language, through SM Core). Each step of the workflow
 * tells the right side: the client, the engineer or the platform team.
 *
 * @package SM\Engineering
 */

namespace SM\Engineering;

use SM\Core\Notifications\Notifications as Notify;
use SM\Core\Routing\Routes as CoreRoutes;

defined( 'ABSPATH' ) || exit;

final class Notifications {

	public static function init() {
		add_filter( 'sm_notification_types', array( __CLASS__, 'types' ) );
		$steps = array( 'quoted', 'declined', 'accepted', 'quote_declined', 'cancelled', 'assigned', 'unassigned', 'started', 'engineer_declined', 'review', 'rework', 'delivered', 'closed', 'fee_paid' );
		foreach ( $steps as $step ) {
			add_action( 'sm_mission_' . $step, array( __CLASS__, 'on_' . $step ), 10, 2 );
		}
	}

	/** Dashboard link of a mission for the client or the engineer. */
	public static function link( $side, $mission_id ) {
		return CoreRoutes::url( 'account', array( 'view' => 'client' === $side ? 'studies' : 'missions', 'id' => (int) $mission_id ) );
	}

	public static function admin_link( $mission_id ) {
		return admin_url( 'admin.php?page=' . Admin\Admin::MISSIONS . '&id=' . (int) $mission_id );
	}

	public static function types( $types ) {
		$m      = static fn( $p ) => Missions::get( (int) ( $p['mission_id'] ?? 0 ) );
		$ref    = static fn( $p ) => ( $x = Missions::get( (int) ( $p['mission_id'] ?? 0 ) ) ) ? $x->ref . ' — ' . $x->title : '';
		$client = static fn( $p ) => self::link( 'client', $p['mission_id'] ?? 0 );
		$eng    = static fn( $p ) => self::link( 'engineer', $p['mission_id'] ?? 0 );
		$admin  = static fn( $p ) => self::admin_link( $p['mission_id'] ?? 0 );
		$money  = static fn( $v ) => number_format( (float) $v, 0, ',', ' ' ) . ' DA';
		$def    = static fn( $subject, $body, $link, $email = true ) => array( 'email' => $email, 'subject' => $subject, 'body' => $body, 'link' => $link );

		// Platform team: on the site only; the e-mail alerts to the platform address
		// come from the e-mail events (SM → E-mails), which the administrator chooses.
		$types['engineer.applied']       = $def(
			static fn( $p ) => __( 'New partner engineer application', 'sm-engineering' ),
			/* translators: %s: engineer name. */
			static fn( $p ) => sprintf( __( '%s applied to become a partner engineer. Check the diploma and the documents.', 'sm-engineering' ), \SM\Core\Accounts\Companies::get( (int) ( $p['company_id'] ?? 0 ) )->name ?? '' ),
			static fn( $p ) => admin_url( 'admin.php?page=' . Admin\Admin::ENGINEERS . '&id=' . (int) ( $p['company_id'] ?? 0 ) ),
			false
		);
		$types['mission.submitted']      = $def(
			static fn( $p ) => __( 'New study request', 'sm-engineering' ),
			/* translators: %s: mission reference and title. */
			static fn( $p ) => sprintf( __( 'New request %s. Prepare the quote for the client.', 'sm-engineering' ), $ref( $p ) ),
			$admin,
			false
		);
		$types['mission.client_accepted'] = $def(
			static fn( $p ) => __( 'Quote accepted: assign an engineer', 'sm-engineering' ),
			/* translators: %s: mission reference and title. */
			static fn( $p ) => sprintf( __( 'The client accepted the quote of %s. Choose the engineer.', 'sm-engineering' ), $ref( $p ) ),
			$admin,
			false
		);
		$types['mission.client_refused'] = $def(
			static fn( $p ) => __( 'Quote refused by the client', 'sm-engineering' ),
			/* translators: %s: mission reference and title. */
			static fn( $p ) => sprintf( __( 'The client did not accept the quote of %s.', 'sm-engineering' ), $ref( $p ) ),
			$admin,
			false
		);
		$types['mission.client_cancelled'] = $def(
			static fn( $p ) => __( 'Study request cancelled', 'sm-engineering' ),
			/* translators: %s: mission reference and title. */
			static fn( $p ) => sprintf( __( 'The client cancelled %s.', 'sm-engineering' ), $ref( $p ) ),
			$admin,
			false
		);
		$types['mission.engineer_took']  = $def(
			static fn( $p ) => __( 'The engineer accepted the mission', 'sm-engineering' ),
			/* translators: %s: mission reference and title. */
			static fn( $p ) => sprintf( __( 'The engineer started %s.', 'sm-engineering' ), $ref( $p ) ),
			$admin,
			false
		);
		$types['mission.engineer_gaveup'] = $def(
			static fn( $p ) => __( 'The engineer declined the mission', 'sm-engineering' ),
			/* translators: %s: mission reference and title. */
			static fn( $p ) => sprintf( __( 'The engineer cannot take %s. Choose another engineer.', 'sm-engineering' ), $ref( $p ) ),
			$admin,
			false
		);
		$types['mission.to_check']       = $def(
			static fn( $p ) => __( 'Deliverables to check', 'sm-engineering' ),
			/* translators: %s: mission reference and title. */
			static fn( $p ) => sprintf( __( 'The engineer sent the deliverables of %s. Check them before delivering them to the client.', 'sm-engineering' ), $ref( $p ) ),
			$admin,
			false
		);
		$types['mission.message_in']     = $def(
			static fn( $p ) => __( 'New message on a mission', 'sm-engineering' ),
			/* translators: 1: client or engineer, 2: mission reference and title. */
			static fn( $p ) => sprintf( __( 'New message from the %1$s on %2$s.', 'sm-engineering' ), 'engineer' === ( $p['channel'] ?? '' ) ? __( 'engineer', 'sm-engineering' ) : __( 'client', 'sm-engineering' ), $ref( $p ) ),
			$admin,
			false
		);

		// Client.
		$types['mission.quote']          = $def(
			static fn( $p ) => __( 'Your quote for the study is ready', 'sm-engineering' ),
			/* translators: 1: mission reference and title, 2: amount. */
			static fn( $p ) => sprintf( __( 'The quote for %1$s is ready: %2$s. Accept it to start the study.', 'sm-engineering' ), $ref( $p ), ( $x = $m( $p ) ) ? $money( $x->quote_amount ) : '' ),
			$client
		);
		$types['mission.not_taken']      = $def(
			static fn( $p ) => __( 'Your study request was not taken', 'sm-engineering' ),
			/* translators: 1: mission reference and title, 2: reason. */
			static fn( $p ) => sprintf( __( 'The platform cannot take %1$s: %2$s', 'sm-engineering' ), $ref( $p ), ( $x = $m( $p ) ) ? $x->reason : '' ),
			$client
		);
		$types['mission.started_client'] = $def(
			static fn( $p ) => __( 'Your study has started', 'sm-engineering' ),
			/* translators: %s: mission reference and title. */
			static fn( $p ) => sprintf( __( 'A partner engineer of the platform is working on %s.', 'sm-engineering' ), $ref( $p ) ),
			$client,
			false
		);
		$types['mission.delivered']      = $def(
			static fn( $p ) => __( 'Your study is delivered', 'sm-engineering' ),
			/* translators: %s: mission reference and title. */
			static fn( $p ) => sprintf( __( 'The documents of %s are available in your account.', 'sm-engineering' ), $ref( $p ) ),
			$client
		);
		$types['mission.reply_client']   = $def(
			static fn( $p ) => __( 'The platform answered about your study', 'sm-engineering' ),
			/* translators: %s: mission reference and title. */
			static fn( $p ) => sprintf( __( 'New message about %s.', 'sm-engineering' ), $ref( $p ) ),
			$client
		);

		// Engineer.
		$types['engineer.approved']      = $def(
			static fn( $p ) => __( 'You are a partner engineer', 'sm-engineering' ),
			static fn( $p ) => __( 'Your application is approved. You appear on the list of partner engineers and the platform can offer you missions.', 'sm-engineering' ),
			static fn( $p ) => CoreRoutes::url( 'account' )
		);
		$types['engineer.rejected']      = $def(
			static fn( $p ) => __( 'Your application needs changes', 'sm-engineering' ),
			/* translators: %s: reason. */
			static fn( $p ) => sprintf( __( 'Your partner engineer application was not approved: %s. You can correct it and send it again.', 'sm-engineering' ), $p['reason'] ?? '' ),
			static fn( $p ) => CoreRoutes::url( 'account' )
		);
		$types['engineer.suspended']     = $def(
			static fn( $p ) => __( 'Your partner account is suspended', 'sm-engineering' ),
			/* translators: %s: reason. */
			static fn( $p ) => sprintf( __( 'You no longer receive missions: %s. Write to the platform for details.', 'sm-engineering' ), $p['reason'] ?? '' ),
			static fn( $p ) => CoreRoutes::url( 'account' )
		);
		$types['mission.offered']        = $def(
			static fn( $p ) => __( 'A mission is offered to you', 'sm-engineering' ),
			/* translators: 1: mission reference and title, 2: fee. */
			static fn( $p ) => sprintf( __( 'The platform offers you %1$s. Fee: %2$s. Accept or decline it from your dashboard.', 'sm-engineering' ), $ref( $p ), ( $x = $m( $p ) ) ? $money( $x->engineer_fee ) : '' ),
			$eng
		);
		$types['mission.withdrawn']      = $def(
			static fn( $p ) => __( 'A mission is no longer assigned to you', 'sm-engineering' ),
			/* translators: %s: mission reference and title. */
			static fn( $p ) => sprintf( __( 'The platform withdrew %s from you.', 'sm-engineering' ), $ref( $p ) ),
			$eng
		);
		$types['mission.rework']         = $def(
			static fn( $p ) => __( 'Changes asked on your deliverables', 'sm-engineering' ),
			/* translators: %s: mission reference and title. */
			static fn( $p ) => sprintf( __( 'The platform asks for changes on %s. Read the message in the mission.', 'sm-engineering' ), $ref( $p ) ),
			$eng
		);
		$types['mission.validated']      = $def(
			static fn( $p ) => __( 'Your deliverables are validated', 'sm-engineering' ),
			/* translators: %s: mission reference and title. */
			static fn( $p ) => sprintf( __( 'The platform validated and delivered %s. Your fee is due.', 'sm-engineering' ), $ref( $p ) ),
			$eng
		);
		$types['mission.fee_paid']       = $def(
			static fn( $p ) => __( 'Your fee is paid', 'sm-engineering' ),
			/* translators: 1: amount, 2: mission reference and title. */
			static fn( $p ) => sprintf( __( 'The platform paid your fee of %1$s for %2$s.', 'sm-engineering' ), ( $x = $m( $p ) ) ? $money( $x->engineer_fee ) : '', $ref( $p ) ),
			$eng
		);
		$types['mission.reply_engineer'] = $def(
			static fn( $p ) => __( 'The platform wrote about a mission', 'sm-engineering' ),
			/* translators: %s: mission reference and title. */
			static fn( $p ) => sprintf( __( 'New message about %s.', 'sm-engineering' ), $ref( $p ) ),
			$eng
		);
		return $types;
	}

	private static function to_client( $m, $type ) {
		Notify::notify_company( $m->client_id, $type, array( 'mission_id' => $m->id ) );
	}

	private static function to_engineer( $engineer_id, $m, $type ) {
		if ( $engineer_id ) {
			Notify::notify_company( $engineer_id, $type, array( 'mission_id' => $m->id ) );
		}
	}

	private static function to_team( $m, $type, array $extra = array() ) {
		Notify::notify_moderators( $type, array( 'mission_id' => $m->id ) + $extra );
	}

	public static function on_quoted( $m ) {
		self::to_client( $m, 'mission.quote' );
	}

	public static function on_declined( $m ) {
		self::to_client( $m, 'mission.not_taken' );
	}

	public static function on_accepted( $m ) {
		self::to_team( $m, 'mission.client_accepted' );
	}

	public static function on_quote_declined( $m ) {
		self::to_team( $m, 'mission.client_refused' );
	}

	public static function on_cancelled( $m ) {
		self::to_team( $m, 'mission.client_cancelled' );
	}

	public static function on_assigned( $m, $log ) {
		if ( ! empty( $log['previous'] ) && (int) $log['previous'] !== (int) $m->engineer_id ) {
			self::to_engineer( (int) $log['previous'], $m, 'mission.withdrawn' );
		}
		self::to_engineer( $m->engineer_id, $m, 'mission.offered' );
	}

	public static function on_unassigned( $m, $log ) {
		self::to_engineer( (int) ( $log['engineer_id'] ?? 0 ), $m, 'mission.withdrawn' );
	}

	public static function on_started( $m ) {
		self::to_team( $m, 'mission.engineer_took' );
		self::to_client( $m, 'mission.started_client' );
	}

	public static function on_engineer_declined( $m ) {
		self::to_team( $m, 'mission.engineer_gaveup' );
	}

	public static function on_review( $m ) {
		self::to_team( $m, 'mission.to_check' );
	}

	public static function on_rework( $m ) {
		self::to_engineer( $m->engineer_id, $m, 'mission.rework' );
	}

	public static function on_delivered( $m ) {
		self::to_client( $m, 'mission.delivered' );
		self::to_engineer( $m->engineer_id, $m, 'mission.validated' );
	}

	public static function on_closed( $m ) {
	}

	public static function on_fee_paid( $m ) {
		self::to_engineer( $m->engineer_id, $m, 'mission.fee_paid' );
	}

	/** After a message: the other side is told. */
	public static function message( $m, $channel, $from_platform ) {
		if ( ! $from_platform ) {
			self::to_team( $m, 'mission.message_in', array( 'channel' => $channel ) );
		} elseif ( 'client' === $channel ) {
			self::to_client( $m, 'mission.reply_client' );
		} else {
			self::to_engineer( $m->engineer_id, $m, 'mission.reply_engineer' );
		}
	}
}

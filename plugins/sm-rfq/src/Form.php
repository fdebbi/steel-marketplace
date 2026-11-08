<?php
/**
 * Form handlers (admin-post.php): publish or edit a request, close or delete it;
 * send, update or withdraw an offer; shortlist, decline or accept an offer.
 *
 * @package SM\Rfq
 */

namespace SM\Rfq;

use SM\Core\Accounts\Companies;
use SM\Core\Content\Media;
use SM\Core\Notifications\Notifications as Notify;
use SM\Core\Routing\Routes;
use SM\Core\Support\Forms;

defined( 'ABSPATH' ) || exit;

final class Form {

	public static function init() {
		add_action( 'admin_post_sm_rfq_save', array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_post_sm_rfq_action', array( __CLASS__, 'handle_action' ) );
		add_action( 'admin_post_sm_rfq_offer', array( __CLASS__, 'handle_offer' ) );
		add_action( 'admin_post_sm_rfq_offer_action', array( __CLASS__, 'handle_offer_action' ) );
	}

	/** The request belongs to the user's company. */
	public static function is_buyer( $rfq, $user_id = null ) {
		$user_id = $user_id ?? get_current_user_id();
		return $rfq && $user_id && (bool) Companies::member_role( $rfq->company_id, $user_id );
	}

	/** Why the user cannot send an offer on this request ('' = can). */
	public static function offer_block( $rfq, $user_id = null ) {
		$user_id = $user_id ?? get_current_user_id();
		if ( ! $user_id ) {
			return __( 'Sign in with a business account to send an offer.', 'sm-rfq' );
		}
		$co      = Companies::for_user( $user_id );
		$allowed = sm_can_explain( 'rfq.offer', $co ? $co->id : 0 );
		if ( is_wp_error( $allowed ) ) {
			return $allowed->get_error_message();
		}
		if ( self::is_buyer( $rfq, $user_id ) ) {
			return __( 'This is your own request.', 'sm-rfq' );
		}
		if ( ! Rfqs::is_open( $rfq ) ) {
			return __( 'This request no longer accepts offers.', 'sm-rfq' );
		}
		return '';
	}

	private static function files_mimes() {
		return array_merge( Media::PDF_TYPES, Media::IMAGE_TYPES );
	}

	private static function owns_attachment( $aid, $user_id, $entity, $id ) {
		if ( ! $aid || 'attachment' !== get_post_type( $aid ) ) {
			return false;
		}
		return (int) get_post_field( 'post_author', $aid ) === (int) $user_id || ( $id && in_array( $aid, Media::get( $entity, $id, 'file' ), true ) );
	}

	/** Uploaded and kept files of a form; errors returned for the notice. */
	private static function files( $user_id, $entity, $id ) {
		$s    = Settings::all();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in Forms::begin().
		$keep = array_map( 'absint', (array) wp_unslash( $_POST['keep_files'] ?? array() ) );
		$keep = array_values( array_filter( $keep, static fn( $a ) => self::owns_attachment( $a, $user_id, $entity, $id ) ) );
		$new  = Media::upload( 'files', self::files_mimes(), max( 0, $s['max_files'] - count( $keep ) ), $s['file_mb'] * 1048576 );
		foreach ( $new['ids'] as $aid ) {
			update_post_meta( $aid, '_sm_owner', $user_id );
		}
		$_POST['keep_files'] = array_merge( $keep, $new['ids'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- kept for the form on error.
		return array( array_merge( $keep, $new['ids'] ), $new['errors'] );
	}

	public static function handle_save() {
		Forms::begin( 'rfq_save' );
		$user_id = get_current_user_id();
		$company = Companies::for_user( $user_id );
		$intent  = sanitize_key( Forms::post( 'intent', 'submit' ) );
		if ( 'refresh' === $intent ) {
			Forms::back( array( '_refresh' => '' ) );
		}
		$allowed = sm_can_explain( 'rfq.create', $company ? $company->id : 0 );
		if ( is_wp_error( $allowed ) || ! $company ) {
			Forms::back( array( is_wp_error( $allowed ) ? $allowed->get_error_message() : __( 'Your account has no company.', 'sm-rfq' ) ) );
		}
		$id  = absint( Forms::post( 'rfq_id' ) );
		$rfq = $id ? Rfqs::get( $id ) : null;
		if ( $id && ( ! self::is_buyer( $rfq, $user_id ) || ! in_array( $rfq->status, array( 'pending', 'open', 'rejected' ), true ) || $rfq->offers_count > 0 ) ) {
			Forms::back( array( __( 'This request can no longer be edited: it already has offers or is closed.', 'sm-rfq' ) ) );
		}
		if ( ! $id && ! Forms::rate_limit( 'rfq|' . $company->id, (int) Settings::get( 'max_per_day' ), DAY_IN_SECONDS ) ) {
			Forms::back( array( __( 'You published many requests today. Try again tomorrow.', 'sm-rfq' ) ) );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in Forms::begin().
		$spec = Spec::build( (array) wp_unslash( $_POST ), true );
		list( $files, $file_errors ) = self::files( $user_id, 'rfq', $id );
		if ( $spec['errors'] ) {
			Forms::back( array_merge( $spec['errors'], $file_errors ) );
		}
		$status = (int) Settings::get( 'moderation' ) && ! current_user_can( 'sm_moderate' ) ? 'pending' : 'open';
		$rid    = Rfqs::save( $id, $company->id, $user_id, $spec, $status, $files );
		if ( 'pending' === $status ) {
			Notify::notify_moderators( 'rfq.submitted', array( 'rfq_id' => $rid ) );
		}
		$msg = 'pending' === $status ? __( 'Thank you. Your request will be visible after a quick check by the platform team.', 'sm-rfq' ) : __( 'Your request is published. Suppliers can now send their offers.', 'sm-rfq' );
		Forms::back( $file_errors, $msg, \SM\Rfq\Routes::link( $rid ) );
	}

	public static function handle_action() {
		Forms::begin( 'rfq_action' );
		$rfq = Rfqs::get( absint( Forms::post( 'rfq_id' ) ) );
		if ( ! self::is_buyer( $rfq ) ) {
			Forms::back( array( __( 'You cannot change this request.', 'sm-rfq' ) ) );
		}
		switch ( sanitize_key( Forms::post( 'do' ) ) ) {
			case 'close':
				if ( ! in_array( $rfq->status, array( 'open', 'pending' ), true ) ) {
					Forms::back( array( __( 'This request is already closed.', 'sm-rfq' ) ) );
				}
				Rfqs::set_status( $rfq->id, 'closed' );
				foreach ( Offers::for_rfq( $rfq->id, false ) as $o ) {
					Notify::notify_company( $o->company_id, 'rfq.closed', array( 'rfq_id' => $rfq->id ) );
				}
				Forms::back( array(), __( 'The request is closed. Suppliers were told.', 'sm-rfq' ) );
				break;
			case 'delete':
				if ( $rfq->offers_count > 0 || 'awarded' === $rfq->status ) {
					Forms::back( array( __( 'A request with offers is kept for your records: close it instead.', 'sm-rfq' ) ) );
				}
				Rfqs::delete( $rfq->id );
				Forms::back( array(), __( 'Request deleted.', 'sm-rfq' ), Routes::url( 'account', array( 'view' => 'rfq' ) ) );
				break;
		}
		Forms::back( array( __( 'Unknown action.', 'sm-rfq' ) ) );
	}

	public static function handle_offer() {
		Forms::begin( 'rfq_offer' );
		$user_id = get_current_user_id();
		$rfq     = Rfqs::get( absint( Forms::post( 'rfq_id' ) ) );
		$company = Companies::for_user( $user_id );
		$block   = $rfq ? self::offer_block( $rfq, $user_id ) : __( 'This request does not exist.', 'sm-rfq' );
		if ( $block || ! $company ) {
			Forms::back( array( $block ? $block : __( 'Your account has no company.', 'sm-rfq' ) ) );
		}
		$existing = Offers::by_company( $rfq->id, $company->id );
		if ( $existing && in_array( $existing->status, array( 'accepted', 'declined' ), true ) ) {
			Forms::back( array( __( 'The buyer already decided on your offer.', 'sm-rfq' ) ) );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in Forms::begin().
		$spec = Spec::offer( $rfq, (array) wp_unslash( $_POST ), true );
		list( $files, $file_errors ) = self::files( $user_id, 'rfq_offer', $existing ? $existing->id : 0 );
		if ( $spec['errors'] ) {
			Forms::back( array_merge( $spec['errors'], $file_errors ) );
		}
		list( $oid, $is_new ) = Offers::save( $rfq->id, $company->id, $user_id, $spec['data'], $files );
		Notify::notify_company( $rfq->company_id, $is_new ? 'rfq.offer_received' : 'rfq.offer_updated', array( 'rfq_id' => $rfq->id, 'offer_id' => $oid, 'company_id' => $company->id ) );
		Forms::back( $file_errors, $is_new ? __( 'Your offer was sent. The buyer is notified.', 'sm-rfq' ) : __( 'Your offer is updated. The buyer is notified.', 'sm-rfq' ) );
	}

	public static function handle_offer_action() {
		Forms::begin( 'rfq_offer_action' );
		$user_id = get_current_user_id();
		$offer   = Offers::get( absint( Forms::post( 'offer_id' ) ) );
		$rfq     = $offer ? Rfqs::get( $offer->rfq_id ) : null;
		$do      = sanitize_key( Forms::post( 'do' ) );
		if ( ! $offer || ! $rfq ) {
			Forms::back( array( __( 'This offer does not exist.', 'sm-rfq' ) ) );
		}
		if ( 'withdraw' === $do ) {
			if ( ! Companies::member_role( $offer->company_id, $user_id ) || ! in_array( $offer->status, array( 'submitted', 'shortlisted' ), true ) ) {
				Forms::back( array( __( 'You cannot withdraw this offer.', 'sm-rfq' ) ) );
			}
			Offers::set_status( $offer->id, 'withdrawn' );
			Notify::notify_company( $rfq->company_id, 'rfq.offer_withdrawn', array( 'rfq_id' => $rfq->id, 'company_id' => $offer->company_id ) );
			Forms::back( array(), __( 'Your offer is withdrawn.', 'sm-rfq' ) );
		}
		if ( ! self::is_buyer( $rfq, $user_id ) ) {
			Forms::back( array( __( 'Only the buyer can decide on an offer.', 'sm-rfq' ) ) );
		}
		if ( ! in_array( $offer->status, array( 'submitted', 'shortlisted' ), true ) || ! in_array( $rfq->status, array( 'open', 'expired' ), true ) ) {
			Forms::back( array( __( 'This offer can no longer be changed.', 'sm-rfq' ) ) );
		}
		switch ( $do ) {
			case 'shortlist':
				Offers::set_status( $offer->id, 'submitted' === $offer->status ? 'shortlisted' : 'submitted' );
				Forms::back( array(), 'submitted' === $offer->status ? __( 'Offer shortlisted.', 'sm-rfq' ) : __( 'Offer removed from the shortlist.', 'sm-rfq' ) );
				break;
			case 'decline':
				Offers::set_status( $offer->id, 'declined' );
				Notify::notify_company( $offer->company_id, 'rfq.offer_declined', array( 'rfq_id' => $rfq->id ) );
				Forms::back( array(), __( 'Offer declined. The supplier is told.', 'sm-rfq' ) );
				break;
			case 'accept':
				Offers::set_status( $offer->id, 'accepted' );
				Rfqs::set_status( $rfq->id, 'awarded', array( 'awarded_offer' => $offer->id ) );
				Notify::notify_company( $offer->company_id, 'rfq.offer_accepted', array( 'rfq_id' => $rfq->id ) );
				foreach ( Offers::for_rfq( $rfq->id, false ) as $o ) {
					if ( $o->id !== $offer->id && in_array( $o->status, array( 'submitted', 'shortlisted' ), true ) ) {
						Offers::set_status( $o->id, 'declined' );
						Notify::notify_company( $o->company_id, 'rfq.awarded_other', array( 'rfq_id' => $rfq->id ) );
					}
				}
				Forms::back( array(), __( 'Offer accepted. The supplier is notified; contact him to confirm the order. The other suppliers were told.', 'sm-rfq' ) );
				break;
		}
		Forms::back( array( __( 'Unknown action.', 'sm-rfq' ) ) );
	}
}

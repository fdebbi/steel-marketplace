<?php
/**
 * Form handlers (admin-post.php): service pages, service requests, quotes,
 * client decisions, references.
 *
 * @package SM\Services
 */

namespace SM\Services;

use SM\Core\Accounts\Companies;
use SM\Core\Content\Media;
use SM\Core\Notifications\Notifications as Notify;
use SM\Core\Routing\Routes as CoreRoutes;
use SM\Core\Support\Forms;

defined( 'ABSPATH' ) || exit;

final class Form {

	public static function init() {
		foreach ( array( 'service_save', 'service_action', 'service_request', 'service_reply', 'service_decide', 'portfolio_save', 'portfolio_delete' ) as $f ) {
			add_action( 'admin_post_sm_' . $f, array( __CLASS__, 'handle_' . $f ) );
		}
	}

	private static function member( $company_id, $user_id = null ) {
		$user_id = $user_id ?? get_current_user_id();
		return $user_id && $company_id && (bool) Companies::member_role( $company_id, $user_id );
	}

	/** Why the user cannot request this service ('' = can). */
	public static function request_block( $service, $user_id = null ) {
		$user_id = $user_id ?? get_current_user_id();
		if ( ! $user_id ) {
			return __( 'Sign in to send a request.', 'sm-services' );
		}
		$co = Companies::for_user( $user_id );
		if ( ! $co ) {
			return __( 'Your account has no company.', 'sm-services' );
		}
		if ( (int) $co->id === (int) $service->company_id ) {
			return __( 'This is your own service.', 'sm-services' );
		}
		$allowed = sm_can_explain( 'service.request', $co->id );
		if ( is_wp_error( $allowed ) ) {
			return $allowed->get_error_message();
		}
		if ( 'published' !== $service->status ) {
			return __( 'This service is not available at the moment.', 'sm-services' );
		}
		return '';
	}

	private static function upload( $field, array $mimes, $max, $mb, $user_id ) {
		$r = Media::upload( $field, $mimes, $max, $mb * 1048576 );
		foreach ( $r['ids'] as $aid ) {
			update_post_meta( $aid, '_sm_owner', $user_id );
		}
		return $r;
	}

	private static function kept( $key, $user_id, $entity, $id ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in Forms::begin().
		$keep = array_map( 'absint', (array) wp_unslash( $_POST[ $key ] ?? array() ) );
		return array_values(
			array_filter(
				$keep,
				static fn( $a ) => $a && 'attachment' === get_post_type( $a ) && ( (int) get_post_field( 'post_author', $a ) === (int) $user_id || ( $id && in_array( $a, Media::get( $entity, $id, 'photo' ), true ) ) )
			)
		);
	}

	public static function handle_service_save() {
		Forms::begin( 'service_save' );
		$user_id = get_current_user_id();
		$company = Companies::for_user( $user_id );
		if ( ! $company ) {
			Forms::back( array( __( 'Your account has no company.', 'sm-services' ) ) );
		}
		$kind = sanitize_key( Forms::post( 'kind' ) );
		$def  = Catalogue::kind( $kind );
		$cap  = $def ? sm_can_explain( $def['cap'], $company->id ) : new \WP_Error( 'kind', __( 'Choose the type of service.', 'sm-services' ) );
		if ( is_wp_error( $cap ) ) {
			Forms::back( array( $cap->get_error_message() ) );
		}
		$id      = absint( Forms::post( 'service_id' ) );
		$service = $id ? Services::get( $id ) : null;
		if ( $id && ( ! $service || (int) $service->company_id !== (int) $company->id || $service->kind !== $kind ) ) {
			Forms::back( array( __( 'You cannot edit this service.', 'sm-services' ) ) );
		}
		if ( ! $id && Services::count_for_company( $company->id ) >= (int) Settings::get( 'max_services' ) ) {
			Forms::back( array( __( 'You reached the number of service pages allowed. Edit an existing one.', 'sm-services' ) ) );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in Forms::begin().
		$spec   = Spec::service( (array) wp_unslash( $_POST ), true );
		$s      = Settings::all();
		$keep   = self::kept( 'keep_photos', $user_id, 'service', $id );
		$new    = self::upload( 'photos', Media::IMAGE_TYPES, max( 0, $s['max_photos'] - count( $keep ) ), $s['photo_mb'], $user_id );
		$photos = array_merge( $keep, $new['ids'] );
		$_POST['keep_photos'] = $photos; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- kept for the form on error.
		if ( $spec['errors'] ) {
			Forms::back( array_merge( $spec['errors'], $new['errors'] ) );
		}
		$status = (int) $s['moderation'] && ! current_user_can( 'sm_moderate' ) ? 'pending' : 'published';
		if ( $service && 'paused' === $service->status ) {
			$status = 'paused';
		}
		$sid = Services::save( $id, $company->id, $user_id, $spec, $status, $photos );
		if ( 'pending' === $status ) {
			Notify::notify_moderators( 'service.submitted', array( 'service_id' => $sid ) );
		}
		$msg = 'pending' === $status ? __( 'Thank you. Your service page will be visible after a quick check by the platform team.', 'sm-services' ) : __( 'Your service page is saved.', 'sm-services' );
		Forms::back( $new['errors'], $msg, Routes::link( $sid ) );
	}

	public static function handle_service_action() {
		Forms::begin( 'service_action' );
		$service = Services::get( absint( Forms::post( 'service_id' ) ) );
		if ( ! $service || ! self::member( $service->company_id ) ) {
			Forms::back( array( __( 'You cannot change this service.', 'sm-services' ) ) );
		}
		switch ( sanitize_key( Forms::post( 'do' ) ) ) {
			case 'pause':
				Services::set_status( $service->id, 'paused' );
				Forms::back( array(), __( 'The service is paused: clients no longer see it.', 'sm-services' ) );
				break;
			case 'resume':
				$status = (int) Settings::get( 'moderation' ) && ! current_user_can( 'sm_moderate' ) && 'rejected' === $service->status ? 'pending' : 'published';
				Services::set_status( $service->id, $status );
				Forms::back( array(), __( 'The service is visible again.', 'sm-services' ) );
				break;
			case 'delete':
				Services::delete( $service->id );
				Forms::back( array(), __( 'Service deleted.', 'sm-services' ), CoreRoutes::url( 'account', array( 'view' => 'services' ) ) );
				break;
		}
		Forms::back( array( __( 'Unknown action.', 'sm-services' ) ) );
	}

	public static function handle_service_request() {
		Forms::begin( 'service_request' );
		$user_id = get_current_user_id();
		$service = Services::get( absint( Forms::post( 'service_id' ) ) );
		$block   = $service ? self::request_block( $service, $user_id ) : __( 'This service does not exist.', 'sm-services' );
		if ( $block ) {
			Forms::back( array( $block ) );
		}
		$company = Companies::for_user( $user_id );
		if ( Requests::open_for( $service->id, $company->id ) ) {
			Forms::back( array( __( 'You already have an open request for this service. Follow it in "My service requests".', 'sm-services' ) ) );
		}
		if ( ! Forms::rate_limit( 'service_request|' . $company->id, (int) Settings::get( 'requests_per_day' ), DAY_IN_SECONDS ) ) {
			Forms::back( array( __( 'You sent many requests today. Try again tomorrow.', 'sm-services' ) ) );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in Forms::begin().
		$spec  = Spec::request( (array) wp_unslash( $_POST ) );
		$s     = Settings::all();
		$files = self::upload( 'files', array_merge( Media::PDF_TYPES, Media::IMAGE_TYPES ), $s['max_files'], $s['file_mb'], $user_id );
		if ( $spec['errors'] ) {
			Forms::back( array_merge( $spec['errors'], $files['errors'] ) );
		}
		$rid = Requests::create( $service, $company->id, $user_id, $spec['data'], $files['ids'] );
		Notify::notify_company( $service->company_id, 'service.request_received', array( 'request_id' => $rid, 'service_id' => $service->id, 'company_id' => $company->id ) );
		Forms::back( $files['errors'], __( 'Your request is sent. The provider is notified and will answer with a quote.', 'sm-services' ) );
	}

	public static function handle_service_reply() {
		Forms::begin( 'service_reply' );
		$req = Requests::get( absint( Forms::post( 'request_id' ) ) );
		if ( ! $req || ! self::member( $req->provider_id ) ) {
			Forms::back( array( __( 'You cannot answer this request.', 'sm-services' ) ) );
		}
		if ( ! in_array( $req->status, array( 'new', 'quoted' ), true ) ) {
			Forms::back( array( __( 'This request is closed.', 'sm-services' ) ) );
		}
		if ( 'decline' === sanitize_key( Forms::post( 'do' ) ) ) {
			Requests::update( $req->id, array( 'status' => 'declined_provider', 'quote_message' => Forms::post_textarea( 'quote_message' ), 'decided_at' => current_time( 'mysql', true ) ) );
			Notify::notify_company( $req->client_id, 'service.request_declined', array( 'request_id' => $req->id, 'service_id' => $req->service_id, 'company_id' => $req->provider_id ) );
			Forms::back( array(), __( 'You declined the request. The client is told.', 'sm-services' ) );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in Forms::begin().
		$spec = Spec::quote( (array) wp_unslash( $_POST ) );
		if ( $spec['errors'] ) {
			Forms::back( $spec['errors'] );
		}
		Requests::update( $req->id, $spec['data'] + array( 'status' => 'quoted', 'quoted_at' => current_time( 'mysql', true ) ) );
		Notify::notify_company( $req->client_id, 'service.quote_received', array( 'request_id' => $req->id, 'service_id' => $req->service_id, 'company_id' => $req->provider_id ) );
		Forms::back( array(), __( 'Your quote is sent. The client is notified.', 'sm-services' ) );
	}

	public static function handle_service_decide() {
		Forms::begin( 'service_decide' );
		$req = Requests::get( absint( Forms::post( 'request_id' ) ) );
		if ( ! $req || ! self::member( $req->client_id ) ) {
			Forms::back( array( __( 'You cannot change this request.', 'sm-services' ) ) );
		}
		$do  = sanitize_key( Forms::post( 'do' ) );
		$map = array(
			'accept'  => array( array( 'quoted' ), 'accepted', 'service.quote_accepted', __( 'Quote accepted. Contact the provider to organise the work.', 'sm-services' ) ),
			'decline' => array( array( 'quoted' ), 'declined', 'service.quote_declined', __( 'Quote declined. The provider is told.', 'sm-services' ) ),
			'cancel'  => array( array( 'new', 'quoted' ), 'cancelled', 'service.request_cancelled', __( 'Request cancelled. The provider is told.', 'sm-services' ) ),
		);
		if ( ! isset( $map[ $do ] ) || ! in_array( $req->status, $map[ $do ][0], true ) ) {
			Forms::back( array( __( 'This request can no longer be changed.', 'sm-services' ) ) );
		}
		Requests::update( $req->id, array( 'status' => $map[ $do ][1], 'decided_at' => current_time( 'mysql', true ) ) );
		Notify::notify_company( $req->provider_id, $map[ $do ][2], array( 'request_id' => $req->id, 'service_id' => $req->service_id, 'company_id' => $req->client_id ) );
		Forms::back( array(), $map[ $do ][3] );
	}

	public static function handle_portfolio_save() {
		Forms::begin( 'portfolio_save' );
		$user_id = get_current_user_id();
		$company = Companies::for_user( $user_id );
		if ( ! $company || ! Companies::can_manage( $company->id, $user_id ) ) {
			Forms::back( array( __( 'Only the account owner can edit the references.', 'sm-services' ) ) );
		}
		$id  = absint( Forms::post( 'portfolio_id' ) );
		$row = $id ? Portfolio::get( $id ) : null;
		if ( $id && ( ! $row || (int) $row->company_id !== (int) $company->id ) ) {
			Forms::back( array( __( 'You cannot edit this reference.', 'sm-services' ) ) );
		}
		if ( ! $id && Portfolio::count( $company->id ) >= (int) Settings::get( 'max_portfolio' ) ) {
			Forms::back( array( __( 'You reached the number of references allowed.', 'sm-services' ) ) );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in Forms::begin().
		$spec  = Spec::reference( (array) wp_unslash( $_POST ) );
		$s     = Settings::all();
		$photo = self::upload( 'photo', Media::IMAGE_TYPES, 1, $s['photo_mb'], $user_id );
		if ( $spec['errors'] ) {
			Forms::back( array_merge( $spec['errors'], $photo['errors'] ) );
		}
		Portfolio::save( $id, $company->id, $spec['data'], $photo['ids'] ? $photo['ids'][0] : ( ! empty( $_POST['photo_remove'] ) ? 0 : null ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in Forms::begin().
		Forms::back( $photo['errors'], __( 'Reference saved.', 'sm-services' ), CoreRoutes::url( 'account', array( 'view' => 'portfolio' ) ) );
	}

	public static function handle_portfolio_delete() {
		Forms::begin( 'portfolio_delete' );
		$company = Companies::for_user( get_current_user_id() );
		$row     = Portfolio::get( absint( Forms::post( 'portfolio_id' ) ) );
		if ( ! $company || ! $row || (int) $row->company_id !== (int) $company->id || ! Companies::can_manage( $company->id, get_current_user_id() ) ) {
			Forms::back( array( __( 'You cannot delete this reference.', 'sm-services' ) ) );
		}
		Portfolio::delete( $row->id );
		Forms::back( array(), __( 'Reference deleted.', 'sm-services' ) );
	}
}

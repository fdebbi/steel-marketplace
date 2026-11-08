<?php
/**
 * Data for the sm-marketplace theme: services, service, service_form,
 * my_services, service_requests, portfolio, services_settings.
 *
 * @package SM\Services\Theme
 */

namespace SM\Services\Theme;

use SM\Core\Accounts\Companies;
use SM\Core\Content\Media;
use SM\Core\Content\Translations;
use SM\Services\Catalogue;
use SM\Services\Form;
use SM\Services\Portfolio;
use SM\Services\Presenter;
use SM\Services\Requests;
use SM\Services\Services;
use SM\Services\Settings;

defined( 'ABSPATH' ) || exit;

final class Bridge {

	public static function init() {
		foreach ( array( 'services', 'service', 'service_form', 'my_services', 'service_requests', 'portfolio', 'services_settings' ) as $r ) {
			add_filter( 'sm_theme_data_' . $r, array( __CLASS__, $r ), 10, 2 );
		}
	}

	/** Published services (list). Args: kind, category, wilaya, q, company (slug), per_page, page. */
	public static function services( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$company = 0;
		if ( ! empty( $args['company'] ) ) {
			$c       = Companies::get_by_slug( (string) $args['company'] );
			$company = $c ? (int) $c->id : -1;
		}
		$q = Services::query(
			array(
				'kind'     => in_array( $args['kind'] ?? '', Catalogue::kinds(), true ) ? $args['kind'] : '',
				'category' => sanitize_key( (string) ( $args['category'] ?? '' ) ),
				'wilaya'   => preg_replace( '/\D/', '', (string) ( $args['wilaya'] ?? '' ) ),
				'q'        => sanitize_text_field( (string) ( $args['q'] ?? '' ) ),
				'company'  => $company,
				'per_page' => (int) ( $args['per_page'] ?? 24 ),
				'page'     => (int) ( $args['page'] ?? 1 ),
			)
		);
		if ( ! $q['total'] && empty( $args['company'] ) && empty( $args['category'] ) && empty( $args['q'] ) && empty( $args['wilaya'] ) && function_exists( 'sm_theme_demo_enabled' ) && sm_theme_demo_enabled() && ! Services::query( array( 'per_page' => 1 ) )['total'] ) {
			return null; // Nothing published yet anywhere: let the demo show the layout.
		}
		$ids    = wp_list_pluck( $q['items'], 'id' );
		$texts  = Translations::get_many( 'service', $ids );
		$photos = Media::first_many( 'service', $ids );
		return array_map( static fn( $s ) => Presenter::service( $s, $texts[ $s->id ] ?? array(), $photos[ $s->id ] ?? 0 ), $q['items'] );
	}

	public static function service( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$s = Services::get( (int) ( $args['id'] ?? 0 ) );
		if ( ! $s ) {
			return null;
		}
		$user   = get_current_user_id();
		$member = $user && Companies::member_role( $s->company_id, $user );
		if ( 'published' !== $s->status && ! $member && ! current_user_can( 'sm_moderate' ) ) {
			return null;
		}
		if ( ! $member && empty( $args['no_count'] ) ) {
			Services::add_view( $s->id );
		}
		$out     = Presenter::detail( $s );
		$block   = Form::request_block( $s, $user );
		$co      = $user ? Companies::for_user( $user ) : null;
		$open    = $co && ! $member ? Requests::open_for( $s->id, $co->id ) : 0;
		$out['viewer'] = array(
			'is_owner'      => (bool) $member,
			'can_request'   => '' === $block && ! $open,
			'request_block' => $block,
			'open_request'  => $open,
			'signed_in'     => (bool) $user,
		);
		return $out;
	}

	public static function service_form( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$s = Services::get( (int) ( $args['id'] ?? 0 ) );
		if ( ! $s || ! Companies::member_role( $s->company_id, get_current_user_id() ) ) {
			return null;
		}
		$t = Translations::get( 'service', $s->id );
		return array(
			'status' => $s->status,
			'values' => array(
				'service_id'   => $s->id,
				'kind'         => $s->kind,
				'categories'   => $s->categories,
				'title'        => array( 'fr' => $t['title']['fr'] ?? '', 'ar' => $t['title']['ar'] ?? '' ),
				'description'  => trim( implode( '', (array) ( $t['description'] ?? array() ) ) ),
				'capabilities' => trim( implode( '', (array) ( $t['capabilities'] ?? array() ) ) ),
				'equipment'    => trim( implode( '', (array) ( $t['equipment'] ?? array() ) ) ),
				'coverage'     => $s->wilayas ? 'list' : 'all',
				'wilayas'      => $s->wilayas,
				'lead_days'    => $s->lead_days ? (string) $s->lead_days : '',
				'price_mode'   => $s->price_mode,
				'price_from'   => $s->price_from > 0 ? (string) ( 0 + $s->price_from ) : '',
				'price_unit'   => $s->price_unit,
				'keep_photos'  => Media::get( 'service', $s->id, 'photo' ),
			),
			'reject_reason' => (string) $s->reject_reason,
		);
	}

	public static function my_services( $result ) {
		if ( null !== $result ) {
			return $result;
		}
		$co = Companies::for_user( get_current_user_id() );
		if ( ! $co ) {
			return array( 'items' => array() );
		}
		$q = Services::query( array( 'company' => $co->id, 'status' => 'any', 'per_page' => 60 ) );
		return array( 'items' => array_map( array( Presenter::class, 'service' ), $q['items'] ) );
	}

	/** Args: box received|sent, status. */
	public static function service_requests( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$co  = Companies::for_user( get_current_user_id() );
		$box = 'sent' === ( $args['box'] ?? '' ) ? 'sent' : 'received';
		if ( ! $co ) {
			return array( 'items' => array(), 'box' => $box );
		}
		$status = sanitize_key( (string) ( $args['status'] ?? '' ) );
		$items  = Requests::for_company( $co->id, $box, in_array( $status, Requests::STATUSES, true ) ? $status : '' );
		return array( 'box' => $box, 'items' => array_map( static fn( $r ) => Presenter::request( $r, $box ), $items ) );
	}

	/** References of a company (args: company slug, or the signed-in company). */
	public static function portfolio( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$c = ! empty( $args['company'] ) ? Companies::get_by_slug( (string) $args['company'] ) : Companies::for_user( get_current_user_id() );
		return $c ? Portfolio::for_company( $c->id ) : array();
	}

	public static function services_settings( $result ) {
		if ( null !== $result ) {
			return $result;
		}
		$s     = Settings::all();
		$co    = Companies::for_user( get_current_user_id() );
		$kinds = array();
		foreach ( Catalogue::all() as $k => $def ) {
			$err         = $co ? sm_can_explain( $def['cap'], $co->id ) : new \WP_Error( 'none', '' );
			$kinds[ $k ] = array(
				'label'      => $def['label'],
				'categories' => $def['categories'],
				'units'      => $def['units'],
				'allowed'    => ! is_wp_error( $err ),
				'reason'     => is_wp_error( $err ) ? $err->get_error_message() : '',
			);
		}
		return array(
			'kinds'      => $kinds,
			'max_photos' => (int) $s['max_photos'],
			'photo_mb'   => (int) $s['photo_mb'],
			'max_files'  => (int) $s['max_files'],
			'file_mb'    => (int) $s['file_mb'],
			'moderation' => (int) $s['moderation'],
			'new_requests' => $co ? Requests::count_new( $co->id ) : 0,
		);
	}

}

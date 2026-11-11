<?php
/**
 * Array shapes for the theme and the REST API: service, service request, reference.
 *
 * @package SM\Services
 */

namespace SM\Services;

use SM\Core\Accounts\Companies;
use SM\Core\Content\Media;
use SM\Core\Content\Translations;

defined( 'ABSPATH' ) || exit;

final class Presenter {

	public static function pick( array $values ) {
		$lang = function_exists( 'sm_current_language' ) ? sm_current_language() : 'fr';
		return (string) ( $values[ $lang ] ?? '' ) !== '' ? $values[ $lang ] : (string) ( array_values( array_filter( $values ) )[0] ?? '' );
	}

	public static function title_text( $s ) {
		$t = Translations::get( 'service', $s->id );
		return self::pick( (array) ( $t['title'] ?? array() ) );
	}

	/** Card / list shape (compatible with the theme's "services" contract). */
	public static function service( $s, $texts = null, $photo = null ) {
		$texts   = $texts ?? Translations::get( 'service', $s->id );
		$def     = Catalogue::kind( $s->kind );
		$company = Companies::get( $s->company_id );
		$photos  = null === $photo ? Media::get( 'service', $s->id, 'photo' ) : array_filter( array( $photo ) );
		$tags    = array();
		foreach ( $s->categories as $c ) {
			if ( isset( $def['labels'][ $c ] ) ) {
				$tags[] = $def['labels'][ $c ];
			}
		}
		return array(
			'id'           => $s->id,
			'kind'         => $s->kind,
			'kind_label'   => $def['label'] ?? array(),
			'status'       => $s->status,
			'company'      => $company ? $company->slug : '',
			'company_id'   => $s->company_id,
			'title'        => array_filter( (array) ( $texts['title'] ?? array() ) ),
			'description'  => array_filter( (array) ( $texts['description'] ?? array() ) ),
			'categories'   => $s->categories,
			'tags'         => $tags,
			'wilayas'      => $s->wilayas,
			'all_algeria'  => ! $s->wilayas,
			'lead_days'    => $s->lead_days,
			'price_mode'   => $s->price_mode,
			'price_from'   => $s->price_from,
			'price_unit'   => $s->price_unit,
			'image'        => $photos ? (int) $photos[0] : 'demo:welding',
			'photos'       => array_map( 'intval', $photos ),
			'views'        => $s->views,
			'date'         => $s->published_at ? $s->published_at : $s->updated_at,
			'reject_reason' => (string) $s->reject_reason,
		);
	}

	public static function detail( $s ) {
		$texts = Translations::get( 'service', $s->id );
		$out   = self::service( $s, $texts );
		$out['capabilities'] = array_filter( (array) ( $texts['capabilities'] ?? array() ) );
		$out['equipment']    = array_filter( (array) ( $texts['equipment'] ?? array() ) );
		$out['portfolio']    = Portfolio::for_company( $s->company_id );
		return $out;
	}

	public static function request( $r, $box = 'received' ) {
		$s       = Services::get( $r->service_id );
		$other   = Companies::get( 'sent' === $box ? $r->provider_id : $r->client_id );
		$files   = array();
		foreach ( Media::get( 'service_request', $r->id, 'file' ) as $aid ) {
			$files[] = array( 'id' => $aid, 'name' => get_the_title( $aid ), 'url' => wp_get_attachment_url( $aid ) );
		}
		return array(
			'id'            => $r->id,
			'box'           => $box,
			'status'        => $r->status,
			'service_id'    => $r->service_id,
			'service_title' => $s ? array_filter( (array) ( Translations::get( 'service', $s->id )['title'] ?? array() ) ) : array(),
			'service_kind'  => $s ? $s->kind : '',
			'wilaya'        => $r->wilaya,
			'needed_by'     => $r->needed_by,
			'budget'        => $r->budget,
			'message'       => (string) $r->message,
			'files'         => $files,
			'quote_amount'  => $r->quote_amount,
			'quote_days'    => $r->quote_days,
			'quote_message' => (string) $r->quote_message,
			'date'          => $r->created_at,
			'updated'       => $r->updated_at,
			'other'         => $other ? array(
				'slug'         => $other->slug,
				'name'         => array( 'fr' => $other->name, 'ar' => $other->name_ar ?: $other->name ),
				'wilaya'       => $other->wilaya,
				'verification' => (int) $other->verification_level,
				'phone'        => $other->phone,
				'email'        => $other->email,
			) : array(),
		);
	}
}

<?php
/**
 * E-mail events and journal entries of services (SM Core → E-mails).
 *
 * @package SM\Services
 */

namespace SM\Services;

use SM\Core\Mail\Events;

defined( 'ABSPATH' ) || exit;

final class Activity {

	public static function init() {
		add_filter( 'sm_mail_events', array( __CLASS__, 'events' ) );
		add_filter( 'sm_activity_labels', array( __CLASS__, 'labels' ) );
		add_filter( 'sm_activity_link', array( __CLASS__, 'link' ), 10, 2 );
	}

	private static function service_vars( $s ) {
		if ( ! $s ) {
			return array();
		}
		$def = Catalogue::kind( $s->kind );
		return array(
			'titre'      => Presenter::title_text( $s ),
			'catalogue'  => $def ? Presenter::pick( (array) $def['label'] ) : $s->kind,
			'lien'       => Routes::link( $s->id ),
			'lien_admin' => admin_url( 'admin.php?page=' . Admin\Admin::SLUG ),
		);
	}

	public static function vars( $entry ) {
		$s = Services::get( (int) $entry->object_id );
		if ( ! $s ) {
			return array();
		}
		return Events::user_vars( $s ? $s->user_id : $entry->user_id ) + Events::company_vars( $s ? $s->company_id : $entry->company_id ) + self::service_vars( $s );
	}

	/** Service request: client, provider ("prestataire"), amounts. */
	public static function request_vars( $entry ) {
		$r = Requests::get( (int) $entry->object_id );
		$s = $r ? Services::get( (int) $r->service_id ) : null;
		if ( ! $r ) {
			return array();
		}
		$c = Events::company_vars( $r ? $r->client_id : 0 );
		$p = Events::company_vars( $r ? $r->provider_id : 0 );
		return Events::user_vars( $r ? $r->client_user : $entry->user_id ) + $c + self::service_vars( $s ) + array(
			'client'      => $c['entreprise'],
			'prestataire' => $p['entreprise'],
			'message'     => $r ? (string) $r->message : '',
			'montant'     => $r && (float) $r->quote_amount > 0 ? number_format_i18n( (float) $r->quote_amount, 0 ) . ' DA' : '',
			'delai'       => $r && (int) $r->quote_days > 0 ? (string) (int) $r->quote_days : '',
		);
	}

	public static function events( array $events ) {
		$events['service.custom'] = array(
			'group'     => 'services',
			'label'     => __( 'New service name proposed ("Other")', 'sm-services' ),
			'audit'     => 'service.custom_proposed',
			'admin'     => 'now',
			'user'      => null,
			'vars'      => static fn( $e ) => self::vars( $e ) + array( 'service' => (string) ( $e->data['name'] ?? '' ), 'lien_admin' => admin_url( 'admin.php?page=sm-types&tab=services_fabrication' ) ),
			'to'        => array( Events::class, 'author' ),
			'variables' => array( 'service', 'titre', 'entreprise', 'nom', 'email', 'telephone', 'wilaya', 'lien', 'lien_admin' ),
			'describe'  => static fn( $v ) => sprintf( /* translators: 1: company, 2: service name. */ __( '%1$s proposed the service "%2$s"', 'sm-services' ), $v['entreprise'], $v['service'] ?? '' ),
			'templates' => array(
				'admin' => array(
					static fn() => __( 'Service to file: {service}', 'sm-services' ),
					static fn() => __( "{entreprise} ({wilaya}) published a service under \"Other\" with its own name:\n\n{service}\n\nThe page is visible under \"Other services\". Add the name to the list (with its Arabic translation) or attach it to an existing service:\n{lien_admin}\n\nThe page: {lien}", 'sm-services' ),
				),
			),
		);
		$events['service.new'] = array(
			'group'     => 'services',
			'label'     => __( 'New service page', 'sm-services' ),
			'audit'     => 'service.saved',
			'when'      => static fn( $e ) => Events::is_new( $e ) && in_array( $e->data['status'] ?? '', array( 'published', 'pending' ), true ),
			'admin'     => 'digest',
			'user'      => true,
			'vars'      => array( __CLASS__, 'vars' ),
			'to'        => array( Events::class, 'author' ),
			'variables' => array( 'titre', 'catalogue', 'entreprise', 'nom', 'email', 'telephone', 'wilaya', 'lien', 'lien_admin' ),
			'describe'  => static fn( $v ) => sprintf( /* translators: 1: company, 2: service. */ __( '%1$s published the service "%2$s"', 'sm-services' ), $v['entreprise'], $v['titre'] ?? '' ),
			'templates' => array(
				'admin' => array(
					static fn() => __( 'New service: {titre}', 'sm-services' ),
					static fn() => __( "{entreprise} ({wilaya}) published a service page.\n\n{titre}\nCatalogue: {catalogue}\n\nBy {nom} — {email} — {telephone}\n\n{lien}", 'sm-services' ),
				),
				'user'  => array(
					static fn() => __( 'Your service page is saved: {titre}', 'sm-services' ),
					static fn() => __( "Your service page \"{titre}\" is saved on {site}. Clients find it by service and wilaya and send you their requests, which you answer with a quote.\n\nSee your page:\n{lien}\n\nTip: add photos of your work and your references; they reassure clients.\n\nThe {site} team", 'sm-services' ),
				),
			),
		);
		$req_vars = array( 'titre', 'client', 'prestataire', 'nom', 'email', 'telephone', 'wilaya', 'message', 'montant', 'delai', 'lien' );
		$events['service.request'] = array(
			'group'     => 'services',
			'label'     => __( 'Service request sent', 'sm-services' ),
			'audit'     => 'service.request_sent',
			'admin'     => 'digest',
			'user'      => false,
			'vars'      => array( __CLASS__, 'request_vars' ),
			'to'        => array( Events::class, 'author' ),
			'variables' => $req_vars,
			'describe'  => static fn( $v ) => sprintf( /* translators: 1: client, 2: provider, 3: service. */ __( '%1$s asked %2$s for a quote ("%3$s")', 'sm-services' ), $v['client'], $v['prestataire'], $v['titre'] ?? '' ),
			'templates' => array(
				'admin' => array(
					static fn() => __( 'Service request: {client} → {prestataire}', 'sm-services' ),
					static fn() => __( "{client} ({wilaya}) asked {prestataire} for a quote about \"{titre}\".\n\n{message}\n\nClient: {nom} — {email} — {telephone}", 'sm-services' ),
				),
				'user'  => array(
					static fn() => __( 'Your request is sent to {prestataire}', 'sm-services' ),
					static fn() => __( "Your request about \"{titre}\" was sent to {prestataire}. You are told by e-mail when the quote arrives.\n\nThe {site} team", 'sm-services' ),
				),
			),
		);
		$events['service.accepted'] = array(
			'group'     => 'services',
			'label'     => __( 'Service quote accepted', 'sm-services' ),
			'audit'     => 'service.request_accepted',
			'admin'     => 'now',
			'user'      => null,
			'vars'      => array( __CLASS__, 'request_vars' ),
			'variables' => $req_vars,
			'describe'  => static fn( $v ) => sprintf( /* translators: 1: client, 2: provider, 3: amount. */ __( '%1$s accepted the quote of %2$s (%3$s)', 'sm-services' ), $v['client'], $v['prestataire'], $v['montant'] ),
			'templates' => array(
				'admin' => array(
					static fn() => __( 'Deal: {client} accepted the quote of {prestataire}', 'sm-services' ),
					static fn() => __( "{client} accepted the quote of {prestataire} for \"{titre}\".\n\nAmount: {montant}\nTime: {delai} days", 'sm-services' ),
				),
			),
		);
		return $events;
	}

	public static function labels( array $labels ) {
		return $labels + array(
			'service.saved'                     => __( 'Service page saved', 'sm-services' ),
			'service.status'                    => __( 'Service page status changed', 'sm-services' ),
			'service.request_quoted'            => __( 'Quote sent for a service request', 'sm-services' ),
			'service.request_declined'          => __( 'Quote declined by the client', 'sm-services' ),
			'service.request_declined_provider' => __( 'Service request declined by the provider', 'sm-services' ),
			'service.request_cancelled'         => __( 'Service request cancelled', 'sm-services' ),
			'service.'                          => __( 'Service', 'sm-services' ),
		);
	}

	public static function link( $url, $entry ) {
		if ( $url ) {
			return $url;
		}
		if ( 'service' === $entry->object_type ) {
			return Routes::link( (int) $entry->object_id );
		}
		if ( 'service_request' === $entry->object_type ) {
			$r = Requests::get( (int) $entry->object_id );
			return $r ? Routes::link( (int) $r->service_id ) : '';
		}
		return $url;
	}
}

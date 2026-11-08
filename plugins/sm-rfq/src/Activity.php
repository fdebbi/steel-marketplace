<?php
/**
 * E-mail events and journal entries of requests for quotation (SM Core → E-mails).
 *
 * @package SM\Rfq
 */

namespace SM\Rfq;

use SM\Core\Mail\Events;

defined( 'ABSPATH' ) || exit;

final class Activity {

	public static function init() {
		add_filter( 'sm_mail_events', array( __CLASS__, 'events' ) );
		add_filter( 'sm_activity_labels', array( __CLASS__, 'labels' ) );
		add_filter( 'sm_activity_link', array( __CLASS__, 'link' ), 10, 2 );
	}

	private static function rfq_vars( $r ) {
		if ( ! $r ) {
			return array();
		}
		$units = array(
			'pieces' => _n( 'piece', 'pieces', max( 1, (int) ceil( (float) $r->quantity ) ), 'sm-rfq' ),
			'kg'     => 'kg',
			't'      => 't',
			'm'      => 'm',
			'm2'     => 'm²',
			'lot'    => __( 'lot', 'sm-rfq' ),
		);
		$qty = (float) $r->quantity > 0 ? number_format_i18n( (float) $r->quantity, 0 ) . ' ' . ( $units[ $r->unit ] ?? $r->unit ) : '';
		return array(
			'titre'      => Presenter::title_text( $r ),
			'quantite'   => trim( $qty . ( (float) $r->weight_kg > 0 ? ' · ' . number_format_i18n( (float) $r->weight_kg, 0 ) . ' kg' : '' ), ' ·' ),
			'date_limite' => $r->deadline ? (string) $r->deadline : '',
			'lien'       => Routes::link( $r->id ),
			'lien_admin' => admin_url( 'admin.php?page=' . Admin\Admin::SLUG ),
		);
	}

	/** Request saved: the buyer and his company. */
	public static function vars( $entry ) {
		$r = Rfqs::get( (int) $entry->object_id );
		if ( ! $r ) {
			return array();
		}
		return Events::user_vars( $r ? $r->user_id : $entry->user_id ) + Events::company_vars( $r ? $r->company_id : $entry->company_id ) + self::rfq_vars( $r );
	}

	/** Offer: the supplier ("fournisseur"), the buyer ("client") and the amounts. */
	public static function offer_vars( $entry ) {
		$o = Offers::get( (int) $entry->object_id );
		$r = $o ? Rfqs::get( (int) $o->rfq_id ) : null;
		if ( ! $o || ! $r ) {
			return array();
		}
		$s = Events::company_vars( $o ? $o->company_id : 0 );
		$b = Events::company_vars( $r ? $r->company_id : 0 );
		return Events::user_vars( $o ? $o->user_id : $entry->user_id ) + $s + self::rfq_vars( $r ) + array(
			'fournisseur' => $s['entreprise'],
			'client'      => $b['entreprise'],
			'montant'     => $o && (float) $o->total_price > 0 ? number_format_i18n( (float) $o->total_price, 0 ) . ' DA' : '',
			'delai'       => $o ? (string) (int) $o->delivery_days : '',
		);
	}

	public static function events( array $events ) {
		$events['rfq.new'] = array(
			'group'     => 'rfq',
			'label'     => __( 'New request for quotation', 'sm-rfq' ),
			'audit'     => 'rfq.saved',
			'when'      => static fn( $e ) => Events::is_new( $e ) && in_array( $e->data['status'] ?? '', array( 'open', 'pending' ), true ),
			'admin'     => 'now',
			'user'      => true,
			'vars'      => array( __CLASS__, 'vars' ),
			'to'        => array( Events::class, 'author' ),
			'variables' => array( 'titre', 'quantite', 'date_limite', 'entreprise', 'nom', 'email', 'telephone', 'wilaya', 'lien', 'lien_admin' ),
			'describe'  => static fn( $v ) => sprintf( /* translators: 1: company, 2: request. */ __( '%1$s published the request "%2$s"', 'sm-rfq' ), $v['entreprise'], $v['titre'] ?? '' ),
			'templates' => array(
				'admin' => array(
					static fn() => __( 'New request for quotation: {titre}', 'sm-rfq' ),
					static fn() => __( "{entreprise} ({wilaya}) published a request for quotation.\n\n{titre}\nQuantity: {quantite}\nOffers until: {date_limite}\n\nBuyer: {nom} — {email} — {telephone}\n\n{lien}", 'sm-rfq' ),
				),
				'user'  => array(
					static fn() => __( 'Your request for quotation is published', 'sm-rfq' ),
					static fn() => __( "Your request \"{titre}\" is published on {site}. Suppliers and manufacturers can now send you their offers until {date_limite}.\n\nYou are told by e-mail for each offer. Compare them here:\n{lien}\n\nThe {site} team", 'sm-rfq' ),
				),
			),
		);
		$offer_vars = array( 'titre', 'fournisseur', 'client', 'montant', 'delai', 'nom', 'email', 'telephone', 'lien' );
		$events['rfq.offer'] = array(
			'group'     => 'rfq',
			'label'     => __( 'Offer sent on a request', 'sm-rfq' ),
			'audit'     => 'rfq.offer_sent',
			'admin'     => 'digest',
			'user'      => false,
			'vars'      => array( __CLASS__, 'offer_vars' ),
			'to'        => array( Events::class, 'author' ),
			'variables' => $offer_vars,
			'describe'  => static fn( $v ) => sprintf( /* translators: 1: supplier, 2: request, 3: amount. */ __( '%1$s sent an offer on "%2$s" (%3$s)', 'sm-rfq' ), $v['fournisseur'], $v['titre'] ?? '', $v['montant'] ),
			'templates' => array(
				'admin' => array(
					static fn() => __( 'Offer from {fournisseur} on {titre}', 'sm-rfq' ),
					static fn() => __( "{fournisseur} answered the request \"{titre}\" of {client}.\n\nAmount: {montant}\nDelivery: {delai} days\n\n{lien}", 'sm-rfq' ),
				),
				'user'  => array(
					static fn() => __( 'Your offer is sent: {titre}', 'sm-rfq' ),
					static fn() => __( "Your offer of {montant} on \"{titre}\" was sent to {client}. You are told when the buyer decides.\n\n{lien}\n\nThe {site} team", 'sm-rfq' ),
				),
			),
		);
		$events['rfq.awarded'] = array(
			'group'     => 'rfq',
			'label'     => __( 'Offer accepted by the buyer', 'sm-rfq' ),
			'audit'     => 'rfq.offer_accepted',
			'admin'     => 'now',
			'user'      => null,
			'vars'      => array( __CLASS__, 'offer_vars' ),
			'variables' => $offer_vars,
			'describe'  => static fn( $v ) => sprintf( /* translators: 1: buyer, 2: supplier, 3: request. */ __( '%1$s accepted the offer of %2$s on "%3$s"', 'sm-rfq' ), $v['client'], $v['fournisseur'], $v['titre'] ?? '' ),
			'templates' => array(
				'admin' => array(
					static fn() => __( 'Deal: {client} accepted {fournisseur}', 'sm-rfq' ),
					static fn() => __( "{client} accepted the offer of {fournisseur} on \"{titre}\".\n\nAmount: {montant}\nDelivery: {delai} days\n\n{lien}", 'sm-rfq' ),
				),
			),
		);
		return $events;
	}

	public static function labels( array $labels ) {
		return $labels + array(
			'rfq.saved'          => __( 'Request for quotation saved', 'sm-rfq' ),
			'rfq.status'         => __( 'Request for quotation status changed', 'sm-rfq' ),
			'rfq.offer_updated'  => __( 'Offer updated', 'sm-rfq' ),
			'rfq.offer_declined' => __( 'Offer declined', 'sm-rfq' ),
			'rfq.offer_withdrawn' => __( 'Offer withdrawn', 'sm-rfq' ),
			'rfq.offer_shortlisted' => __( 'Offer shortlisted', 'sm-rfq' ),
			'rfq.'               => __( 'Request for quotation', 'sm-rfq' ),
		);
	}

	public static function link( $url, $entry ) {
		if ( $url ) {
			return $url;
		}
		if ( 'rfq' === $entry->object_type ) {
			return Routes::link( (int) $entry->object_id );
		}
		if ( 'rfq_offer' === $entry->object_type ) {
			$o = Offers::get( (int) $entry->object_id );
			return $o ? Routes::link( (int) $o->rfq_id ) : '';
		}
		return $url;
	}
}

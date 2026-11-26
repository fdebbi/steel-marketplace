<?php
/**
 * Questions written at installation (texts validated by ReuSteel on 2026-11-26),
 * in the order of data/faq/{fr,ar,en}.md: ## a theme, ### a question, then its
 * answer. Each entry: its name, its theme, the pages of the site it is shown on.
 * Never written over a question that exists.
 *
 * @package SM\Guides
 */

defined( 'ABSPATH' ) || exit;

return array(
	array( 'slug' => 'qui-peut-sinscrire', 'group' => 'account' ),
	array( 'slug' => 'difference-comptes', 'group' => 'account' ),
	array( 'slug' => 'confirmer-contact-code', 'group' => 'account' ),
	array( 'slug' => 'qui-peut-publier', 'group' => 'publish', 'pages' => array( 'reuse', 'buildings' ) ),
	array( 'slug' => 'declarer-etat-reemploi', 'group' => 'publish', 'pages' => array( 'reuse' ) ),
	array( 'slug' => 'annonce-pas-encore-visible', 'group' => 'publish' ),
	array( 'slug' => 'vendre-chutes-surplus', 'group' => 'publish' ),
	array( 'slug' => 'contacter-vendeur', 'group' => 'buy' ),
	array( 'slug' => 'demander-devis', 'group' => 'buy', 'pages' => array( 'rfq' ) ),
	array( 'slug' => 'qui-voit-offres', 'group' => 'buy', 'pages' => array( 'rfq' ) ),
	array( 'slug' => 'prix-da-kg', 'group' => 'buy', 'pages' => array( 'rfq' ) ),
	array( 'slug' => 'verifier-lot', 'group' => 'buy', 'pages' => array( 'reuse', 'buildings' ) ),
	array( 'slug' => 'demander-etude-inspection', 'group' => 'engineering', 'pages' => array( 'buildings', 'engineers' ) ),
	array( 'slug' => 'ingenieur-sans-coordonnees', 'group' => 'engineering', 'pages' => array( 'engineers' ) ),
	array( 'slug' => 'ingenieur-verifie', 'group' => 'engineering', 'pages' => array( 'engineers' ) ),
	array( 'slug' => 'devenir-ingenieur-partenaire', 'group' => 'engineering' ),
	array( 'slug' => 'calculateur-poids', 'group' => 'tools' ),
	array( 'slug' => 'optimiseur-decoupe', 'group' => 'tools' ),
	array( 'slug' => 'dimensions-catalogue', 'group' => 'tools' ),
	array( 'slug' => 'langues', 'group' => 'trust' ),
	array( 'slug' => 'coordonnees-publiques', 'group' => 'trust' ),
	array( 'slug' => 'prix-du-marche', 'group' => 'trust' ),
	array( 'slug' => 'contacter-equipe', 'group' => 'trust' ),
);

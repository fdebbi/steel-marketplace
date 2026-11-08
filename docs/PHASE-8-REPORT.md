# Phase 8 — rapport de livraison (8 novembre 2026)

Livrables : nouvelle extension `sm-services` 0.1.0, thème `sm-marketplace` 0.10.0, `sm-core` 0.3.3 (années d'expérience du profil). `sm-weight-engine` 0.1.0, `sm-products` 0.3.0, `sm-reuse` 0.1.0, `sm-buildings` 0.1.0 et `sm-rfq` 0.1.0 inchangés.

## Ce qui est livré

- **Services de fabrication et de chantier** (comptes entreprise avec l'activité fabrication, montage…) : découpe, perçage, soudage, charpente, poutres PRS, poteaux, assemblage, sablage, peinture, galvanisation, montage, démontage, coordination du transport.
- **Services d'ingénierie** (comptes ingénierie uniquement) : conception, notes de calcul, assemblages, plans techniques, plans d'atelier, plans de fabrication, inspections, expertise de structures d'occasion, conseil.
- **Page de service** : photos, services proposés, description, capacités techniques, équipements, zone d'intervention, délai habituel, prix indicatif ou « sur devis », prestataire (vérification, expérience), références, **demande de devis**.
- **Pages publiques** « Fabrication et montage » et « Ingénierie » : filtre par service, wilaya desservie et mot-clé.
- **Demandes de service** : le client décrit son projet (wilaya, date, budget, plans) ; le prestataire répond par un devis (montant, délai, message) ou décline ; le client accepte, refuse ou annule ; téléphone et e-mail du prestataire après acceptation.
- **Profil d'ingénieur** : nom, titre, qualifications, spécialités, **années d'expérience**, **références** (photo, année, wilaya, rôle), wilaya, contact, statut de vérification, services proposés.
- **Séparation** : un ingénieur ne voit que le catalogue ingénierie, ne publie pas d'annonce et ne répond pas aux demandes de devis d'acier ; les services de fabrication n'apparaissent pas sur la page Ingénierie.
- **Notifications** FR/AR, **administration** (modération facultative, réglages : pages par entreprise, photos, fichiers, demandes par jour, références, prix maximal), API REST en lecture.

## Tests

| Suite | Résultat |
| --- | --- |
| sm-services, tests PHP | 32/32 |
| sm-core, sm-weight-engine, sm-products, sm-reuse, sm-buildings, sm-rfq, tests PHP | 103 + 46 + 40 + 38 + 35 + 39 |
| Services dans le navigateur (`tests/e2e-services.py` : atelier qui publie avec erreurs puis photo et zone, recherche par service et par wilaya, page publique, ingénieur limité à son catalogue, référence avec photo sur le profil, demande d'un client sur mobile avec plan, demande en double refusée, devis vide refusé puis devis envoyé, acceptation et coordonnées, notifications, arabe, menu ingénieur, aucune erreur JS) | 39/39 |
| Demandes de devis, bâtiments, réemploi, produits, calculateur, parcours Phase 2 | 25 + 24 + 26 + 25 + 22 + 56 |
| Tests visuels AR/FR, 7 largeurs, 31 pages (8 nouvelles) | 462/462 |

## Limites connues

- Pas de messagerie intégrée : les échanges se font par téléphone ou e-mail (prévue plus tard).
- Pas de suivi des travaux après acceptation (commande, facture, avis client) : à cadrer avec les abonnements et les avis.
- Pas d'alerte automatique aux prestataires pour les nouvelles demandes de devis d'acier.
- Les qualifications d'un ingénieur sont déclaratives ; la vérification porte sur l'identité et les documents de l'entreprise (Phase 2).

## Mise à jour d'un site

Installer : sm-core 0.3.3, sm-services 0.1.0 (crée ses tables), thème 0.10.0 (la page « Service » est créée automatiquement à la première visite d’un administrateur ; sinon *Apparence → Pages SM*).

# Phase 7 — rapport de livraison (8 novembre 2026)

Livrables : nouvelle extension `sm-rfq` 0.1.0, thème `sm-marketplace` 0.9.0, `sm-core` 0.3.2 (valeurs imbriquées conservées après une erreur de formulaire). `sm-products` 0.3.0, `sm-weight-engine` 0.1.0, `sm-reuse` 0.1.0 et `sm-buildings` 0.1.0 inchangés.

## Ce qui est livré

- **Création d'une demande** (toute entreprise commerciale) : marché et catégorie ; lignes de profils pesées en direct par le moteur de poids, ou description libre avec quantité, unité et poids s'il est connu ; titre FR/AR, description technique, notes ; wilaya et lieu de livraison, date souhaitée, date limite des offres ; plans et documents (PDF, images).
- **Page publique des demandes** : filtres (profil ou mot-clé, marché, wilaya), tri, cartes avec quantité, poids, date limite et nombre d'offres ; page d'une demande avec le besoin détaillé.
- **Réponses des fournisseurs et fabricants** : prix total ou au kg, poids, délai, livraison incluse, validité, paiement, message, documents ; une offre par entreprise, modifiable ou retirable ; « Devis envoyés » dans le tableau de bord.
- **Comparaison** pour l'acheteur : offres côte à côte, tri (total, prix au kg, délai, fournisseurs vérifiés), moins cher, meilleur prix au kg et plus rapide signalés, téléphone et e-mail du fournisseur, présélection, refus, acceptation.
- **Gestion par l'acheteur** : « Mes demandes de devis » par statut, modification avant la première offre, clôture, suppression d'une demande sans offre.
- **Confidentialité** : offres visibles uniquement par l'acheteur (toutes) et par leur auteur ; plans réservés aux entreprises connectées.
- **Notifications** FR/AR (application + e-mail) : offre reçue, acceptée, refusée, demande attribuée à un autre, clôturée, expirée ; modération facultative.
- **Administration** : demandes par statut, modération, réglages (délais, fichiers, limite quotidienne, bornes de prix).

## Tests

| Suite | Résultat |
| --- | --- |
| sm-rfq, tests PHP | 39/39 |
| sm-core, sm-weight-engine, sm-products, sm-reuse, sm-buildings, tests PHP | 103 + 46 + 40 + 38 + 35 |
| Demandes de devis dans le navigateur (`tests/e2e-rfq.py` : publication mobile avec erreurs et lignes conservées, page publique, deux fournisseurs, confidentialité, comparaison et tri, acceptation, notifications, arabe, compte ingénierie) | 25/25 |
| Bâtiments, réemploi, produits, calculateur, parcours Phase 2 | 24 + 26 + 25 + 22 + 56 |
| Tests visuels AR/FR, 7 largeurs, 23 pages | 322/322 |

Correction trouvée en route : après une erreur de formulaire, les lignes d'inventaire (valeurs imbriquées) étaient perdues ; `sm-core` 0.3.2 les conserve (profite aussi au réemploi et aux bâtiments).

## Limites connues

- Pas de messagerie intégrée : le contact se fait par téléphone ou e-mail du fournisseur (messagerie prévue plus tard).
- Pas d'alerte automatique aux fournisseurs pour les nouvelles demandes correspondant à leurs catégories (possible en phase ultérieure, à cadrer pour éviter le spam).
- Plans DWG/DXF à exporter en PDF.
- Les « services de devis payants » du cahier des charges relèvent de la phase abonnements.

## Mise à jour d'un site

Installer : sm-core 0.3.2, sm-rfq 0.1.0 (crée ses tables et sa tâche quotidienne), thème 0.9.0.

# Changelog — sm-trade

## 0.5.0 — 2026-11-15

- Une commande ou une offre part avec le nom, le téléphone et l'e-mail du compte (`Form::account()`), plus de saisie dans le formulaire ; sans téléphone dans le compte, l'envoi est refusé avec un message clair. La boîte d'achat reçoit `contact`.

## 0.4.0 — 2026-11-14

- Statistiques de la Vue d'ensemble (`Stats`, donnée de thème `account_stats`) : montants, poids et nombre des ventes et achats acceptés, par jour (30 jours), par semaine (3 mois), par mois (12 mois et tout), avec la période précédente pour comparer.
- Poids de chaque commande enregistré à sa création (colonne `weight_kg`, base v3) : kg tels quels, barres à partir du poids d'une barre, lot au poids de l'annonce, rien pour le m². Commandes passées : poids estimé une fois à partir de l'annonce quand c'est possible.
- Noms des mois fournis par l'extension (français et arabe même sans traduction de WordPress installée).
- Tests : 48.

## 0.3.0 — 2026-11-14

- Offre sur des barres au prix au kg (`amount_kg`) : le total = nombre de barres × poids d'une barre × prix proposé. Une offre en prix total reste possible.
- Demande SM Products 0.15.0.
- Tests : 43.

## 0.2.0 — 2026-11-14

- Achat et offre par élément (bâtiments, SM Buildings 0.4.0) : les pièces choisies sont enregistrées avec la commande (colonne `items`), l'offre se fait au kg (`amount_kg`) et son total suit le poids des pièces ; à l'acceptation, les pièces quittent l'inventaire.
- Demande SM Products 0.14.0.
- Tests : 40 (inchangés) ; les cas par élément sont dans les tests de SM Buildings.

## 0.1.0 — 2026-11-13

- Première version : « Acheter » (lot ou quantité au prix affiché) et « Faire une offre » sur toutes les annonces ; réponses du vendeur et de l'acheteur (accepter, refuser, autre prix, annuler), 3 réponses avec un prix au plus, expiration après 7 jours.
- Stock retiré de l'annonce à l'acceptation (SM Products 0.10.0), annonce vendue à zéro.
- Coordonnées du vendeur données à l'acheteur après acceptation, même en formule gratuite.
- Pages du compte « Mes achats et offres », « Commandes et offres reçues », détail avec étapes ; compteurs du menu.
- Administration : historique complet, filtres, détail des étapes, export CSV.
- 40 tests PHP, 45 vérifications navigateur.

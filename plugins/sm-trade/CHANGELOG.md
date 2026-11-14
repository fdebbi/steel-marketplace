# Changelog — sm-trade

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

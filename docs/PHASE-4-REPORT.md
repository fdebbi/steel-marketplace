# Phase 4 — rapport de livraison (7 novembre 2026)

Livrables : extension `sm-products` 0.1.0, `sm-core` 0.3.0 (index des annonces, textes et fichiers), thème `sm-marketplace` 0.5.0. `sm-weight-engine` 0.1.0 inchangé.

## Tests

| Suite | Résultat |
| --- | --- |
| sm-products, tests PHP | 38/38 |
| sm-weight-engine, tests PHP | 46/46 |
| sm-core, tests PHP | 103/103 |
| Produits dans le navigateur (`tests/e2e-products.py` : publication mobile, erreurs et fichiers conservés, toiture, brouillon, modération, pages publiques FR/AR, vendu, suppression, sans JavaScript) | 25/25 |
| Calculateur de poids (`tests/e2e-weight.py`) | 22/22 |
| Parcours Phase 2 (`tests/e2e.py`) | 56/56 |
| Tests visuels AR/FR, 7 largeurs, 23 pages | 322/322 |

## Limites connues

- Les recherches qui mélangent plusieurs marchés (page d'accueil, recherche générale) montrent encore les annonces de démonstration pour le réemploi et les bâtiments (Phases 5 et 6). Désactiver le mode démonstration avant l'ouverture.
- Liste des catégories et de leurs champs à confirmer par le client (modifiable dans wp-admin).
- Pas encore de recadrage ni de réordonnancement des photos (la première envoyée est la photo principale).
- Les numéros d'annonce réels peuvent coïncider avec ceux des annonces de démonstration (101, 202…) tant que la démonstration est active.

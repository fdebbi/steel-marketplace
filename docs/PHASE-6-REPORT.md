# Phase 6 — rapport de livraison (7 novembre 2026)

Livrables : nouvelle extension `sm-buildings` 0.1.0, `sm-products` 0.3.0 (types de catégorie ouverts aux marchés), thème `sm-marketplace` 0.8.0. `sm-core` 0.3.1, `sm-weight-engine` 0.1.0 et `sm-reuse` 0.1.0 inchangés.

## Ce qui est livré

- **Marché des bâtiments métalliques** sur le moteur d'annonces commun : six catégories (industriels, entrepôts, ateliers, agricoles, élevage, kits).
- **Bâtiment monté** : dimensions, surface calculée, portiques, pente, système porteur, toiture, couverture, bardage, état ; ce que comprend le prix (démontage, transport, remontage, fondations et accessoires) ; prix demandé global ; poids d'acier estimé ou calculé depuis un inventaire facultatif ; **prix au kg et prix au m²** calculés.
- **Bâtiment démonté** : inventaire obligatoire par élément (poteaux, traverses, pannes, lisses, contreventements, potelets, poutres de roulement, autres), profil, quantité, longueur ; poids unitaire et total par ligne du moteur de poids ; prix au kg par défaut ou global ; prix au m².
- **Règles serveur** : prix global obligatoire pour un bâtiment monté ; « démontage inclus » refusé pour un bâtiment démonté ; limites (dimensions, portiques, lignes) réglables dans *SM → Bâtiments* ; un compte ingénierie ne publie jamais.
- **Fiche** : caractéristiques, « ce que comprend le prix », bloc prix (global, au kg, au m²), inventaire, **schéma indicatif** façade + plan tracé depuis les chiffres saisis (mention « ce n'est pas une étude structurelle »), avis d'ingénieur avant remontage d'un bâtiment d'occasion.
- **Configurateur 3D** (préparation) : chaque fiche expose les données de forme (longueur, largeur, hauteur, portiques, toiture, pente).
- **Recherche** : filtre monté / démonté ; la recherche générale sur les trois marchés est désormais servie par les extensions (plus de démonstration).

## Tests

| Suite | Résultat |
| --- | --- |
| sm-buildings, tests PHP | 35/35 |
| sm-products, tests PHP | 40/40 |
| sm-reuse, tests PHP | 38/38 |
| sm-weight-engine, tests PHP | 46/46 |
| sm-core, tests PHP | 103/103 |
| Bâtiments dans le navigateur (`tests/e2e-buildings.py` : hangar monté et bâtiment démonté sur mobile, surface et prix en direct, règles, pages publiques, filtre, recherche, fiche FR/AR, modification, compte ingénierie) | 24/24 |
| Réemploi (`tests/e2e-reuse.py`) | 26/26 |
| Produits neufs (`tests/e2e-products.py`) | 25/25 |
| Calculateur de poids (`tests/e2e-weight.py`) | 22/22 |
| Parcours Phase 2 (`tests/e2e.py`) | 56/56 |
| Tests visuels AR/FR, 7 largeurs, 23 pages | 322/322 |

## Limites connues

- Le schéma indicatif ne dessine que l'enveloppe et les portiques ; le configurateur 3D reste à faire (phase ultérieure).
- L'inventaire n'accepte que les profils du catalogue (pas de cotes libres par ligne) ; les éléments non métalliques (couverture, boulonnerie) ne sont pas pesés.
- Les bâtiments de démonstration ne s'affichent plus puisque l'extension sert ce marché ; le mode démo ne concerne plus que les services, l'ingénierie et les demandes de devis.
- En attente du client : liste définitive des catégories et champs, longueurs de barres, tubes EN 10210.

## Mise à jour d'un site

Installer : sm-products 0.3.0 (ajoute la colonne `variant`), sm-buildings 0.1.0 (crée ses catégories), thème 0.8.0. Les autres extensions sont inchangées.

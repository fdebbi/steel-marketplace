# Phase 5 — rapport de livraison (7 novembre 2026)

Livrables : nouvelle extension `sm-reuse` 0.1.0, `sm-products` 0.2.0 (moteur d'annonces multi-marchés), thème `sm-marketplace` 0.7.0 (nom ReuSteel, animations, réemploi), `sm-core` 0.3.1. `sm-weight-engine` 0.1.0 inchangé.

## Ce qui est livré

- **Marché du réemploi** sur le même moteur que les produits neufs : choix du marché puis du type de lot dans « Publier une annonce ».
- **Inventaire ligne par ligne** pour les chutes de profilés et barres, profilés de démontage et tubes : type, profil du catalogue européen, pièces, longueur ; poids de chaque ligne calculé en direct par le moteur de poids ; ligne vide ajoutée automatiquement, suppression d'une ligne d'un clic.
- **Lots au poids** pour les chutes de tôles, lots mixtes et ferraille : poids pesé ou estimé, affiché comme tel aux acheteurs.
- **Échelle d'état** en cinq classes, origine, état de surface, découpe à la demande, chargement.
- **Prix** au kg, à la pièce ou pour le lot entier (avec équivalent au kg).
- **Règles serveur** : ferraille ↔ catégorie « Ferraille » ; découpe réservée aux barres et profilés ; état et origine obligatoires ; un compte ingénierie ne publie jamais.
- **Avis d'ingénieur** sur chaque fiche de réemploi, avec lien vers les ingénieurs.
- **Page Réemploi** : filtres état, longueur minimale, nombre minimal de pièces ; recherche dans toutes les tailles d'un lot (« HEA160 »).
- Administration : filtre par marché, catégories groupées par marché, modifiables comme les autres.

## Tests

| Suite | Résultat |
| --- | --- |
| sm-reuse, tests PHP | 38/38 |
| sm-products, tests PHP | 40/40 |
| sm-weight-engine, tests PHP | 46/46 |
| sm-core, tests PHP | 103/103 |
| Réemploi dans le navigateur (`tests/e2e-reuse.py` : publication mobile d'un inventaire, lignes, suppression, prix du lot, modification, règles ferraille, filtres publics, fiche FR/AR avec avis, compte ingénierie) | 26/26 |
| Produits neufs dans le navigateur (`tests/e2e-products.py`) | 25/25 |
| Calculateur de poids (`tests/e2e-weight.py`) | 22/22 |
| Parcours Phase 2 (`tests/e2e.py`) | 56/56 |
| Tests visuels AR/FR, 7 largeurs, 23 pages | 322/322 |

Corrections trouvées en route : deux tests PHP de sm-products passaient avec une entreprise invalide (activité « buyer » inexistante) — corrigés ; une transition de page interrompue par le navigateur produisait une erreur JavaScript — neutralisée.

## Limites connues

- La recherche générale qui inclut les bâtiments affiche encore la démonstration tant que le mode démo est actif (bâtiments : Phase 6). Désactiver le mode démo avant l'ouverture.
- Les inventaires n'acceptent que les profils du catalogue (pas de cotes libres par ligne).
- En attente du client : liste définitive des catégories et champs, longueurs autres que 12 m et 6 m, tubes EN 10210.

## Mise à jour d'un site

Installer dans l'ordre : sm-core 0.3.1, sm-weight-engine 0.1.0, sm-products 0.2.0 (ajoute ses colonnes automatiquement), sm-reuse 0.1.0 (crée ses catégories), puis le thème 0.7.0.

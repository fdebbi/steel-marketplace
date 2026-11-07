# Changelog — sm-products

## 0.2.0 — 2026-11-07 (Phase 5 : moteur multi-marchés)

- `sm-products` devient le moteur d'annonces acier de plusieurs marchés : chaque annonce et chaque catégorie portent un marché (`products` par défaut). D'autres extensions ajoutent le leur par le filtre `sm_products_markets` (sm-reuse ajoute `reuse`) ; un marché définit sa permission, son échelle d'état et ses champs communs.
- Deux nouveaux types de catégorie : **inventaire** (`lines` : une ligne par profil et longueur, jusqu'à 30, chaque ligne calculée par SM Weight Engine) et **lot au poids** (`weight` : poids pesé ou estimé, champs propres).
- Unité de prix « lot » (prix du lot entier, avec équivalent au kg).
- État (`item_condition`) et champs du marché (`attrs`) contrôlés côté serveur ; filtre `sm_products_spec_check` pour les règles d'un marché.
- Recherche : plusieurs marchés à la fois, filtres état, longueur minimale (plus longue pièce) et nombre minimal de pièces ; recherche dans toutes les tailles d'un inventaire.
- Fiche : tableau d'inventaire (`elements`), lignes état et champs du marché, filtre `sm_products_listing_detail`.
- Admin : filtre et colonne marché, catégories groupées par marché. REST : paramètre `market`.
- Base v2 : colonnes `market`, `item_condition`, `attrs`, `inventory` (le mot `lines` est réservé en MySQL).
- Traductions : 130 chaînes. Tests : 40 PHP (fixtures d'entreprises corrigées : deux tests passaient sans entreprise valide).

## 0.1.0 — 2026-11-07 (Phase 4)

- Catégories : profilés, tubes, tôles, fers plats et barres (données et poids de SM Weight Engine), toiture et bardage, pièces de finition, autres ; noms, unités et champs modifiables dans wp-admin.
- Formulaire de publication par catégorie avec résumé en direct (poids, nombre de barres, total, prix au kg équivalent) calculé par le serveur ; quantité en barres ou en poids total ; poids théorique ou pesé (contrôle d'écart) ; photos et fiches techniques PDF conservées en cas d'erreur.
- Modération avec motif, notifications (soumis, publié, à modifier, expiré), expiration quotidienne.
- Index des annonces de sm-core : numéro public commun à tous les marchés ; propriétaire pour les demandes de contact.
- Liste publique filtrée, fiche produit (schéma, caractéristiques, documents), « Mes annonces ».
- API REST publique `sm/v1/products`. Traductions fr_FR et ar (118 chaînes). 38 tests PHP, 25 vérifications navigateur.

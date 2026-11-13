# Changelog — sm-products

## 0.11.0 — 2026-11-13

- `Facets::get( market, category, family )` (ressource `listing_facets`) : onglets des types avec leur nombre d'annonces en ligne, filtres propres au type choisi construits sur les valeurs réellement utilisées.
- Nouveaux filtres de recherche : désignation exacte, poids minimal, mode de vente (lot / détail), champs à liste des catégories (`spec[clé]`).
- 9 tests PHP de plus.

## 0.10.0 — 2026-11-13 (base v7)

- Publication directe : la vérification avant publication est désactivée par défaut (aussi pour les clients particuliers et les modifications d'annonces en ligne). À la mise à jour, le réglage est désactivé et les annonces en attente sont publiées. La vérification reste disponible dans les réglages.
- E-mail immédiat aux administrateurs pour chaque annonce publiée et chaque modification par son vendeur, avec le lien vers l'administration.
- Suppression d'une annonce par l'administrateur avec un motif obligatoire, envoyé au vendeur (notification et e-mail), journalisée.
- Filtres de recherche : vendeurs vérifiés (niveau 2), surface minimale et hauteur minimale des bâtiments.
- `Stock::options()`, `check()`, `take()` : ce qu'un acheteur peut commander (lot, barres entières, kg, pièces, m², mètres) et retrait du stock à l'acceptation d'une commande (SM Trade).

## 0.9.0 — 2026-11-12

- Indice des prix (SM → Produits → Indice des prix) : calcul automatique de la moyenne activable ou non, seuils réglables (annonces par semaine, semaines nécessaires), tableau des dernières semaines pour choisir les seuils.
- Prix de référence saisis par l'administrateur (date, prix au kg, source facultative) : chaque date ajoute un point, une nouvelle saisie à la même date la corrige, suppression possible ; journalisé. Affichés quand le calcul automatique est désactivé ou n'a pas encore assez d'annonces.
- `PriceTrend::summary()` renvoie `mode` (auto / manual / none), `points` { date, value }, `last`, `last_date`, `change`, `source`, `reuse`.
- 11 tests PHP de plus.

## 0.8.0 — 2026-11-12 (Phase 10, base v6)

- Colonnes `bumped_at`, `featured_until`, `urgent_until` ; statut `paused` (mis en pause par la formule, republiable).
- Tri : annonces à la une d'abord, puis date de remontée ou de publication.
- `Products::online_totals()`, `online_oldest_first()`, `boost()` ; filtre `sm_products_publish_limit` appelé à la publication et à la republication.
- Presenter : `is_featured`, `is_urgent`, `featured_until`, `urgent_until`.

## 0.7.1 — 2026-11-12

- `section_sheet` (dimensions, aire, poids au mètre, norme) dans les données d'une annonce sans photo ; fichiers du dessin refaits automatiquement quand la version des fichiers change.

## 0.7.0 — 2026-11-12 (base v5)

- Produits neufs calculés (catégories de type « engine » du marché des produits) : titre généré « type et dimension · nuance · longueur » en français et en arabe (« IPE 200 · S275JR · 12 m » / « … · 12 م ») ; sans longueur quand le produit est vendu au poids ; un titre saisi est ignoré. Les annonces existantes gardent leur titre jusqu'à leur prochaine modification.
- Photos facultatives pour ces produits : la colonne `drawing` (base v5) garde la clé du dessin de la section ; l'annonce affiche `image` = dessin SVG, `image_drawing`, `share_image` = PNG.

## 0.6.0 — 2026-11-12

- `PriceTrend` : prix moyen demandé au kg des profilés laminés (IPE, HEA, HEB, HEM, IPN, UPN, UPE, cornières), semaine par semaine, à partir des annonces mises en ligne (vendues, en pause ou expirées comprises ; brouillons, en attente et refusées exclues). Une semaine compte avec au moins 5 annonces ; la courbe exige au moins 4 semaines. Mis en cache une heure, vidé à chaque enregistrement ou changement de statut.
- Ressource du thème `price_trend` : `points`, `last`, `change`, `reuse`.

## 0.5.1 — 2026-11-12

- Données en anglais (via `sm_pick`, sm-core 0.8.0).

## 0.5.0 — 2026-11-11 (base v4)

- Mode de vente : détail (kg / pièce), lot complet ou les deux ; prix du lot vérifié (pas au-dessus du total au détail) ; hangars démontés vendables aussi à la pièce. Les anciennes annonces « prix du lot » passent en « lot complet ».
- Modification d'une annonce publiée : prix, mode de vente et quantités tout de suite ; textes, photos, fiches et données techniques en attente de validation, l'ancienne version restant en ligne (onglet « Modifications à vérifier », publier / refuser avec motif).
- L'administrateur modifie le titre, la description, les photos et les fiches d'une annonce avec un motif ; le vendeur reçoit une notification et un e-mail avec le motif et la liste des changements.
- Une annonce déjà en ligne garde sa date de publication quand elle est modifiée.

## 0.4.0 — 2026-11-11 (Phase 9.1)

- Comptes client : annonces toujours vérifiées (réglable), nombre d'annonces en ligne limité (5 par défaut, réglable), vendeur affiché « Particulier » sans lien public.

## 0.3.1 — 2026-11-08 (Phase 8.1)

- E-mails (avec sm-core 0.4.0) : événements « nouvelle annonce publiée » et « nouvelle annonce en attente de vérification » (alerte aux administrateurs, confirmation au vendeur, modèles FR/AR), phrases et liens du journal d'activité.

## 0.3.0 — 2026-11-07 (Phase 6 : types de catégorie ouverts aux marchés)

- Un marché peut ajouter son propre type de catégorie (filtre `sm_products_kinds`, action `sm_products_spec_kind_{type}`) ; `Spec::lines()` est public pour qu'il réutilise l'inventaire pesé par le moteur.
- Lignes d'inventaire avec type d'élément quand le marché en définit (`line_elements`) ; nombre de lignes réglable (`sm_products_max_lines`).
- Colonne `variant` (base v3) : monté / démonté pour les bâtiments, filtre `mode`.
- Prix au m² calculé quand la quantité est une surface ; filtre `sm_products_listing` sur la forme « annonce » ; champs de marché masquables dans les caractéristiques (`row => false`) ; valeurs de formulaire filtrables (`sm_products_form_values`).
- Une recherche sur les trois marchés est désormais servie entièrement par le moteur.

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

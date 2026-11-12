# SM Products — moteur d'annonces acier (produits neufs et autres marchés)

Extension de **ReuSteel** (Phase 4, multi-marchés depuis la Phase 5). Nécessite **SM Core 0.3.0** et **SM Weight Engine 0.1.0**.

Depuis la version 0.2.0, cette extension est le **moteur d’annonces** commun : elle porte le marché des produits neufs et accueille les marchés ajoutés par d’autres extensions (SM Reuse ajoute le réemploi, SM Buildings les bâtiments avec leur propre type de catégorie depuis la 0.3.0) — mêmes photos, modération, recherche, fiche et tableau de bord. Un marché ajoute sa permission, son échelle d'état, ses champs et ses catégories (types *inventaire* ligne par ligne et *lot au poids* disponibles). Voir `docs/hooks.md`.

## Ce qu'elle fait

- **Publication par catégorie**, chaque catégorie avec son formulaire :
  - *Profilés, tubes, tôles, fers plats et barres* : type choisi sur son schéma exact, dimension du catalogue européen (ou cotes saisies pour les tubes, barres et tôles), nuance, longueur (12 m, 6 m ou libre), quantité en **nombre de barres** ou en **poids total** — le moteur de poids calcule l'autre. Poids théorique ou poids pesé.
  - *Toiture et bardage* : type (panneau sandwich PS, tôle nervurée…), nom du type chez le fabricant, largeur utile, longueurs minimale et maximale, épaisseur de tôle, épaisseur d'isolant, finition. Quantité et prix au m², au ml ou à la pièce ; aucun poids inventé.
  - *Pièces de finition*, *Autres produits acier* : champs simples.
  - Les catégories, leurs unités et leurs champs se modifient dans wp-admin.
- Photos (10, JPG/PNG/WebP) et **fiches techniques PDF** téléchargeables par les acheteurs.
- Prix au kg, à la pièce, au m² ou au ml ; total et équivalent au kg **calculés par le serveur**.
- **Modération** (réglable) : brouillon → en attente → publié ou modifications demandées (motif envoyé au vendeur) ; vendu, retiré, expiré (durée réglable, 90 jours par défaut).
- Pages publiques : liste avec filtres (catégorie, wilaya, recherche « IPE200 »), fiche produit avec schéma, caractéristiques, documents et formulaire de contact du vendeur.
- Tableau de bord vendeur : « Mes annonces » par statut, modifier, marquer vendu, retirer, republier, supprimer.
- Règles : seules les entreprises commerciales ayant une activité de vente et un contact vérifié publient ; un compte ingénierie ne publie jamais.

## Indice des prix (0.9.0)

SM → Produits → Indice des prix. Le prix affiché sous l'image de l'accueil et sur la page Indice des prix vient :

- **du calcul automatique** (activé par défaut) : moyenne au kg des annonces de profilés laminés neufs (IPE, HEA, HEB, HEM, IPN, UPN, UPE, cornières) mises en ligne chaque semaine. Une semaine compte avec au moins 5 annonces, la courbe s'affiche avec au moins 4 semaines sur 12 ; les deux seuils sont réglables ;
- **sinon, des prix de référence** saisis par l'administrateur (date, prix au kg, source facultative). Ils sont affichés quand le calcul est désactivé ou n'a pas encore assez d'annonces, avec la mention « Prix de référence ReuSteel » et la date. Les 12 derniers forment la courbe.

Sans calcul possible ni prix saisi, l'accueil affiche « Prix bientôt disponible » : aucun prix n'est inventé.

## Installation

1. Activer SM Core 0.3.0 et SM Weight Engine 0.1.0, puis téléverser et activer `sm-products.zip`.
2. Thème sm-marketplace 0.5.0 : le formulaire réel remplace la maquette de « Publier une annonce ».
3. Steel Marketplace → Produits : file de modération, catégories, réglages (modération, durée, photos, nuances proposées, prix minimal et maximal).

## Développeurs

- Données thème : `listings` (market = products), `listing`, `categories`, `product_categories`, `product_form`, `product_preview`, `my_products`, `product_settings` — voir `docs/hooks.md`.
- API publique : `GET /wp-json/sm/v1/products`, `/products/{numéro}`, `/products/categories`.
- Tests : `wp eval-file wp-content/plugins/sm-products/tests/run.php` (38 tests) ; navigateur : `tests/e2e-products.py` (25 vérifications).

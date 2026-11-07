# SM Products — marché des produits acier neufs

Extension de **Steel Marketplace** (Phase 4). Nécessite **SM Core 0.3.0** et **SM Weight Engine 0.1.0**.

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

## Installation

1. Activer SM Core 0.3.0 et SM Weight Engine 0.1.0, puis téléverser et activer `sm-products.zip`.
2. Thème sm-marketplace 0.5.0 : le formulaire réel remplace la maquette de « Publier une annonce ».
3. Steel Marketplace → Produits : file de modération, catégories, réglages (modération, durée, photos, nuances proposées, prix minimal et maximal).

## Développeurs

- Données thème : `listings` (market = products), `listing`, `categories`, `product_categories`, `product_form`, `product_preview`, `my_products`, `product_settings` — voir `docs/hooks.md`.
- API publique : `GET /wp-json/sm/v1/products`, `/products/{numéro}`, `/products/categories`.
- Tests : `wp eval-file wp-content/plugins/sm-products/tests/run.php` (38 tests) ; navigateur : `tests/e2e-products.py` (25 vérifications).

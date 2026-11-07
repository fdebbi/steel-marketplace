# Changelog — sm-products

## 0.1.0 — 2026-11-07 (Phase 4)

- Catégories : profilés, tubes, tôles, fers plats et barres (données et poids de SM Weight Engine), toiture et bardage, pièces de finition, autres ; noms, unités et champs modifiables dans wp-admin.
- Formulaire de publication par catégorie avec résumé en direct (poids, nombre de barres, total, prix au kg équivalent) calculé par le serveur ; quantité en barres ou en poids total ; poids théorique ou pesé (contrôle d'écart) ; photos et fiches techniques PDF conservées en cas d'erreur.
- Modération avec motif, notifications (soumis, publié, à modifier, expiré), expiration quotidienne.
- Index des annonces de sm-core : numéro public commun à tous les marchés ; propriétaire pour les demandes de contact.
- Liste publique filtrée, fiche produit (schéma, caractéristiques, documents), « Mes annonces ».
- API REST publique `sm/v1/products`. Traductions fr_FR et ar (118 chaînes). 38 tests PHP, 25 vérifications navigateur.

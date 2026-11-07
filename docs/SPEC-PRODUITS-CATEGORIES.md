# Publication des produits par catégorie — cahier des charges (remarque n° 1, 7 nov. 2026)

À réaliser en **Phase 3** (catalogue de référence et moteur de poids `sm-weight-engine`) et **Phase 4** (formulaires de `sm-products`). Aucune dimension ni aucun poids ne sera saisi de mémoire : les valeurs de référence viendront de tables européennes validées.

## Principe

Pour publier un produit neuf, le vendeur choisit d'abord la **catégorie** (profilés, tôles, fer plat, toiture, pièces de finition…). Chaque catégorie a son propre formulaire de spécification. Chaque produit accepte des **photos** et une **fiche technique** (PDF).

## Profilés

1. Choix du type dans une liste visuelle, chaque type avec son **schéma de section exact** : HEA, HEB, IPE, IPN, UPN, cornières, tubes carrés, tubes rectangulaires, tubes ronds…
2. Choix de la **dimension** dans le catalogue européen (ex. IPE 200), avec ses caractéristiques (h, b, tw, tf, r, kg/m).
3. Nuance (S235JR, S275JR, S355JR…).
4. **Quantité**, au choix :
   - poids total → la plateforme estime le nombre de barres de 12 m ;
   - nombre de barres de 12 m → la plateforme estime le poids total.
   Les estimations sont signalées comme théoriques (poids nominal du catalogue).

## Toiture (panneau sandwich PS, tôle nervurée)

Nom du type chez le fabricant, largeur utile, longueur minimale et maximale, épaisseur de la tôle, épaisseur de l'isolant (PS), finition / couleur. Pas de poids calculé sans fiche du fabricant.

## Autres catégories

Tôles, fer plat, pièces de finition, etc. : champs à définir avec le client avant la Phase 4, même règle pour les valeurs de référence.

## Questions ouvertes (à valider avant la Phase 3)

- Liste complète des catégories et de leurs champs.
- Longueurs de barre autres que 12 m (ex. tubes en 6 m) et liberté du vendeur.
- Tables de référence : EN 10365 (profilés H, I, U), EN 10219 / EN 10210 (profils creux), catalogue fabricant retenu.

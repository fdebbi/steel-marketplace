# Rapport de livraison — Phase 9.3 : services, vente au lot, modifications vérifiées, cloche

Date : 11 novembre 2026
Versions : sm-core 0.7.2 · sm-products 0.5.0 (base v4) · sm-services 0.3.3 (base v4) · thème sm-marketplace 0.16.0

## 1. Ce qui a été demandé et validé

1. Profil : supprimer le carré d'informations, ne plus afficher le titre « Type d'entreprise », types affichés sur une ligne dans la présentation.
2. Services : un seul service par page, choisi dans une liste déroulante ou « Autre » (nom libre) ; titre = nom du service (plus de titres FR/AR) ; description de 15 caractères minimum ; une image par service ; les autres services de l'entreprise en bas de page.
3. Vente **à la pièce / au lot complet** pour les profilés, les chutes et les hangars démontés, avec le mode « les deux » (prix de détail + prix du lot).
4. Une annonce ou un service modifié **reste en ligne dans sa version actuelle** jusqu'à la validation de l'administrateur. Prix, quantité, vendu/pause s'appliquent tout de suite.
5. L'administrateur peut modifier les annonces et les services (texte, photos) avec un motif envoyé à l'entreprise.
6. La cloche ouvre une fenêtre flottante avec les 8 dernières notifications et « Voir toutes les notifications ».

## 2. Ce qui a été fait

### Vente au lot (sm-products, thème)
- Dans le formulaire : « Comment le vendez-vous ? » — À la pièce / au kg · Lot complet uniquement · Les deux.
- Lot : un seul prix pour tout, avec l'équivalent au kg calculé. Les deux : un prix de détail et un prix du lot ; un prix du lot supérieur au total au détail est refusé.
- Proposé pour les catégories avec inventaire (profilés, chutes, ferraille au poids) et les bâtiments démontés (pas pour un bâtiment monté).
- Cartes et page produit : « Prix du lot entier », ou « ou X le lot complet » en mode « les deux ».
- Migration : les anciennes annonces vendues « au lot » passent en mode lot automatiquement.

### Modifications vérifiées (sm-products, sm-services)
- Si le texte, les photos, les fiches ou les données techniques changent, la modification est mise en attente ; la version en ligne ne bouge pas. Badge « Modification en vérification » dans le compte.
- Prix, quantité, vendu, pause : appliqués tout de suite, sans vérification.
- Une modification mixte (texte + prix) attend entièrement la validation.
- Admin : onglet « Modifications » avec le détail des changements, Approuver ou Refuser (motif obligatoire, envoyé à l'entreprise).

### Modification par l'administrateur
- Annonces : titre FR/AR, description, suppression et ajout de photos, suppression de fiches. Services : description, capacités, équipements, image.
- Motif obligatoire ; l'entreprise reçoit une notification et un e-mail avec le motif et la liste des changements ; l'action est inscrite au journal.

### Cloche
- Clic : fenêtre flottante (8 dernières, « Tout marquer comme lu », « Voir toutes les notifications »). Fermeture par clic extérieur ou Échap.
- Page /compte/notifications/ : liste complète (100 dernières). Un clic sur une notification la marque lue et ouvre sa page.

## 3. Tests

| Suite | Résultat |
| --- | --- |
| PHP sm-core / weight / products / reuse / buildings / rfq / services / ai / engineering | 167 / 46 / 51 / 38 / 35 / 39 / 52 / 46 / 86 |
| e2e comptes / engineering / services / mail | 64 / 59 / 57 / 31 |
| e2e products (vente au lot, révisions, édition admin, cloche) | 48/48 |
| e2e reuse / buildings / rfq / ai / weight / directory | 26 / 24 / 25 / 30 / 22 / 23 |
| Tests visuels (FR/AR, 360 à 1440 px) | 616/616 |

## 4. Points à noter

- Hangars démontés : le prix de détail peut être à la pièce ou au kg ; le prix au kg reste le plus parlant, car les éléments sont différents.
- Les anciennes pages multi-services (comptes de test) gardent leur premier service.
- Mise à jour : les bases passent en v4 (sm-products, sm-services) automatiquement.

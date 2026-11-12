# Rapport de livraison — Refonte du design (thème 0.18.0)

Date : 12 novembre 2026
Versions : sm-core 0.8.0 · sm-products 0.5.1 · sm-rfq 0.1.2 · sm-services 0.3.5 · sm-ai 0.1.2 · thème sm-marketplace 0.18.0
Maquette validée : canevas « ReuSteel — Refonte du design » (12 novembre 2026).

## 1. Décisions du client

- Maquette d'abord, puis le code.
- Accueil et vitrine sombres ; catalogue, fiches, formulaires et compte clairs, avec un mode sombre en option dans le compte.
- Anglais ajouté avec la refonte.
- Gestion de stock, bons de commande, gestion des commandes et facturation : phase séparée, plus tard. Rien sur l'accueil ne les présente.

## 2. Ce qui a été fait

### Charte
- Vert ReuSteel #06CE77 pour les boutons et l'état actif, avec un texte noir dessus. Le thème choisit seul le noir ou le blanc selon le contraste si l'administrateur change la couleur.
- Vert texte #047A47 pour les liens sur fond clair (contraste 5,4:1). Le vert #06CE77 n'est jamais une couleur de texte sur fond blanc (2,1:1).
- Graphite #111820 (en-tête, éléments sombres), gris texte #5B6670, fond #F5F7F8.
- Titres en Space Grotesk, textes en Inter, arabe en IBM Plex Sans Arabic. Polices hébergées sur le site.
- Mise à jour : les anciennes couleurs par défaut sont remplacées ; une couleur choisie par l'administrateur dans Apparence → Personnaliser → SM Brand est gardée.

### Accueil
- Bannière sombre : étoiles, halo vert, titre « Le futur du commerce de l'acier commence ici. », deux boutons, recherche, chiffres réels de la plateforme.
- Poutre 3D : une vraie section IPE 200 (EN 10365) en métal brossé, avec un léger bord vert et des particules.
  - Elle ne s'affiche que sur ordinateur, après le chargement de la page. Three.js (167 Ko compressé) ne se télécharge pas sur mobile.
  - Elle est remplacée par une image fixe si l'appareil demande « réduire les animations » ou si WebGL manque.
- Sections : marchés, annonces à la une, lots de réemploi, économie circulaire (Démonter → Vérifier → Revendre), devis et indice des prix, fabricants et ingénieurs, entreprises et professionnels à la une, « Pourquoi ReuSteel ? », bandeau d'inscription.
- Toutes les sections existantes sont gardées.

### Catalogue
- Les cartes se soulèvent avec un bord vert au survol.
- Une image absente laisse un fond acier au lieu d'une image cassée.
- Prix en police de titre, puces de catégories redessinées.

### Compte
- En-tête plus sombre, panneaux arrondis.
- Bouton « Mode sombre » dans le menu. Le choix est mémorisé dans le navigateur et appliqué avant l'affichage, donc pas de flash blanc.

### Anglais
- Adresses /en/… et sélecteur Français · العربية · English.
- Les textes du site sont écrits en anglais à la source, donc l'anglais est complet sans fichier de traduction.
- Données de la plateforme (catégories, champs, options, catalogue des services) : traduites depuis le français grâce à un dictionnaire de 129 entrées.
  - Un texte ajouté par l'administrateur sans version anglaise s'affiche en français.
- E-mails : modèles, signature et adresse modifiables aussi en anglais.
- Annonces des vendeurs : titre en français ou en arabe. En anglais, le titre français s'affiche.

## 3. Tests

| Suite | Résultat |
| --- | --- |
| PHP : sm-core / weight / products / reuse / buildings / rfq / services / ai / engineering | 167 / 46 / 51 / 38 / 35 / 39 / 52 / 46 / 86, tous réussis |
| e2e comptes / mail / products / reuse / buildings | 64 / 31 / 48 / 26 / 24 |
| e2e rfq / services / engineering / ai / weight / directory | 25 / 57 / 59 / 30 / 22 / 23 |
| Tests visuels FR, AR et EN, de 360 à 1440 px | 924/924 |

- e2e-mail : l'e-mail de test part maintenant en trois langues. Le test a été mis à jour.
- e2e-rfq et e2e-services : un échec isolé lors de la série complète, réussis tous les deux en relançant seuls (suites sensibles à l'ordre d'exécution, pas une régression).

## 4. Points à noter

- Le dictionnaire anglais ne couvre que les données par défaut. Pour un type ou un service ajouté par l'administrateur, l'anglais affiche le français. Si besoin, on ajoutera un champ « nom anglais » dans SM → Types et catégories.
- Les adresses des pages gardent leurs mots français (/en/produits/) pour ne casser aucun lien existant.

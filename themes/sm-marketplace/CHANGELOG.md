# Changelog — sm-marketplace

## 0.1.1 — 2026-11-07

- Cache de page (Hostinger LiteSpeed / CDN) : les pages qui dépendent d'un paramètre d'URL (`?lang=`, `?id=`, `?view=`, filtres) et les pages de compte ne sont plus mises en cache. Corrige des filtres sans effet, une langue erronée ou la mauvaise annonce affichée.
- Un visiteur ayant choisi l'arabe qui ouvre une adresse sans `?lang=` est redirigé vers la version `?lang=ar` : le contenu d'une URL ne dépend plus d'un cookie.
- URL du sélecteur de langue fiable aussi en installation dans un sous-dossier.

## 0.1.0 — 2026-11-05 (Phase 1)

- Thème classique sur mesure, sans constructeur de pages ni thème commercial.
- En-tête avec recherche, méga-menu des marchés, tiroir mobile, barre d'onglets mobile, pied de page.
- Pages : accueil, marchés (produits, réemploi, bâtiments, recherche), détail d'annonce (produit, lot de réemploi, bâtiment monté / démonté avec inventaire), services, ingénierie, annuaire et fiche entreprise (commerciale ou bureau d'études), demandes de devis, indice des prix, abonnements, connexion, inscription (compte professionnel ou ingénierie), mot de passe oublié, tableau de bord commercial (vue d'ensemble, annonces, publication en 4 étapes) et ingénieur, charte graphique, 404.
- Couleurs de marque, nom et logo modifiables dans le Personnalisateur, avec aperçu en direct.
- Bilingue arabe RTL / français LTR : 447 chaînes traduites, CSS logique + RTLCSS, unités et désignations isolées.
- Contrat de données par filtres + fournisseur de démonstration ; installateur de pages non destructif.
- Outils : build CSS, compilation des traductions, tests visuels responsive automatisés.

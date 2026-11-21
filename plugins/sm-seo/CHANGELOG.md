# Changelog — sm-seo

## 0.2.0 — 2026-11-21 (avec sm-weight-engine 0.8.0, thème 0.59.0)

- **Catalogue des profilés** : titre, description (valeurs réelles : kg/m, barres de 6 et 12 m, dimensions), adresse canonique, données structurées (Product avec ses dimensions sans prix, FAQPage des deux questions affichées, liste des tailles d'une famille, fil d'Ariane). Taille inconnue : hors index.
- Plan du site `smcatalogue` : page du catalogue, familles et tailles, dans les 3 langues. `llms.txt` : lien vers le catalogue.

## 0.1.1 — 2026-11-21

- Description des annonces en français : « Wilaya **d'**Alger », « d'Oran », « d'El Bayadh » devant une voyelle (« Wilaya de M'Sila » ailleurs). Vérifié sur reusteel.com après la mise en ligne de la 0.58.1.

## 0.1.0 — 2026-11-21 (avec le thème 0.57.0)

- Première version : **référencement dans les moteurs de recherche et les IA**.
- Titre et description de chaque page à partir des vraies données, en français, arabe et anglais ; adresse canonique dans la langue de la page (corrige l'adresse canonique de WordPress qui pointait vers `/annonce/` sans le numéro).
- Pages hors index (`noindex, follow`) : compte, connexion, inscription, formulaire d'étude, recherches avec texte / filtres / tri, annonces vendues, en pause ou expirées, demandes de devis une par une, entreprises sans contenu.
- Données structurées JSON-LD : Produit + Offre (prix demandé en DZD, au kilo ou au lot), Organisation, WebSite + recherche, fil d'Ariane, services d'ingénierie, services des entreprises.
- Aperçus de partage Open Graph et X sur toutes les pages (photo de l'annonce, logo de l'entreprise, image par défaut).
- Plans du site XML : pages, types de produit, annonces (neuf, réemploi, bâtiments), entreprises, services — dans les 3 langues.
- `robots.txt` (choix A : recherche IA permise, entraînement refusé, compte fermé, plan du site) et `llms.txt`.
- Réglages SM → SEO : robots des IA, image de partage par défaut, codes Google Search Console et Bing Webmaster Tools.

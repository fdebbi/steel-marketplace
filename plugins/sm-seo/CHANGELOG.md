# Changelog — sm-seo

## 0.5.1 — 2026-11-27 (avec sm-core 0.10.0, thème 0.63.0)

- Pages « Conditions générales d'utilisation » et « Politique de confidentialité » : titre et description dans les 3 langues (indexées, dans le plan du site des pages, hreflang).

## 0.5.0 — 2026-11-26 (avec sm-guides 0.2.0, thème 0.62.0)

- Page `/faq/` : titre, description, adresse canonique, fil d'Ariane et données « FAQPage » avec les questions et réponses affichées, dans la langue de la page (texte simple, sans HTML) ; une question affichée en français à une adresse /ar/ ou /en/ n'y est pas reprise.
- `/faq/` dans le plan du site des pages (3 langues, hreflang) ; `llms.txt` cite les questions fréquentes.

## 0.4.0 — 2026-11-26 (avec sm-guides 0.1.0, thème 0.61.0)

- Guides (`/guides/` et `/guides/{nom}/`) : titre, description tirée du résumé du guide, adresse canonique, fil d'Ariane, données « Article » (sans aucun prix) ; liste des guides en « ItemList ».
- Un guide affiché en français à une adresse /ar/ ou /en/ parce qu'il n'est pas écrit dans cette langue : hors index.
- Plan du site `smguides` : la liste des guides et chaque guide dans les langues où il est écrit ; `llms.txt` cite les guides.

## 0.3.1 — 2026-11-26 (avec sm-products 0.24.1, thème 0.60.1) — vérification sur reusteel.com

- Description des pages « type × wilaya » : « dans la wilaya d’Alger » (élision devant une voyelle : Alger, Oran, Adrar, El Oued, Aïn Defla…) au lieu de « de Alger » (`Pages::in_wilaya`).
- Plan du site des pages : chaque adresse une seule fois (`/tarifs/` était listé deux fois sur le site en ligne).
- Un seul prix demandé (tous les vendeurs au même prix) : « Prix demandé par les vendeurs : 170 DA/kg » au lieu de « de 170 à 170 DA/kg ».

## 0.3.0 — 2026-11-25 (avec sm-products 0.24.0, thème 0.60.0)

- Pages « type × wilaya » : titre (« Profilés à M'Sila – Acier neuf »), description avec les vrais chiffres, adresse canonique, liste des annonces et fil d'Ariane en données structurées ; hors index sous 3 annonces.
- Plan du site `smlanding` : seulement les pages d'au moins 3 annonces, dans les 3 langues.

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

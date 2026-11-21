# SM SEO

Référencement de ReuSteel dans les moteurs de recherche (Google, Bing) et la recherche par IA (ChatGPT Search, Perplexity, Claude…). Toute la logique est ici ; le thème garde seulement les liens hreflang.

## Ce que l'extension écrit dans le `<head>`

- **Titre** et **description** construits à partir des vraies données de la page, dans la langue de la page. Annonce : « IPE 200 S275JR – 40 barres de 12 m – M'Sila », description avec le marché, le type, le poids, le prix demandé et la wilaya.
- **Adresse canonique** dans la langue de la page ; les arguments de suivi (`utm_*`, `fbclid`, `gclid`) sont retirés.
- **Robots** : `noindex, follow` pour le compte, la connexion, l'inscription, le formulaire « Demander une étude », les recherches avec texte / filtres / tri, les annonces vendues, en pause ou expirées, une demande de devis, une entreprise sans annonce, service ni présentation. Restent indexés avec leur argument : `?category=`, `?family=`, `?activity=`, `?pg=`.
- **Aperçus de partage** Open Graph et X (photo de l'annonce, logo de l'entreprise, sinon l'image par défaut).
- **Données structurées** JSON-LD : Product + Offer, Organization, WebSite + SearchAction (accueil), BreadcrumbList, ItemList de Service (ingénierie), Service (services des entreprises).
- Codes de validation Google Search Console (`google-site-verification`) et Bing (`msvalidate.01`).

## Règles

- Le prix est toujours le **prix demandé par le vendeur** (au kilo ou au lot) ; jamais un prix du marché.
- Jamais d'avis, de note, de source de données, de téléphone, d'e-mail ni d'adresse exacte (la wilaya seulement).
- Le vendeur n'est nommé que si sa page est publique (entreprise ou professionnel).
- Aucune page d'ingénieur partenaire : les services d'ingénierie sont présentés comme services de la plateforme.

## Plans du site (`/wp-sitemap.xml`)

`smpages` (accueil, pages de la marketplace sans les pages privées, types de produit avec annonces, autres pages), `smlistings-products|reuse|buildings`, `smcompanies`, `smservices`. Chaque adresse en français, arabe et anglais. Le plan des pages de WordPress, les utilisateurs et les taxonomies sont retirés.

## robots.txt et llms.txt

- Réglage « Robots des IA » : `search` (par défaut, choix A : recherche IA permise, robots d'entraînement refusés), `all`, `none`. Les pages du compte sont fermées à tous ; le plan du site est indiqué à la fin.
- Un vrai fichier `robots.txt` à la racine du site remplace celui de l'extension (avertissement dans SM → SEO).
- `/llms.txt` : description courte du site pour les assistants IA (marchés, services, outils, langues).

## Filtres

- `sm_seo_context( $meta )` — tout ce qui est écrit pour la page courante.
- `sm_seo_default_image( $url )`, `sm_seo_same_as( [] )` (liens des réseaux sociaux de l'Organisation), `sm_seo_llms_txt( $text )`.

## Dépendances

SM Core. Lit les données par les filtres `sm_theme_data_*` des autres extensions (annonces, entreprises, services, ingénierie, wilayas, catégories).

## Tests

- `wp eval-file wp-content/plugins/sm-seo/tests/run.php`
- `python3 tests/e2e-seo.py http://localhost:8080 /chemin/vers/wp`

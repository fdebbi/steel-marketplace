# SM Guides — guides pratiques

Extension de **ReuSteel** (référencement, phase 3). Nécessite **SM Core 0.5.0** et le thème **sm-marketplace 0.61.0** (page « Guides »).

## Ce qu'elle fait

- **Guides en français, arabe et anglais**, écrits dans *SM → Guides* : titre (le titre français est celui de l'article), thème (étiquette de la carte), texte court de la carte, résumé (sous le titre et description pour les moteurs de recherche), texte du guide, pages du site liées, ordre (attribut « Ordre »).
- **Texte en Markdown simple** : `## Section` (va dans le sommaire), `### Sous-titre`, `**gras**`, `*italique*`, listes `-` et `1.`, liste de vérification `- [ ]`, tableaux `| a | b |`, citations `>`, liens `[texte](https://…)`. Le texte est échappé : aucun HTML écrit dans un guide n'arrive sur la page.
- **Pages** `/guides/` (liste) et `/guides/{nom}/` (et `/ar/…`, `/en/…`). Un guide n'est affiché dans une langue que si son titre et son texte y sont écrits ; sinon le texte français y est montré, avec un avis, et la page est hors index (SM SEO).
- **Pages du site liées** (réemploi, produits, bâtiments, calculateur, catalogue, optimiseur, ingénierie, services, demandes de devis) : affichées à côté du guide, la première est le bouton principal ; le guide est proposé sous le titre de ces pages (« Guide : … »).
- **Installation** : les 4 guides validés le 2026-11-26 (`data/guides.php`, `data/guides/{nom}-{langue}.md`) sont écrits une seule fois ; un guide existant, modifié ou mis à la corbeille n'est jamais réécrit.
- Méthodes et fonctionnement du site seulement : aucun prix du marché, aucune statistique.

## Données pour le thème

`sm_theme_data( 'guides', { slug } )` : liste (`kind = index`, `items`) ou guide (`kind = guide` : `title`, `summary`, `topic`, `html`, `toc`, `tools`, `languages`, `fallback`, `updated`, `others`) ; `null` pour un nom inconnu. `{ for: clé }` : guides liés à une page du site. `sm_page_url( 'guides', { slug } )`.

## Tests

```
wp eval-file wp-content/plugins/sm-guides/tests/run.php     # 24 tests (site de TEST)
python3 tests/e2e-guides.py URL WP_PATH OUT                 # 32 vérifications navigateur
```

## Traductions

`languages/` : fr_FR et ar (administration). Régénérer : `python3 tools/compile-translations.py`.

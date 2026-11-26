# Changelog — sm-guides

## 0.2.0 — 2026-11-26 (avec sm-seo 0.5.0, thème 0.62.0)

- **Questions fréquentes** (maquette et textes validés le 2026-11-26) : type privé « Question » dans *SM → Questions fréquentes* : question et réponse en français, arabe et anglais (Markdown simple), thème (Compte, Publier une annonce, Acheter et demander un devis, Ingénierie, Outils, Langues et confiance), ordre, pages du site où la question est aussi affichée (réemploi, bâtiments, ingénierie, demandes de devis ; 4 au plus par page).
- Les 23 questions validées sont écrites une seule fois à l'installation (`data/faq.php`, `data/faq/{fr,ar,en}.md`), sans la note de relecture ni les marques [réglage] / [page]. Une question existante, modifiée ou mise à la corbeille n'est jamais réécrite.
- Dans une réponse, `[texte](guide:nom)` renvoie vers un guide et `[texte](page:clé)` vers une page du site, dans la langue de la page ; un guide ou une page inconnus : le texte seul.
- Arabe : les mots en lettres latines (DA/kg, IPE 200, PDF, ReuSteel, adresse e-mail) sont isolés (`<bdi>`) pour que la ponctuation reste à sa place (« لماذا الأسعار بـ DA/kg؟ »).
- `sm_theme_data( 'faq' )` (la page) et `sm_theme_data( 'faq', { for: clé } )` (le bloc d'une page).
- Tests : `tests/run.php` (48), `tests/e2e-faq.py` (44).

## 0.1.0 — 2026-11-26 (avec sm-seo 0.4.0, thème 0.61.0)

- Guides pratiques en français, arabe et anglais, écrits dans l'administration (*SM → Guides*) : titre, thème, texte court de la carte, résumé, texte en Markdown simple (sections ##, gras, listes, liste de vérification, tableaux, liens http(s)), pages du site liées (la première est le bouton principal), ordre.
- Pages `/guides/` et `/guides/{nom}/` (et `/ar/`, `/en/`) par la page « guides » du thème ; sommaire tiré des sections ; un guide non écrit dans une langue y est affiché en français et marqué comme tel.
- Les 4 guides validés le 2026-11-26 sont écrits à l'installation, dans les 3 langues, dans l'ordre 3, 5, 6, 4 : acheter de l'acier de réemploi, bâtiment métallique d'occasion, réduire les chutes (barres de 6 m et 12 m), vendre ses chutes et surplus. Un guide existant n'est jamais réécrit.
- Le texte est échappé : aucun HTML écrit dans un guide n'arrive sur la page. Expressions régulières en UTF-8 (« م » contient l'octet 0x85, que PCRE sans /u prend pour un saut de ligne).
- Tests : `tests/run.php` (24), `tests/e2e-guides.py` (32).

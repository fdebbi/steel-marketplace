# Système de design — SM Marketplace

## Couleurs (modifiables dans Apparence → Personnaliser → Marque SM)

| Jeton | Défaut | Rôle |
| --- | --- | --- |
| `--sm-primary` | #172B3A | En-tête, titres, surfaces sombres |
| `--sm-accent` | #16A36A | Actions principales, prix au kg, badges vérifiés |
| `--sm-secondary` | #64748B | Textes secondaires, icônes, bordures |
| `--sm-bg` | #F1F5F9 | Fond de page |

Toutes les autres teintes (survol, fonds doux, bordures, encre d'accent) sont **dérivées** de ces quatre jetons avec `color-mix()` dans `assets/css/01-tokens.css`. Changer une couleur dans l'administration re-thème tout le site, y compris l'arabe. Les couleurs sont injectées après la feuille principale (`wp_add_inline_style`), elles l'emportent toujours.

Échelle d'état du réemploi (5 niveaux) : `--sm-cond-1` (surplus neuf) → `--sm-cond-5` (ferraille), affichée en barres + libellé (jamais la couleur seule).

## Typographie

- Français : **Inter** 400/500/600/700. Arabe : **IBM Plex Sans Arabic** 400/500/600/700. Auto-hébergées, `font-display: swap`, préchargement de la langue courante.
- Arabe : taille de base 17 px et interligne 1,7 (au lieu de 16 px / 1,55) ; pas de majuscules ni d'espacement de lettres sur les libellés.
- Chiffres occidentaux (0-9) dans les deux langues ; `tabular-nums` sur les prix, poids et tableaux.

## RTL / LTR

- Le CSS source utilise uniquement des **propriétés logiques** (`margin-inline-start`, `inset-inline-end`, `text-align: start`…). RTLCSS génère `dist/main-rtl.css` pour les rares valeurs physiques (position de flèche des `select`, animations).
- Ne jamais écrire de règle `[dir="rtl"]` avec une propriété physique : RTLCSS l'inverserait une deuxième fois.
- Icônes directionnelles (chevrons, flèches) : classe `.sm-icon--dir`, inversées en RTL. Les autres (recherche, utilisateur) ne le sont pas.
- Valeurs techniques et unités : `sm_tech()` (`<bdi dir="ltr">`), prix unitaires : `sm_per_unit()`.

## Points de rupture

| Plage | Navigation | Filtres | Tableaux | Grille |
| --- | --- | --- | --- | --- |
| < 768 px | Barre + tiroir + barre d'onglets en bas | Plein écran | Lignes en cartes étiquetées | 1 colonne |
| 768–1023 px | Barre condensée + tiroir | Panneau latéral 400 px | Défilement dans un cadre | 2 colonnes |
| ≥ 1024 px | En-tête complet + méga-menu | Colonne fixe 280 px | Tableau complet | 3–4 colonnes |

Cibles tactiles ≥ 44 px, champs à 16 px (pas de zoom iOS), claviers numériques (`inputmode`) pour dimensions et prix, envoi de photos avec appareil photo (`capture`).

## Composants

`template-parts/components/` : `card-listing` (produit, réemploi, bâtiment), `card-company`, `card-service`, `price-block`, `condition-badge`, `filter-panel`, `empty-state`. Rendu avec `sm_component( 'nom', $args )` ; une extension peut remplacer un composant par le filtre `sm_component_path`.

Catalogue visuel : page **/styleguide/** (administrateurs), à consulter en arabe et en français.

# SM Buildings — marché des bâtiments métalliques

Extension de **ReuSteel** (Phase 6). Nécessite **SM Core 0.3.0**, **SM Weight Engine 0.1.0** et **SM Products 0.12.0**.

## Ce qu'elle fait

- Ajoute le marché **Bâtiments métalliques** au moteur d'annonces de SM Products : mêmes photos, documents techniques, modération, recherche, fiche et « Mes annonces ».
- **Six catégories**, modifiables dans wp-admin (*SM → Produits → Catégories*) : hangars et bâtiments industriels, entrepôts et stockage, ateliers, hangars agricoles, bâtiments d'élevage, kits et structures préfabriqués.
- **Bâtiment monté** : longueur, largeur, hauteur à l'égout, nombre de portiques, pente (facultative), surface calculée ; système porteur, forme de toiture, couverture, bardage ; état (neuf, occasion excellent ou bon état, à inspecter) ; ce que comprend le prix (démontage, transport, remontage, fondations et accessoires). Prix demandé pour le bâtiment entier ; **poids d'acier** estimé par le vendeur (affiché comme estimé) ou calculé à partir de l'inventaire ; **prix équivalent au kg et prix au m²** calculés par le serveur.
- **Bâtiment démonté** : **inventaire obligatoire** élément par élément (type d'élément, profil du catalogue européen, quantité, longueur) ; poids unitaire et total de chaque ligne calculés par SM Weight Engine ; prix au kg (par défaut) ou pour l'ensemble, prix au m² calculé.
- **Schéma indicatif** (façade et plan avec les portiques) tracé uniquement à partir des chiffres saisis, avec la mention « ce n'est pas une étude structurelle ». Les mêmes données de forme (`configurator`) serviront au futur configurateur 3D.
- **Avis d'ingénieur** sur les bâtiments d'occasion : vérification par un ingénieur qualifié avant remontage (éléments, assemblages, corrosion, nouveau calcul pour le site).
- Permission `listing.buildings` de SM Core : fabricants, fournisseurs, monteurs, maîtres d'ouvrage et revendeurs publient ; un compte ingénierie ne publie jamais.

## Réglages

*SM → Bâtiments* : longueur et largeur maximales, hauteur maximale, nombre maximal de portiques et de lignes d'inventaire.

## Points d'intégration

| Élément | Usage |
| --- | --- |
| `sm_buildings_market` (filtre) | Modifier le marché : libellés, états, champs, types d'éléments. |
| Type de catégorie `building` | `sm_products_kinds`, `sm_products_spec_kind_building`. |
| Annonce | Clés ajoutées : `length`, `width`, `height`, `portals`, `area`, `system`, `roof`, `roof_cover`, `cladding` ({fr, ar}), `includes` {dismantling, transport, reinstall, foundations}, `asking_price`, `mode`, `price_per_m2`. |
| Fiche | `spec_rows`, `drawing` (SVG), `drawing_note`, `configurator` {length, width, height, portals, roof, slope}, `advice`, `elements`. |
| Formulaire | `mode` (`assembled` / `dismantled`), `specs[length_m\|width_m\|height_m\|portals\|slope]`, `attrs[system\|roof\|roof_cover\|cladding\|inc_*]`, `lines[i][element\|family\|section\|qty\|length]`, `total_kg`, `price`, `price_unit` (`lot` / `kg`). |
| Options | `sm_buildings_db_version`, `sm_buildings_settings`. |

## Tests

```
wp eval-file wp-content/plugins/sm-buildings/tests/run.php   # 35 tests (site de TEST)
python3 tests/e2e-buildings.py URL WP_PATH OUT               # 24 vérifications navigateur
```

## Traductions

`languages/` : fr_FR et ar (40 chaînes). Régénérer : `python3 tools/compile-translations.py`.

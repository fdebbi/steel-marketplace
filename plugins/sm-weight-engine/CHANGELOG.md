# Changelog — sm-weight-engine

## 0.3.1 — 2026-11-18

- Schémas des profilés : les cotes donnent les vraies valeurs de la dimension choisie au lieu des lettres (« 100 mm », « 50 mm » au lieu de h, b), partout où le schéma apparaît (formulaire d'annonce, fiche, calculateur, catalogue de l'administration).
- Tubes (SHS, RHS, CHS) et cornières : l'épaisseur de paroi est cotée sur la paroi (« t = 4 mm »).
- Tubes ronds et ronds pleins : le diamètre est donné une seule fois, en bas (« Ø 48,3 mm »).
- Images partagées des sections régénérées (version 3). L'option `values => false` garde les lettres.

## 0.3.0 — 2026-11-15

- Données de référence `2026.11.3` : dimensions courantes ajoutées pour les tubes rectangulaires (420, gamme Ferpinta, masses publiées contrôlées à 0,01 kg/m près), les fers plats (58), les ronds pleins (24) et les carrés pleins (40). Une dimension saisie reste possible.
- Tôles par type (`data/plates.php`, classe `Plates`) : noire laminée à chaud, laminée à froid, galvanisée, larmée / striée, perforée, inox, alu à damier, autre ; épaisseurs courantes et 4 formats (1000 × 2000, 1250 × 2500, 1500 × 3000, 2000 × 6000). Larmée et alu à damier : poids au m² d'un tableau publié ; inox 7 900 kg/m³ ; aluminium 2 700 kg/m³ ; tôle perforée : trous en quinconce déduits (diamètre et pas facultatifs).
- `Calculator::calculate()` accepte `kind` et `holes` pour une tôle ; `Calculator::sheet()` accepte le type de tôle.
- Les sources des données (texte et lien) ne sont plus envoyées aux visiteurs (thème et API) : seule la norme est publique ; les sources restent dans l'administration.

## 0.2.1 — 2026-11-12

- Dessins enregistrés (version 2 des fichiers) : valeurs réelles sur les lignes de cote (option `values` de `Drawing::svg()`).
- `Calculator::sheet()` et `Calculator::dims_from_designation()` : caractéristiques d'une section du catalogue ou d'une dimension saisie.

## 0.2.0 — 2026-11-12

- `Drawing::files()` : dessin d'une section enregistré une fois dans uploads/sm-drawings/ en SVG (acier clair) et en PNG 1200 × 630 (fond acier, bord vert) pour les aperçus de liens ; `Drawing::polygons()` (arcs aplatis) et `Drawing::png()` (GD).

## 0.1.0 — 2026-11-07 (Phase 3)

- Catalogue de référence `2026.11.1` : 224 profilés laminés (IPE 18, HEA 24, HEB 24, HEM 24, IPN 21, UPN 18, UPE 14, cornières égales 66, inégales 15) vérifiés trois fois avant import ; 417 tailles courantes de tubes (carrés 224, ronds 193).
- Géométrie exacte : contour avec congés (r, r1), arrondis d'aile (r2), pentes d'aile IPN (14 %) et UPN (8 % / 5 %), rayons EN 10219-2 des tubes ; aire calculée exactement (arcs intégrés), identique aux aires vérifiées.
- Schémas SVG à l'échelle, accessibles, avec cotes h / b.
- Calculs : barres → poids, poids → barres (exact, barres entières inférieure et supérieure, reste), longueurs commerciales réglables (12 m, 6 m), longueur libre, tôles (kg/m², poids par tôle). Chaque résultat porte `weight_source = theoretical`, la version du moteur et des données.
- Tubes, fers plats, ronds, carrés et tôles : toute dimension dans les limites réglables ; les profilés laminés seulement depuis le catalogue.
- Administration : familles (masquer), dimensions (corriger avec motif obligatoire, ajouter, désactiver), contrôle de cohérence masse publiée / cotes (2 %), réglages, journal, sources. Une mise à jour des données de référence n'écrase jamais une correction : l'écart est journalisé.
- API REST `sm/v1/weight/*`, service `weight`, passerelle thème (`weight_families`, `weight_family`, `weight_section`, `weight_calc`, `weight_drawing`, `weight_settings`).
- Traductions fr_FR et ar (87 chaînes). 46 tests PHP.

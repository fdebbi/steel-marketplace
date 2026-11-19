# Changelog — sm-weight-engine

## 0.6.0 — 2026-11-20 (avec sm-stock 0.2.0 et le thème 0.49.0)

- Optimiseur : **Mon magasin** cherché en premier. Ordre : chutes saisies et magasin de l'entreprise (filtre `sm_cutting_stock_offers( $offers, $section, $filters )`, fourni par sm-stock), puis le marché pour les pièces restantes, puis les barres neuves. Chaque étape ne reçoit que ce que la précédente n'a pas placé (`Cutting::stages()`).
- `Cutting::read()` : `mine` (coché par défaut), `mine_new`, `mine_reuse`, `mine_unpublished` (seulement ce qui est en stock seulement).
- Résultat : `mine` par profilé (repère, emplacement, barres prises, pièces), totaux `mine_bars`, `mine_kg`, `mine_items` ; chutes réutilisables avec leur provenance (`offcuts` : profilé, longueur, nombre, repère, état, nuance) pour les ajouter au magasin.
- Placement : une barre déjà entamée est remplie avant d'en prendre une nouvelle du stock (moins de barres sorties).
- PDF : « De votre magasin : n barres », provenance « magasin 12,00 m · N-0007 » ; titre de profilé recomposé (magasin + marché + neuves + chutes).

## 0.5.0 — 2026-11-19 (avec sm-products 0.19.0 et le thème 0.47.0)

- Optimiseur : recherche sur le **marché ReuSteel** avant d'acheter. Pour chaque profilé, les barres publiées (filtre `sm_cutting_market_offers`, fourni par sm-products) entrent dans le calcul : même profilé, longueur égale ou la plus proche au-dessus, puis l'annonce la moins chère au kg ; ce que le marché ne couvre pas devient des barres neuves à acheter.
- Filtres : nuance minimale, états acceptés (par défaut neuf, surplus neuf, excellent, bon ; jamais la ferraille), wilaya, lots vendus en entier (seulement si demandé ; le reste du lot est signalé). Les annonces de son propre compte ne sont pas proposées.
- Résultat par annonce : barres prises, pièces coupées, poids, coût estimé (prix affiché × poids, ou prix du lot). PDF : provenance de chaque barre et rappel « photo du marché, rien n'est réservé ».
- `Cutting::read()` : `src`, `market`, `lots`, `conds[]`, `grade_min`, `wilaya`. `Cutting::solve()` accepte des barres disponibles avec leur provenance.

## 0.4.1 — 2026-11-19

- PDF du plan de coupe : pied de page sur chaque page — « Plan de coupe établi avec l'Optimiseur de découpe ReuSteel - reusteel.com/optimiseur-decoupe » (lien cliquable) et « Calcul indicatif à vérifier avant découpe · Poids théoriques du catalogue · Page x/y ». Le contenu s'arrête au-dessus du pied de page.

## 0.4.0 — 2026-11-19

- **Optimiseur de découpe** (`Cutting`) : pièces par profilé, barres du commerce (6 m, 12 m ou autre), trait de scie, longueur à partir de laquelle une chute est réutilisable, **chutes déjà en stock** utilisées en premier. Calcul exact, sans IA : « best fit decreasing » puis recherche bornée qui prouve le plus petit nombre de barres neuves (ou s'arrête à 1 000 000 d'essais sur une très longue liste et le signale). 40 lignes, 300 pièces par profilé au plus. Poids depuis le catalogue (kg/m).
- **Plan de coupe en PDF** (`Pdf`, sans bibliothèque ; A4 paysage ; en français pour une page en arabe) : `admin-post.php?action=sm_weight_cut_pdf`.
- **Demande de devis pré-remplie** avec les barres neuves à acheter et les pièces dans la description : `admin-post.php?action=sm_weight_cut_rfq` (compte pouvant créer des demandes).
- Données du thème : `cutting_sections`, `cutting_input`, `cutting`.

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

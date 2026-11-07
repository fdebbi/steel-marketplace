# Changelog — sm-weight-engine

## 0.1.0 — 2026-11-07 (Phase 3)

- Catalogue de référence `2026.11.1` : 224 profilés laminés (IPE 18, HEA 24, HEB 24, HEM 24, IPN 21, UPN 18, UPE 14, cornières égales 66, inégales 15) vérifiés trois fois avant import ; 417 tailles courantes de tubes (carrés 224, ronds 193).
- Géométrie exacte : contour avec congés (r, r1), arrondis d'aile (r2), pentes d'aile IPN (14 %) et UPN (8 % / 5 %), rayons EN 10219-2 des tubes ; aire calculée exactement (arcs intégrés), identique aux aires vérifiées.
- Schémas SVG à l'échelle, accessibles, avec cotes h / b.
- Calculs : barres → poids, poids → barres (exact, barres entières inférieure et supérieure, reste), longueurs commerciales réglables (12 m, 6 m), longueur libre, tôles (kg/m², poids par tôle). Chaque résultat porte `weight_source = theoretical`, la version du moteur et des données.
- Tubes, fers plats, ronds, carrés et tôles : toute dimension dans les limites réglables ; les profilés laminés seulement depuis le catalogue.
- Administration : familles (masquer), dimensions (corriger avec motif obligatoire, ajouter, désactiver), contrôle de cohérence masse publiée / cotes (2 %), réglages, journal, sources. Une mise à jour des données de référence n'écrase jamais une correction : l'écart est journalisé.
- API REST `sm/v1/weight/*`, service `weight`, passerelle thème (`weight_families`, `weight_family`, `weight_section`, `weight_calc`, `weight_drawing`, `weight_settings`).
- Traductions fr_FR et ar (87 chaînes). 46 tests PHP.

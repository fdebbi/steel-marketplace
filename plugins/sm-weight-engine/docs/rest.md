# API REST — sm/v1/weight (publique, lecture seule)

Ajouter `?lang=ar` pour les messages en arabe. Réponses mises en cache 1 h (`Cache-Control: public`).

| Méthode | Route | Réponse |
| --- | --- | --- |
| GET | `/weight/families` | `[{code, shape, group, name:{fr,ar}, standard, method (table/formula), count, source, source_url, dims_keys, icon (SVG)}]` |
| GET | `/weight/families/{code}` | famille + `sections: [{id, family, designation, dims, area_mm2, mass_kg_m, mass_published, origin}]` |
| GET | `/weight/sections/{id}` | dimension + `drawing` (SVG avec cotes) + `source {standard, text, url}` |
| GET | `/weight/drawing?family=RHS&dims[h]=200&dims[b]=100&dims[t]=6` | `{designation, dims, drawing}` (familles à formule) |
| GET | `/weight/calculate?…` | résultat du calcul, ou 400 avec message |
| GET | `/weight/settings` | `{bar_lengths, default_length, max_length, density}` |

## /weight/calculate

| Paramètre | Valeur |
| --- | --- |
| `family` | IPE, HEA, HEB, HEM, IPN, UPN, UPE, L, LI, SHS, RHS, CHS, FLAT, ROUND, SQUARE, PLATE |
| `section` | ID ou désignation (`IPE 200`) — obligatoire pour les profilés laminés |
| `dims[...]` | familles à formule : SHS `b,t` ; RHS `h,b,t` ; CHS `d,t` ; FLAT `b,t` ; ROUND `d` ; SQUARE `a` ; PLATE `t` (mm, virgule acceptée) |
| `mode` | `bars` (défaut) ou `weight` |
| `length` | longueur d'une barre ou d'une tôle en mm (défaut : réglage, 12 000) |
| `width` | largeur de tôle en mm |
| `bars` / `total_kg` | quantité selon le mode |

Exemple : `/wp-json/sm/v1/weight/calculate?family=IPE&section=IPE%20200&mode=weight&total_kg=5000` →
`mass_kg_m 22.36, per_piece_kg 268.32, bars_exact 18.63, bars_full 18 (4829.764 kg), bars_next 19 (5098.08 kg), remainder_kg 170.236, weight_source "theoretical", engine "0.1.0", reference "2026.11.1"`.

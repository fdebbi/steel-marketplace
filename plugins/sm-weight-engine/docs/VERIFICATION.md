# Vérification des données de référence (version 2026.11.1)

Règle du projet : **aucune donnée acier inventée**. Les tables ont été reprises de documents publiés, puis chaque ligne a été contrôlée par programme (`tools/reference/verify.py`, rapport complet `tools/reference/verification-report.csv`).

## Méthode

Pour chaque dimension d'une table :

1. **Cotes** (h, b, tw, tf, r…) comparées à une compilation indépendante : fib *structuralcodes* 0.7.3 (Apache-2.0) et, pour IPE et HE, *eurocodepy* (LGPL). Écart toléré : 0,05 mm.
2. **Aire** recalculée à partir des cotes, géométrie exacte (congés, pentes d'ailes) et comparée à l'aire publiée (0,6 %).
3. **Masse** recalculée (aire × 7850 kg/m³, valeur des normes) et comparée à la masse publiée, en tenant compte de l'arrondi d'impression (0,1 kg/m).

Une ligne est acceptée seulement si les trois contrôles passent. Le moteur PHP recalcule ensuite chaque aire : l'écart maximal avec l'aire vérifiée est de 0,03 % (arrondi au dixième de mm²).

## Sources

| Famille | Norme | Source des cotes | Résultat |
| --- | --- | --- | --- |
| IPE | EN 10365:2017 | [STAD, Technical data of sections IPE](https://www.stad.fr/downloads/pdf/stad-technical-data-of-sections-ipe.pdf) | 18/18 |
| HEA, HEB, HEM | EN 10365:2017 | [STAD, Technical data of sections HE](https://www.stad.fr/downloads/pdf/stad-technical-data-of-sections-he.pdf) | 72/72 |
| IPN | EN 10365:2017 | [ArcelorMittal, Poutrelles en I à ailes inclinées](https://sections.arcelormittal.com/repository2/Sections/Sections_MB_ArcelorMittal_FR_EN_DE_IPN.pdf) | 21/21 |
| UPN | EN 10365:2017 | [STAD UPN](https://www.stad.fr/downloads/pdf/stad-technical-data-of-sections-upn.pdf) (80–400) et table UPN ArcelorMittal (générateur Orange Book : r2, UPN 50 et 65) | 18/18 |
| UPE | EN 10365:2017 | [STAD UPE](https://www.stad.fr/downloads/pdf/stad-technical-data-of-sections-upe.pdf) | 14/14 |
| Cornières égales / inégales | EN 10056-1 | [STAD L](https://www.stad.fr/downloads/pdf/stad-technical-data-of-sections-l.pdf) ; rayons r1/r2 de la liste EN 10056-1 de structuralcodes | 66 + 15 |
| Tubes carrés et ronds | EN 10219-2 | Formule de la norme, contrôlée sur le catalogue [Ferpinta EN 10219](https://www.ferpinta.pt/en/products-en/tubes-en/structural-hollow-sections-en/) (22 carrés, 12 ronds, écart nul) ; tailles proposées = gamme Ferpinta | formule |
| Tubes rectangulaires (0.3.0) | EN 10219-2 | Formule de la norme ; tailles et masses du [manuel Ferpinta, section 8.1.3](https://ct.upt.ro/suscos/files/2016-2018/L2_Annex_Manual_Ferpinta_vEN_20-02-14.pdf) (`raw/rhs_ferpinta.txt`) : les 420 masses publiées sont retrouvées à 0,01 kg/m près | 420/420 |
| Fers plats (0.3.0) | EN 10058 | b × t × 7850 kg/m³ ; tailles de la gamme [Melinda Steel](https://melindasteel.com/profiles-and-laminated-products/42-flat-steel-bar-3.html) (masses publiées retrouvées à l'arrondi près) | 58 |
| Ronds pleins (0.3.0) | EN 10060 | π·d²/4 × 7850 kg/m³ ; diamètres de la gamme [Melinda Steel](https://melindasteel.com/profiles-and-laminated-products/40-round-steel-bar-2.html) | 24 |
| Carrés pleins (0.3.0) | EN 10059 | a² × 7850 kg/m³ ; côtés de la gamme [Emile Maurin, série A](https://acier.emile-maurin.fr/custom/docs/produit/serie%20a_a/acier-construction-non-allie-tube-rond-carre-hexagone-serie-pdf-259-ko-serie_a-lser2.pdf) | 40 |
| Tôles (0.3.0, `data/plates.php`) | EN 10029 / EN 10051 | Épaisseur × 7850 kg/m³. Épaisseurs proposées : gamme de tôles [ArcelorMittal e-steel](https://e-steel.arcelormittal.com/FR/fr/Tole/Tole-Acier-/Tole-acier/p/000000000002097292) (0,5 à 25 mm). Larmée 3/5 à 10/12 : poids des feuilles 2000 × 1000 de [Codip](https://www.codip.fr/tole/63-tole-larmee.html) (29 à 81 kg/m² ; d'autres négociants publient jusqu'à ±10 %). Alu à damier 2/3,5 à 5,5/7 : [Codip](https://www.codip.fr/49-7520-tole-damier.html). Inox 7900 kg/m³ (EN 10088-1, 1.4301), aluminium 2700 kg/m³. Perforée : trous en quinconce à 60°, vide = π/(2√3)·(d/p)². Poids estimé : le vendeur peut donner le poids réel d'une feuille | formule / tableau |

Les sources ne sont jamais affichées aux visiteurs (0.3.0) : le site montre la norme seulement.

## Écarts relevés et décisions

- **Cornières L 20x20x3, L 25x25x3, L 25x25x4** : la fiche STAD indique un congé de 4 mm, la liste EN 10056-1 3,5 mm. La valeur EN est conservée ; les masses concordent dans les deux cas.
- **Cornières non retenues** (36 tailles de la fiche STAD absentes de la liste EN 10056-1 de référence, par ex. L 55x55x5, L 120x120x8, L 200x100x16) : écartées faute de seconde source. Elles peuvent être ajoutées dans l'administration avec leur source ; le contrôle masse / cotes s'applique.
- **Anomalies de la fiche STAD L** non reprises : « L 200 x 200 x 12 » avec b = 12, « L 200 x 100 x 16 » avec R = 1, « L 300 x 300 x 33 » avec t = 34.
- **UPN 65** : aire imprimée « 9 » (arrondie), aire calculée 9,06 cm² ; masse 7,1 kg/m concordante → acceptée.
- **IPE 400 à 600** : British Steel imprime r = 20 mm, l'EN 10365 (STAD, ArcelorMittal) 21 ou 24 mm ; la valeur EN est retenue.
- **Tubes** : EN 10219-2 (formés à froid, courants en Algérie). Les tubes finis à chaud (EN 10210, rayons plus petits) ne sont pas encore proposés.

## Reproduire

```bash
cd tools/reference
pip download --no-deps eurocodepy && unzip eurocodepy-*.whl -d ec      # optionnel
pip install shapely numpy
EUROCODEPY_I=ec/eurocodepy/data/i_profiles_euro.json python3 verify.py   # OK 258, REJECT 0
python3 ../build-catalogue.py                                            # -> data/sections.php
```

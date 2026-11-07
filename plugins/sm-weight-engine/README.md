# SM Weight Engine — moteur de poids et catalogue acier

Extension de **Steel Marketplace** (Phase 3). Elle fournit :

- le **catalogue de référence** des profilés : IPE, HEA, HEB, HEM, IPN, UPN, UPE, cornières à ailes égales et inégales (tables) ; tubes carrés, rectangulaires et ronds, fers plats, ronds et carrés pleins, tôles (formules des normes) ;
- le **schéma exact** de chaque section, dessiné à l'échelle à partir de ses cotes (congés et pentes d'ailes compris) ;
- les **calculs de poids théorique** : nombre de barres → poids total, poids total → nombre de barres (barres entières), tôles (kg/m², poids par tôle) ;
- une **API REST** publique (`sm/v1/weight/…`), un **service** pour les autres extensions et les données du **calculateur de poids** du thème ;
- un écran d'administration : consultation, correction tracée, ajout, désactivation, réglages, journal, sources.

Nécessite **SM Core 0.2.0** ou plus. Version 0.1.0, données de référence `2026.11.1`.

## Installation

1. Extensions → Ajouter → Téléverser `sm-weight-engine.zip`, puis Activer (SM Core doit être actif).
2. Les tables et le catalogue (641 dimensions) sont créés à l'activation.
3. Avec le thème sm-marketplace 0.4.0, la page **Calculateur de poids** (`/calcul-poids/`) est créée automatiquement à la première visite de l'administration.
4. Réglages : Steel Marketplace → Catalogue acier → Réglages (masse volumique, longueurs commerciales 12 m et 6 m, limites).

## Fiabilité des données

Aucune valeur n'a été saisie de mémoire. Chaque dimension d'une table a été **acceptée seulement si trois contrôles concordent** : cotes identiques à une compilation indépendante, aire recalculée = aire publiée, masse recalculée (aire × 7850 kg/m³) = masse publiée. Détail et sources : [`docs/VERIFICATION.md`](docs/VERIFICATION.md).

Les poids sont **théoriques** (dimensions nominales × masse volumique) et signalés comme tels ; le poids réel pesé peut différer dans les tolérances de fabrication.

## Pour les développeurs

- Service : `SM\Core\Modules::service( 'weight' )->calculate( array( 'family' => 'IPE', 'section' => 'IPE 200', 'bars' => 10 ) )`.
- Fonction : `sm_weight_calculate( $input )`.
- API : [`docs/rest.md`](docs/rest.md). Points d'extension : [`docs/hooks.md`](docs/hooks.md).
- Tests (site de test uniquement) : `wp eval-file wp-content/plugins/sm-weight-engine/tests/run.php` (46 tests).
- Reconstruire les données : `tools/reference/verify.py` puis `tools/build-catalogue.py` ; traductions : `python3 tools/compile-translations.py`.

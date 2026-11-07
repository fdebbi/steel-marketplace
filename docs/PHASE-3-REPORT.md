# Phase 3 — rapport de livraison (7 novembre 2026)

Livrables : extension `sm-weight-engine` 0.1.0, thème `sm-marketplace` 0.4.0 (page Calculateur de poids). `sm-core` 0.2.0 inchangé.

## Tests

| Suite | Résultat |
| --- | --- |
| sm-weight-engine, tests PHP (`tests/run.php`) | 46/46 |
| sm-core, tests PHP | 103/103 |
| Calculateur dans le navigateur (`tests/e2e-weight.py` : mode direct, sans JavaScript, arabe) | 22/22 |
| Parcours Phase 2 (`tests/e2e.py`) | 56/56 |
| Tests visuels AR/FR, 7 largeurs, 21 pages | 294/294 |
| Vérification des données de référence (`verify.py`) | 258 lignes acceptées, 0 rejet, 36 cornières écartées (pas de seconde source) |

## Limites connues

- Poids théoriques : les tolérances de laminage ne sont pas appliquées (le poids réel sera saisi par le vendeur en Phase 4 : « poids pesé »).
- Tubes finis à chaud (EN 10210) non proposés ; tubes rectangulaires sans liste de tailles (saisie libre).
- Liste des catégories, longueurs autres que 12 m / 6 m et catalogue fabricant de référence : à confirmer par le client.
- Sur le serveur de test local, les images de fond de carte (Phase 2) ne se chargent pas ; sans rapport avec cette phase.

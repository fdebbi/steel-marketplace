# ReuSteel (projet Steel Marketplace)

Plateforme industrielle B2B/B2C de l'acier en Algérie : produits sidérurgiques au kilo, réemploi (chutes, surplus, occasion), bâtiments métalliques, fabrication/montage et services d'ingénierie. Bilingue arabe (RTL) / français (LTR), responsive mobile → desktop.

Architecture : un thème WordPress sur mesure (présentation uniquement) + des extensions indépendantes `sm-*` (logique métier).

## Contenu du dépôt

| Dossier | Contenu | État |
| --- | --- | --- |
| `themes/sm-marketplace/` | Thème de présentation (FR / AR / EN) | 0.25.0 |
| `plugins/sm-core/` | Socle : comptes, permissions, vérification, langues, notifications, demandes, API | 0.8.1 |
| `plugins/sm-weight-engine/` | Catalogue acier vérifié, schémas exacts, calcul des poids | 0.2.1 |
| `plugins/sm-products/` | Produits neufs (et base des annonces réemploi / bâtiments), indice des prix | 0.11.0 |
| `plugins/sm-reuse/` | Réemploi de l'acier | 0.1.0 |
| `plugins/sm-buildings/` | Bâtiments métalliques | 0.1.0 |
| `plugins/sm-rfq/` | Demandes de devis | 0.1.3 |
| `plugins/sm-services/` | Services (fabrication, montage…), réalisations | 0.3.6 |
| `plugins/sm-ai/` | Assistant IA | 0.1.3 |
| `plugins/sm-engineering/` | Ingénieurs partenaires et missions | 0.1.1 |
| `plugins/sm-subscriptions/` | Formules, commandes, paiement hors ligne, factures, crédits IA, options | 0.1.1 — Phase 10 |
| `plugins/sm-trade/` | Commandes et offres entre acheteurs et vendeurs, historique | 0.1.0 |
| `tests/` | Tests de bout en bout (navigateur) | |
| `docs/` | Décisions, rapports de phase | |

## Phases

| Phase | Livrable | État |
| --- | --- | --- |
| 0 | Architecture technique | Validée le 5 nov. 2026 |
| 1 | Thème `sm-marketplace` | Validée le 7 nov. 2026 |
| 2 | Extension `sm-core` + thème (0.2.0), puis carte de l'entreprise (sm-core 0.2.0, thème 0.3.0) | Validée le 7 nov. 2026 |
| 3 | Moteur de poids `sm-weight-engine` 0.1.0 + calculateur (thème 0.4.0) | Validée le 7 nov. 2026 |
| 4 | Produits neufs `sm-products` | Livrée le 7 nov. 2026 |
| 5 | Réemploi `sm-reuse` | Livrée le 7 nov. 2026 |
| 6 | Bâtiments `sm-buildings` | Livrée le 7 nov. 2026 |
| 7 | Demandes de devis `sm-rfq` | Livrée le 8 nov. 2026 |
| 8 – 8.1 | Services `sm-services`, e-mails et activité | Livrée le 8 nov. 2026 |
| 9 – 9.3 | Assistant IA `sm-ai`, types de comptes et ingénieurs partenaires `sm-engineering`, annuaires, à la une, vente au lot, modifications vérifiées | Livrée le 11 nov. 2026 |
| 10 | Abonnements, paiement hors ligne, factures, crédits IA et options (`sm-subscriptions`) | Livrée le 12 nov. 2026 |
| 10.1 | Commandes et offres (`sm-trade`), publication directe, filtres | Livrée le 13 nov. 2026 |
| 11 | Statistiques (`sm-analytics`) et recette complète (QA) avant ouverture | À venir |
| 12 | Applications mobiles (API REST existante) | À venir |

## Installer

1. Thème : [`themes/sm-marketplace/README.md`](themes/sm-marketplace/README.md).
2. Extension sm-core : [`plugins/sm-core/README.md`](plugins/sm-core/README.md).

Zips installables :

```bash
cd themes && zip -r sm-marketplace.zip sm-marketplace -x "sm-marketplace/node_modules*" "sm-marketplace/visual-test-output*"
cd ../plugins && zip -r sm-core.zip sm-core -x "sm-core/tests/*" "sm-core/tools/*"
zip -r sm-products.zip sm-products -x "sm-products/tests/*" "sm-products/tools/*"
zip -r sm-weight-engine.zip sm-weight-engine -x "sm-weight-engine/tests/*" "sm-weight-engine/tools/*"
```

Tests : `wp eval-file wp-content/plugins/sm-core/tests/run.php` (103 tests) et `python3 tests/e2e.py URL CHEMIN_WP sortie` (56 vérifications), `wp eval-file wp-content/plugins/sm-weight-engine/tests/run.php` (46 tests) et `python3 tests/e2e-weight.py URL sortie` (22 vérifications), sur un site de test uniquement.

Les CSS compilés (`dist/`) et les traductions compilées (`languages/*.mo`) sont versionnés : le zip s'installe sans étape de build.

# ReuSteel (projet Steel Marketplace)

Plateforme industrielle B2B/B2C de l'acier en Algérie : produits sidérurgiques au kilo, réemploi (chutes, surplus, occasion), bâtiments métalliques, fabrication/montage et services d'ingénierie. Bilingue arabe (RTL) / français (LTR), responsive mobile → desktop.

Architecture : un thème WordPress sur mesure (présentation uniquement) + des extensions indépendantes `sm-*` (logique métier).

## Contenu du dépôt

| Dossier | Contenu | État |
| --- | --- | --- |
| `themes/sm-marketplace/` | Thème de présentation | 0.6.0 |
| `plugins/sm-products/` | Marché des produits neufs : publication par catégorie, modération, annonces | 0.1.0 — Phase 4 |
| `plugins/sm-weight-engine/` | Catalogue acier vérifié, schémas exacts, calcul des poids | 0.1.0 — Phase 3 |
| `plugins/sm-core/` | Socle : comptes, permissions, vérification, langues, notifications, demandes, API | 0.2.0 — Phase 2 |
| `plugins/` (à venir) | `sm-reuse`, `sm-buildings`, `sm-rfq`, `sm-fabrication`, `sm-engineer-services`, `sm-ai`, `sm-subscriptions`, `sm-analytics` | Phases 3 à 10 |
| `tests/` | Tests de bout en bout (navigateur) | |
| `docs/` | Décisions, rapports de phase | |

## Phases

| Phase | Livrable | État |
| --- | --- | --- |
| 0 | Architecture technique | Validée le 5 nov. 2026 |
| 1 | Thème `sm-marketplace` | Validée le 7 nov. 2026 |
| 2 | Extension `sm-core` + thème (0.2.0), puis carte de l'entreprise (sm-core 0.2.0, thème 0.3.0) | Validée le 7 nov. 2026 |
| 3 | Moteur de poids `sm-weight-engine` 0.1.0 + calculateur (thème 0.4.0) | Validée le 7 nov. 2026 |
| 4 | Produits neufs `sm-products` 0.1.0 (sm-core 0.3.0, thème 0.5.0) | Livrée le 7 nov. 2026 — en attente de validation |
| 5–12 | Marchés, devis, services, IA, abonnements, QA, applications mobiles | À venir |

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

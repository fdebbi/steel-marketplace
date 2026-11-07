# Steel Marketplace

Plateforme industrielle B2B/B2C de l'acier en Algérie : produits sidérurgiques au kilo, réemploi (chutes, surplus, occasion), bâtiments métalliques, fabrication/montage et services d'ingénierie. Bilingue arabe (RTL) / français (LTR), responsive mobile → desktop.

Architecture : un thème WordPress sur mesure (présentation uniquement) + des extensions indépendantes `sm-*` (logique métier).

## Contenu du dépôt

| Dossier | Contenu | État |
| --- | --- | --- |
| `themes/sm-marketplace/` | Thème de présentation | 0.2.0 |
| `plugins/sm-core/` | Socle : comptes, permissions, vérification, langues, notifications, demandes, API | 0.1.0 — Phase 2 |
| `plugins/` (à venir) | `sm-weight-engine`, `sm-products`, `sm-reuse`, `sm-buildings`, `sm-rfq`, `sm-fabrication`, `sm-engineer-services`, `sm-ai`, `sm-subscriptions`, `sm-analytics` | Phases 3 à 10 |
| `tests/` | Tests de bout en bout (navigateur) | |
| `docs/` | Décisions, rapports de phase | |

## Phases

| Phase | Livrable | État |
| --- | --- | --- |
| 0 | Architecture technique | Validée le 5 nov. 2026 |
| 1 | Thème `sm-marketplace` | Validée le 7 nov. 2026 |
| 2 | Extension `sm-core` + thème 0.2.0 | Livrée le 7 nov. 2026 — en attente de validation |
| 3 | Moteur de poids `sm-weight-engine` | À venir |
| 4–12 | Marchés, devis, services, IA, abonnements, QA, applications mobiles | À venir |

## Installer

1. Thème : [`themes/sm-marketplace/README.md`](themes/sm-marketplace/README.md).
2. Extension sm-core : [`plugins/sm-core/README.md`](plugins/sm-core/README.md).

Zips installables :

```bash
cd themes && zip -r sm-marketplace.zip sm-marketplace -x "sm-marketplace/node_modules*"
cd ../plugins && zip -r sm-core.zip sm-core -x "sm-core/tests/*" "sm-core/tools/*"
```

Tests : `wp eval-file wp-content/plugins/sm-core/tests/run.php` (87 tests) et `python3 tests/e2e.py URL CHEMIN_WP sortie` (43 vérifications), sur un site de test uniquement.

Les CSS compilés (`dist/`) et les traductions compilées (`languages/*.mo`) sont versionnés : le zip s'installe sans étape de build.

# Steel Marketplace

Plateforme industrielle B2B/B2C de l'acier en Algérie : produits sidérurgiques au kilo, réemploi (chutes, surplus, occasion), bâtiments métalliques, fabrication/montage et services d'ingénierie. Bilingue arabe (RTL) / français (LTR), responsive mobile → desktop.

Architecture : un thème WordPress sur mesure (présentation uniquement) + des extensions indépendantes `sm-*` (logique métier).

## Contenu du dépôt

| Dossier | Contenu | État |
| --- | --- | --- |
| `themes/sm-marketplace/` | Thème de présentation | 0.1.1 — Phase 1 livrée |
| `plugins/` | `sm-core`, `sm-weight-engine`, `sm-products`, `sm-reuse`, `sm-buildings`, `sm-rfq`, `sm-fabrication`, `sm-engineer-services`, `sm-ai`, `sm-subscriptions`, `sm-analytics` | à venir (Phases 2 à 10) |
| `docs/` | Architecture, décisions, feuille de route | |

## Phases

| Phase | Livrable | État |
| --- | --- | --- |
| 0 | Architecture technique | Validée le 5 nov. 2026 |
| 1 | Thème `sm-marketplace` | Livré (0.1.0 le 5 nov., 0.1.1 le 7 nov.) — en attente de validation |
| 2 | Extension `sm-core` | À venir |
| 3–12 | Moteur de poids, marchés, devis, services, IA, abonnements, QA, applications mobiles | À venir |

## Installer le thème

Voir [`themes/sm-marketplace/README.md`](themes/sm-marketplace/README.md). Pour obtenir un zip installable :

```bash
cd themes && zip -r sm-marketplace.zip sm-marketplace -x "sm-marketplace/node_modules*"
```

Les CSS compilés (`dist/`) et les traductions compilées (`languages/*.mo`) sont versionnés : le zip s'installe sans étape de build.

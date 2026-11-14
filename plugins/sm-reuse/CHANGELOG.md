# Changelog — sm-reuse

## 0.2.0 — 2026-11-14

- « Faites contrôler cet acier avant d'acheter » : demande d'inspection (Expertise et Inspection Technique) pré-remplie avec le lot, envoyée à la plateforme qui désigne l'ingénieur. Pas pour la ferraille. Le type de projet est laissé à l'acheteur.
- Tests : 40.

## 0.1.0 — 2026-11-07 (Phase 5)

- Marché « Réemploi » ajouté au moteur d'annonces SM Products 0.2.0 : échelle d'état en cinq classes, origine, état de surface, découpe à la demande, chargement.
- Six catégories : trois inventaires ligne par ligne (chutes de profilés et barres, profilés de démontage, tubes) calculés par SM Weight Engine, trois lots au poids (chutes de tôles, lots mixtes, ferraille).
- Règles serveur : ferraille ↔ état « ferraille », découpe réservée aux barres et profilés, état et origine obligatoires.
- Avis d'ingénieur sur les fiches (renforcé pour « à inspecter », note de recyclage pour la ferraille).
- Traductions fr_FR et ar. 38 tests PHP, 26 vérifications navigateur.

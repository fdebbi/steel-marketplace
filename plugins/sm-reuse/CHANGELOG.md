# Changelog — sm-reuse

## 0.3.0 — 2026-11-15

- Les catégories du réemploi deviennent celles du neuf : profilés, tubes, tôles, fers plats et barres, boulonnerie, plus la ferraille. Les anciennes (chutes de profilés, profilés de démontage, tubes de réemploi, chutes de tôles, lots mixtes) ne sont plus proposées ; leurs annonces restent en ligne jusqu'à leur expiration. Chutes et démontage sont dans « Origine ».
- Une dimension par annonce, en plusieurs longueurs (ou formats de tôle) avec leur nombre ; ou au poids total pour des morceaux en vrac.
- Ordre du formulaire : données géométriques, origine, état de l'acier, état de surface, prix, ce que le prix inclut (chargement, transport, découpe). 2 photos au minimum.
- Lots groupés (`Groups`) : dans « Mes annonces », cocher des annonces de réemploi en ligne (même wilaya, pas la ferraille) puis « Regrouper en lot ». Le lot est vendu entier seulement ; ses produits ne sont plus vendus séparément ; vendre le lot vend ses produits ; « Défaire le lot » les remet en vente. Fiche du lot : photos des produits, inventaire avec les longueurs et l'état de chacun.
- Boulons précontraints (HR / HV) en réemploi : avertissement aux acheteurs (EN 1090-2).

## 0.2.0 — 2026-11-14

- « Faites contrôler cet acier avant d'acheter » : demande d'inspection (Expertise et Inspection Technique) pré-remplie avec le lot, envoyée à la plateforme qui désigne l'ingénieur. Pas pour la ferraille. Le type de projet est laissé à l'acheteur.
- Tests : 40.

## 0.1.0 — 2026-11-07 (Phase 5)

- Marché « Réemploi » ajouté au moteur d'annonces SM Products 0.2.0 : échelle d'état en cinq classes, origine, état de surface, découpe à la demande, chargement.
- Six catégories : trois inventaires ligne par ligne (chutes de profilés et barres, profilés de démontage, tubes) calculés par SM Weight Engine, trois lots au poids (chutes de tôles, lots mixtes, ferraille).
- Règles serveur : ferraille ↔ état « ferraille », découpe réservée aux barres et profilés, état et origine obligatoires.
- Avis d'ingénieur sur les fiches (renforcé pour « à inspecter », note de recyclage pour la ferraille).
- Traductions fr_FR et ar. 38 tests PHP, 26 vérifications navigateur.

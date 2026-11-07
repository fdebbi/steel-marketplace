# Changelog — sm-buildings

## 0.1.0 — 2026-11-07 (Phase 6)

- Marché « Bâtiments métalliques » ajouté au moteur d'annonces SM Products 0.3.0 : six catégories (industriels, entrepôts, ateliers, agricoles, élevage, kits), états neuf / excellent / bon / à inspecter.
- Bâtiment monté : dimensions et surface, portiques, pente, système porteur, toiture, couverture, bardage, ce que comprend le prix (démontage, transport, remontage, fondations et accessoires), prix demandé global ; poids d'acier estimé ou calculé depuis l'inventaire ; prix au kg et au m² calculés.
- Bâtiment démonté : inventaire obligatoire élément par élément (poteaux, traverses, pannes, lisses, contreventements, potelets, poutres de roulement, autres) pesé par SM Weight Engine ; prix au kg (par défaut) ou global.
- Règles : prix global pour un bâtiment monté ; pas de « démontage inclus » pour un bâtiment démonté ; limites réglables dans SM → Bâtiments.
- Schéma indicatif (plan et façade) et données de forme pour le futur configurateur 3D ; avis d'ingénieur avant remontage.
- Traductions fr_FR et ar (40 chaînes). 35 tests PHP, 24 vérifications navigateur.

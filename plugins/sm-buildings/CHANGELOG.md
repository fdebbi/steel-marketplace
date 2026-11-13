# Changelog — sm-buildings

## 0.2.0 — 2026-11-13

- Géométrie : hauteur au poteau et hauteur de toiture (faîtage) ; la pente n'est plus saisie, elle est calculée (deux versants : sur la demi-portée ; un versant : sur la portée) ; entraxe des portiques calculé ; nombre d'étages (1 par défaut) ; mezzanine (oui / non, surface facultative).
- Système porteur : « Portique poteau / traverse », « Profilé reconstitué soudé (PRS) », « Ferme en treillis », « Autre » (codes inchangés, les annonces existantes suivent). Élément « Fermes » ajouté.
- Éléments obligatoires : poteaux + traverses (portique) ou poteaux + fermes (treillis), monté comme démonté. PRS et « Autre » : pas de profilé de catalogue, donc éléments facultatifs et poids total obligatoire.
- Poids : poids des éléments déclarés calculé (source « partial ») ; poids total facultatif donné par le vendeur (source « estimated »), jamais inférieur aux éléments déclarés. Le prix au kg utilise le poids total s'il existe.
- Titre automatique : type, largeur × longueur, hauteur, système porteur, wilaya (français et arabe).
- Commune et position sur la carte obligatoires ; visite proposée sur tous les bâtiments.

## 0.1.0 — 2026-11-07 (Phase 6)

- Marché « Bâtiments métalliques » ajouté au moteur d'annonces SM Products 0.3.0 : six catégories (industriels, entrepôts, ateliers, agricoles, élevage, kits), états neuf / excellent / bon / à inspecter.
- Bâtiment monté : dimensions et surface, portiques, pente, système porteur, toiture, couverture, bardage, ce que comprend le prix (démontage, transport, remontage, fondations et accessoires), prix demandé global ; poids d'acier estimé ou calculé depuis l'inventaire ; prix au kg et au m² calculés.
- Bâtiment démonté : inventaire obligatoire élément par élément (poteaux, traverses, pannes, lisses, contreventements, potelets, poutres de roulement, autres) pesé par SM Weight Engine ; prix au kg (par défaut) ou global.
- Règles : prix global pour un bâtiment monté ; pas de « démontage inclus » pour un bâtiment démonté ; limites réglables dans SM → Bâtiments.
- Schéma indicatif (plan et façade) et données de forme pour le futur configurateur 3D ; avis d'ingénieur avant remontage.
- Traductions fr_FR et ar (40 chaînes). 35 tests PHP, 24 vérifications navigateur.

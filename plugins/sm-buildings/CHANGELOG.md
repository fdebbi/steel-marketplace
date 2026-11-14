# Changelog — sm-buildings

## 0.4.0 — 2026-11-14

- Mode de vente (`Sale`) : « Lot entier (bâtiment complet) », coché par défaut, et / ou « Par élément », pour un bâtiment monté comme démonté. Enregistré dans le mode de vente de SM Products (lot, both, detail).
- Un seul prix, saisi au kg ou pour le bâtiment entier : l'autre est calculé à partir du poids d'acier de l'annonce (poids donné par le vendeur, sinon poids des éléments déclarés). Prix au kg saisi : total arrondi au dinar inférieur. Total saisi : prix au kg arrondi au centime supérieur. Le total est le prix de vente ; le prix au kg est son équivalent et le prix des éléments vendus séparément. « Total indicatif » disparaît.
- Par élément : demande un inventaire ; l'acheteur choisit des pièces de l'inventaire. Après une vente par élément, la vente en lot entier se ferme et le poids des éléments déclarés suit.
- Annonces d'avant 0.4.0 (migration) : un bâtiment monté au kg, ou vendu au kg sans inventaire, devient une vente en lot entier à son total.
- Fiche : chiffres clés de la géométrie (longueur × largeur, surface, hauteur au poteau et faîtage, portiques et pente) ; les autres caractéristiques dans une liste à déplier.
- Inspection : le conseil « consultez un ingénieur » devient « Faites inspecter ce bâtiment avant d'acheter » (ingénieur « vérifié », pas « certifié »), avec la demande pré-remplie (`mission_prefill`) : service « Expertise et Inspection Technique », titre, dimensions, poids, lieu et lien de l'annonce. La demande arrive à la plateforme, qui désigne l'ingénieur (circuit existant de SM Engineering).
- Demande SM Products 0.14.0 (et SM Trade 0.2.0 pour l'achat par élément).
- Tests : 111.

## 0.3.0 — 2026-11-14

- Pont roulant : oui / non, capacité en tonnes facultative ; affiché dans les caractéristiques de l'annonce.
- Éléments proposés à partir des dimensions (`Proposal`), seulement pour le système « Portique poteau / traverse » : poteaux IPE (largeur × 20) ou HEA avec pont roulant (largeur × 18), 2 par portique, longueur = hauteur au poteau ; traverses IPE (largeur × 20), 2 par portique de largeur / 2 (deux versants) ou 1 par portique de la largeur (un versant), aucune pour les autres toitures ; pannes IPE 120, ⌈largeur / 1,2⌉ files × (portiques − 1), longueur = entraxe ; lisses UPN 120, 2 × ⌈hauteur / 1,2⌉ × (portiques − 1), longueur = entraxe, aucune sans bardage ; contreventements 32 × L 80x80x8 de 6 m. Dimension de catalogue la plus proche ; au-delà de la plus grande, la dimension est laissée au vendeur.
- Tous ces chiffres et profilés sont des réglages (SM → Bâtiments). Le vendeur peut modifier chaque ligne ; une ligne modifiée n'est jamais écrasée ; une annonce existante n'est jamais recalculée seule.
- Prix : un bâtiment monté peut être vendu au kg ou pour le bâtiment entier (la règle « monté = prix du bâtiment entier » est retirée).
- Tests : 92.

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

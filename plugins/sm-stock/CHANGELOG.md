# Changelog — sm-stock

## 0.3.0 — 2026-11-20 (avec sm-ai 0.4.0)

- **Ajout depuis un document** (`Documents`, filtre `sm_ai_doc_to_stock`) : bon de livraison, facture, inventaire → un article par ligne, en stock seulement, sans prix, repère automatique, emplacement facultatif, Neuf ou Réemploi (état « Bon », origine « Fin de stock »). Ligne donnée par son poids : nombre arrondi au plus proche et **poids du document gardé** (poids réel). Tôles comprises. Capacité de la formule vérifiée pour tout le document. Mouvement « Lu dans un document ».

## 0.2.0 — 2026-11-20 (avec sm-weight-engine 0.6.0 et le thème 0.49.0)

- **Magasin dans l'optimiseur de découpe** (`Cutting`) : filtre `sm_cutting_stock_offers` — barres du même profilé, neuves et variantes du réemploi, publiées ou en stock seulement (option), avec repère, emplacement, état, nuance. La ferraille n'est jamais proposée.
- « Sortir n barres du magasin » (admin-post `sm_stock_cut_out`) : rien n'est sorti par le calcul ; une fois la coupe faite, les barres sont enlevées (mouvement `cutting` avec le repère), l'article passe en vendu s'il ne reste rien. Refus si le magasin n'a plus ces barres.
- « Ajouter au magasin réemploi » (admin-post `sm_stock_add_offcuts`) : chutes réutilisables du plan, en stock seulement, un article par état et nuance, un repère R- par longueur, emplacement facultatif, mouvement `offcuts`. État et nuance de la barre d'origine (neuf → surplus neuf), « Bon » pour les barres achetées. Capacité de la formule vérifiée.
- **Repère dans les commandes** (sm-trade) : la donnée `deal` reçoit `stock_refs` — pour le vendeur toujours (avec l'emplacement), pour l'acheteur après acceptation (sans l'emplacement).
- `Actions::sold_if_empty()`. Tests : 44.

## 0.1.0 — 2026-11-19 (nécessite SM Products 0.20.0)

- Magasin des entreprises et des professionnels : tout leur acier, publié sur le marché ou gardé en stock seulement (statut `stock` de sm-products), en deux magasins (neuf, réemploi). Une seule fiche par article : stock et annonce.
- Repère automatique unique par entreprise (N-0001 pour le neuf, R-0001 pour chaque variante du réemploi : profilé et longueur), modifiable ; emplacement facultatif (tables `sm_stock_refs`, `sm_stock_moves`).
- Corrections de quantité avec motif obligatoire (barres, kg ou ligne d'inventaire ; poids et prix total recalculés) ; vendu quand il ne reste rien.
- Mouvements : arrivée, vente acceptée sur la plateforme (déduite automatiquement, référence de commande), correction, publication, retrait, prix.
- Prix pour tout un type (famille, nuance, de / à la dimension ; nouveau prix au kg ou variation en %), aperçu avec exceptions, une seule notification aux modérateurs (`stock.prices`).
- Étiquettes PDF 4 × 3 par page A4 avec QR code (bibliothèque QR de Kazuhiko Arase, MIT, `vendor/`).
- Limites par formule : capacité en kg (`sm_stock_capacity_kg`, null = illimité) et outils (`sm_stock_tools`) ; refus « Votre magasin est plein… » sans rien supprimer.
- Données du thème : `stock_enabled`, `stock`, `stock_moves`, `stock_prices`. Tests : `tests/run.php` (23).

# Changelog — sm-stock

## 0.1.0 — 2026-11-19 (nécessite SM Products 0.20.0)

- Magasin des entreprises et des professionnels : tout leur acier, publié sur le marché ou gardé en stock seulement (statut `stock` de sm-products), en deux magasins (neuf, réemploi). Une seule fiche par article : stock et annonce.
- Repère automatique unique par entreprise (N-0001 pour le neuf, R-0001 pour chaque variante du réemploi : profilé et longueur), modifiable ; emplacement facultatif (tables `sm_stock_refs`, `sm_stock_moves`).
- Corrections de quantité avec motif obligatoire (barres, kg ou ligne d'inventaire ; poids et prix total recalculés) ; vendu quand il ne reste rien.
- Mouvements : arrivée, vente acceptée sur la plateforme (déduite automatiquement, référence de commande), correction, publication, retrait, prix.
- Prix pour tout un type (famille, nuance, de / à la dimension ; nouveau prix au kg ou variation en %), aperçu avec exceptions, une seule notification aux modérateurs (`stock.prices`).
- Étiquettes PDF 4 × 3 par page A4 avec QR code (bibliothèque QR de Kazuhiko Arase, MIT, `vendor/`).
- Limites par formule : capacité en kg (`sm_stock_capacity_kg`, null = illimité) et outils (`sm_stock_tools`) ; refus « Votre magasin est plein… » sans rien supprimer.
- Données du thème : `stock_enabled`, `stock`, `stock_moves`, `stock_prices`. Tests : `tests/run.php` (23).

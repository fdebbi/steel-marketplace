# SM Stock

Magasin (stock) des entreprises et des professionnels de ReuSteel.

- **Deux magasins** : acier neuf (marché `products`) et réemploi (marché `reuse`). Un article = une fiche sm-products : publié sur le marché ou gardé en stock seulement (statut `stock`).
- **Repères** : N-0001 / R-0001 automatiques, uniques par entreprise, modifiables ; un repère par variante (profilé + longueur) pour le réemploi ; emplacement facultatif.
- **Mouvements** : arrivée, ventes acceptées (déduites automatiquement via l'audit `product.stock_taken`), corrections avec motif, publication / retrait, changements de prix.
- **Outils (formules payantes)** : prix pour tout un type avec aperçu et exceptions ; étiquettes PDF avec QR code.
- **Limites** : capacité en tonnes par formule (sm-subscriptions 0.4.0).

| Formule | Magasin | Outils |
| --- | --- | --- |
| Entreprise Découverte | 20 t | non |
| Entreprise Pro | 500 t | oui |
| Entreprise Business | illimité | oui |
| Professionnel Gratuit | 10 t | non |
| Artisan Pro | 100 t | oui |
| Artisan Premium | 300 t | oui |

## Dépendances

SM Core, SM Products ≥ 0.20.0 (SM Weight Engine pour le PDF). Ordre : sm-products → sm-stock → sm-subscriptions → thème 0.48.0.

## Filtres

- `sm_stock_capacity_kg( $kg, $company )` — capacité (null = illimité).
- `sm_stock_tools( $bool, $company )` — prix pour tout un type et étiquettes.
- Utilise `sm_products_stock_limit` (sm-products) pour refuser un ajout quand le magasin est plein.

## Tests

`wp eval-file wp-content/plugins/sm-stock/tests/run.php` ; navigateur : `python3 tests/e2e-stock.py`.

Bibliothèque QR : Kazuhiko Arase, licence MIT (`vendor/LICENSE-qrcode.txt`).

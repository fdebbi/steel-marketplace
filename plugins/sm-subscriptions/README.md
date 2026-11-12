# SM Subscriptions

Formules des entreprises et des professionnels, packs de crédits IA et options d'annonce, avec paiement hors du site et facture. Nécessite SM Core 0.8.1 ; utilise SM Products 0.8.0, SM Services 0.3.6, SM RFQ 0.1.3 et SM AI 0.1.3 quand ils sont actifs.

Si l'une de ces extensions est active dans une version plus ancienne, les pages restent affichées sans les chiffres qui en dépendent, les limites qu'elle contrôle ne sont pas appliquées, et l'administration affiche un avis rouge « mettez à jour ces extensions » (0.1.1).

## Formules (valeurs par défaut, modifiables)

| Classe | Formule | Prix (DA TTC) | Annonces | Tonnage | Bâtiments | Services | Réalisations | Réponses devis / mois | IA / mois | Coordonnées | À la une |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Entreprise | Découverte | 0 | 5 | 10 t | 1 | 1 | 3 | 3 | 5 | masquées | non |
| Entreprise | Pro | 3 500 / mois, 35 000 / an | 40 | 200 t | 5 | 5 | 20 | illimité | 50 | visibles | non |
| Entreprise | Business | 9 000 / mois, 90 000 / an | 150 | 1 000 t | 20 | 20 | illimité | illimité | 200 | visibles | rotation |
| Professionnel | Gratuit | 0 | 3 | 5 t | 1 | 1 | 3 | 3 | 5 | masquées | non |
| Professionnel | Artisan Pro | 1 500 / mois, 15 000 / an | 10 | 30 t | 2 | 3 | 15 | illimité | 30 | visibles | non |
| Professionnel | Artisan Premium | 3 000 / mois, 30 000 / an | 30 | 100 t | 5 | 8 | illimité | illimité | 80 | visibles | rotation |

Clients et ingénieurs partenaires : pas de formule ; 5 crédits IA par mois (filtre `sm_sub_no_plan_ai_credits`) et achat de packs.

Les bâtiments sont comptés en nombre, à part du tonnage. Les prix et toutes les limites se modifient dans **SM → Abonnements → Prix et limites** (vide ou -1 = illimité).

## Parcours de paiement

1. Le propriétaire ou un gestionnaire du compte commande (formule mois / an, pack, option) : commande `CMD-AAAA-#####`, statut « En attente de paiement ».
2. Il paie (virement, CCP, BaridiMob, chèque) avec les coordonnées des réglages, puis envoie la preuve (PDF ou image, 10 Mo) : statut « Paiement en vérification ». La preuve est rangée dans le dossier privé de SM Core (`…/payments/`), jamais dans la médiathèque.
3. L'équipe ouvre la commande (SM → Abonnements), vérifie la preuve, puis confirme ou refuse avec un motif. La confirmation active la formule (ou prolonge la même formule), ajoute les crédits ou applique l'option, et émet la facture `FAC-AAAA-#####` (HT, TVA, TTC ; identité du vendeur et du client figées au paiement).

Le paiement en ligne (Chargily, CIB) viendra plus tard sur la même table de commandes.

## Limites appliquées

| Extension | Filtre | Effet |
| --- | --- | --- |
| sm-products | `sm_products_publish_limit` | annonces, tonnage, bâtiments en ligne à la publication et à la republication |
| sm-services | `sm_services_limit`, `sm_portfolio_limit` | pages de services, réalisations |
| sm-rfq | `sm_rfq_offer_limit` | premières réponses aux demandes de devis du mois |
| sm-ai | `sm_ai_plan_credits`, `sm_ai_can_use`, `sm_ai_remaining`, `sm_ai_used` | crédits du mois, puis crédits achetés |
| sm-core | `sm_company_contacts_visible`, `sm_company_featured` | coordonnées publiques, rangée « à la une » en rotation quotidienne |

À la fin d'une période payée (tâche quotidienne `sm_sub_daily`), le compte revient à la formule gratuite et ce qui dépasse est mis en pause, les plus anciens d'abord. Rien n'est supprimé. Un rappel part avant la fin (7 jours par défaut), sauf si un renouvellement suit déjà.

## Membres fondateurs

Les 100 premiers comptes dont les documents sont vérifiés (niveau 2) reçoivent Pro (entreprise) ou Artisan Pro (professionnel) gratuitement pendant 6 mois, et gardent le prix de ce moment. Réglable dans SM → Abonnements → Réglages.

## Options d'annonce

Remontée en tête 300 DA, badge « Urgent » 500 DA / 7 jours, « À la une » 2 000 DA / 7 jours (en tête des résultats). Packs IA : 50 crédits 1 000 DA, 150 crédits 2 500 DA.

## Données

Tables `sm_subscriptions` (une ligne par période), `sm_sub_orders` (commandes, preuve, facture), `sm_sub_credits` (mouvements de crédits achetés). Options `sm_sub_plans`, `sm_sub_settings`, `sm_sub_founder_prices`.

## Données pour le thème

`sm_theme_data('plans')`, `sm_theme_data('subscription')`, `sm_theme_data('invoice', array('id' => …))` ; le nom de la formule est ajouté à la ressource `account`.

## Tests

```
wp eval-file wp-content/plugins/sm-subscriptions/tests/run.php
python3 tests/e2e-subscriptions.py http://localhost:8080 /chemin/wordpress sortie/
```

## Traductions

`tools/translations.py` puis `python3 tools/compile-translations.py` (FR, AR ; l'anglais est la langue source).

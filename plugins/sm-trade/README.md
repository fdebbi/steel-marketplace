# SM Trade — commandes et offres

Achats et offres entre acheteurs et vendeurs sur les annonces (produits neufs, réemploi, bâtiments). Nécessite SM Core 0.8.1 et SM Products 0.10.0.

## Ce qu'elle fait

- **Acheter** (page d'une annonce) : tout le lot au prix du lot, ou une quantité au prix affiché, selon ce que le vendeur a choisi :
  - annonce comptée en barres : achat par barres entières (prix d'une barre calculé depuis le prix au kg, à la pièce ou au mètre) ;
  - vente au poids : en kg, jusqu'au poids annoncé ;
  - autres unités : pièces, m², mètres linéaires, jusqu'à la quantité annoncée.
- **Faire une offre** (toutes les annonces) : même choix de quantité, avec le prix total proposé.
- **Réponses** : le vendeur accepte, refuse ou propose un autre prix ; l'acheteur peut accepter, refuser ou répondre à son tour. Au plus **3 réponses avec un prix** ; sans réponse, la demande se ferme après **7 jours** (tâche quotidienne). L'acheteur peut annuler tant que rien n'est accepté.
- **Compte connecté obligatoire** pour acheter ou faire une offre ; une seule demande ouverte par acheteur et par annonce ; nom, téléphone et e-mail de l'acheteur envoyés au vendeur.
- **Acceptation** : la quantité est retirée de l'annonce (à zéro, l'annonce passe en « Vendue » ; une vente partielle d'un lot « lot ou détail » supprime le prix du lot). Si le stock ne suffit plus, l'acceptation est refusée avec la raison. L'acheteur reçoit alors les coordonnées du vendeur, **même en formule gratuite**.
- **Rien n'est payé sur la plateforme** : paiement et livraison se règlent directement entre acheteur et vendeur (message clair sur le formulaire, la confirmation et la page de la commande).
- **Notifications** (site et e-mail, dans la langue du destinataire) à chaque étape.
- **Compte** : « Mes achats et offres », « Commandes et offres reçues » (avec compteur des réponses attendues), page de chaque demande avec ses étapes.
- **Administration** : SM → Commandes et offres : historique de toutes les demandes et de chaque étape, filtres (statut, type, marché, période, recherche), détail, export CSV pour Excel.

## Données

Tables `sm_trade_deals` (une demande : référence `ACH-AAAA-#####` ou `OFR-AAAA-#####`, statut, tour de réponse, quantité, prix affiché, prix proposé ou convenu, coordonnées de l'acheteur) et `sm_trade_events` (chaque étape avec son auteur, son prix et son message).

## Données pour le thème

`trade_box` (bloc de la page d'annonce), `deals` (listes), `deal` (une demande), `trade_waiting` (compteurs). Compteurs du menu par le filtre `sm_dashboard_badges`.

## Tests

```
wp eval-file wp-content/plugins/sm-trade/tests/run.php     # 40 tests
python3 tests/e2e-trade.py http://localhost:8080 /chemin/wordpress sortie/   # 45 vérifications
```

## Traductions

`tools/translations.py` puis `python3 tools/compile-translations.py` (FR, AR ; l'anglais est la langue source).

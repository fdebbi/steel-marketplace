# Changelog — sm-subscriptions

## 0.5.1 — 2026-11-21 (pour sm-ai 0.7.0)

- Outils de plusieurs crédits : refus avec le prix quand il ne reste pas assez de crédits ; après l'appel, les crédits du mois d'abord, puis le bon nombre de crédits achetés.
- Filtre `sm_ai_credit_value` : valeur d'un crédit en DA = pack le moins cher au crédit (pour les coûts et la marge de sm-ai).

## 0.5.0 — 2026-11-20 (pour sm-insights 0.1.0)

- Formules : case **« Rapport Business du marché »** (`report`), cochée pour Entreprise Business seulement par défaut ; filtre `sm_insights_access` fourni à sm-insights. Ligne visible seulement quand sm-insights est actif.

## 0.4.0 — 2026-11-19 (pour sm-stock 0.1.0)

- Formules : capacité du **Magasin** en tonnes (`stock_t`, publié ou non) et outils du magasin (`stock_tools` : prix pour tout un type, étiquettes). Par défaut : Entreprise Découverte 20 t sans outils, Pro 500 t, Business illimité ; Professionnel Gratuit 10 t sans outils, Artisan Pro 100 t, Artisan Premium 300 t.
- Filtres `sm_stock_capacity_kg` et `sm_stock_tools` fournis à sm-stock. Lignes visibles seulement quand sm-stock est actif.

## 0.3.0 — 2026-11-19 (nécessite SM AI 0.2.0)

- La **formule gratuite** (entreprises et professionnels) et les comptes sans formule (clients, ingénieurs partenaires) **n'ont plus de crédits IA mensuels** ; tous peuvent acheter des packs. Mois de transition : sur un site existant, les crédits gratuits de ce mois restent jusqu'au 1er du mois suivant (option `sm_sub_ai_grace`, migration DB v3) ; les valeurs réglées par l'administrateur pour la formule gratuite passent à 0.
- Sans crédit : « Améliorez votre formule pour bénéficier de l'assistant IA, ou achetez un pack de crédits IA » (code `sm_ai_plan`) ; crédits du mois épuisés : `sm_ai_credits`.
- La recherche intelligente ne consomme plus de crédit (SM AI 0.2.0).

## 0.2.1 — 2026-11-18

- Administration : « Arrêter » une formule demande confirmation dans la fenêtre centrée de SM Core 0.8.6.

## 0.2.0 — 2026-11-13

- Commandes d'autres extensions (filtre `sm_sub_order_quote`, avec leur propre taux de TVA) : avance et solde des études d'ingénierie (sm-engineering 0.3.0). Libellé (`sm_sub_order_label`) et page de retour (`sm_sub_order_back`) fournis par l'extension.
- Page de commande : « TVA incluse » seulement si la commande a une TVA ; lien retour vers l'étude.

## 0.1.1 — 2026-11-12

- Versions des autres extensions vérifiées (`Compat`) : si SM Core, SM Products, SM Services, SM RFQ ou SM AI est actif dans une version trop ancienne, la page Abonnement s'affiche quand même (sans le chiffre concerné) au lieu d'une erreur critique, et l'administration affiche quelle extension mettre à jour.
- Ressource `plans` : compte du visiteur (`viewer` : type de compte, formule en cours) pour la page Abonnements.
- 4 tests PHP de plus.

## 0.1.0 — 2026-11-12 (Phase 10, base v2)

- Formules entreprises (Découverte, Pro, Business) et professionnels (Gratuit, Artisan Pro, Artisan Premium), prix et limites modifiables dans l'administration.
- Commandes de formule (mois / an), de packs de crédits IA et d'options d'annonce ; paiement hors du site avec preuve stockée en privé ; confirmation ou refus par l'équipe ; facture numérotée avec HT / TVA / TTC.
- Limites appliquées aux annonces, au tonnage, aux bâtiments, aux pages de services, aux réalisations, aux réponses aux devis et aux crédits IA ; coordonnées masquées sur les comptes gratuits ; rangée « à la une » en rotation.
- Fin de période : retour à la formule gratuite, mise en pause de ce qui dépasse (les plus anciens d'abord), sans suppression ; rappel avant la fin.
- Offre membres fondateurs (100 places, 6 mois, prix gardé).
- Notifications : preuve à vérifier (équipe), paiement confirmé, refusé, fin proche, fin, mise en pause, bienvenue fondateur.
- 38 tests PHP, 44 vérifications navigateur.

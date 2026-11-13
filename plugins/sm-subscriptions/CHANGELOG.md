# Changelog — sm-subscriptions

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

# Phase 10 — Abonnements, paiement hors ligne, factures, crédits IA

Livrée le 12 nov. 2026. Nouvelle extension `sm-subscriptions` 0.1.0 ; thème 0.22.0 ; sm-core 0.8.1, sm-products 0.8.0 (base v6), sm-services 0.3.6, sm-rfq 0.1.3, sm-ai 0.1.3.

## 1. Ce qui a été validé avec vous

| Point | Décision |
| --- | --- |
| Formules | Entreprises : Découverte / Pro / Business. Professionnels : Gratuit / Artisan Pro / Artisan Premium. Clients et ingénieurs partenaires : pas de formule, achat de crédits IA. |
| Limites | Nombre d'annonces et poids total en ligne ; bâtiments comptés en nombre ; pages de services et réalisations ; réponses aux devis par mois ; crédits IA. |
| Coordonnées | Comptes gratuits : téléphone, e-mail, WhatsApp et site masqués ; le formulaire de contact reste. |
| Dépassement | À l'expiration ou au passage à une formule inférieure : mise en pause des plus anciennes, rien n'est supprimé. |
| Paiement | Hors ligne via la plateforme (preuve, activation par l'administrateur). Chargily / CIB plus tard. |
| Fondateurs | Pro gratuit 6 mois pour les 100 premiers comptes vérifiés, prix gardé ensuite. |
| Options | Remontée 300 DA, Urgent 500 DA / 7 j, À la une 2 000 DA / 7 j. Packs IA : 50 crédits 1 000 DA, 150 crédits 2 500 DA. |
| Administration | Tous les prix et toutes les limites modifiables. |

## 2. Choix techniques et pourquoi

- **Une extension séparée qui agit par filtres.** Les autres extensions ne connaissent pas les formules : elles exposent un filtre (`sm_products_publish_limit`, `sm_services_limit`, `sm_portfolio_limit`, `sm_rfq_offer_limit`, `sm_ai_*`, `sm_company_contacts_visible`, `sm_company_featured`). Sans `sm-subscriptions`, le site fonctionne comme avant, sans limite.
- **On compte ce qui est en ligne**, pas ce qui a été publié dans le mois : retirer ou vendre une annonce libère de la place tout de suite.
- **Prix TTC dans les réglages, HT calculé sur la facture** (TVA 19 % par défaut, réglable, 0 = pas de TVA). La facture fige l'identité du vendeur et du client au moment du paiement : modifier les réglages ensuite ne change pas les anciennes factures.
- **Preuves de paiement privées.** Elles montrent des numéros de compte : elles vont dans le dossier privé de SM Core (hors du dossier web quand l'hébergeur le permet), jamais dans la médiathèque. Seuls l'équipe et le propriétaire ou les gestionnaires du compte peuvent les ouvrir ; chaque ouverture est journalisée.
- **Renouvellement = période qui suit.** Payer la même formule avant la fin ajoute la période à la suite ; la page affiche « Payé jusqu'au » avec la date de fin réelle, et le rappel de fin n'est pas envoyé quand un renouvellement suit déjà. Changer de formule remplace la période en cours.
- **Crédits IA :** les crédits du mois de la formule sont utilisés d'abord, puis les crédits achetés (qui n'expirent pas). Les quotas par outil de sm-ai restent valables quand sm-subscriptions n'est pas actif.
- **« À la une » des comptes :** les comptes Business / Artisan Premium entrent dans la rangée de l'accueil dans un ordre qui change chaque jour (rotation), après les comptes choisis à la main.

## 3. Écrans

- `/tarifs/` : formules (onglets Entreprises / Professionnels et artisans), packs, options, moyens de paiement, offre fondateurs.
- Compte → Abonnement : formule en cours, jauges, cartes avec « Payer 1 mois / 1 an » ou « Renouveler », historique et factures.
- Compte → Commande : référence, montant, coordonnées de paiement, envoi de la preuve, annulation.
- Compte → Facture : imprimable / PDF via le navigateur.
- Compte → Assistant IA → Crédits IA : solde et packs (aussi pour les clients et ingénieurs partenaires).
- Mes annonces : « En pause (formule) », options de visibilité ; cartes : badges « Urgent » et « À la une ».
- Administration : SM → Abonnements (commandes et preuves, formules en cours, attribution à la main, prix et limites, réglages).

Captures : `Design/phase-10/` dans la livraison.

## 4. Tests

- PHP `sm-subscriptions` : 38 tests (formules, limites, commande → preuve → paiement → facture, renouvellement, refus, crédits, fin de période et mise en pause, fondateurs). Les autres suites PHP passent (sm-ai : les quotas par outil sont testés sans sm-subscriptions).
- Navigateur `tests/e2e-subscriptions.py` : 44 vérifications (page publique FR / AR / EN, compte gratuit, commande, preuve, refus d'accès à un autre compte, confirmation par l'administrateur, facture 29 411,76 HT + 5 588,24 TVA = 35 000,00 TTC, coordonnées visibles avec Pro, option Urgent, achat de pack et annulation, téléphone en arabe).
- Suites navigateur existantes et test visuel : voir le résumé de livraison.

## 5. À savoir avant la mise en ligne

1. **Identité de facturation à compléter** (SM → Abonnements → Réglages) : raison sociale, RC, NIF, NIS, article d'imposition, adresse. Sans elles, la facture ne montre que le nom du site : elle n'est pas conforme pour une entreprise.
2. **Coordonnées de paiement à saisir** (banque et RIB, CCP, RIP BaridiMob, ordre des chèques). Tant qu'elles sont vides, la page de commande demande de contacter l'équipe.
3. **Comptes existants :** à l'activation, chaque entreprise ou professionnel passe sur la formule gratuite. Ceux qui ont plus d'annonces en ligne que la limite les gardent (rien n'est mis en pause à l'activation), mais ne peuvent plus en ajouter. Les comptes déjà au niveau 2 ne reçoivent pas l'offre fondateurs automatiquement : l'administrateur peut la donner à la main (« Attribuer une formule »).
4. Paiement en ligne (Chargily, CIB) : non fait ; la table des commandes a déjà le champ « moyen de paiement » pour l'ajouter.

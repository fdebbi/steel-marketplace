# Changelog — sm-ai

## 0.4.0 — 2026-11-20 (avec sm-stock 0.3.0 et le thème 0.51.0)

- **« Lire un document (DQE IA) »** : un seul outil (remplace « Analyser un document » dans l'assistant) — DQE, devis, bon de commande, bon de livraison, facture, inventaire, marché. Après vérification, trois destinations (admin-post `sm_ai_doc_use`) : optimiseur de découpe (lignes ajoutées à sa liste), demande de devis (formulaire pré-rempli, publié par l'utilisateur), magasin (filtre `sm_ai_doc_to_stock`, sm-stock).
- **Poids** : une ligne donnée par son poids total (ou en mètres) reçoit des barres de 12 m (modifiables) et le nombre calculé avec le poids au mètre du catalogue — valeur exacte, arrondie au-dessus pour acheter, au plus proche pour le magasin ; « calculé depuis le poids », à vérifier. Nombre et poids écrits : la longueur d'une barre est calculée. Les mètres sont traités comme un poids (plus de liste « en mètres » à part).
- La lecture garde la page d'origine et la destination proposée (`for` : cutting, rfq, stock). Donnée `ai_cutlist` : `for`, `token`, `can_rfq`, `warehouse`.

## 0.3.0 — 2026-11-20 (avec sm-weight-engine 0.7.0 et le thème 0.50.0)

- **DQE IA** (service `cutlist`, 1 crédit) : un document (DQE, devis, bon de commande, métré, marché) en Excel (.xlsx), CSV, PDF ou photo devient la liste de l'optimiseur de découpe. Les plans ne sont pas lus (plus tard).
  - Barres : profilé vérifié dans le catalogue, longueur d'une pièce, nombre. « Sûr » seulement si tout est écrit ; « à vérifier » si le profilé est inconnu, la longueur ou le nombre manque, la ligne est déduite par l'IA, ou la source est une photo.
  - Quantités en mètres sans longueurs de pièces, et tôles et platines (épaisseur, longueur × largeur, nombre ; poids par le moteur de poids) : vers la demande de devis.
  - Lignes hors charpente seulement comptées. Photo illisible : dit, et le crédit est utilisé.
- `Sheets` : Excel (toutes les feuilles, chaînes partagées) et CSV (« ; » ou « , ») lus en tableau texte ; jamais gardés. `Files::read( …, $sheets )`.
- admin-post `sm_ai_cutlist` : lecture puis retour à l'optimiseur (`?ai=jeton`, résultat gardé 2 h pour l'utilisateur). Donnée du thème `ai_cutlist` (état, crédits restants, résultat). Carte sur la page des outils IA.
- Une fonctionnalité peut demander plus de jetons de réponse (`max_tokens()`).

## 0.2.0 — 2026-11-19

- Nouveau service **Rédaction de l'annonce** (`describe`) : dans le formulaire de publication, « Rédiger avec l'IA » écrit la description à partir de ce que le vendeur a saisi (champs du formulaire et chiffres calculés par la plateforme). Le modèle n'utilise que ces informations ; téléphone, e-mail et liens sont retirés. Appel `admin-ajax` `sm_ai_describe` (jeton), 1 crédit.
- La **recherche intelligente est gratuite pour tous** : elle ne consomme plus de crédit IA (limites par jour inchangées). `Feature::credits()` (1 par défaut, 0 pour la recherche), `Gateway::free_features()` ; `Usage::company_month_total()` ne compte que les services payants.
- Données du thème : `ai_catalog` (chaque service avec son état `ok | login | upgrade | blocked`, crédits par utilisation, crédits restants) et `ai_describe` ; `ai_tools` donne aussi l'état. `Form::state_of()`.

## 0.1.4 — 2026-11-15

- Les lots groupés (catégorie « group » de sm-reuse) ne sont pas proposés par l'assistant d'annonce. Une catégorie de réemploi à dimension unique reçoit la famille et la dimension (le vendeur ajoute les longueurs).

## 0.1.3 — 2026-11-12 (Phase 10)

- Quand `sm_ai_plan_credits` est vrai (sm-subscriptions), les quotas par outil sont remplacés par les crédits de la formule ; `sm_ai_remaining` donne le solde ; action `sm_ai_used` après chaque appel réussi.
- `Usage::company_month_total()`.

## 0.1.2 — 2026-11-12

- Réponses en anglais quand la page est en anglais ; libellés des marchés en anglais.

## 0.1.1 — 2026-11-12

- Ressource `ai_tools` : `quota` (limite mensuelle de l'entreprise) et `used` (utilisations du mois) pour chaque outil ; page « Crédits IA » du tableau de bord (`/compte/credits-ia/`).

## 0.1.0 — 2026-11-08 (Phase 9)

- Connecteurs Anthropic (Messages) et compatible OpenAI (Chat Completions), fournisseur « Simulation » pour les tests ; clés chiffrées ou dans wp-config.php.
- Assistant d'annonce, reconnaissance de profilé, recherche intelligente, analyse de document ; réponses vérifiées contre le catalogue, poids par le moteur de poids.
- Pré-remplissage du formulaire d'annonce et du formulaire de demande de devis (SM Core `Forms::prefill`).
- Quotas par entreprise et par mois, limites par utilisateur et par visiteur, budget mensuel, journal de consommation et de coûts, test de connexion.
- 46 tests PHP, 105 chaînes traduites.

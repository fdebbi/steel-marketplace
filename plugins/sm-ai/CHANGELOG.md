# Changelog — sm-ai

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

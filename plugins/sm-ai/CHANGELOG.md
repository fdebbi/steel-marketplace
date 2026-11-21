# Changelog — sm-ai

## 0.7.0 — 2026-11-21

- **Prix de chaque outil en crédits, réglable** (onglet « Coûts et marge ») ; par défaut 1 crédit, recherche intelligente et résumé du Rapport Business gratuits. `Gateway::credits()`, `Settings::credits()` ; chaque appel garde le prix qu'il avait (colonne `credits` de `sm_ai_usage`, base v2 ; les anciens appels payants comptent 1 crédit). Les crédits du mois = somme des prix des appels.
- **Onglet « Coûts et marge »** : valeur d'un crédit (filtre `sm_ai_credit_value`, sm-subscriptions : le pack le moins cher au crédit), taux de change saisi par l'administrateur (aucun taux inventé : sans taux, pas de marge), marge visée, budget mensuel avec alerte e-mail (80 % par défaut) et, à 100 %, pause des outils payants ou simple avertissement ; ce mois-ci (coût réel, budget, crédits, marge, outils sous la marge) ; par outil (prix, utilisations, coût réel moyen, rapporte, marge, conseil « Passer à n crédits ») ; 6 derniers mois. Le budget quitte l'onglet Fournisseurs.
- Donnée `ai_tools` : prix de chaque onglet (`credits`) pour son bouton. Simulation du résumé du Rapport Business alignée sur les chiffres envoyés par sm-insights.
- Filtre `sm_ai_can_use( $ok, $id, $company_id, $credits )` et action `sm_ai_used( $id, $company_id, $credits )` avec le prix de l'outil.

## 0.6.0 — 2026-11-20 (pour sm-insights 0.1.0)

- Service **« Résumé du Rapport Business »** (`report`, gratuit : pas de crédit) et filtre `sm_ai_report_summary( null, $figures, $lang )` → 3 ou 4 points écrits uniquement à partir des chiffres de la plateforme ; « prix demandés », jamais de nombre de ventes (retiré s'il en écrit un), aucun nom d'entreprise, pas de tendance sur un seul bâtiment.

## 0.5.0 — 2026-11-20 (avec le thème 0.52.0)

- **Service « État de l'acier »** (`condition`, 1 crédit, 30 par entreprise et par mois par défaut) : depuis les photos d'un lot de réemploi, suggestion d'état (excellent, bon, à contrôler, ferraille) et de surface (propre, rouille superficielle, peint, galvanisé, corrosion importante), ce que l'IA voit et ce qu'elle ne peut pas voir. Jamais « excellent » sans photos nettes de près (sinon « à contrôler ») ; une estimation visuelle, jamais une inspection.
- **Photos du formulaire de publication** : admin-ajax `sm_ai_form_photo` (clé `sm_ai_form_photo`) pour `recognize` (3 profilés au plus) et `condition` ; photos gardées de l'utilisateur (`keep[]`, vérifiées : pièces jointes à lui) et nouveaux fichiers (`photos[]`). Réponse JSON `{ tool, result, remaining }` ou `{ message, state }`.
- Donnée du thème `ai_form_photo` : outils offerts, état (ok / login / upgrade / blocked), crédits, adresse et clé de l'appel.

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

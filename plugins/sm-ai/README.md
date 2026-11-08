# SM AI — assistance par intelligence artificielle

Extension de **ReuSteel** (Phase 9). Nécessite **SM Core 0.5.0**. Utilise, quand elles sont actives, SM Weight Engine (catalogue et poids), SM Products (marchés et catégories) et SM RFQ (demandes de devis).

## Principes

- **Indépendante du fournisseur** : connecteurs **Anthropic** (API Messages, modèles Claude) et **compatible OpenAI** (API Chat Completions : OpenAI et les fournisseurs qui reprennent ce format, URL de base réglable). Fournisseur et modèle se choisissent par service.
- **Appels uniquement depuis le serveur**. Les clés API sont **chiffrées** en base (SM Core Crypto) et ne sont jamais réaffichées. On peut aussi les définir dans `wp-config.php`, ce qui est plus sûr :
  ```php
  define( 'SM_AI_ANTHROPIC_KEY', 'sk-ant-…' );
  define( 'SM_AI_OPENAI_KEY', 'sk-…' );
  ```
- **Des suggestions, jamais des décisions** :
  - toute réponse est vérifiée contre la plateforme : catégories, familles et désignations du catalogue, nuances, wilayas ;
  - ce qui n'existe pas est écarté ;
  - **les poids viennent du moteur de poids, jamais de l'IA** ;
  - les interfaces rappellent que l'IA ne remplace ni l'ingénieur, ni une vérification structurelle, ni une certification ;
  - rien n'est publié sans validation de l'utilisateur.
- **Coûts maîtrisés** :
  - quota par entreprise et par mois pour chaque service ;
  - limite par utilisateur et par jour ;
  - limite par adresse IP et par jour pour les visiteurs (recherche intelligente) ;
  - **budget mensuel** (estimé à partir des prix par million de jetons saisis) ;
  - journal de chaque appel (service, fournisseur, modèle, entreprise, jetons, coût estimé, durée, erreur).
  - Le filtre `sm_ai_can_use` (true|WP_Error, service, entreprise) permettra de brancher les crédits IA (Phase 10).

## Services (0.1.0)

| Service | Qui | Ce qu'il fait |
| --- | --- | --- |
| Assistant d'annonce | Comptes entreprise pouvant publier | À partir de photos et de notes : marché, catégorie, profilé du catalogue, nuance, état, titres et description FR/AR, informations manquantes. Bouton « Créer l'annonce » : formulaire pré-rempli. |
| Reconnaissance de profilé | Comptes connectés (ingénieurs compris) | Jusqu'à 4 profilés possibles avec confiance et raison, vérifiés au catalogue (masse linéique du catalogue), questions pour lever le doute ; liens « Le peser » et « Le trouver sur la plateforme ». |
| Recherche intelligente | Tout le monde | Une phrase (FR, AR, darija) devient des filtres : marché, catégorie, désignation, wilaya, état, longueur, quantité, prix maximal. « Compris comme » affiché sur les résultats ; recherche simple en cas d'échec. |
| Analyse de document | Comptes connectés | PDF ou photo (métré, liste de profilés, cahier des charges) : articles extraits, profilés du catalogue reconnus et **pesés par le moteur** ; bouton « Créer une demande de devis » (lignes pesées et autres articles dans la description). |

## Administration (*SM → Assistant IA*)

- **Fournisseurs et services** :
  - clés, modèles, prix par million de jetons et URL de base ;
  - fournisseur par défaut, puis activation, fournisseur, modèle et quotas de chaque service ;
  - limites : budget mensuel, utilisations par jour, taille des réponses, photos, taille des fichiers, délai d'attente, conservation ;
  - « Tester la connexion ».
- **Consommation** :
  - totaux du mois par service (réponses, erreurs, refus, jetons, coût estimé) ;
  - entreprises qui l'utilisent le plus ;
  - derniers appels.

## Simulation (tests)

`define( 'SM_AI_SIMULATION', true );` dans `wp-config.php` ajoute un fournisseur « Simulation » qui ne contacte aucune IA et répond de façon plausible à partir des notes. Il sert aux tests automatiques et aux démonstrations ; les résultats portent le badge « Simulation ». **Ne pas l'activer en production.**

## Données

- Table `{prefix}sm_ai_usage`.
- Options `sm_ai_settings` (clés chiffrées).
- Résultats conservés 2 heures pour l'utilisateur qui a demandé (transients). Les fichiers envoyés ne sont **pas enregistrés** : ils sont lus, transmis au fournisseur puis oubliés. Les photos sont réduites à 1568 px.

## Tests

```
wp eval-file wp-content/plugins/sm-ai/tests/run.php   # 46 tests (site de TEST)
python3 tests/e2e-ai.py URL WP_PATH OUT                # 30 vérifications navigateur (SM_AI_SIMULATION requis)
```

Les connecteurs sont testés contre des réponses enregistrées aux formats Anthropic et OpenAI (`pre_http_request`). **Aucun appel réel n'a été fait pendant le développement** : faire « Tester la connexion » sur le serveur après avoir saisi la clé.

## Traductions

`languages/` : fr_FR et ar (105 chaînes). Régénérer : `python3 tools/compile-translations.py`.

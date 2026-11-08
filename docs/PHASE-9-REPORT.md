# Phase 9 — rapport de livraison (8 novembre 2026)

Livrables : nouvelle extension `sm-ai` 0.1.0, thème `sm-marketplace` 0.12.0, `sm-core` 0.5.0 (pré-remplissage des formulaires ; inclut 0.4.1 : en-tête, signature et réseaux sociaux des e-mails). Les autres extensions sont inchangées.

## Ce qui est livré

- **Assistant d'annonce** :
  - à partir de photos et de notes, l'IA propose le marché, la catégorie, le profilé (vérifié au catalogue), la nuance, l'état, les titres et la description en français et en arabe, et la liste des informations manquantes ;
  - « Créer l'annonce avec ces suggestions » ouvre le formulaire de publication pré-rempli ; le poids est calculé par le moteur sur le formulaire.
- **Reconnaissance de profilé** :
  - jusqu'à 4 profilés possibles avec un niveau de confiance et la raison ;
  - seules les familles et tailles du catalogue sont gardées, avec leur masse linéique du catalogue ;
  - des questions pour lever le doute ;
  - liens « Le peser » (calculateur) et « Le trouver sur la plateforme » ;
  - toujours présenté comme « à confirmer par une mesure ».
- **Recherche intelligente** :
  - une phrase en français, en arabe ou en darija devient des filtres : marché, catégorie, désignation, wilaya, état, longueur, quantité, prix maximal ;
  - les résultats affichent « Compris comme » ;
  - en cas d'indisponibilité, la recherche simple prend le relais.
- **Analyse de document** :
  - PDF ou photo d'un métré : articles extraits, profilés du catalogue reconnus et **pesés par le moteur de poids**, autres articles marqués « à préciser » ;
  - « Créer une demande de devis » remplit les lignes pesables et met le reste dans la description.
- **Administration (*SM → Assistant IA*)** :
  - fournisseurs Anthropic et compatible OpenAI, clés chiffrées (ou dans `wp-config.php`), modèle et prix par million de jetons ;
  - fournisseur par défaut, puis activation, fournisseur, modèle et quotas de chaque service ;
  - budget mensuel, limites, test de connexion ;
  - page Consommation : appels, erreurs, refus, jetons, coût estimé, entreprises, derniers appels.
- **Séparation et sécurité** :
  - un compte ingénierie n'a pas l'assistant d'annonce (il ne vend pas) mais a la reconnaissance et l'analyse de document ;
  - les visiteurs n'ont que la recherche intelligente, limitée par jour ;
  - les fichiers ne sont jamais stockés.

## Tests

| Suite | Résultat |
| --- | --- |
| sm-ai, tests PHP : réglages et clés chiffrées, connecteurs Anthropic et OpenAI contre des réponses enregistrées (requêtes, réponses, erreurs, panne réseau), droits, quotas, budget, journal, contrôle des réponses contre le catalogue, poids du moteur, simulation | 46/46 |
| sm-core (dont pré-remplissage et en-tête/signature des e-mails), sm-weight-engine, sm-products, sm-reuse, sm-buildings, sm-rfq, sm-services | 144 + 46 + 40 + 38 + 35 + 39 + 32 |
| IA dans le navigateur (`tests/e2e-ai.py` : réglages, assistant d'annonce → formulaire pré-rempli, reconnaissance → calculateur, document → demande de devis pré-remplie, compte ingénieur, recherche intelligente en français et en arabe, quotas, consommation, aucune erreur JS) | 30/30 |
| Parcours existants (Phase 2, calculateur, produits, réemploi, bâtiments, demandes de devis, services, e-mails) | 56 + 22 + 25 + 26 + 24 + 25 + 39 + 29 |
| Tests visuels AR/FR, 7 largeurs, 35 pages | 518/518 |

Une vérification du parcours produits (total au m² sur la page de toiture) a échoué une fois pendant la régression complète puis est passée deux fois de suite. C'est un délai d'attente du rafraîchissement en direct, pas une erreur de calcul.

## Limites connues

- **Aucun appel réel à une IA n'a été fait** : l'environnement de développement ne peut pas joindre les API. Les connecteurs sont vérifiés contre les formats documentés, et le reste avec le fournisseur Simulation. Le premier vrai test se fait sur le serveur : saisir la clé et le modèle, puis cliquer sur « Tester la connexion ».
- La qualité réelle des suggestions dépend du modèle choisi et des photos : à évaluer sur de vraies annonces avant d'ouvrir le service à tous (on peut n'activer qu'un service à la fois).
- Le format « compatible OpenAI » n'accepte pas toujours les PDF chez les autres fournisseurs : l'erreur est alors affichée et journalisée. Anthropic accepte les PDF.
- Les autres services du cahier des charges (assistant hangar, assistant technique, mise en relation, veille des prix) viendront progressivement. Les crédits IA payants arrivent en Phase 10.

## Mise à jour d'un site

1. Installer sm-core 0.5.0, puis sm-ai 0.1.0 (crée sa table) et le thème 0.12.0.
2. *SM → Assistant IA* : coller la clé (ou l'ajouter dans `wp-config.php`), indiquer le modèle et les prix, choisir le fournisseur par défaut, puis « Tester la connexion ».
3. Régler les quotas et le budget mensuel ; activer les services un par un si besoin.
4. Ne pas définir `SM_AI_SIMULATION` en production.

# Rapport de tests — Phase 1

Date : 5 novembre 2026 — WordPress 7.1.4, PHP 8.3, Chromium (Playwright). Outil : `tools/visual-test.py`.

**Résultat : 224/224 vérifications réussies** (16 pages × 2 langues × 7 largeurs). Chaque vérification contrôle l’absence de défilement horizontal et la direction du document (`dir=rtl` en arabe, `ltr` en français), et produit une capture pleine page.

| Page | 360 | 390 | 430 | 768 | 1024 | 1280 | 1440 |
| --- | --- | --- | --- | --- | --- | --- | --- |
| home | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ |
| products | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ |
| reuse | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ |
| buildings | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ |
| product-detail | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ |
| reuse-detail | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ |
| building-dismantled | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ |
| building-assembled | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ |
| company | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ |
| engineer-profile | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ |
| register | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ |
| dashboard | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ |
| dashboard-listings | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ |
| dashboard-publish | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ |
| dashboard-engineer | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ |
| rfq | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ | FR ✓ · AR ✓ |

## Tests fonctionnels (navigateur)

| Test | Résultat |
| --- | --- |
| Méga-menu : ouverture au clic, fermeture par Échap | ✓ |
| Tiroir mobile : ouverture / fermeture | ✓ |
| Panneau de filtres mobile (arabe) : ouverture | ✓ |
| Filtre état « bon état » sur le réemploi : 1 résultat attendu, 1 obtenu | ✓ |
| Publication : aucun défilement au chargement, passage aux étapes, champs bâtiment, inventaire démonté, ajout de ligne, bouton Publier à la dernière étape | ✓ |
| Inscription : activités commerciales masquées pour un compte ingénierie | ✓ |
| Couleurs de marque modifiées en base → en-tête et boutons re-thémés en FR et en AR | ✓ |
| Erreurs console JavaScript (accueil, détail, publication) | ✓ |

Toutes les pages répondent 200 (404 attendue sur une URL inconnue) sans avertissement PHP du thème.

## Corrections apportées pendant les tests

- Tableau « Mes annonces » qui élargissait le tableau de bord (colonne de grille sans `minmax(0,1fr)`, texte d’accessibilité absolu hors du conteneur défilant).
- Couleurs du Personnalisateur écrasées par la feuille principale : désormais injectées après elle.
- Unités autour des nombres en arabe (`/kg`, `t`) : isolées par `sm_per_unit()` / `sm_tech()`.
- Désignations techniques retirées par le filtre de sécurité dans les fiches techniques : liste blanche `sm_kses_tech()`.
- Sélecteurs tronqués dans la recherche d’accueil (tablette et 1024 px) : mise en page dédiée.
- Bouton favori mal placé sur les cartes ; défilement et focus parasites du formulaire de publication au chargement.
- Dates relatives traduites par le thème (indépendantes du pack de langue du cœur).

## Limites connues

- Données de démonstration : poids unitaires et prix illustratifs, signalés à l’écran ; les tables normalisées européennes seront saisies et validées en Phase 3.
- Formulaires (connexion, inscription, demande, publication, devis) : présentation seulement, traitement en Phase 2 et suivantes.
- Langue par `?lang=` + cookie en Phase 1 ; préfixes `/ar/` `/fr/` et préférence du compte avec sm-core.
- Textes internes de WordPress en arabe : nécessitent le pack de langue arabe du cœur (installation en un clic sur Hostinger).
- Non testé sur appareils physiques ni sur Safari iOS réel (Chromium uniquement dans cet environnement).

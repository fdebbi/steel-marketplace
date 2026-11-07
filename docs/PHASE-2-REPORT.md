# Rapport de la Phase 2 — sm-core 0.1.0 et thème 0.2.0

Date : 7 novembre 2026 — WordPress 7.1.4, PHP 8.3, Chromium (Playwright).

## Résultats des tests

| Suite | Résultat | Commande |
| --- | --- | --- |
| Tests PHP de sm-core (installation, matrice des droits des 6 types de compte, règle ingénierie, vérification, code, langues, e-mails en arabe, REST, adresses) | **87 / 87** | `wp eval-file wp-content/plugins/sm-core/tests/run.php` |
| Parcours navigateur (visiteur, inscription FR mobile, ingénieur AR ordinateur, modération, langue, connexion par téléphone) | **43 / 43** | `python3 tests/e2e.py URL CHEMIN_WP sortie` |
| Responsive AR/FR : 20 pages × 2 langues × 7 largeurs (360 → 1440 px), tableaux de bord connectés compris | **280 / 280** | `SM_VT_COM=… SM_VT_ENG=… python3 themes/sm-marketplace/tools/visual-test.py URL sortie` |

## Corrections faites pendant les tests

- Messages des formulaires en anglais côté arabe (traitement via `admin-post.php`) : chargement explicite des traductions de la langue de la page.
- Documents de vérification lisibles par URL sur un serveur sans `.htaccess` : stockage déplacé au-dessus du dossier public.
- Message « vous pouvez publier » affiché aux ingénieurs : message propre aux comptes ingénierie.

## Limites connues

- Codes de vérification envoyés par **e-mail** (aucune passerelle SMS choisie) : configurer l'envoi SMTP sur Hostinger.
- Réinitialisation du mot de passe : l'écran final est celui de WordPress (`wp-login.php`), non stylé.
- Annonces, devis, services, abonnements : encore en démonstration (Phases 3 à 10). Les demandes sur annonces de démonstration arrivent aux modérateurs.
- Un seul membre par entreprise via l'interface (invitations de collaborateurs : phase ultérieure).
- Tests sous Chromium uniquement, en local (pas de Safari iOS réel).

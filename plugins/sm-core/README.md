# SM Core — socle de Steel Marketplace (Phase 2)

Extension centrale dont dépendent toutes les autres extensions `sm-*`. Version 0.1.0 — WordPress 6.4+ (testé 7.1.4), PHP 8.1+ (testé 8.3).

| Domaine | Ce que fait sm-core |
| --- | --- |
| Comptes | Entreprise = compte (classe `commercial` ou `engineering`, fixée à l'inscription), membres (titulaire, gestionnaire, membre), activités multiples |
| Permissions | `sm_can( $cap )` : activité → droit (matrice modifiable), niveau de vérification minimum, abonnement (filtre), **règle stricte ingénierie** dans le code |
| Vérification | Niveau 1 : code à 6 chiffres (SMS si passerelle, sinon e-mail). Niveau 2/3 : documents RC/NIF/NIS ou diplôme/agrément, examinés par un modérateur |
| Langues | Français sans préfixe, arabe sous `/ar/`, préférence mémorisée (cookie + compte), e-mails dans la langue du destinataire |
| Formulaires | Inscription, connexion (e-mail ou téléphone), mot de passe oublié, fiche entreprise, paramètres, demandes (contact vendeur / prestation) |
| Notifications | Tableau de bord + e-mail, types extensibles |
| Sécurité | Nonces, limitation de débit, champ piège anti-robots, numéros RC/NIF/NIS chiffrés, documents hors du dossier public, journal d'audit (365 jours) |
| API | REST `sm/v1` (voir `docs/rest.md`) |
| Admin | Menu « Steel Marketplace » : vue d'ensemble, entreprises, vérifications, droits, wilayas (69), réglages, journal |

## Installation (Hostinger)

1. Extensions → Ajouter → Téléverser `sm-core.zip` → Activer. Les tables sont créées automatiquement.
2. Thème `sm-marketplace` **0.2.0 ou plus** (nécessaire pour les formulaires et adresses propres).
3. Réglages → Permaliens → « Titre de la publication » → Enregistrer (pour `/ar/`, `/annonce/302/`, `/compte/annonces/`).
4. **E-mails** : les codes de vérification partent par e-mail tant qu'aucune passerelle SMS n'est branchée. Sur Hostinger, créez une boîte e-mail du domaine et configurez l'envoi SMTP (par ex. avec l'extension gratuite « WP Mail SMTP »), sinon les codes risquent d'arriver en spam ou pas du tout.
5. Steel Marketplace → Droits : vérifiez la matrice ; Steel Marketplace → Réglages : limites.
6. Videz le cache (hPanel → Performance) après l'activation.

## Développement

```bash
wp eval-file wp-content/plugins/sm-core/tests/run.php          # 87 tests (site de TEST uniquement)
python3 tests/e2e.py http://localhost:8080 /chemin/wordpress out  # 43 vérifications navigateur (racine du dépôt)
python3 tools/compile-translations.py                           # tools/translations.py -> languages/*.po|mo
wp i18n make-pot . languages/sm-core.pot --domain=sm-core --exclude=tests --skip-js
```

Documentation : [`docs/hooks.md`](docs/hooks.md), [`docs/rest.md`](docs/rest.md), [`docs/database.md`](docs/database.md), [`docs/security.md`](docs/security.md).

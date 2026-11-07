# Sécurité — sm-core

| Risque | Mesure |
| --- | --- |
| CSRF | Nonce `_sm_nonce` sur chaque formulaire ; nonces WordPress dans l'admin et pour les documents |
| Robots / spam | Champ piège invisible ; limites : 5 inscriptions/heure/IP, 10 demandes/heure/compte (réglables) |
| Force brute | 5 échecs de connexion → blocage 15 minutes (IP + identifiant) ; message identique compte existant ou non ; codes : 5 essais, 10 minutes, renvoi après 60 s, 5/heure |
| Droits | Contrôle côté serveur à chaque action (`sm_can`), règle ingénierie non contournable (tests) ; la classe, le niveau et le statut ne sont modifiables que par un modérateur |
| Données sensibles | RC/NIF/NIS chiffrés (libsodium, clé dérivée des sels de `wp-config.php`). **Ne changez pas les sels** sans réexporter ces numéros : ils deviendraient illisibles |
| Documents | Stockés hors du dossier public quand c'est possible (`../sm-private-xxxx`), sinon `uploads/sm-private` avec règles de refus ; noms aléatoires ; servis uniquement via `admin-post.php?action=sm_document` (nonce + modérateur ou titulaire), consultation journalisée ; PDF/JPG/PNG ≤ 5 Mo, type vérifié |
| XSS / SQL | Échappement à l'affichage, requêtes préparées (`$wpdb->prepare`) |
| Accès admin | Les membres (`sm_member`) sont renvoyés de wp-admin vers leur tableau de bord ; barre d'admin masquée |
| Traçabilité | Journal d'audit : inscriptions, connexions et échecs, modifications d'entreprise, vérifications, décisions, réglages, consultations de documents |
| Cache | Pages de compte et pages à paramètres non mises en cache (thème) ; une URL = une langue |

Sauvegardes (Hostinger) : activer les sauvegardes quotidiennes du plan et, avant chaque mise à jour d'extension, une sauvegarde manuelle (fichiers + base). Le dossier `sm-private-*` situé au-dessus de `public_html` doit être inclus dans la sauvegarde des fichiers.

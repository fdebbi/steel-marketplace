# Changelog — sm-core

## 0.1.0 — 2026-11-07 (Phase 2)

- Comptes : entreprises (classe commerciale ou ingénierie fixée à l'inscription), membres, activités multiples, slugs uniques.
- Permissions : `sm_can()` / `sm_can_explain()`, matrice activité × droit modifiable, niveaux de vérification minimum, filtre d'abonnement, règle stricte « l'ingénierie ne commerce jamais ».
- Inscription, connexion par e-mail ou téléphone, mot de passe oublié, code de vérification à 6 chiffres (e-mail, SMS prêt via filtre).
- Vérification documentaire (RC/NIF/NIS, diplôme/agrément) avec file de modération, documents privés hors du dossier public.
- Langues : arabe sous `/ar/`, français sans préfixe, préférence mémorisée, e-mails dans la langue du destinataire.
- Adresses propres : `/annonce/{id}/`, `/entreprise/{slug}/`, `/compte/{vue}/`.
- Notifications (tableau de bord + e-mail), demandes (contact vendeur, demande de prestation), journal d'audit.
- API REST `sm/v1`, écrans d'administration, 69 wilayas (loi n° 26-06 du 4 avril 2026).
- Traductions fr_FR et ar (174 chaînes). 87 tests PHP, 43 vérifications de bout en bout.

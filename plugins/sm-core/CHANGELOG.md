# Changelog — sm-core

## 0.4.0 — 2026-11-08 (Phase 8.1 : e-mails et journal d'activité)

- *SM → E-mails* : catalogue d'événements (filtre `sm_mail_events`), alerte aux administrateurs immédiate / résumé quotidien / non, confirmation à l'utilisateur, modèles FR/AR modifiables avec variables, aperçu, test, retour au texte par défaut ; e-mails des notifications activables et réécrivables (`notice:{type}`) ; expéditeur, destinataires, langue des alertes, heure du résumé, conservation.
- Envoi commun (`Mail\Mailer`) : mise en page unique (RTL en arabe), en-têtes From et Reply-To, journal des e-mails `sm_mail_log` (statut, erreur, lecture, renvoi ; codes de vérification jamais conservés).
- Résumé quotidien des événements choisis (tâche `sm_mail_hourly`).
- *SM → Journal d'activité* (remplace « Journal d'audit ») : phrases lisibles, groupes, filtres par dates et recherche (nom, e-mail, entreprise, IP), export CSV, connexions réussies rangées sous « Sécurité » ; chaque nouvelle entrée déclenche `sm_audit_logged`.
- Formulaire de contact (`sm_contact`) : validation, limite de 5 messages par heure, alerte avec Reply-To, accusé de réception.
- Base de données version 4 (table `sm_mail_log`). 35 tests ajoutés.

## 0.3.3 — 2026-11-08

- Profil professionnel : années d'expérience (`profile[experience_years]`), exposées au thème (`experience`).

## 0.3.2 — 2026-11-08

- Formulaires : après une erreur, les valeurs imbriquées (lignes d'inventaire, `lines[0][qty]`) sont conservées.
- Capacités `rfq.create` / `rfq.offer` utilisées par SM RFQ (inchangées).

## 0.3.1 — 2026-11-07

- Nom de la plateforme dans les e-mails, le code de vérification et le menu d'administration : `sm_brand_name()` (« ReuSteel » par défaut, suit le Personnalisateur).

## 0.3.0 — 2026-11-07 (Phase 4)

- Index des annonces (`SM\Core\Listings\Index`) : un numéro public par annonce, commun à tous les marchés ; prix, unité, total, date de mise à jour (base v3) ; propriétaire pour les demandes de contact.
- Textes traduisibles des annonces (`SM\Core\Content\Translations`) et fichiers liés avec envoi contrôlé (`SM\Core\Content\Media` : type réel du fichier, taille, nombre).
- Passerelle thème : toutes les valeurs d'un formulaire refusé (`sm_form_old_all`).

## 0.2.0 — 2026-11-07 (complément Phase 2 : carte)

- Emplacement de l'entreprise sur la carte : colonnes `lat`, `lng`, `map_public` (base v2, migration automatique à la mise à jour).
- Centre approximatif des 69 wilayas (`data/wilaya-centres.php`, colonnes `lat`/`lng` de `sm_wilayas`) pour ouvrir la carte sur la bonne wilaya ; jamais écrasé s'il est modifié.
- Validation côté serveur : position numérique, complète et dans les limites de l'Algérie ; refus avec message traduit.
- Position publique seulement si l'entreprise l'autorise (`location` dans la passerelle thème et l'API ; `null` sinon).
- API : `PATCH /me/company` accepte `lat`, `lng` (vides = suppression) et `map_public` ; `/reference/wilayas` renvoie `centre`.
- Passerelle thème : ressources `map` (serveur de tuiles, filtre `sm_map_tiles`) et `wilaya_centres`.
- 103 tests PHP (+16).

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

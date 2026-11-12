# Changelog — sm-core

## 0.8.1 — 2026-11-12 (Phase 10)

- `Bridge::shape_company` : filtre `sm_company_contacts_visible` ; quand il renvoie faux, téléphone, e-mail et site sont vides et `contacts_hidden` vaut vrai. Les membres du compte et l'équipe les voient toujours.

## 0.8.0 — 2026-11-12

- Troisième langue : anglais (`/en/…`, locale en_US). Les chaînes sources étant en anglais, aucun fichier de traduction n'est nécessaire.
- E-mails : modèles, signature et adresse modifiables aussi en anglais.
- `sm_pick()` et `sm_en()` : les données par défaut (catégories, champs, options, catalogue) s'affichent en anglais grâce à `data/en-content.php` (filtre `sm_en_dictionary`) ; un texte écrit par l'administrateur sans version anglaise reste en français.

## 0.7.3 — 2026-11-12

- Ressource `account` : `badges` (page du tableau de bord => éléments en attente), complétée par le filtre `sm_dashboard_badges` (demandes reçues non lues pour sm-core).

## 0.7.2 — 2026-11-11

- Notifications : page « Notifications » du tableau de bord, lien d'ouverture qui marque la notification comme lue, nombre réglable dans la ressource thème.

## 0.7.1 — 2026-11-11

- Types et catégories : une liste peut afficher un bloc sous son tableau (utilisé pour les services proposés).

## 0.7.0 — 2026-11-11 (Phase 9.2, base v6)

- SM → **Types et catégories** (remplace « Types de comptes ») : types d'entreprise, métiers et listes des autres modules (filtre `sm_term_lists`) avec nom FR/AR, description FR/AR, image, ordre ; masquer ; supprimer avec un élément de remplacement quand des comptes l'utilisent.
- À la une : colonnes `featured_until`, `featured_label` (À la une / Sponsorisé / VIP), `featured_priority` ; réglage dans la fiche entreprise ; filtre `sm_company_featured` pour le futur module d'abonnements ; ressources thème `featured_companies` et `directory`.
- E-mail de bienvenue unique avec le code de vérification (canal e-mail) ; le code est masqué dans le journal des e-mails.
- Fiche admin : les types masqués restent cochables pour un compte qui les a.

## 0.6.1 — 2026-11-11

- Ressource thème `company` : adresse et commune exposées pour le bloc Localisation du profil public.

## 0.6.0 — 2026-11-11 (Phase 9.1 : modèle de comptes)

- Classes de comptes : société (types), professionnel (métiers), client (non public), ingénieur partenaire (créé par SM Engineering).
- Page SM → Types de comptes : libellés FR/AR, masquer, ajouter des types et des métiers.
- Droits : `mission.request`, `mission.work` ; `service.engineering` retiré ; tous les comptes sauf ingénieurs achètent et vendent.
- Logo / photo pour tous les comptes ; ressource thème `account_types` ; annuaire et pages publiques limités aux sociétés et professionnels.
- Migration base v5 des anciens comptes ; `Registration::create_account()` réutilisable.
- Correctif : la langue de la requête est rétablie après un e-mail envoyé dans une autre langue.

## 0.5.0 — 2026-11-08 (Phase 9)

- `Forms::prefill( $values, $target, $message )` : ouvre un formulaire déjà rempli (suggestions de l'assistant IA), lu par le thème comme des valeurs renvoyées ; rien n'est enregistré avant l'envoi.

## 0.4.1 — 2026-11-08 (e-mails : en-tête et signature)

- *SM → E-mails → En-tête et signature* : image d'en-tête (médiathèque) ou bandeau de couleur avec logo, signature (logo, nom, adresse FR/AR, téléphone, e-mail, site web, formule de politesse FR/AR), liens des réseaux sociaux (Facebook, Instagram, LinkedIn, YouTube, TikTok, X, WhatsApp) ; appliqués à tous les e-mails ; aperçu FR/AR. 6 tests ajoutés.

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

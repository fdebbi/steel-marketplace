# Tables de sm-core (préfixe `{wp}_sm_`)

Migrations : `src/Install/Installer.php`, version en option `sm_core_db_version` (1), exécutées à l'activation et après mise à jour. Jamais de suppression à la désactivation.

| Table | Contenu | Clés |
| --- | --- | --- |
| `companies` | slug, account_class, name, name_ar, wilaya, commune, address, phone, email, website, logo_id, title_fr/ar, about_fr/ar, profile (JSON : qualifications, specialities), rc_number / nif / nis (**chiffrés**), verification_level 0-3, status, dates | slug unique ; (account_class, status) ; wilaya |
| `company_members` | company_id, user_id, company_role (owner, manager, member) | (company_id, user_id) unique ; user_id |
| `company_activities` | company_id, activity_code | PK (company_id, activity_code) |
| `verifications` | company_id, level visé, documents (JSON : chemins privés), note, status (pending/approved/rejected), reviewer_id, decision_note, decided_at | (company_id, status) |
| `wilayas` | code (01-69), name_fr, name_ar, active | PK code |
| `notifications` | user_id, company_id, type, payload (JSON), read_at | (user_id, read_at) |
| `enquiries` | kind (listing/service), module, entity_id, to_company_id, from_user_id, nom, téléphone, e-mail, quantité, sujet, message, status | (to_company_id, status) |
| `audit_log` | user_id, company_id, action, objet, IP, data (JSON) — purge après 365 jours | action, company_id, created_at |
| `translations`, `media_links`, `listing_index` | Créées pour les Phases 4-6 (schéma du document d'architecture) | — |

Données hors tables : méta utilisateur `sm_phone`, `sm_locale`, `sm_otp` (code haché), `sm_contact_verified` ; option `sm_core_settings` (langue, matrice des droits, niveaux minimum, limites).

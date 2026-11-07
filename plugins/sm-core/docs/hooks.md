# sm-core — fonctions, filtres et actions

## Fonctions publiques (`includes/functions.php`)

| Fonction | Rôle |
| --- | --- |
| `sm_can( $cap, $company_id = null, $context = [] )` | `true` si l'entreprise (par défaut celle de l'utilisateur courant) a le droit |
| `sm_can_explain( … )` | `true` ou `WP_Error` (`sm_no_company`, `sm_suspended`, `sm_class_forbidden`, `sm_activity_missing`, `sm_verification_required`, `sm_plan_limit`) |
| `sm_current_company()` | Objet entreprise de l'utilisateur courant ou `null` |
| `sm_current_language()`, `sm_default_language()`, `sm_language_url( $code )` | Langue courante / par défaut / URL du sélecteur |
| `sm_url( $key, $args )` | URL propre : `listing` (`id`), `company` (`c`), `account` (`view`), et toute page du thème |
| `sm_route_arg( 'id' \| 'c' \| 'view' )` | Argument de route pour les gabarits |
| `sm_event( $name, $payload )` | Déclenche `sm_event` et `sm_event_{name}` |
| `sm_table( 'companies' )`, `sm_setting( $key )` | Nom de table préfixé, réglage |

## Droits (`sm_can`)

`buy`, `listing.products`, `listing.reuse`, `listing.buildings`, `rfq.create`, `rfq.offer`, `service.fabrication`, `service.engineering`, `service.request`. Les extensions en ajoutent avec `sm_capabilities`. Tout droit commençant par `buy`, `listing.`, `rfq.`, `order.` ou égal à `service.fabrication` est **refusé aux comptes ingénierie**, avant et après tous les filtres.

## Filtres

| Filtre | Utilisé par |
| --- | --- |
| `sm_entitlement( true, $cap, $company, $context )` | sm-subscriptions : renvoyer `WP_Error` pour refuser (quota, abonnement) |
| `sm_capabilities`, `sm_activities` | Ajouter des droits ou activités |
| `sm_listing_owner( 0, $module, $entity_id )` | Extensions d'annonces : ID de l'entreprise propriétaire (destinataire des demandes) |
| `sm_notification_types` | Ajouter un type : `subject`, `body`, `link` (callables), `email` (bool) |
| `sm_sms_provider` | Objet avec `send( $phone, $text )` → `true \| WP_Error` ; active le canal SMS |
| `sm_languages`, `sm_dashboard_view_slugs`, `sm_private_dir` | Langues, slugs de vues, dossier privé |
| Passerelle thème : `sm_theme_data_{company,companies,wilayas,account,enquiries,notifications,verification}`, `sm_theme_notices`, `sm_form_old`, `sm_form_action`, action `sm_form_fields` | Thème sm-marketplace |

## Actions

| Action | Quand |
| --- | --- |
| `sm_register_modules( $registry )` | Au démarrage : `$registry->register( 'sm-products', [ 'version' => …, 'requires_core' => '0.1.0' ] )` |
| `sm_core_loaded` | sm-core prêt |
| `sm_event` / `sm_event_{name}` | `account.registered`, `company.created`, `company.updated`, `company.verified`, `verification.submitted`, `enquiry.sent`, `notification.sent` |
| `sm_language_switched( $code, $locale )` | Changement de langue en cours de requête (e-mails) |
| `sm_dashboard_view( $view, $account )` | Le thème demande une vue de tableau de bord inconnue (extensions) |

## Formulaires (admin-post.php)

`sm_register`, `sm_login`, `sm_lost`, `sm_verify_code`, `sm_resend_code`, `sm_company_save`, `sm_settings_save`, `sm_verification_submit`, `sm_enquiry`, `sm_enquiry_status`, `sm_notifications_read`. Tous : nonce `_sm_nonce`, champ `sm_lang`, `redirect_to`, piège `sm_website`. Retour avec `?sm_n={jeton}` (messages + valeurs saisies, 10 minutes).

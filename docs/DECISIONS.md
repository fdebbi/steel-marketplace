# Décisions validées

Document d'architecture complet (Phase 0) : https://claude.ai/artifact/EWVZtQ3ZmHAx9U6ZVfyMmF

## 5 novembre 2026 — validation de la Phase 0

| # | Sujet | Décision |
| --- | --- | --- |
| 1 | Langue par défaut | Français |
| 2 | Chiffres en arabe | Chiffres occidentaux 0-9 |
| 3 | Source des poids de référence | Tables européennes normalisées des profilés |
| 4 | Hébergement | Hostinger |
| 5 | Paiements au lancement | Hors ligne (virement, CCP, BaridiMob), activés par l'administrateur |
| 6 | Documents de vérification | RC, NIF, NIS ; justificatifs d'ingénieur |
| 7 | Messagerie | Formulaires de demande d'abord, messagerie complète plus tard |
| 8 | Marque | Nom et logo plus tard ; couleurs principales modifiables depuis wp-admin |
| 9 | Buildzair | Plateformes séparées pour l'instant |

## 7 novembre 2026 — Phase 2

| Sujet | Décision | Raison |
| --- | --- | --- |
| Adresses par langue | Français sans préfixe, arabe sous `/ar/` (le document d'architecture prévoyait `/fr/` et `/ar/`) | Aucun lien existant cassé ; une URL = une langue, compatible avec le cache Hostinger |
| Wilayas | 69 wilayas (loi n° 26-06 du 4 avril 2026, décret n° 26-206 du 25 mai 2026, effectives au 1er janvier 2027), modifiables dans l'admin | Liste officielle à jour |
| Vérification niveau 1 | Code à 6 chiffres par e-mail tant qu'aucune passerelle SMS n'est choisie ; libellé « Coordonnées vérifiées » | Pas de fournisseur SMS retenu ; branchement prévu par filtre |
| Documents de vérification | Stockés au-dessus du dossier public quand c'est possible | Inaccessibles par URL quel que soit le serveur |
| Contact | Compte obligatoire pour contacter un vendeur ou demander une prestation (conforme Phase 0) ; demandes sur annonces de démonstration envoyées aux modérateurs | |

## 7 novembre 2026 — nom et style

| Sujet | Décision | Raison |
| --- | --- | --- |
| Nom | **ReuSteel** (domaine reusteel.com disponible selon le client), écrit « Reu » + « Steel » en couleur d'accent | Choix du client ; met en avant le réemploi du fabricant d'acier |
| Code | Les identifiants techniques restent `sm-*` (extensions, tables, fonctions) | Renommer le code n'apporte rien aux utilisateurs et casserait les installations |
| Animations | Courtes, discrètes, désactivées si l'appareil demande moins d'animations | Touche moderne sans gêner les téléphones modestes ni l'accessibilité |

## 7 novembre 2026 — Phase 4 (produits neufs)

| Sujet | Décision | Raison |
| --- | --- | --- |
| Catégories | Profilés, tubes, tôles, fers plats et barres (moteur de poids) ; toiture et bardage ; pièces de finition ; autres. Noms, unités et champs modifiables dans wp-admin | Remarque n° 1 du client ; liste à compléter sans redéveloppement |
| Quantité | En nombre de barres ou en poids total, l'autre valeur estimée par le moteur | Remarque n° 1 |
| Toiture | Prix et quantité au m², au ml ou à la pièce ; poids seulement s'il est pesé | Pas de table normalisée fiable pour les panneaux : aucun poids inventé |
| Calculs | Poids, totaux et prix au kg équivalent recalculés par le serveur à l'enregistrement | Le navigateur ne décide jamais d'un poids ni d'un prix |
| Modération | Activée par défaut, motif obligatoire pour un refus | Qualité des annonces au lancement |
| Numéro d'annonce | Index commun de sm-core | Adresses uniques quand réemploi et bâtiments arriveront |
| Durée | 90 jours, puis « expirée » avec notification ; republication en un clic | Annonces à jour |

## 7 novembre 2026 — Phase 3 (moteur de poids)

| Sujet | Décision | Raison |
| --- | --- | --- |
| Sources | Fiches de STAD et d'ArcelorMittal reproduisant l'EN 10365 / EN 10056-1, formule de l'EN 10219-2 contrôlée sur le catalogue Ferpinta | Documents publics et datés ; la norme elle-même est payante |
| Contrôle | Une ligne n'entre au catalogue que si cotes, aire et masse concordent avec une seconde source et avec le calcul ; 36 cornières sans seconde source écartées | Règle « aucune donnée inventée » |
| Poids | Calculé : aire exacte (à partir des cotes) × 7850 kg/m³, affiché comme théorique ; la masse imprimée est conservée pour contrôle | Une seule méthode pour tous les produits, cohérente avec les normes |
| Profilés laminés | Uniquement depuis le catalogue (pas de cotes libres) | Évite les profils inexistants |
| Tubes, fers plats, barres, tôles | Cotes libres dans des limites réglables | Dimensions commerciales très variées |
| Longueurs | 12 m par défaut, 6 m, ou longueur libre ; liste modifiable dans l'administration | Remarque du client ; en attente de confirmation des autres longueurs |
| Tubes | EN 10219 (formés à froid) | Les plus courants sur le marché algérien ; EN 10210 à ajouter si besoin |
| Corrections | Motif et source obligatoires, journal ; une mise à jour des données n'écrase jamais une correction | Traçabilité des poids des annonces |

## 7 novembre 2026 — carte de l'entreprise (remarque n° 2)

| Sujet | Décision | Raison |
| --- | --- | --- |
| Fournisseur de carte | OpenStreetMap avec la bibliothèque Leaflet hébergée dans le thème | Gratuit, sans clé ni compte de facturation (Google Maps exige une carte bancaire et facture au-delà d'un quota). Changeable plus tard par le filtre `sm_map_tiles` |
| Itinéraire | Lien « Itinéraire » vers Google Maps (ouvre l'application sur téléphone) | Pas d'API, pas de clé ; usage le plus courant en Algérie |
| Ouverture de la carte | Centre approximatif du chef-lieu de chaque wilaya, stocké en base et modifiable | Sert uniquement à ouvrir la carte au bon endroit ; la position exacte est placée par l'entreprise |
| Contrôle | Position refusée hors des limites de l'Algérie ; changement de wilaya = repère à replacer | Évite les positions erronées ou falsifiées |
| Confidentialité | Case « Afficher la carte sur ma page publique » (cochée par défaut) | Certaines entreprises (domicile, dépôt) peuvent préférer ne pas publier l'adresse exacte |
| Géolocalisation | « Utiliser ma position actuelle » ne fonctionne qu'en https (cas de Hostinger) ; la précision est affichée et le repère reste ajustable | Le GPS d'un ordinateur de bureau peut être approximatif |

## 7 novembre 2026 — cache

- Le cache de page Hostinger ignore les paramètres d'URL : le thème 0.1.1 marque comme non cachables les pages dépendant d'un paramètre et les pages de compte. Solution définitive en Phase 2 : adresses sans paramètres (`/ar/...`, `/annonce/302/`).

## 7 novembre 2026 — Phase 5 : marché du réemploi

| Sujet | Décision | Raison |
| --- | --- | --- |
| Architecture | Un seul moteur d'annonces (`sm-products` 0.2.0) pour plusieurs marchés ; `sm-reuse` ajoute le marché « réemploi » par filtres (permission, états, champs, catégories, règles, avis) | Photos, modération, recherche, fiche, tableau de bord et index écrits une fois ; désactiver `sm-reuse` retire le marché sans toucher aux produits neufs |
| Description d'un lot | Inventaire ligne par ligne (type, profil, pièces, longueur ; 30 lignes au plus) pour les profilés et tubes ; lot au poids pour tôles, lots mixtes et ferraille | Les chutes ont des longueurs différentes ; les tôles et la ferraille ne se décrivent pas profil par profil |
| Poids | Inventaire : calculé par le moteur de poids, ligne par ligne (théorique) ou pesé ; lot au poids : pesé ou **estimé**, affiché comme tel | Aucune donnée inventée ; l'acheteur voit l'origine du poids |
| Prix | Au kg, à la pièce ou pour le lot entier ; l'équivalent au kg est toujours calculé | Usage courant pour les chutes et la ferraille |
| États | Les cinq classes du cahier des charges ; la ferraille seulement dans la catégorie « Ferraille » et inversement | Un acheteur qui cherche de l'acier réutilisable ne tombe jamais sur de la ferraille |
| Ingénierie | Avis fixe sur chaque fiche de réemploi : vérification par un ingénieur qualifié avant tout usage structurel ; informations déclarées par le vendeur, non certifiées | « L'IA ne remplace jamais l'ingénierie » ; la plateforme ne garantit pas l'aptitude structurelle |
| Filtre longueur | Longueur minimale appliquée à la plus longue pièce du lot | Répond à « y a-t-il des pièces d'au moins X m ? » |
| Recherche multi-marchés | Servie par le moteur ; tant que le mode démo est actif, une recherche qui inclut les bâtiments (Phase 6) reste en démonstration | Évite une page de recherche à moitié vide pendant la revue |

## 7 novembre 2026 — Phase 6 : bâtiments métalliques

| Sujet | Décision | Raison |
| --- | --- | --- |
| Architecture | `sm-buildings` ajoute un marché et un **type de catégorie** (`building`) au moteur `sm-products` 0.3.0, qui expose pour cela un point d'extension | Photos, modération, recherche et tableau de bord restent communs ; le moteur ne connaît rien des bâtiments |
| Monté | Prix demandé global obligatoire ; prix au kg et au m² dérivés | Cahier des charges ; un bâtiment monté ne se vend pas au kilo |
| Poids d'un bâtiment monté | Estimé par le vendeur (affiché « estimé ») ou calculé depuis un inventaire facultatif | Aucun poids inventé ; l'acheteur voit l'origine |
| Démonté | Inventaire obligatoire par type d'élément, profil, quantité, longueur ; prix au kg par défaut | Cahier des charges ; la valeur d'un bâtiment démonté est son acier |
| Surface | Longueur × largeur, calculée | Base du prix au m² |
| Limites | Dimensions, portiques et lignes d'inventaire réglables dans l'administration | Aucune limite codée en dur |
| Visualisation | Schéma indicatif plan + façade tracé depuis les chiffres saisis, marqué « pas une étude structurelle » ; données de forme exposées pour le configurateur 3D | Préparer le configurateur sans présenter un dessin comme une conception approuvée |
| Ingénierie | Avis avant remontage sur tout bâtiment d'occasion | Un bâtiment remonté ailleurs doit être recalculé (vent, neige, fondations) |

## 8 novembre 2026 — Phase 7 : demandes de devis

| Sujet | Décision | Raison |
| --- | --- | --- |
| Description du besoin | Lignes de profils pesées par le moteur, ou description libre avec quantité, unité et poids s'il est connu | Les acheteurs d'acier raisonnent en profils et longueurs ; les autres besoins (bâtiment, pièces) restent possibles |
| Prix d'une offre | Total ou prix au kg ; le prix au kg affiché est toujours recalculé (total ÷ poids de l'offre ou de la demande) | Comparer des offres formulées différemment |
| Confidentialité | L'acheteur voit toutes les offres, chaque fournisseur la sienne, les visiteurs aucune ; plans réservés aux entreprises connectées | Secret des prix entre concurrents ; protection des plans |
| Une offre par entreprise | Renvoyer une offre la met à jour ; retrait possible | Éviter les doublons dans la comparaison |
| Décision | Accepter une offre attribue la demande, refuse les autres et prévient tout le monde ; la commande se conclut hors plateforme (paiement hors ligne) | Décision de la Phase 0 ; pas de paiement en ligne |
| Modification | Interdite dès la première offre (clôturer puis republier) | Les fournisseurs ont chiffré une demande précise |
| Délais | Date limite proposée (21 jours) et maximale (90 jours) réglables ; expiration quotidienne ; acceptation encore possible après la date limite | Pas de limite codée en dur ; l'acheteur garde la main |
| Plans | PDF et images seulement (DWG/DXF à exporter en PDF) | Contrôle fiable du type de fichier à l'envoi |

## 8 novembre 2026 — Phase 8 : services de fabrication et d'ingénierie

| Sujet | Décision | Raison |
| --- | --- | --- |
| Architecture | Une extension `sm-services` avec deux catalogues séparés par permission, plutôt que deux extensions | Pages, demandes, devis et références sont identiques ; la séparation fabrication / ingénierie tient dans les permissions et les pages publiques. Une scission reste possible sans changer les données |
| Séparation ingénierie / commerce | Un compte ingénierie ne publie que des services d'ingénierie et n'a toujours aucune permission commerciale ; les services ne passent pas par le moteur d'annonces `sm-products` | Cahier des charges : l'ingénierie reste séparée des annonces commerciales |
| Prix | « Sur devis » ou « à partir de » avec unité ; pas de prix au kg en ingénierie ; montant maximal réglable | Un prix indicatif aide à trier ; le prix réel est le devis |
| Zone d'intervention | Toute l'Algérie ou liste de wilayas ; la wilaya de l'entreprise compte toujours comme desservie | Recherche « qui intervient dans ma wilaya » |
| Demande de service | Une demande ouverte par client et par service ; limite quotidienne réglable ; plans PDF/images | Éviter le harcèlement d'un prestataire |
| Coordonnées | Le prestataire voit celles du client dès la demande ; le client voit celles du prestataire après acceptation du devis | Le prestataire doit pouvoir poser des questions ; le client décide quand il s'engage |
| Profil ingénieur | Expérience ajoutée à SM Core (donnée de l'entreprise) ; références dans `sm-services` | Le profil appartient au compte ; les références illustrent les services |
| IA | Rappel sur les pages d'ingénierie : aucun outil de la plateforme ne remplace un calcul ni la signature d'un ingénieur | « L'IA ne remplace jamais l'ingénierie » |

## 8 novembre 2026 — Phase 8.1 : e-mails et journal d'activité

| Sujet | Décision | Raison |
| --- | --- | --- |
| Emplacement | Module dans `sm-core` ; chaque extension déclare ses événements (filtre `sm_mail_events`) | Toutes les extensions en dépendent ; les phases futures s'ajoutent sans toucher au module |
| Déclencheur | Le journal d'audit existant : chaque entrée déclenche `sm_audit_logged`, rapprochée des événements | Une seule source de vérité pour le journal et les alertes ; une action non journalisée ne peut pas envoyer d'e-mail « fantôme » |
| Alertes administrateur | Par événement : immédiate, résumé quotidien ou non ; plusieurs adresses ; langue au choix | Être informé de tout sans être noyé (offres, annonces publiées, demandes de service en résumé par défaut) |
| Modèles | Texte simple FR/AR avec variables `{nom}`… ; un texte identique au défaut n'est pas enregistré | Modifiable sans HTML ni risque de casser la mise en page ; les améliorations du texte par défaut continuent de s'appliquer |
| Notifications existantes | Restent créées dans le tableau de bord ; leur e-mail devient activable et réécrivable (`{titre}`, `{message}`, `{lien}`) | Le texte d'origine dépend de chaque situation (montants, noms) ; l'administrateur peut l'entourer ou le remplacer |
| Envoi | `wp_mail()` + **WP Mail SMTP** (choix du porteur de projet) ; expéditeur contact@reusteel.com ; Reply-To = l'utilisateur concerné dans les alertes | Délivrabilité par un compte authentifié ; l'administrateur répond directement depuis sa messagerie |
| Journal des e-mails | Chaque envoi avec statut et erreur ; contenu conservé pour relecture et renvoi, sauf codes de vérification | Répondre à « je n'ai rien reçu » ; un code de vérification ne doit jamais rester lisible |
| Journal d'activité | Phrases lisibles, connexions réussies rangées sous « Sécurité », export CSV, conservation réglable (365 jours par défaut) | Lisible par un non-technicien ; les connexions noieraient le reste |
| Robustesse | Une erreur d'e-mail n'interrompt jamais l'action de l'utilisateur | L'inscription ou la publication ne doit pas échouer à cause d'un serveur SMTP |

## 8 novembre 2026 — Phase 9 : sm-ai

| Sujet | Décision | Raison |
| --- | --- | --- |
| Fournisseurs | Deux connecteurs : Anthropic (Messages) et « compatible OpenAI » (Chat Completions, URL de base réglable) ; fournisseur et modèle par service | Indépendance exigée par le cahier des charges ; le format OpenAI couvre de nombreux fournisseurs |
| Clés | Chiffrées en base, jamais réaffichées ; prioritaires si définies dans wp-config.php ; appels uniquement depuis le serveur | Sécurité des clés |
| Contrôle des réponses | Toute valeur proposée par l'IA est vérifiée contre la plateforme (catégories, familles, désignations du catalogue, nuances, wilayas) ; le reste est écarté ou marqué « à préciser » | L'IA ne doit pas inventer de données de référence |
| Poids | Jamais demandés à l'IA ni conservés depuis sa réponse : masse linéique du catalogue, poids du moteur de poids | « Never allow AI to invent or independently determine official profile weights » |
| Pré-remplissage | `Forms::prefill` (sm-core) : le formulaire existant s'ouvre rempli, l'utilisateur vérifie et envoie | Pas de publication automatique ; réutilise les formulaires et leurs contrôles |
| Coûts | Quotas par entreprise et par mois, limites par jour (utilisateur, visiteur par IP), budget mensuel calculé avec les prix saisis, journal des appels | Maîtriser la dépense avant les crédits payants (Phase 10) |
| Crédits | Filtre `sm_ai_can_use` prévu pour la Phase 10 | Brancher les crédits sans modifier sm-ai |
| Fichiers | Lus, réduits (1568 px) et transmis, jamais stockés | Confidentialité et place disque |
| Tests | Fournisseur « Simulation » (SM_AI_SIMULATION) et réponses enregistrées aux formats réels ; pas d'appel réel depuis l'environnement de développement | Les API externes ne sont pas joignables depuis l'environnement de test |

## 9–11 novembre 2026 — Phase 9.1 : modèle de comptes et ingénieurs partenaires

| Sujet | Décision | Raison |
| --- | --- | --- |
| Types de comptes | Quatre classes : **société** (types : réalisation de constructions métalliques, fabrication, fourniture et distribution, recyclage et ferraille, démontage et revente d'occasion), **professionnel** (métiers : charpentier métallique, monteur de charpente, poseur couverture et bardage, soudeur, chaudronnier, serrurier-métallier, levage, sablage et peinture), **client**, **ingénieur partenaire** | Demande du porteur de projet ; « maître d'ouvrage et promoteur » supprimé |
| Types et métiers éditables | Page SM → Types de comptes : libellés FR/AR, masquer, ajouter. Les codes restent fixes ; un type masqué reste sur les comptes qui l'ont | Pas de liste codée en dur ; pas de comptes cassés par un renommage |
| Achat / vente | Société, professionnel et client achètent et vendent ; les nouveaux types de société reçoivent les droits d'une entreprise de réalisation | Réponse 1 du porteur de projet |
| Comptes client | Non publics : pas de page entreprise, annonces affichées « Particulier » ; annonces toujours vérifiées (réglable) et au plus 5 annonces en ligne (réglable) | Éviter qu'un particulier se transforme en revendeur non déclaré ; limiter les abus |
| Ingénieurs | Plus de « compte ingénierie » à l'inscription : candidature séparée (« Devenir ingénieur partenaire ») avec diplôme et pièces, compte totalement distinct, validation par l'administrateur | Réponse 4 du porteur de projet |
| Missions | Toute demande d'étude ou d'inspection va à la plateforme : l'administrateur fixe le prix (devis au client), confie la mission à un ingénieur validé avec des honoraires internes ; la marge = devis − honoraires | Réponse 2 : la plateforme reste l'unique interlocuteur et fixe les prix |
| Confidentialité | Le client ne voit ni l'ingénieur ni ses honoraires ; l'ingénieur ne voit ni le nom ni le prix du client ; deux fils de messages séparés ; liste publique sans coordonnées | Le client ne contacte jamais l'ingénieur directement |
| Fichiers des missions | Dossier privé (hors des téléversements publics), liens signés, droits vérifiés à chaque ouverture ; les livrables ne sont visibles du client qu'après validation par la plateforme | Plans et notes de calcul sont des documents sensibles ; contrôle qualité avant livraison |
| Services d'ingénierie | Le catalogue « ingénierie » de sm-services est retiré ; les anciennes pages passent au statut « retired » (conservées, jamais affichées) | Une seule voie pour l'ingénierie : la plateforme |
| Migration | Base v5 : `commercial` → société (installer → réalisation, reuse_dealer → recyclage, fabrication → fabrication), comptes « maître d'ouvrage » seuls → client, `engineering` → ingénieur partenaire (à valider) ; matrice des droits remise aux valeurs par défaut | Les colonnes de la matrice ont changé |
| Photo / logo | Tous les comptes : logo ou photo (2 Mo), affiché dans l'en-tête, les cartes, les profils et la liste des ingénieurs | Demande 3 |
| Correctif langue | Après un e-mail envoyé dans une autre langue, la langue de la requête est rétablie explicitement | Un texte de domaine non chargé remettait les messages en anglais |

## 11 novembre 2026 — Profil public des entreprises

| Sujet | Décision | Raison |
|---|---|---|
| Badges | « Compte vérifié » = niveau 1 (e-mail confirmé par code) ; « Qualité vérifiée » = niveau 2 (registre / documents contrôlés par l'administrateur) | Demande du client |
| Ordre des blocs | Principal : Présentation, Réalisations, Services, Produits ; latéral : Informations, Localisation, Coordonnées, Partage | Design validé |
| Demande de devis | Un seul formulaire dans une fenêtre, le service cliqué est présélectionné ; même traitement serveur que la page service | Pas de logique dupliquée |
| Partage | Liens de partage simples (aucun script tiers chargé) | Vie privée et performance |
| Mobile | La barre d'actions fixe du design mobile n'est pas ajoutée : la barre d'onglets mobile du thème occupe déjà le bas de l'écran | Éviter deux barres superposées |

## 11 novembre 2026 — Phase 9.2 : annuaires, à la une, listes éditables, fichiers

| Sujet | Décision | Raison |
|---|---|---|
| Badges | « Entreprise vérifiée » / « Professionnel vérifié » remplacent « Qualité vérifiée » | Demande du client |
| Bandeau d'inscription | Affiché aux visiteurs non connectés seulement | Demande du client |
| À la une | Réglé par l'administrateur (libellé, date de fin, priorité) ; le futur module d'abonnements passera par le filtre `sm_company_featured` | Pas encore de module d'abonnements |
| Remplissage | Sans compte à la une (ou pas assez), derniers comptes vérifiés **avec logo** | Choix du client |
| Suppression d'un type / métier / service utilisé | L'administrateur choisit un remplaçant ; les comptes ou pages basculent, puis l'élément est supprimé | Choix du client ; aucun compte sans type |
| Fichiers enregistrés | Gérés dans chaque formulaire (annonce, service, réalisation, devis, logo) | Choix du client |
| Code dans l'e-mail de bienvenue | Un seul e-mail ; le code est masqué (••••••) dans le journal des e-mails | Ne jamais conserver un code |

## 11 novembre 2026 — Services : un service et une image par page

| Sujet | Décision | Raison |
|---|---|---|
| Règle de travail | Chaque remarque du client est d'abord analysée (bonne idée / à éviter) et validée avant le code | Demande du client |
| Un service par page | Liste déroulante ; anciennes pages : premier service gardé, sans avertissement (comptes de test) | Prix par service, filtres et recherche exacts |
| « Autre » | Publié aussitôt dans « Autres services » ; alerte à l'administrateur qui ajoute le nom à la liste (avec l'arabe) ou le rattache | Liberté pour l'entreprise sans désordre dans les filtres |
| Image | Une seule image par service ; les autres photos vont dans « Réalisations » | Cartes homogènes, publication plus simple |
| Profil | Carré « Informations » supprimé ; types en étiquettes sans titre | Informations en double |
| Titre d'un service | Le nom du service (FR/AR de la liste), plus de titre à saisir | Demande du client ; titres homogènes et bilingues |
| Doublons | Une seule page par service et par entreprise | Prix unique par service, recherche claire |
| Description | 15 caractères minimum, avec une aide pour inviter à détailler | Demande du client |
| Autres services | Jusqu'à 6 petites cartes en bas de la page de service + lien vers le profil | Demande du client |

## 11 novembre 2026 — Lot complet, modifications en attente, éditions par la plateforme, cloche

| Sujet | Décision | Raison |
|---|---|---|
| Mode de vente | Détail, lot complet ou les deux, avec deux prix ; profilés, tubes, tôles, lots, chutes, ferraille, hangars démontés | Demande du client ; usage courant du réemploi |
| Hangar « à la pièce » | Prix à la pièce ou au kg au détail ; le prix au kg reste le plus parlant car les éléments diffèrent | Éléments de tailles différentes |
| Modification d'une annonce en ligne | Immédiat : prix, mode de vente, quantités, vendu/pause. Vérifié : textes, photos, fiches, données techniques ; la version publiée reste en ligne | Choix du client ; éviter de vendre un lot déjà vendu |
| Modification mixte | Si une modification contient du texte/photo/technique, toute la modification attend la validation | Cohérence de l'annonce publiée |
| Édition par l'admin | Écran dans l'admin WP (titre, description, photos, fiches ; services : textes et image), motif obligatoire, notification + e-mail au vendeur, journal | Demande du client |
| Cloche | 8 dernières notifications, « Tout marquer comme lu », « Voir toutes » ; sur mobile la cloche n'est pas dans l'en-tête | Validé par le client |


## 13 novembre 2026 — Page Ingénierie : les services, plus l'annuaire

| Sujet | Décision | Raison |
|---|---|---|
| Annuaire des ingénieurs partenaires | Plus affiché sur la page Ingénierie ni dans le formulaire de demande ; code gardé, réglage « Afficher aussi la liste des ingénieurs partenaires sous les services » (désactivé) | Demande du client ; le client traite avec la plateforme |
| Services d'ingénierie | 6 services (Études et Conception, Note de Calcul, Dessin et Modélisation 2D/3D, Expertise et Inspection Technique, CCTP/DQE/BPU, Suivi et Assistance Technique) ; titre, image, prestations (FR/AR), ordre et affichage modifiables dans Réglages ingénierie ; ajout possible | Demande du client |
| Image par défaut | Dessin du thème (pas de photo d'internet) tant que l'administrateur n'a pas choisi d'image | Droits d'image ; page complète dès l'installation |
| Formulaire de demande | Le client choisit un service puis coche au moins une prestation ; les prestations cochées sont copiées avec la demande | Devis plus précis |
| Ingénieurs partenaires | Ils cochent les services qu'ils réalisent (« Services proposés », pas « spécialités ») ; l'administrateur voit « service » à côté des ingénieurs qui proposent celui de la mission | Choix du client |
| Anciens types d'étude | Cachés mais gardés : les anciennes missions et les anciens profils gardent leur nom | Ne rien perdre |
| Prix | Aucun prix par service : la plateforme envoie un devis | Pas de prix inventé |
| Lien partenaires | Lien discret « Devenir ingénieur partenaire » en bas de page | Choix du client |
| Arabe | Traduction des services et prestations à relire par un ingénieur arabophone | Termes techniques |

## 13 novembre 2026 — Ingénierie : plusieurs services, offre détaillée, avance

| Sujet | Décision | Raison |
|---|---|---|
| Plusieurs services | Une seule demande, une référence, un seul ingénieur pour toute la mission ; prestations cochées par défaut | Choix du client |
| Type de projet | Liste obligatoire modifiable (FR/AR), « Autre » en dernier | Évaluer la demande ; aucun client bloqué |
| Budget indicatif | Supprimé du formulaire (gardé pour les anciennes demandes) | La plateforme fait l'offre |
| Prix | Un prix HT par service ; TVA du réglage de paiement figée sur l'offre ; TTC si TVA, sinon HT | Choix du client |
| Avance | 30 % par défaut, réglable et modifiable par offre, calculée sur le total | Choix du client |
| Validité | 15 jours ; rappel la veille | Choix du client |
| Avance non payée | Rappel client + e-mail admin après 3 jours ; annulation à la fin de l'offre (sauf preuve en vérification) | Choix du client |
| Paiement | Commandes de SM Subscriptions (virement, CCP, BaridiMob, preuve, facture) ; pas d'ingénieur avant l'avance | Un seul circuit de paiement |
| Solde | Payé à la livraison ; documents téléchargeables après le solde | Protège la plateforme |
| Tâche quotidienne | WP-Cron ; une tâche planifiée chez l'hébergeur est conseillée | WP-Cron ne tourne qu'avec des visites |

## 13 novembre 2026 — Candidature d'ingénieur partenaire en étapes

| Sujet | Décision | Raison |
|---|---|---|
| Forme | Assistant en 5 étapes, fenêtre de taille fixe sur ordinateur, plein écran sur téléphone, adresse propre | Lisible, lien partageable, pas de perte au « retour » |
| Création du compte | Seulement après le code reçu par e-mail ; rien n'est créé si la personne abandonne | Adresses réelles, pas de comptes à moitié créés |
| Brouillon | Textes gardés sur l'appareil ; jamais le mot de passe ni les documents | Confidentialité |
| BET | Même compte que l'ingénieur ; nom du bureau ; agrément MHUV obligatoire pour un BET seulement | Choix du client |
| Titre et spécialité | Listes modifiables (FR/AR) avec « Autre (à préciser) » | Profils homogènes, aucun candidat bloqué |
| Établissement, année, n° d'agrément | Supprimés | Choix du client ; le diplôme suffit à la vérification |
| Documents | Tous obligatoires ; photos compressées avant l'envoi | Choix du client ; éviter les fichiers trop lourds |

## 13 novembre 2026 — Compte de l'ingénieur partenaire : verrouillage et approbation

| Sujet | Décision | Raison |
|---|---|---|
| Pendant la vérification | Profil en lecture seule | L'admin vérifie exactement ce qui a été envoyé |
| Après validation | Toute modification du profil est une demande approuvée par l'admin ; l'ancien profil reste actif en attendant | Choix du client |
| Sans approbation | Disponibilité, téléphone, mot de passe, langue | Choix du client ; éviter les demandes inutiles |
| Photo ou logo | Obligatoire à l'inscription | Choix du client |
| Bouton « Demander une étude » | Masqué partout pour un ingénieur connecté ; visible pour les visiteurs et les autres comptes | Un partenaire réalise les études |
| Disponibilité | Bouton en haut de la barre latérale, disponible par défaut, effet immédiat | Choix du client |

## 13 novembre 2026 — Menu d'un membre connecté et cache de pages

**Constat.** Sur le site en ligne, un membre connecté a vu « Se connecter » et « Créer un compte » dans le menu mobile. Le cache de pages de l'hébergeur (LiteSpeed) lui avait servi la page d'accueil faite pour un visiteur. Vider le cache a réglé le cas, sans empêcher qu'il revienne.

**Décision (choix laissé par le propriétaire : « la meilleure expérience pour l'utilisateur »).** Deux protections ensemble, dans le thème 0.30.1 :

1. Le thème demande à LiteSpeed de garder une version à part pour les membres connectés. Non vérifiable hors de l'hébergeur : à contrôler en ligne.
2. La page se vérifie elle-même à l'ouverture et corrige le menu si le cache s'est trompé. Cette protection ne dépend d'aucun réglage de l'hébergeur ; elle est testée (`tests/e2e-cache.py`).

**Écarté.** Ne plus mettre aucune page en cache : le site serait plus lent pour tous les visiteurs. Envoyer `Vary: Cookie` : les caches garderaient une version par visiteur, donc presque plus de cache.

**Limite connue.** Seuls le menu du haut et le bas du menu mobile sont corrigés. Le reste d'une page servie par erreur reste celui d'un visiteur jusqu'à la page suivante.

## 13 novembre 2026 — Publier un bâtiment métallique : étapes, poids, carte et visite

**Demande du propriétaire.** Ordre des étapes : type, géométrie, système porteur et enveloppe, éléments de la structure, état / poids / prix / ce que comprend le prix / titre automatique, photos et fiche technique, localisation (wilaya et carte). Maquettes présentées et validées avant le code.

**Décisions validées.**

- État : on garde l'existant — bâtiment monté ou démonté, et état de l'acier (neuf, excellent, bon, à inspecter).
- Poids (choix B1) : le poids des éléments déclarés est calculé ; le vendeur peut ajouter le poids total s'il le connaît. Raison : avec les seuls poteaux et traverses, le poids est plus bas que la réalité et le prix au kg serait faussé. L'acheteur voit lequel des deux poids est utilisé.
- Carte (proposition du propriétaire) : le vendeur place le bâtiment exactement ; case « Afficher la carte au public ». Carte masquée : le public voit l'adresse sans carte et peut demander une visite avec date et heure ; après accord du vendeur, la carte est montrée à ce demandeur.
- Demande de visite : pour les bâtiments et les ventes en lot. Compte obligatoire (il faut savoir à qui montrer la carte). Le vendeur peut aussi proposer une autre date.
- Mezzanine : option oui / non, surface facultative. Étages : 1 par défaut.
- Poteaux + traverses obligatoires ; avec « Ferme en treillis », poteaux + fermes.

**Choix faits pendant le codage, à confirmer.**

- **PRS et « Autre »** : un profilé reconstitué soudé n'existe pas dans les tables de profilés du site ; on ne peut donc pas le déclarer comme élément. Pour ces deux systèmes, les éléments sont facultatifs et le poids total devient obligatoire. Une ligne « PRS » avec ses dimensions (hauteur, largeur, épaisseurs) pourrait être ajoutée plus tard.
- **« Vente en lot »** est compris comme : toute annonce vendue « lot entier » ou « détail et lot », quel que soit le marché.
- La carte reste visible pour l'acheteur accepté jusqu'à 2 jours après la date de la visite.
- Titre : « Type largeur × longueur m — hauteur — système — wilaya » (comme la maquette).
- Les annonces de bâtiments publiées avant cette version restent en ligne ; à la prochaine modification, le vendeur devra compléter la hauteur de toiture, les éléments, la commune et la position.

**Non vérifié ici.** L'affichage des fonds de carte OpenStreetMap (pas d'accès réseau dans l'environnement de test) : la carte, le repère et les positions sont testés, pas les images du fond.


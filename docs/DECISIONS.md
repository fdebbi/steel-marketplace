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

**Choix faits pendant le codage, confirmés par l'utilisateur le 2026-11-14** (sauf la durée de la carte, changée).

- **PRS et « Autre »** : un profilé reconstitué soudé n'existe pas dans les tables de profilés du site ; on ne peut donc pas le déclarer comme élément. Pour ces deux systèmes, les éléments sont facultatifs et le poids total devient obligatoire. Une ligne « PRS » avec ses dimensions (hauteur, largeur, épaisseurs) pourrait être ajoutée plus tard.
- **« Vente en lot »** est compris comme : toute annonce vendue « lot entier » ou « détail et lot », quel que soit le marché.
- La carte reste toujours visible pour l'acheteur dont une visite a été acceptée (décision de l'utilisateur ; la première version la retirait 2 jours après la visite). Il la perd seulement s'il annule sa visite ou si le vendeur la refuse ensuite. sm-products 0.12.1.
- Titre : « Type largeur × longueur m — hauteur — système — wilaya » (comme la maquette).
- Les annonces de bâtiments publiées avant cette version restent en ligne ; à la prochaine modification, le vendeur devra compléter la hauteur de toiture, les éléments, la commune et la position.

**Non vérifié ici.** L'affichage des fonds de carte OpenStreetMap (pas d'accès réseau dans l'environnement de test) : la carte, le repère et les positions sont testés, pas les images du fond.

## Bâtiment métallique : pont roulant, éléments proposés, prix au kg (2026-11-14)

**Demandé par l'utilisateur, validé sur maquette** (`Design/0.32`). sm-buildings 0.3.0, sm-products 0.13.0, thème 0.32.0.

- **Pont roulant** : case à cocher, capacité (t) facultative.
- **Éléments proposés automatiquement**, pour faciliter la déclaration. Règles données par l'utilisateur (ingénieur) ; ce sont des ordres de grandeur, pas un calcul de structure :
  - Poteaux : IPE de hauteur = largeur (m) × 20 ; avec pont roulant, HEA de hauteur = largeur × 18. Nombre = portiques × 2. Longueur = hauteur au poteau.
  - Traverses : IPE de hauteur = largeur × 20. Deux versants : portiques × 2, longueur = largeur / 2. Un versant : portiques, longueur = largeur.
  - Pannes : IPE 120, ⌈largeur / 1,2⌉ × (portiques − 1). Lisses : UPN 120, 2 × ⌈hauteur au poteau / 1,2⌉ × (portiques − 1).
  - Contreventements : 32 × L 80x80x8 de 6 m.
- **Longueur des pannes et lisses** = longueur totale / (portiques − 1), c'est-à-dire l'entraxe réel (décision de l'utilisateur ; 6 m fixe sous-estimait le poids dès que l'entraxe dépasse 6 m).
- **Dimension la plus proche** dans les tables ; au-delà de la plus grande (IPE 600 → largeur > 30 m ; HEA 1000 → largeur > 55 m) la dimension reste vide et le vendeur choisit, plutôt que proposer un profilé faux.
- **Seulement pour « Portique poteau / traverse »** : PRS, ferme en treillis et « Autre » ne génèrent aucune pièce (décision de l'utilisateur). Dans le portique : pas de traverses pour une toiture multi-travées, cintrée ou autre ; pas de lisses sans bardage.
- **Ne jamais écraser la saisie** : la proposition suit les dimensions tant qu'aucune ligne n'a été modifiée (signature des lignes, champ `lines_auto`). Dès qu'une ligne change, plus rien n'est remplacé sans le bouton « Recalculer » et sa confirmation. Une annonce existante n'est jamais recalculée seule.
- **Réglages** : coefficients, pas et profilés sont dans SM → Bâtiments, pas dans le code.
- **Prix** : au kg ou pour le bâtiment entier, monté comme démonté. Quand le poids vient des seuls éléments déclarés, le total est affiché « Total indicatif » (formulaire et annonce).
- **Affichage** : poids « x t — (y kg) » ; cartes « Monté » / « Démonté » sans phrase sur la vente ; colonne « Nbr pièces ».

## Bâtiment métallique : vente en lot entier ou par élément, fiche refaite, inspection (2026-11-14)

Décisions du propriétaire de la plateforme, validées sur maquette avant le code.

- **Un bâtiment s'achète en lot entier.** Le prix affiché en grand est le total ; le prix au kg, dessous, est un équivalent qui sert à comparer avec l'acier neuf. « Total indicatif » est supprimé : le total est le prix de vente.
- **Mode de vente choisi par le vendeur à la publication** : « Lot entier », coché par défaut, et / ou « Par élément », pour un bâtiment monté comme démonté.
- **Par élément** : l'acheteur ne saisit pas de poids. Il choisit des éléments de l'inventaire et leur nombre ; le site calcule le poids avec les tables de profilés, puis le total avec le prix au kg. Une offre par élément se fait au kg.
- **Un seul prix au kg pour tous les éléments** (pas de prix par type d'élément).
- **Après la première vente par élément, la vente en lot entier se ferme** : le bâtiment n'est plus complet. L'inventaire diminue à chaque vente.
- **Prix liés** : le vendeur saisit le prix au kg ou le total, le site calcule l'autre à partir du poids d'acier de l'annonce. Arrondis : total au dinar inférieur ; prix au kg au centime supérieur (les éléments achetés un par un ne coûtent jamais moins que le bâtiment entier).
- **Comparaison avec l'acier neuf** : affichée seulement quand l'indice des prix du site a une valeur. Aucun prix n'est inventé.
- **Fiche** : localisation en haut ; caractéristiques géométriques et schéma sous les photos, le reste au clic ; inventaire résumé (pièces, poids), détail au clic ; demande d'inspection sous la localisation.
- **Inspection** : « ingénieur vérifié sur ReuSteel », pas « certifié » (la plateforme vérifie des comptes, elle ne délivre pas de certification). La demande part à l'administrateur de la plateforme, qui établit le devis et désigne l'ingénieur ; l'acheteur ne choisit pas l'ingénieur.
- **Carte** : pour les visiteurs, carte de la wilaya sans repère (centre approximatif de la wilaya : il n'existe pas de coordonnées par commune) ; la position exacte reste réservée au vendeur, à la plateforme et à l'acheteur dont la visite est acceptée.
- **Annonces déjà publiées** : un bâtiment monté au kg, ou vendu au kg sans inventaire, devient une vente en lot entier à son total.

## Fiche des profilés et inspection (2026-11-14)

Décisions validées sur la maquette 0.34.

- **Demande d'inspection d'un bâtiment** : le type de projet est pré-sélectionné d'après la catégorie (kits et structures préfabriqués → « Autre »). L'acheteur peut le changer.
- **Demande de visite** : réservée aux comptes connectés (un visiteur est envoyé vers la connexion), comme avant. Le formulaire date / heure / message existait déjà.
- **Fiche des profilés** : même construction que le bâtiment. Pour les profilés, le **prix au kg reste en grand** (on achète une quantité), avec l'équivalent d'une barre ; le lot complet vient en second.
- **Achat en nombre de barres** quand l'annonce est en barres ; le site calcule le poids et le total. Une offre sur des barres se fait au kg. Sans nombre de barres (vente au poids), la quantité reste en kg.
- **Réemploi** : l'état remplace « Neuf », les photos passent avant la fiche de section, et l'encadré « Faites contrôler cet acier avant d'acheter » (ingénieur vérifié, désigné par la plateforme) apparaît sous la localisation. Pas d'encadré pour la ferraille ni pour l'acier neuf.

## Vue d'ensemble du compte (2026-11-14)

Validé sur la maquette 0.35.

- **Montant vendu / acheté** = commandes et offres **acceptées** sur ReuSteel. Le paiement se fait hors plateforme : la page le dit, ce ne sont pas des sommes encaissées.
- **Poids** : enregistré sur chaque commande à partir de la version 0.4.0 de SM Trade ; estimé pour les commandes passées quand l'annonce le permet. Les ventes sans poids d'acier (tôles au m²) comptent dans les montants seulement, avec une note.
- **Acier en vente** : les annonces en ligne du compte seulement (pas le total du marché).
- **Les blocs vente et achat sont toujours affichés**, à zéro s'il n'y a rien, pour tous les comptes acheteurs et vendeurs.
- **Période par défaut : 12 mois.** Les courbes sont dessinées par le thème, sans bibliothèque externe.

## Dimensions courantes, tôles par type, sources (2026-11-15)

Validé sur la maquette 0.36.

- **Listes de dimensions regroupées avec recherche** pour les tubes carrés, rectangulaires et ronds, et pour les fers plats ; carrés et ronds pleins en liste simple. « Autre dimension » garde la saisie libre.
- **Tôles** : 8 types (noire, laminée à froid, galvanisée, larmée / striée — même tôle en Algérie —, perforée, inox, alu à damier, autre). 4 formats suffisent (1000 × 2000, 1250 × 2500, 1500 × 3000, 2000 × 6000), plus un format personnalisé.
- **Vente à la feuille** : le poids d'une feuille est estimé, le vendeur peut le remplacer par le poids réel ; c'est ce poids que voit l'acheteur. Pour la larmée et l'alu à damier, l'estimation vient du poids au m² publié par un négociant ; les autres épaisseurs sont calculées.
- **Sources** : aucune source de données (nom de catalogue, lien) n'est affichée sur le site, pour tous les produits ; la norme reste affichée. Les sources restent dans l'administration et `docs/VERIFICATION.md`.

## Réemploi comme le neuf, boulonnerie, lots groupés (2026-11-15)

Validé sur la maquette 0.37.

- **Réemploi = mêmes catégories que le neuf** (profilés, tubes, tôles, fers plats et barres, boulonnerie) plus la ferraille. Chutes et démontage deviennent l'origine. Les anciennes catégories ne sont plus proposées ; leurs annonces restent jusqu'à expiration.
- **Une dimension par annonce, plusieurs longueurs** (tôles : plusieurs formats) avec leur nombre ; ou au poids total pour du vrac.
- **Ordre** : géométrie → origine → état de l'acier → état de surface → prix → ce que le prix inclut (chargement, transport, découpe). Les photos restent dans leur étape (après le prix) : la partie technique se met à jour en direct et perdrait les fichiers choisis.
- **2 photos au minimum** en réemploi ; elles s'affichent en premier sur la fiche.
- **Boulonnerie** en neuf et en réemploi : boulons, tiges filetées, écrous, rondelles, tiges d'ancrage. Pas de calcul de poids (le vendeur le donne s'il vend au kg). Boulons précontraints en réemploi : avertissement (EN 1090-2). Vente à la pièce ou au kg (pas à la boîte pour l'instant).
- **Lots groupés (réemploi seulement)** : option A, les produits regroupés ne sont plus vendus séparément ; prix proposé = somme, modifiable ; même wilaya ; l'inventaire du lot montre l'état de chaque produit.


## Achat par barres, contact en fenêtre, comptes obligatoires (2026-11-15)

- Réemploi en plusieurs longueurs : on achète des barres choisies dans l'inventaire (nombre plafonné au stock de chaque longueur) ou le lot complet, jamais des kilos libres.
- Aucun achat, aucune offre, aucun message sans compte, sur tous les marchés. Les commandes et messages partent avec le nom, le téléphone et l'e-mail du compte : pas de champs à remplir.
- Aucune coordonnée (téléphone, e-mail) affichée publiquement ; « Contacter » ouvre une fenêtre de message partout. Les coordonnées du vendeur ne sont données qu'après l'acceptation d'une commande.
- « Mes annonces » est séparé par marché ; le regroupement en lot n'est proposé que dans l'onglet Réemploi.

## Demande d'étude détaillée (2026-11-15)

- Une demande = une partie : Étude technique (Conception, Note de calcul, Plans 2D/3D, CCTP-DQE-BPU), Suivi et assistance technique, ou Expertise et inspection technique.
- Le suivi d'un projet se demande après la livraison de l'étude, en reprenant ses dimensions.
- Le client ne choisit jamais les charges, les profilés ni les zones sismique / vent / neige : l'ingénieur les fixe d'après la commune (RPA, RNV).
- Obligatoires pour une étude : type, largeur, longueur, hauteur des poteaux (niveaux pour un bâtiment à étages) ; le reste est facultatif, avec « Je ne sais pas ».
- Schéma en 2D seulement (vue de face + vue en plan), marqué « indicatif » ; pas de 3D.
- Expertise : 2 photos minimum.

## Demande d'étude : enveloppe, portes, niveaux, localisation (2026-11-15)

- Couverture et bardage séparés ; bardage mixte = mur en bas (hauteur donnée) + bardage au-dessus ; « Ouvert » possible.
- Une porte se place au centre d'une travée (jamais sur un portique). Les 4 côtés sont fermés par défaut. Sans entraxe donné, les travées sont comptées à 6 m.
- Bâtiment à niveaux : chaque étage a sa hauteur et son usage.
- Commune obligatoire, choisie dans la liste de la wilaya (texte libre pour les nouvelles wilayas 59-69 tant que leur liste n'est pas publiée). Repère sur la carte facultatif, jamais public.
- Plus de date « souhaitée » dans la demande d'étude.

## Communes et cartes (2026-11-15)

- Partout où il y a une wilaya et une carte, la commune se choisit dans la liste de la wilaya et la carte se déplace sur la commune. Le repère n'est jamais placé automatiquement : l'utilisateur le pose lui-même.
- Source des communes : geoalgeria (69 wilayas, nouveau découpage seulement, option B). Mention « © OpenStreetMap contributors » pour les coordonnées issues d'OSM (ODbL).
- Annonce sans repère précis : la carte publique montre la commune (déjà publique), pas un point exact.

## Échéances des études (2026-11-18)

- L'ingénieur a 24 h pour répondre ; rappel 4 h avant la fin. À la fin des 24 h, la plateforme est prévenue et choisit de changer ou non : pas de retrait automatique, l'ingénieur peut accepter tant qu'il n'est pas remplacé.
- Le délai de l'étude de l'ingénieur part de son acceptation ; rappel 3 jours avant la fin ; en retard, l'ingénieur et la plateforme sont prévenus.
- Le client voit la date de livraison de son devis, calculée au premier démarrage de l'étude, qui ne change pas si l'ingénieur change. Il ne voit jamais le délai de l'ingénieur.
- Tâche horaire (WP-Cron) : sur l'hébergement, un vrai cron doit appeler wp-cron.php pour que les rappels partent à l'heure même sans visite.
- Les e-mails restent courts (référence, projet, missions, lieu, bouton) ; la position exacte et les fichiers ne sont que sur la page de la mission.
- Le schéma indicatif appartient à SM Engineering (site et administration), le thème ne fait que l'afficher.

## Schémas des profilés cotés (2026-11-18)

- Les schémas affichent les vraies cotes de la dimension choisie (hauteur, largeur, diamètre) pour tous les profilés, et l'épaisseur de paroi pour les tubes et les cornières (« t = 2 mm »). Pas de lettres à côté des valeurs : plus lisible sur les petits tubes et sur mobile.

## Messages au centre, cases manquantes, total des offres (2026-11-18)

- Tous les messages (questions, réussite, avertissements, erreurs) s'ouvrent au centre de l'écran au style de la plateforme, sur le site et dans l'administration ReuSteel. Les messages de réussite se ferment seuls après 4 secondes ; les autres attendent un clic. Sans JavaScript, les bandeaux restent.
- Informations manquantes : on emmène l'utilisateur à la case (défilement, curseur, case entourée, étape rouverte).
- Offre au kg : le total s'affiche pendant la saisie ; le fournisseur peut le modifier ou l'arrondir au millier ; l'acheteur reçoit ce total et le prix au kg recalculé.

## Rangée « Outils » et repère IA (2026-11-19)

- Sur l'accueil sombre, la demande de devis est le **seul carré clair**, remonté juste sous les marchés : c'est l'action principale d'un acheteur.
- Les outils IA portent tous le même repère « ✦ IA », dans une couleur réservée (violet → bleu) distincte du vert de la plateforme. Il n'apparaît que si le module IA est actif et seulement sur ce qui passe par l'IA (jamais sur le moteur de poids).
- Le carré IA ne promet que les 4 outils existants et rappelle que ce sont des suggestions à vérifier ; aucun compteur n'est affiché sans vraies données.

## Outils IA : page publique, crédits, feuille de route (2026-11-19)

- « Essayer l'assistant » ouvre une page publique des outils IA. Visiteur : description visible, « Connectez-vous pour utiliser cet outil ». Membre sans crédit : « Améliorez votre formule… ou achetez un pack de crédits ». Membre avec crédits : il utilise l'outil.
- Tous les outils IA consomment des crédits (1 par utilisation), disponibles à l'achat pour tous. La formule gratuite n'inclut plus de crédits (les crédits du mois en cours restent jusqu'au 1er du mois suivant).
- Exceptions : la **recherche intelligente** est gratuite pour tous (limites par jour) ; le **Rapport Business** est réservé à la formule Business et ne consomme pas de crédit.
- Feuille de route validée : (1) page publique, recherche gratuite, rédaction de la description ; (2) optimiseur de découpe — calcul exact, gratuit, ouvert à tous, pas d'IA — puis DQE IA (articles de charpente extraits par l'IA, barres de 12 m et poids calculés par le moteur) ; (3) reconnaissance de profilé au début de la publication, état de l'acier proposé par l'IA pour le réemploi (suggestion à confirmer) ; (4) Rapport Business.
- DQE à partir d'un plan : remplacé pour l'instant par une mission d'ingénieur (fiabilité).
- Règle interne du Rapport Business (jamais affichée telle quelle) : prix affichés et comptages seulement, au moins 5 sources par moyenne, aucune donnée d'utilisateur ni montant de transaction.

## Optimiseur de découpe (2026-11-19)

- Calcul exact, pas d'IA : gratuit, ouvert à tous, même résultat à chaque fois. Objectif : le moins de barres neuves ; les chutes déclarées en stock sont utilisées en premier.
- Valeurs par défaut validées : barres de 12 m (6 m ou autre au choix), trait de scie 3 mm, chute réutilisable dès 1 m.
- Le plan s'imprime, se télécharge en PDF et peut devenir une demande de devis (barres à acheter). Le DQE IA alimentera la liste des pièces (étape 2, suite).

## Optimiseur : marché, puis stock (2026-11-19)

- Ordre de recherche validé : stock de l'entreprise (étape D, avec le Magasin), puis marché ReuSteel, puis barres neuves (demande de devis). Pour chaque pièce : le même profilé, puis la longueur égale ou la plus proche au-dessus ; à égalité, l'annonce qui laisse le moins de chutes puis la moins chère au kg.
- Filtres du marché : nuance minimale, états acceptés (par défaut neuf, surplus neuf, excellent, bon ; « à inspecter » à cocher ; ferraille jamais), wilaya, lots entiers seulement si utiles (le reste du lot est signalé).
- Le résultat est une photo du marché : rien n'est réservé ; prix = prix affiché par le vendeur ; nom du vendeur public, jamais ses coordonnées.
- PDF : pied de page « Plan de coupe établi avec l'Optimiseur de découpe ReuSteel - reusteel.com/optimiseur-decoupe ».
- Magasin (stock) validé pour plus tard (étape C) : une fiche unique article de stock / annonce, « Publier sur le marché » oui ou non, deux magasins (neuf, réemploi), baisse automatique du stock après une vente acceptée (corrigeable).


## Magasin (stock) (2026-11-19)

- Une seule fiche article de stock / annonce ; « Sur le marché » oui ou non (statut `stock` = invisible des acheteurs). Deux magasins : neuf et réemploi. Les ventes acceptées sont déduites automatiquement.
- Repère chiffré automatique par entreprise (N-0042 neuf, R-0042 par variante du réemploi), modifiable ; emplacement facultatif ; étiquettes PDF avec QR code. Le repère est visible du vendeur (et de l'acheteur après acceptation : à venir).
- Prix pour tout un type (ex. 135 DA/kg pour tous les IPE) : au kg ou en %, aperçu, exceptions décochables, commandes et offres en cours inchangées, un seul e-mail à l'administration.
- Limite par formule en tonnes (publié ou non) ; outils (prix par type, étiquettes) réservés aux formules payantes. Entreprise : Découverte 20 t, Pro 500 t, Business illimité ; Professionnel : Gratuit 10 t, Artisan Pro 100 t, Premium 300 t. Magasin plein : refus d'ajout, rien n'est supprimé.
- Les professionnels ont aussi un magasin ; un article gardé en stock seulement a un prix facultatif.

## Optimiseur : Mon magasin (2026-11-20)

- Ordre : chutes saisies et magasin de l'entreprise, puis marché ReuSteel, puis barres neuves. « Mon magasin » coché par défaut (entreprises et professionnels) ; options neuf / réemploi / ne pas prendre les articles publiés.
- Le calcul ne sort rien du stock : bouton « Sortir n barres du magasin » une fois la coupe faite (mouvement « Sortie pour découpe » avec le repère). Un article publié baisse d'autant et passe en vendu s'il ne reste rien.
- Chutes réutilisables : « Ajouter à mon magasin réemploi », en stock seulement, repères R- automatiques ; état et nuance de la barre d'origine (neuf → surplus neuf), « Bon » pour les barres achetées ; capacité de la formule vérifiée.
- Repère visible du vendeur (avec l'emplacement) et de l'acheteur après acceptation (sans l'emplacement) sur le détail de la commande.
- Article en stock seulement : prix facultatif ; prix et photos demandés au moment de publier.

## Optimiseur : point 4 (2026-11-20)

- Comptes avec magasin : plus de « Chutes déjà à disposition » (doublon avec Mon magasin, risque de compter deux fois) ; lien « Ajouter des chutes au magasin ». Visiteurs et clients : point 4 conservé, « Mon magasin » visible mais désactivé. Lien partagé avec des chutes saisies : affichées.

## DQE IA (2026-11-20)

- Documents seulement (DQE, devis, bon de commande, métré, marché) en Excel, PDF ou photo ; les plans viendront plus tard.
- Rien n'est ajouté sans l'utilisateur : lignes « sûres » cochées, « à vérifier » décochées (profilé absent du catalogue, longueur ou nombre absent, ligne déduite par l'IA, source photo).
- Tôles et platines : extraites dans une liste séparée, poids par le moteur, vers la demande de devis (pas dans le plan de coupe des barres ; optimiseur de tôles plus tard). Quantités en mètres : barres à acheter.
- 1 crédit à la lecture, toujours utilisé (même si la photo est illisible ou si l'utilisateur annule). Le fichier n'est pas gardé.

## Lire un document (DQE IA) : un outil, trois destinations (2026-11-20)

- Un seul outil « Lire un document (DQE IA) » remplace « Analyser un document » : optimiseur de découpe, demande de devis (pré-remplie, publiée par l'utilisateur), magasin (en stock seulement ; documents adaptés : bon de livraison, facture, inventaire). Les annonces passent par le magasin (« Sur le marché » article par article), jamais publiées en masse depuis un document.
- Colonne Poids : ligne donnée par son poids → barres de 12 m (modifiables), nombre = poids ÷ (poids au mètre × longueur) ; arrondi au-dessus pour acheter (devis, découpe), au plus proche pour le magasin, qui garde le poids du document. Ligne « calculé depuis le poids », à vérifier.
- Indicateur d'attente commun à tous les outils IA : sans faux pourcentage, compteur de secondes, boutons bloqués (un seul crédit), message après 60 s, mouvement réduit respecté.

## Aide IA dans le formulaire de publication (2026-11-20)

- Deux boutons ✦ IA en tête de l'étape 2, à partir des photos du formulaire : « Reconnaître depuis mes photos » (catégories du moteur, pas les tôles) et « Évaluer l'état depuis mes photos » (réemploi seulement, pas la Ferraille). 1 crédit par appel, attente IA commune.
- Rien n'est rempli sans clic : « Choisir » (type et dimension, le poids vient du moteur de poids, profilés vérifiés dans le catalogue), « Appliquer » (état et surface) ou « Ignorer ».
- L'état reste la déclaration du vendeur : estimation visuelle, pas une inspection ; jamais « Excellent » sans photos nettes de près (sinon « À contrôler ») ; lien vers une inspection par un ingénieur vérifié. Si l'IA voit de la ferraille, elle propose la catégorie Ferraille.

## Rapport Business (2026-11-20)

- Réservé à la formule Business (case de la formule, réglable par l'administrateur), sans crédit IA. Page du compte « Rapport Business », visible de toute entreprise (la formule est proposée sur la page).
- Filtres : période 30 jours / 3 mois / 12 mois, marché Tous / Neuf / Réemploi, région Algérie / Centre / Est / Ouest / Sud (4 grandes zones ; la wilaya plus tard).
- Contenu : résumé IA « Ce qu'il faut retenir » à côté d'un cercle animé des poids mis en vente (neuf, réemploi, bâtiments, avec %), annonces, recherches, demandes de devis, bâtiments en vente, prix moyens demandés par famille (détail par dimension et courbe 12 mois au clic), les plus vendus, les plus recherchés, demandé et peu proposé, par région.
- **Aucune quantité de vente affichée** : ni nombre de ventes acceptées, ni nombre par profilé ; « Les plus vendus » est un classement seulement ; l'IA ne peut pas en écrire.
- Le cercle suit la période et la région et montre toujours les trois marchés.
- Recherches comptées sans savoir qui cherche (profilé, marché, wilaya, jour) ; le compteur démarre à la livraison.
- Calcul une fois par jour ; PDF par l'impression du navigateur (l'arabe y est correct).

## Explorer la marketplace (2026-11-20)

- « Explorer la marketplace » (accueil) mène à la page de tous les marchés, mise au niveau des pages de chaque marché : 3 cartes de marché, les types qui ont des annonces (avec l'étiquette du marché), « Type de produit » groupé par marché dans les filtres ; un type ou un marché choisi ouvre la page de ce marché avec tous ses filtres.

## Accueil (2026-11-21)

- La recherche de l'accueil n'a plus la wilaya (filtre des résultats seulement).
- Les chiffres de l'accueil sont les vrais (sm-products) : jamais de chiffres de démonstration sur le site.
- Promesse de l'accueil : « Nous donnons une nouvelle vie à l’acier… » (titre) et « …et transformons les ressources usagées en valeur et en opportunités durables. » (sous-titre), textes du client en arabe, français et anglais.

## Prix des outils IA et marge (2026-11-21)

- Chaque outil a un prix fixe en crédits, réglé dans l'administration (onglet « Coûts et marge ») ; l'utilisateur le voit avant de cliquer. Le coût réel (tokens × prix du fournisseur) ne sert qu'à l'administration : marge par outil, conseil de prix, budget mensuel (alerte e-mail, pause des outils payants ou simple avertissement à 100 %).
- Valeur d'un crédit = pack le moins cher au crédit ; taux de change saisi par l'administrateur.
- Plus tard : paliers pour « Lire un document » selon le nombre de pages.
- Le prix d'un outil IA se lit dans son bouton seulement (pas sous les boutons, pas sur la page des outils) ; « Gratuit » et « Inclus dans la formule Business » restent comme étiquettes.
- Attente de l'IA : un cercle (anneau mauve qui tourne, ✦ qui pulse au centre), sans barre ; dans une zone basse, le cercle et le texte restent dans la zone. « Sans crédit » n'est plus affiché.

## Référencement — SEO et recherche par IA, phase 1 (2026-11-21)

- Toute la logique dans une extension **sm-seo** (pas Yoast ni RankMath : ils ne voient pas les tables des annonces) ; le thème garde seulement les liens hreflang.
- Titre et description de chaque page construits à partir des vraies données (annonce : désignation, nuance, barres, poids, prix demandé, wilaya), en français, arabe et anglais.
- Indexées : accueil, Explorer, marchés et types de produit (`?category=`, `?family=`), annonces publiées, entreprises et professionnels qui ont une annonce, un service ou une présentation, services, **services d'ingénierie** (présentés comme services de la plateforme), outils (calcul de poids, optimiseur…). Hors index : compte, connexion, inscription, formulaire « Demander une étude », recherches avec texte / filtres / tri, annonces vendues, en pause ou expirées, demandes de devis une par une. **Aucune page d'ingénieur partenaire.**
- Données structurées : Produit + Offre (prix **demandé par le vendeur** en DZD, au kilo ou au lot, état déclaré, date de fin de l'annonce, wilaya), Organisation, WebSite + recherche, fil d'Ariane, services d'ingénierie. Jamais d'avis, de note en étoiles, de prix du marché, de source de données, de téléphone, d'e-mail ni d'adresse exacte ; le vendeur n'est nommé que si sa page est publique.
- Plans du site XML (`wp-sitemap.xml`) : pages, types de produit, annonces par marché, entreprises, services — chaque adresse dans les 3 langues ; utilisateurs et taxonomies retirés.
- **Robots des IA : choix A** — les moteurs de recherche IA (ChatGPT Search, Perplexity, Claude…) peuvent lire et citer les pages publiques ; les robots qui collectent des textes pour entraîner des modèles (GPTBot, Google-Extended, ClaudeBot, CCBot…) sont refusés. Modifiable dans SM → SEO. `llms.txt` publié.
- Réglages SM → SEO : robots des IA, image de partage par défaut, codes de validation Google Search Console et Bing Webmaster Tools.
- Phase 2 à décider : adresses lisibles des annonces (`/annonce/ipe-200-s275jr-alger-101/`), vitesse (images WebP). Phase 3 : pages de contenu (seulement avec de vraies annonces), guides.

## Référencement phase 2, vitesse et sécurité (2026-11-21)

- Le client laisse le choix de la meilleure solution (« rapide, fluide, visible et sécurisé »).
- **Adresses lisibles** : `/annonce/{nom}-{numéro}/` et `/service/{nom}-{numéro}/` ; nom fait une fois à la première publication (jamais modifié ensuite, pour la stabilité dans Google), en lettres latines pour les trois langues ; anciennes adresses en 301 (liens partagés et e-mails déjà envoyés intacts).
- **Photos en WebP** à l'envoi (champs photos seulement) ; anciennes photos non converties (pas de conversion en masse sur l'hébergement mutualisé) ; logos laissés tels quels (e-mails).
- Premières photos visibles chargées en priorité ; pas d'extension de cache en plus (cache LiteSpeed de Hostinger), pas de CDN payant.
- **Sécurité** : en-têtes de sécurité, identifiants des utilisateurs non publiés (API REST, `?author=`), XML-RPC coupé, version de WordPress masquée.


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

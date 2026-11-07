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

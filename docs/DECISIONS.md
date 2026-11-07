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

## 7 novembre 2026 — cache

- Le cache de page Hostinger ignore les paramètres d'URL : le thème 0.1.1 marque comme non cachables les pages dépendant d'un paramètre et les pages de compte. Solution définitive en Phase 2 : adresses sans paramètres (`/ar/...`, `/annonce/302/`).

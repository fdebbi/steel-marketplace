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

## 7 novembre 2026

- Le cache de page Hostinger ignore les paramètres d'URL : le thème 0.1.1 marque comme non cachables les pages dépendant d'un paramètre et les pages de compte. Solution définitive en Phase 2 : adresses sans paramètres (`/ar/...`, `/annonce/302/`).

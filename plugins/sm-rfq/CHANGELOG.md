# Changelog — sm-rfq

## 0.1.2 — 2026-11-12

- Données de demande en anglais (via `sm_pick`, sm-core 0.8.0).

## 0.1.1 — 2026-11-08 (Phase 8.1)

- E-mails (avec sm-core 0.4.0) : événements « nouvelle demande de devis », « offre envoyée », « offre acceptée » (alerte aux administrateurs, confirmations, modèles FR/AR), phrases et liens du journal d'activité.

## 0.1.0 — 2026-11-08 (Phase 7)

- Demandes de devis : profils pesés par le moteur ou description libre, livraison, date limite, plans et documents, modération facultative.
- Offres des fournisseurs : prix total ou au kg, poids, délai, livraison, validité, paiement, documents ; une par entreprise, modifiable ou retirable.
- Comparaison pour l'acheteur : tri, meilleures valeurs signalées, contact, présélection, refus, acceptation (attribution et information des autres).
- Confidentialité des offres, règles (propre demande, ingénierie, modification après offres, date limite), notifications FR/AR, tâche quotidienne d'expiration, administration et réglages, REST en lecture.
- Traductions fr_FR et ar (106 chaînes). 39 tests PHP, 25 vérifications navigateur.

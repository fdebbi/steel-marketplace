# Changelog — sm-services

## 0.3.4 — 2026-11-12

- Badge du tableau de bord : nouvelles demandes de service reçues (filtre `sm_dashboard_badges`).
- Correctif : texte de l'alerte « Autre » sans entreprise (avertissement PHP).

## 0.3.3 — 2026-11-11 (base v4)

- Modification d'une page publiée : prix, zone et délai tout de suite ; service, textes et image en attente de validation, l'ancienne version restant en ligne.
- L'administrateur modifie la description, les capacités, le matériel et l'image avec un motif envoyé au prestataire.

## 0.3.2 — 2026-11-11

- Titre d'une page = nom du service dans la liste, en français et en arabe (renommer un service renomme ses pages) ; « Autre » : le nom écrit par l'entreprise.
- Description : 15 caractères minimum (au lieu de 30).
- Une page par service et par entreprise : une deuxième page pour le même service est refusée.
- Page de service : les autres services publiés de l'entreprise (6 au plus).

## 0.3.1 — 2026-11-11 (base v3)

- Un seul service par page (les anciennes pages gardent leur premier service) et une seule image.
- « Autre » : l'entreprise écrit le nom de son service ; la page est publiée dans « Autres services », trouvable par la recherche ; l'administrateur reçoit une alerte et, dans SM → Types et catégories, ajoute le nom à la liste ou le rattache à un service existant (les pages basculent).

## 0.3.0 — 2026-11-11 (Phase 9.2)

- Services modifiables dans SM → Types et catégories (nom FR/AR, description, image, ordre, masquer, ajouter, supprimer avec remplacement sur les pages qui les utilisent).
- Réalisations : la photo enregistrée peut être supprimée.

## 0.2.0 — 2026-11-11 (Phase 9.1)

- Catalogue d'ingénierie retiré (les études passent par SM Engineering) ; anciennes pages d'ingénierie au statut « retired ».
- Professionnels : services et réalisations ; titre et qualifications sur leur profil.

## 0.1.1 — 2026-11-08 (Phase 8.1)

- E-mails (avec sm-core 0.4.0) : événements « nouvelle page de service », « demande de service envoyée », « devis de service accepté » (alerte aux administrateurs, confirmations, modèles FR/AR), phrases et liens du journal d'activité.

## 0.1.0 — 2026-11-08 (Phase 8)

- Catalogues fabrication/chantier et ingénierie, séparés par permission.
- Pages de service : services, textes FR/AR, capacités, équipements, zone, délai, prix indicatif, photos ; pause, suppression, modération facultative.
- Recherche par catalogue, service, wilaya desservie et mot-clé.
- Demandes de service avec plans, réponse par devis, acceptation, refus, annulation ; coordonnées du prestataire après acceptation.
- Références (portfolio) des entreprises et ingénieurs.
- Notifications FR/AR, administration, réglages, API REST en lecture.
- 32 tests PHP, 107 chaînes traduites.

# Changelog — sm-engineering

## 0.2.0 — 2026-11-13

- Services d'ingénierie : 6 services par défaut (Études et Conception, Note de Calcul, Dessin et Modélisation 2D/3D, Expertise et Inspection Technique, CCTP/DQE/BPU, Suivi et Assistance Technique) avec leurs prestations en français et en arabe.
- Réglages ingénierie : tableau des services — titre FR/AR, image (médiathèque), prestations une par ligne FR/AR, ordre, actif ; ajout d'un service. Remplace le tableau « Types d'étude ».
- Demande d'étude : choix d'un service et d'au moins une prestation ; prestations copiées avec la demande (nouvelle colonne, base v2), visibles par l'administrateur, le client, l'ingénieur et dans l'e-mail à la plateforme.
- Ingénieurs partenaires : « Services proposés » au lieu de « Spécialités » ; e-mails de candidature mis à jour.
- Liste publique des ingénieurs désactivée par défaut (et à la mise à jour) ; l'ingénieur préféré n'est proposé dans le formulaire que si la liste est publique.
- Anciens types d'étude gardés cachés pour les anciennes missions et profils.
- 99 tests PHP, 64 vérifications de bout en bout, 343 chaînes traduites.

## 0.1.1 — 2026-11-11

- Profil partenaire : la photo enregistrée s'affiche avec Remplacer / Supprimer.

## 0.1.0 — 2026-11-11 (Phase 9.1)

- Candidature d'ingénieur partenaire (compte séparé) : profil, diplôme, spécialités, expérience, photo, documents privés ; validation, refus motivé, suspension par l'administrateur.
- Liste publique des ingénieurs validés, sans coordonnées ; bouton « Demander une étude » avec l'ingénieur en préférence.
- Demandes d'étude envoyées à la plateforme ; devis fixé par l'administrateur ; acceptation par le client ; attribution à un ingénieur avec honoraires internes ; acceptation ou refus par l'ingénieur ; livrables, vérification, livraison, clôture ; paiement des honoraires.
- Tableau de bord ingénieur : vue d'ensemble, missions, facturation, dossiers et plans, profil partenaire.
- Fichiers privés à liens signés ; deux fils de messages séparés (client ↔ plateforme, ingénieur ↔ plateforme).
- Notifications, alertes e-mail et journal d'activité ; types d'étude modifiables (FR/AR).
- 86 tests PHP, 59 vérifications de bout en bout, 332 chaînes traduites.

# Changelog — sm-engineering

## 0.4.0 — 2026-11-13 (avec le thème 0.29.0)

- Candidature d'ingénieur partenaire en 5 étapes : nom et prénom séparés ; « Ingénieur indépendant » ou « Bureau d'études (BET) » (même compte, nom du bureau) ; titre professionnel et spécialité en listes (avec « Autre (à préciser) ») ; années d'expérience ; services ; présentation. Établissement et année du diplôme supprimés.
- Listes des titres et spécialités modifiables (FR/AR, masquer, ordre, ajout) dans Réglages ingénierie ; « Autre » toujours en dernier.
- Documents tous obligatoires : diplôme, pièce d'identité ; « Agrément de BET (MHUV, en cours de validité) » pour un BET.
- Vérification de l'adresse e-mail par code à 6 chiffres avant la création du compte (`sm_eng_code`, `sm_eng_check`) : code haché, valable 10 minutes, nouveau code après 45 s, 5 essais, 8 codes par heure et par connexion. Le compte créé a son contact vérifié.
- Base v4 (type de partenaire, nom du BET, titre, spécialité). Les candidatures existantes gardent leur établissement et leur année.
- 129 tests PHP, 87 vérifications de bout en bout.

## 0.3.0 — 2026-11-13 (avec sm-subscriptions 0.2.0 et le thème 0.28.0)

- Demande d'étude : plusieurs services d'ingénierie dans une seule demande ; toutes les prestations d'un service coché sont cochées par défaut. Type de projet obligatoire (industriel, hangar de stockage, agricole, élevage, commercial, bureaux, équipement public, habitation, autre), liste modifiable FR/AR dans les réglages. Champ « Budget indicatif » supprimé.
- Offre : un prix HT par service ; TVA selon le taux des réglages de paiement (TTC si TVA, sinon HT) ; total et avance calculés automatiquement (30 % par défaut, réglable, modifiable sur chaque offre).
- Offre détaillée envoyée au client (services, prestations, montants, total, avance, solde, délai) ; valable 15 jours ; rappel la veille de l'expiration.
- Acceptation : le client est dirigé vers le paiement de l'avance (commande SM Subscriptions : virement, CCP, BaridiMob, preuve, vérification, facture). « Complétez votre paiement pour lancer les services demandés » tant qu'elle n'est pas reçue ; pas d'ingénieur avant l'avance.
- Tâche quotidienne : rappel au client et e-mail à l'administrateur si l'avance n'est pas payée 3 jours après l'acceptation ; annulation automatique (client et administrateur prévenus) si elle n'est pas payée à la fin de l'offre.
- Solde demandé à la livraison : les documents ne sont téléchargeables qu'une fois le solde reçu.
- Administration : tableau des montants, commandes liées, boutons « Enregistrer l'avance reçue » / « Enregistrer le solde reçu » (espèces…). Réglages : avance (%), rappel après (jours), types de projet.
- Base v3 (services, type de projet, HT, TVA, avance, dates de paiement et de rappel) ; les offres envoyées avant 0.3.0 gardent l'ancien fonctionnement.
- 116 tests PHP, 75 vérifications de bout en bout.

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

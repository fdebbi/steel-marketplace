# Rapport de livraison — Phase 9.1 : types de comptes et ingénieurs partenaires

Date : 11 novembre 2026
Versions : sm-core 0.6.0 · **sm-engineering 0.1.0 (nouvelle extension)** · sm-services 0.2.0 · sm-products 0.4.0 · thème sm-marketplace 0.13.0

## 1. Ce qui a été demandé

1. Page d'inscription : logo et texte « Un seul compte… » en haut, en face de « Créez votre compte ».
2. Nouveaux types de comptes : **Société** (types modifiables par l'administrateur), **Professionnel** (métiers de la charpente : publie ses services et ses réalisations, achète et vend), **Client** (non public : achète, vend, demande des services et des études). « Maître d'ouvrage et promoteur » supprimé.
3. **Ingénieur partenaire** : compte totalement séparé, candidature avec documents, validation par l'administrateur, tableau de bord propre (missions en cours et demandées, facturation, dossiers et plans). Toutes les demandes d'étude ou d'inspection passent par la plateforme : l'administrateur fixe le prix et confie la mission à un ingénieur en sous-traitance.
4. Photo ou logo pour tous les comptes.
5. Liste publique des ingénieurs validés, sans coordonnées, avec « Demander une étude ».

## 2. Ce qui a été fait

### Inscription (thème)
- Panneau de gauche aligné en haut : le logo est au niveau du titre « Créez votre compte ».
- Trois cartes : Société / Professionnel / Client. Société → types d'entreprise ; Professionnel → métiers ; Client → aucun nom d'entreprise demandé.
- Lien « Vous êtes ingénieur ? Devenez ingénieur partenaire de la plateforme ».

### Types de comptes (sm-core 0.6.0)
- **SM → Types de comptes** : renommer en français et en arabe, masquer, ajouter un type de société ou un métier.
- Société, professionnel et client achètent et vendent ; un nouveau type reçoit les droits d'une entreprise de réalisation (modifiables dans SM → Droits).
- Clients : pas de page publique, annonces affichées « Particulier », toujours vérifiées par la plateforme (réglable), **5 annonces en ligne maximum** (réglable dans SM → Produits).
- Annuaire : sociétés et professionnels seulement, filtres par classe et par type ou métier.
- Logo / photo (2 Mo) dans le profil ; affiché dans l'en-tête, le tableau de bord, les cartes et les pages publiques.
- Migration automatique des comptes existants (voir § 5).

### Ingénieurs partenaires et missions (sm-engineering 0.1.0)
**Candidature** — page « Devenir ingénieur partenaire » :
- titre, diplôme, école, année, expérience, spécialités, présentation, photo ;
- diplôme et pièce d'identité obligatoires, agrément et CV facultatifs ;
- documents rangés dans le dossier privé.

Le compte reste « en cours d'examen » jusqu'à la décision. SM → Ingénieurs partenaires : valider, refuser avec motif (l'ingénieur peut corriger et renvoyer), suspendre, rétablir.

**Liste publique** (page Ingénierie) :
- photo, nom, titre, spécialités, expérience, wilaya, missions réalisées ;
- **aucun téléphone, e-mail ni lien de contact** ;
- filtres par spécialité, wilaya et recherche ;
- « Demander une étude » ouvre la demande avec cet ingénieur en préférence.

**Circuit d'une mission** :
1. Le client envoie sa demande avec ses plans.
2. L'administrateur envoie le devis (prix, délai, message ; validité 30 jours réglable) ou refuse avec motif.
3. Le client accepte ou refuse le devis.
4. L'administrateur confie la mission à un ingénieur validé avec ses **honoraires internes**. L'ingénieur préféré par le client et ceux de la bonne spécialité sont proposés en premier. La **marge** (devis − honoraires) est affichée à l'administrateur seul.
5. L'ingénieur accepte ou refuse.
6. L'ingénieur dépose ses livrables et les envoie.
7. L'administrateur vérifie, demande des corrections ou livre au client.
8. Le client confirme la réception (clôture).
9. L'administrateur enregistre le paiement des honoraires (avec sa référence) et peut noter le paiement du client.

**Confidentialité** :
- le client ne voit jamais l'ingénieur ni ses honoraires ;
- l'ingénieur ne voit jamais le nom du client ni le prix ;
- deux fils de messages séparés, client ↔ plateforme et ingénieur ↔ plateforme ;
- fichiers privés à liens signés, vérifiés à chaque ouverture ;
- livrables invisibles du client avant validation.

**Tableau de bord ingénieur** :
- vue d'ensemble : missions proposées et en cours, honoraires dus et payés ;
- mes missions ;
- facturation, avec envoi de facture ;
- dossiers et plans ;
- profil partenaire : photo, disponibilité, aperçu de la fiche publique.

Aucune fonction commerciale.

**Autres comptes** : nouvelle rubrique « Études d'ingénierie », avec la liste des demandes, la progression, le devis, les documents et les messages.

**E-mails et journal** :
- alertes à contact@reusteel.com : candidature, demande d'étude, devis accepté, ingénieur qui refuse, livrables à vérifier ;
- confirmations au candidat et au client ;
- notifications à chaque étape ;
- toutes les étapes figurent dans le journal d'activité.

**Réglages** (SM → Réglages ingénierie) : candidatures ouvertes, liste publique, validité du devis, demandes par jour, fichiers (nombre, taille, extensions), types d'étude en français et en arabe.

### Services (sm-services 0.2.0)
Le catalogue « ingénierie » est retiré : les sociétés et les professionnels publient leurs services de fabrication et de chantier et leurs réalisations. Les anciennes pages d'ingénierie sont conservées mais masquées.

## 3. Tests

| Suite | Résultat |
| --- | --- |
| PHP sm-core | 160 / 160 |
| PHP sm-engineering (nouveau) | 86 / 86 |
| PHP sm-services | 35 / 35 |
| PHP sm-products | 47 / 47 |
| PHP sm-reuse 38, sm-buildings 35, sm-rfq 39, sm-ai 46, moteur de poids 46 | toutes réussies |
| Navigateur e2e (comptes : société FR mobile, professionnel AR) | 62 / 62 |
| Navigateur e2e-engineering (nouveau : candidature → validation → demande → devis → mission → livraison → paiement) | 59 / 59 |
| Autres suites navigateur : poids 22, produits 25, réemploi 26, bâtiments 24, devis 25, services 39, e-mails 29, IA 30 | toutes réussies |
| Tests visuels (FR/AR, 390 / 768 / 1280 px, dont les nouvelles pages et tableaux de bord) | 602 / 602 |

## 4. Points à vérifier par vous

- Les libellés des types et des métiers, surtout en arabe : vous pouvez les corriger dans SM → Types de comptes.
- La limite de 5 annonces pour les clients, et la vérification systématique de leurs annonces.
- Les textes des e-mails d'ingénierie, modifiables dans SM → E-mails, groupe « Ingénierie ».

## 5. Migration des comptes existants

Elle se fait automatiquement à la mise à jour :

| Ancien compte | Nouveau compte |
| --- | --- |
| Compte « commercial » | Société. Installateur → réalisation de constructions métalliques ; revendeur d'occasion → recyclage ; fabrication → fabrication. |
| Compte seulement « maître d'ouvrage » | Client |
| Compte « ingénierie » | Ingénieur partenaire **à valider**. Il doit compléter sa candidature. |

La matrice des droits revient aux valeurs par défaut, car ses colonnes ont changé.

## 6. Régression complète

Toutes les suites (PHP, navigateur, visuelles) passent après la refonte, sans erreur JavaScript.
Les tests navigateur existants ont été adaptés au nouveau modèle : le compte « ingénierie » de l'ancien test devient un professionnel (arabe) ; l'ingénieur partenaire est créé par `tests/e2e-engineering.py`.

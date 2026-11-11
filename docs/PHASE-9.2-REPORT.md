# Rapport de livraison — Phase 9.2 : profil, annuaires, à la une, listes éditables, fichiers

Date : 11 novembre 2026
Versions : sm-core 0.7.0 (base v6) · sm-services 0.3.0 · sm-engineering 0.1.1 · thème sm-marketplace 0.15.0

## 1. Ce qui a été demandé

1. Un e-mail de bienvenue avec le code de vérification à l'inscription.
2. Types d'entreprise, métiers des professionnels et services modifiables dans l'admin WP (nom, description, image ; ajouter, modifier, supprimer).
3. Un annuaire des entreprises et un annuaire des professionnels.
4. Sur l'accueil, « Entreprises à la une » et « Professionnels à la une » avec « Voir tous » ; sans compte à la une, les derniers inscrits.
5. Fichiers : chaque fichier choisi affiché avec son nom et « Téléchargé ✓ », retirer ou ajouter avant d'enregistrer ; les fichiers enregistrés restent visibles dans le compte (supprimer, remplacer, ajouter).
6. Profil : clic sur une image de réalisation pour la voir en grand.
7. Après validation du design : « Entreprise vérifiée / Professionnel vérifié » à la place de « Qualité vérifiée » ; le bandeau « Créer un compte » réservé aux visiteurs non connectés.

Choix du client : « À la une » réglé par l'administrateur en attendant les abonnements ; derniers inscrits **vérifiés avec logo** ; suppression avec remplacement ; fichiers gérés dans chaque formulaire.

## 2. Ce qui a été fait

### E-mail de bienvenue (sm-core)
- Un seul e-mail « Bienvenue sur ReuSteel — votre code de vérification » : le code (en grand), le lien « Vérifier mon compte », les étapes suivantes. FR ou AR selon la langue d'inscription.
- Le texte reste modifiable dans SM → E-mails (variable `{code}`, `{lien_code}`).
- Le code est masqué (••••••) dans le journal des e-mails.
- Avec un fournisseur SMS, le code part par SMS et l'e-mail de bienvenue l'indique.

### SM → Types et catégories (sm-core, sm-services)
- Trois onglets : Types d'entreprise, Métiers des professionnels, Services.
- Pour chaque élément : nom FR/AR, description FR/AR, image, ordre ; Modifier, Masquer / Afficher, Supprimer.
- Supprimer un élément utilisé : l'administrateur choisit un remplaçant, les comptes (ou pages de services) basculent, puis l'élément est supprimé.
- La colonne « Utilisé par » indique le nombre de comptes ou de pages.

### À la une (sm-core, thème)
- Fiche entreprise (admin) : « À la une », libellé (À la une / Sponsorisé / VIP), date de fin, priorité.
- Accueil : deux sections ; les comptes à la une d'abord, complétés par les derniers comptes vérifiés avec logo (« Nouveaux sur la plateforme » quand aucun compte n'est à la une).
- Le futur module d'abonnements pourra ajouter des comptes via le filtre `sm_company_featured`.

### Annuaires (thème)
- /entreprises/ et /professionnels/ : recherche, wilaya, type ou métier (avec sa description et son image en tête), « vérifiés seulement », tri (à la une, plus récents, nom), pagination. Liens dans le menu Services et le pied de page.

### Fichiers et visionneuse (thème)
- Tous les champs de fichiers du site : liste des fichiers choisis (miniature, nom, taille, « Téléchargé ✓ », bouton ×), ajout cumulatif, pas de doublon.
- Fichiers enregistrés (annonces, services, devis, réalisation, logo, photo d'ingénieur) : Remplacer / Supprimer, annulable jusqu'à l'enregistrement.
- Réalisations du profil : visionneuse plein écran, flèches, clavier, glisser sur mobile, « Taille originale ».

## 3. Tests

| Suite | Résultat |
| --- | --- |
| PHP sm-core | 167/167 |
| PHP sm-services | 39/39 |
| PHP autres extensions | 46 + 47 + 38 + 35 + 39 + 46 + 86, tous réussis |
| e2e comptes | 64/64 |
| e2e-directory (nouveau) | 23/23 |
| e2e-services | 44/44 |
| e2e-products | 31/31 |
| e2e-mail | 31/31 |
| e2e engineering / reuse / buildings / rfq / ai / weight | 59 / 26 / 24 / 25 / 30 / 22 |
| Tests visuels (FR/AR, 360 à 1440 px) | 616/616 |

## 4. Points à noter

- Les comptes sans logo n'apparaissent pas dans le remplissage de l'accueil (choix « vérifiés avec logo »). Si aucun compte ne remplit la condition, la section est masquée.
- Une réalisation garde une seule photo ; la visionneuse fait défiler les photos de toutes les réalisations du profil.
- Mise à jour : la base passe en v6 automatiquement ; la page « Professionnels » est créée à la première visite d'un administrateur.

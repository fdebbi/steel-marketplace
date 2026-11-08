# Phase 8.1 — rapport de livraison (8 novembre 2026)

Livrables : `sm-core` 0.4.0, thème `sm-marketplace` 0.11.0, `sm-products` 0.3.1, `sm-rfq` 0.1.1, `sm-services` 0.1.1 (déclaration de leurs événements). `sm-weight-engine`, `sm-reuse` et `sm-buildings` inchangés. Guide d'installation des e-mails : `docs/EMAIL-SETUP.md`.

## Ce qui est livré

- **SM → E-mails**
  - *Expéditeur et destinataires* : nom et adresse d'expéditeur (contact@reusteel.com), une ou plusieurs adresses d'administrateurs, langue des alertes, heure du résumé quotidien, durée de conservation, e-mail de test, envoi du résumé à la demande. État de WP Mail SMTP affiché.
  - *Événements de la plateforme* (14) : pour chacun, alerte aux administrateurs (immédiate, résumé quotidien, non) et e-mail à l'utilisateur (oui / non).

    | Groupe | Événements | Par défaut |
    | --- | --- | --- |
    | Comptes | Nouvelle inscription (nom, e-mail, téléphone, entreprise, wilaya, type, activités) + **e-mail de bienvenue** | immédiat + bienvenue |
    | Vérification | Documents envoyés (+ accusé de réception) ; téléphone ou e-mail confirmé | immédiat ; résumé |
    | Annonces | Nouvelle annonce publiée ; nouvelle annonce à vérifier (+ confirmation au vendeur) | résumé ; immédiat |
    | Demandes de devis | Nouvelle demande (+ confirmation à l'acheteur) ; offre envoyée ; offre acceptée | immédiat ; résumé ; immédiat |
    | Services | Nouvelle page de service (+ confirmation) ; demande de service ; devis de service accepté | résumé ; résumé ; immédiat |
    | Messages | Demande envoyée à une entreprise ; message du formulaire de contact (+ accusé de réception) | résumé ; immédiat |
    | Sécurité | Connexion bloquée après des échecs | immédiat |

  - *Modèles* : chaque e-mail (administrateur et utilisateur) se réécrit en français et en arabe, avec les variables proposées (`{nom}`, `{email}`, `{telephone}`, `{entreprise}`, `{wilaya}`, `{titre}`, `{lien}`…), aperçu avec valeurs d'exemple, « Enregistrer et m'envoyer un test », « Rétablir le texte par défaut ». Tous les textes par défaut sont rédigés dans les deux langues.
  - *Notifications aux utilisateurs* : les e-mails existants (offre reçue, devis accepté, annonce approuvée…) sont activables et réécrivables.
  - *Journal des e-mails* : date, destinataire, objet, statut (envoyé / échec avec l'erreur), lecture du message, renvoi.
- **Mise en page commune** des e-mails (logo, bonjour, pied de page ; de droite à gauche en arabe), expéditeur `ReuSteel <contact@reusteel.com>`, « Répondre à » = la personne concernée dans les alertes.
- **Résumé quotidien** aux administrateurs, groupé par thème, à l'heure choisie.
- **SM → Journal d'activité** : phrases lisibles (« Acier Hodna a publié « IPE 270 » »), type, auteur, entreprise, IP ; filtres par type, dates et recherche (nom, e-mail, entreprise, IP) ; activité d'une entreprise ; export CSV ; connexions réussies rangées sous « Sécurité ».
- **Page Contact** (FR/AR) : formulaire nom, e-mail, téléphone, objet, message ; alerte aux administrateurs et accusé de réception au visiteur.

## Tests

| Suite | Résultat |
| --- | --- |
| sm-core, tests PHP (dont 35 nouveaux : réglages, modèles, rendu, envois, résumé, journal des e-mails, notifications, journal d'activité, contact) | 138/138 |
| sm-weight-engine, sm-products, sm-reuse, sm-buildings, sm-rfq, sm-services | 46 + 40 + 38 + 35 + 39 + 32 |
| E-mails dans le navigateur (`tests/e2e-mail.py` : réglages, liste des événements, modification et test du modèle de bienvenue, inscription réelle → alertes aux deux administrateurs + bienvenue, formulaire de contact en arabe, journal et recherche, export CSV, journal des e-mails, renvoi, désactivation, retour au texte par défaut) | 25/25 |
| Parcours existants (Phase 2, calculateur, produits, réemploi, bâtiments, demandes de devis, services) — e-mails des événements vérifiés dans le journal des e-mails | 56 + 22 + 25 + 26 + 24 + 25 + 39 |
| Tests visuels AR/FR, 7 largeurs, 32 pages | 476/476 |

En local, les e-mails sont interceptés (aucun envoi réel) : la délivrabilité réelle se vérifie sur le serveur après la configuration de WP Mail SMTP (`docs/EMAIL-SETUP.md`).

## Limites connues

- Le résumé quotidien dépend des tâches planifiées de WordPress (première visite après l'heure choisie) ; sur un site peu visité, ajouter un cron serveur (expliqué dans le guide).
- Les modèles sont en texte simple (paragraphes, liens automatiques) : pas de mise en forme riche, volontairement.

## Mise à jour d'un site

1. Installer sm-core 0.4.0 (crée la table `sm_mail_log`), puis sm-products 0.3.1, sm-rfq 0.1.1, sm-services 0.1.1 et le thème 0.11.0.
2. Installer et configurer **WP Mail SMTP** selon `docs/EMAIL-SETUP.md` (boîte contact@reusteel.com, SPF / DKIM / DMARC).
3. *SM → E-mails* : vérifier les adresses des administrateurs, envoyer un e-mail de test, relire les modèles.

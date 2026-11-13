# Changelog — sm-marketplace

## 0.30.0 — 2026-11-13 (avec sm-engineering 0.5.0)

- Compte ingénieur : bouton Disponible / Indisponible en haut de la barre latérale (ingénieurs validés).
- Profil partenaire : lecture seule pendant la vérification ; ensuite formulaire « Envoyer la demande de modification », bandeau de la demande en attente (avant → après, annulation), motif du dernier refus ; téléphone enregistré tout de suite.
- Boutons « Demander une étude » et « Demander ce service » masqués pour un ingénieur partenaire connecté (visibles pour les visiteurs et les autres comptes).
- Candidature : photo ou logo obligatoire. Paramètres : nom en lecture seule pour un ingénieur.

## 0.29.0 — 2026-11-13 (avec sm-engineering 0.4.0)

- « Devenir ingénieur partenaire » : assistant en 5 étapes dans une fenêtre de taille fixe (plein écran sur téléphone) — informations personnelles, professionnelles, documents, mot de passe et conditions, vérification (récapitulatif + code e-mail).
- Vérification de chaque étape avant de continuer ; brouillon des textes sur l'appareil (jamais le mot de passe ni les fichiers) ; services en liste déroulante à choix multiples ; « Autre » ouvre un champ ; photos des documents compressées avant l'envoi ; indicateur de mot de passe ; retour du navigateur = étape précédente.
- Profil partenaire du tableau de bord : mêmes champs (type, nom du BET, titre verrouillé après validation, spécialité).

## 0.28.1 — 2026-11-13 (avec sm-core 0.8.2)

- Formulaires : code de sécurité renouvelé avant l'envoi d'une page ancienne (cache ou onglet ouvert longtemps) ; pages « Devenir ingénieur partenaire », « Demander une étude » et « Contact » jamais mises en cache.

## 0.28.0 — 2026-11-13 (avec sm-engineering 0.3.0)

- Demander une étude : cartes à cocher pour un ou plusieurs services, prestations cochées par défaut ; type de projet ; plus de budget.
- Page de l'étude (client) : offre détaillée (services, prestations, montants HT, TVA, TTC, avance, solde, délai, validité 15 jours), « Accepter l'offre » vers le paiement de l'avance, bandeau « Complétez votre paiement… » avec bouton de paiement, suivi Avance / Solde, solde à payer avant les documents.
- Page de commande : retour vers l'étude ; « TVA incluse » seulement si la commande a une TVA.

## 0.27.0 — 2026-11-13 (avec sm-engineering 0.2.0)

- Page Ingénierie : cartes des services d'ingénierie (image, titre, prestations, bouton « Demander ce service »), puis « Comment ça marche » ; plus d'annuaire des ingénieurs partenaires (sauf si l'administrateur le réactive) ; lien discret « Devenir ingénieur partenaire » en bas.
- Demander une étude : liste des services (préchoisi depuis la carte) et cases des prestations du service choisi ; plus de choix d'ingénieur quand la liste n'est pas publique.
- Compte client et ingénieur : prestations demandées affichées sur la page de la demande ; « Service » au lieu de « Type d'étude ».
- Candidature et profil ingénieur : « Services proposés » au lieu de « Spécialités ».

## 0.26.0 — 2026-11-13

- Types des pages Produits, Réemploi et Bâtiments en cadres : petit dessin du type (profilé, tube, tôle, barres, toiture) ou icône de la catégorie, nom, nombre d'annonces (« Aucune annonce pour l'instant » en pointillés). Plus de cadre « Tous » : recliquer sur le type choisi (marqué ×) revient à toutes les annonces ; « Tous les types » est en tête de la colonne de filtres. Sur téléphone, les cadres défilent horizontalement.
- Cartes d'annonces (résultats et accueil) : image plus basse (16:9 au lieu de 4:3), une seule image, nombre de photos en badge.

## 0.25.0 — 2026-11-13 (avec sm-products 0.11.0)

- Pages Produits, Réemploi et Bâtiments : les types sont les onglets du haut, avec leur nombre d'annonces en ligne (onglet vide en pointillés) ; ils ne sont plus répétés dans la colonne de filtres.
- Colonne de filtres propre au type choisi, avec seulement les valeurs utilisées par des annonces en ligne et leur nombre : famille (IPE, HEA… ; forme pour les tubes et barres), désignation (limitée à la famille choisie), épaisseur des tôles, longueur et poids minimaux, champs à liste des catégories Toiture / Finition / Autres, état (réemploi), monté ou démonté, surface et hauteur (bâtiments).
- Filtres communs : mot-clé, wilaya, prix au kg (sauf bâtiments), vente au détail ou en lot entier, vendeurs vérifiés.
- Changer d'onglet garde les filtres communs et retire ceux du type précédent ; le tri garde tous les filtres ; « Réinitialiser » garde l'onglet.

## 0.24.0 — 2026-11-13 (avec sm-trade 0.1.0 et sm-products 0.10.0)

- Page d'annonce : boutons « Acheter », « Faire une offre », « Contacter le vendeur ». Formulaire replié qui s'ouvre au clic : choix tout le lot / une quantité, quantité dans l'unité de vente (barres, kg, pièces, m²…) avec le disponible, total au prix affiché calculé en direct, prix proposé pour une offre, nom / téléphone / e-mail préremplis, message. Mention claire : rien n'est payé sur la plateforme.
- Formulaire de contact : champ « Objet » visible, prérempli avec le titre de l'annonce ; ses messages ne se mélangent plus avec ceux du formulaire d'achat.
- Compte : « Mes achats et offres » (Achat), « Commandes et offres reçues » (Vente), page d'une demande (coordonnées, réponses, étapes) ; compteurs des réponses attendues.
- Filtres des pages de résultats : « Vendeurs vérifiés uniquement » et surface / hauteur des bâtiments fonctionnent ; le bouton donne en direct le nombre de résultats des filtres choisis ; l'adresse ne garde que les filtres utilisés.
- Icônes panier, horloge, étiquette.

## 0.23.0 — 2026-11-12 (avec sm-products 0.9.0 et sm-subscriptions 0.1.1)

- Courbe du prix de l'acier sous l'image de l'accueil, refaite : courbe lissée en SVG sans bibliothèque (sans pic ni creux inventés entre deux points), tracée de gauche à droite quand elle entre dans l'écran, lueur légère, points qui apparaissent l'un après l'autre, carte « verre métallique » accrochée au dernier point avec le prix au kg, la date et l'évolution. Hauteur réservée : rien ne bouge sur la page. Immobile avec « réduire les animations » ou sans JavaScript.
- Toujours affichée : avec un seul prix, le passé est une ligne en pointillés (décor, pas des données) qui finit sur le prix de la semaine ; sans aucun prix, la carte indique « Prix bientôt disponible ». Le libellé dit d'où vient le prix : « Prix moyen demandé » (annonces) ou « Prix de référence ReuSteel » (saisi par l'équipe), jamais « prix officiel ».
- La même courbe en tête de la page Indice des prix.
- Page Abonnements selon le compte connecté : l'onglet de son type de compte s'ouvre en premier avec la mention « votre compte », la formule en cours est marquée (« Gérer ma formule »), les formules de l'autre type restent visibles sans bouton de commande. Clients et ingénieurs partenaires : packs de crédits IA en tête avec « Acheter des crédits IA ».

## 0.22.0 — 2026-11-12 (Phase 10, avec sm-subscriptions 0.1.0)

- Page publique « Abonnements » (/tarifs/) : onglets Entreprises / Professionnels et artisans, 3 formules chacune avec prix mensuel et annuel (économie affichée), limites, packs de crédits IA, options d'annonce, moyens de paiement, offre membres fondateurs. Tout vient des réglages ; rien n'est écrit en dur.
- Compte → Abonnement : formule en cours, jauges d'utilisation (annonces, tonnage, bâtiments, services, réalisations, réponses aux devis du mois, crédits IA), coordonnées visibles ou masquées, bandeau fondateurs, cartes des formules avec « Payer 1 mois / 1 an » ou « Renouveler », historique des commandes.
- Nouvelle page Commande (/compte/commande/) : référence, montant TTC, coordonnées de paiement de la plateforme, envoi de la preuve (moyen + fichier + note), annulation tant que rien n'est payé.
- Nouvelle page Facture (/compte/facture/) imprimable : vendeur, client, HT, TVA, TTC.
- Crédits IA : solde (crédits du mois + crédits achetés), packs à acheter ; l'onglet existe aussi pour les ingénieurs partenaires.
- Mes annonces : statut « En pause (formule) » avec republication, options « Remonter en tête », « Badge Urgent », « À la une » sur les annonces en ligne ; dates de fin affichées.
- Cartes d'annonce : badge « Urgent » (et « À la une » pour les annonces payées).
- Page entreprise : message quand les coordonnées sont masquées (compte gratuit) ; rappel au propriétaire.
- Traductions FR / AR des nouveaux textes.

## 0.21.0 — 2026-11-12 (avec sm-products 0.7.1, sm-weight-engine 0.2.1)

- Dessin de section sans mention sur l'image (cartes et fiche) ; cotes réelles sur le dessin (« 160 mm », « 82 mm ») au lieu de h et b.
- Fiche d'annonce sans photo : panneau « Caractéristiques de la section » dans le cadre du dessin (dimensions exactes, aire en cm², poids en kg/ml ou kg/m², norme) ; sous le dessin sur mobile.

## 0.20.0 — 2026-11-12 (avec sm-products 0.7.0, sm-weight-engine 0.2.0)

- Produits neufs calculés (profilés, tubes, fers plats et ronds, tôles) : plus de champs de titre ; un encadré explique le titre automatique ; les photos sont facultatives.
- Sans photo, la carte et la fiche montrent le dessin standard de la section sur fond acier, avec « Image indicative ».
- Aperçu des liens partagés (WhatsApp, Facebook) : balises og:title et og:image (photo, ou image PNG du dessin).
- Poids : sous 1 000 kg en kg ; à partir de 1 000 kg en tonnes (« 1,25 t », « 1,25 طن » en arabe) avec les kg en petit, partout sur le site. La saisie et les calculs restent en kg.

## 0.19.0 — 2026-11-12 (avec sm-products 0.6.0)

- Accueil : sous la poutre, courbe du prix moyen demandé au kg des profilés neufs (12 semaines), qui se trace puis affiche le dernier prix, la variation et le prix moyen du réemploi (30 jours). Masquée tant qu'il n'y a pas assez d'annonces. Lien vers l'indice des prix.
- Menu du compte sombre comme l'accueil ; sur ordinateur il se replie en icônes (noms au survol, point vert pour les éléments en attente, choix mémorisé) ; sur téléphone, volet qui glisse depuis le côté (bouton « Menu », fermeture par ×, fond ou Échap).
- La photo et le nom en haut du menu mènent à la page publique (entreprise, professionnel), à « Mon profil » (client) ou à la page des ingénieurs (ingénieur agréé).

## 0.18.0 — 2026-11-12 (refonte du design, avec sm-core 0.8.0)

- Charte ReuSteel : vert #06CE77 (texte noir dessus, choisi automatiquement selon le contraste), vert texte #047A47 (5,4:1), graphite #111820, fond #F5F7F8 ; anciennes couleurs par défaut migrées, couleurs choisies par l'administrateur conservées.
- Titres en Space Grotesk (auto-hébergée, licence OFL) ; l'arabe garde IBM Plex Sans Arabic.
- Accueil sombre « galaxie » : bannière avec poutre IPE 200 en 3D (Three.js r160 auto-hébergé, chargé après la page, ordinateur seulement, coupé si « réduire les animations ») sur une image SVG de secours ; marchés, économie circulaire, « Pourquoi ReuSteel ? », bandeau d'inscription ; barre du bas sombre sur mobile.
- Catalogue : cartes qui se soulèvent avec bord vert, fond acier pour les images absentes, prix en police de titre, puces de catégories.
- Compte : mode sombre (bouton dans le menu, mémorisé dans le navigateur, appliqué avant l'affichage).
- Anglais : sélecteur Français · العربية · English ; toutes les chaînes du thème sont déjà en anglais.

## 0.17.1 — 2026-11-12 (avec sm-ai 0.1.1)

- Menu du compte : « Études d'ingénierie » et « Abonnement » deviennent des entrées du menu ; « Assistant IA » regroupe l'assistant et la nouvelle page « Crédits IA » (utilisations du mois par outil).
- Sous « Publier une annonce » : raccourcis « Ajouter un service » (comptes qui proposent des services) et « Demander une étude ».
- Page « Abonnement » : formule de l'entreprise, ou « Gratuit pendant le lancement » en attendant les abonnements.

## 0.17.0 — 2026-11-12 (avec sm-core 0.7.3, sm-services 0.3.4)

- Menu du compte regroupé : Vue d'ensemble, Vente, Achat, Services, Assistant IA, Compte (6 entrées au lieu de 19). Un clic sur un groupe ouvre sa première page ; les pages du groupe sont des onglets sous le titre. Chaque page garde son adresse.
- Bouton « Publier une annonce » en haut du menu. « Paramètres » passe dans le groupe Compte ; « Études d'ingénierie » reste dans Services.
- Badges : demandes en attente sur le groupe et sur l'onglet concerné (demandes reçues, demandes de service reçues).
- Mobile : bouton « Menu » avec les groupes ; onglets défilants, l'onglet courant reste visible.

## 0.16.0 — 2026-11-11 (avec sm-products 0.5.0, sm-services 0.3.3, sm-core 0.7.2)

- Formulaire d'annonce : « Comment le vendez-vous ? » — à la pièce / au kg, lot complet uniquement, ou les deux (profilés, tubes, tôles, lots d'inventaire, chutes et ferraille, hangars démontés). Le prix du lot complet et son prix au kg s'affichent dans le résumé, sur les cartes et sur la page de l'annonce.
- Annonce ou service en ligne modifié : avis clair (prix, mode de vente et quantités changent tout de suite ; textes, photos, données techniques vérifiés) et étiquette « Modification en vérification » dans « Mes annonces » / « Mes services ».
- Cloche : fenêtre flottante avec les 8 dernières notifications, « Tout marquer comme lu », « Voir toutes les notifications » ; nouvelle page « Notifications ». Ouvrir une notification la marque comme lue.
- Correctif : la variable de couleur `--sm-ink` (inexistante) est remplacée par `--sm-text` dans les feuilles de style.

## 0.15.3 — 2026-11-11 (avec sm-services 0.3.2)

- Formulaire de service : plus de titre FR/AR à saisir ; le titre de la page est le nom du service choisi (ou le nom écrit dans « Autre »). Aide sous la description (15 caractères minimum).
- Page de service : « Autres services de l'entreprise » en petites cartes (image, nom, prix), jusqu'à 6, avec « Voir tous les services de l'entreprise ».
- Les étiquettes de service qui répètent le titre ne sont plus affichées.

## 0.15.2 — 2026-11-11 (avec sm-services 0.3.1 et sm-core 0.7.1)

- Profil : carré « Informations » supprimé (tout est déjà dans l'en-tête et la Présentation) ; case « Sur la plateforme » supprimée ; types d'entreprise / métiers en étiquettes sous le texte, sans titre visible ni fond.
- Formulaire de service : un seul service par page, choisi dans une liste déroulante, ou « Autre » avec le nom écrit par l'entreprise ; une seule image par service (remplaçable).
- Page Services : filtre « Autres services ».

## 0.15.1 — 2026-11-11

- Profil : les types d'entreprise (ou métiers) ne sont plus dans l'en-tête ; ils s'affichent sur une ligne pleine largeur dans la carte « Présentation ».
- Photo d'une réalisation : s'ouvre dans une fenêtre (popup) au-dessus de la page ; Échap, le bouton × ou un clic à côté la ferment.

## 0.15.0 — 2026-11-11 (Phase 9.2, avec sm-core 0.7.0 et sm-services 0.3.0)

- Badges : « Entreprise vérifiée » / « Professionnel vérifié » (registre ou documents contrôlés) à la place de « Qualité vérifiée » ; « Compte vérifié » dès que l'e-mail est confirmé.
- Accueil : sections « Entreprises à la une » et « Professionnels à la une » avec « Voir tous » ; sans compte à la une, « Dernières entreprises / derniers professionnels inscrits » (vérifiés, avec logo). Le bandeau « Créer un compte gratuit » n'est affiché qu'aux visiteurs non connectés.
- Deux annuaires : /entreprises/ et /professionnels/ (recherche, wilaya, type ou métier avec sa description et son image, « vérifiés seulement », tri, pagination). L'ancien lien « ?class=professional » redirige vers /professionnels/.
- Fichiers : chaque fichier choisi s'affiche avec son nom, sa taille et « Téléchargé ✓ » ; on peut le retirer ou en ajouter d'autres avant d'enregistrer. Les fichiers enregistrés restent visibles dans le formulaire avec Remplacer / Supprimer (annulable).
- Profil : clic sur la photo d'une réalisation → visionneuse en grand (flèches, clavier, glisser sur mobile, « Taille originale »).

## 0.14.0 — 2026-11-11 (profil public des entreprises, avec sm-core 0.6.1)

- Page profil entreprise / professionnel refaite (préfixe `smc-`) d'après le design validé.
- En-tête : logo, nom à côté, statut et badges au-dessus du nom : « Compte vérifié » dès que l'e-mail est vérifié, « Qualité vérifiée » après contrôle du registre par l'administrateur.
- Colonne principale : Présentation → Réalisations → Services proposés → Produits à vendre, avec onglets d'ancrage.
- Chaque service ouvre une fenêtre « Demander un devis » avec ce service présélectionné ; la demande arrive dans le tableau de bord du prestataire.
- Colonne latérale : Informations → Localisation (adresse + carte) → Coordonnées (téléphone masqué, « Afficher ») → Partager cette page (Facebook, WhatsApp, LinkedIn, X, Telegram, copier le lien).
- « Envoyer un message » ouvre le formulaire de contact dans une fenêtre ; nouvelles icônes réseaux sociaux et lien.

## 0.13.0 — 2026-11-11 (Phase 9.1, avec sm-core 0.6.0 et sm-engineering 0.1.0)

- Inscription : trois cartes (Société / Professionnel / Client), types ou métiers selon le choix, lien « Devenir ingénieur partenaire » ; logo et texte du panneau alignés en haut avec « Créez votre compte ».
- Nouvelles pages : Devenir ingénieur partenaire, Demander une étude ; page Ingénierie refaite (fonctionnement + liste des ingénieurs partenaires sans coordonnées).
- Tableau de bord ingénieur : vue d'ensemble, missions, facturation, dossiers et plans, profil partenaire ; vue « Études d'ingénierie » pour les autres comptes.
- Logo / photo dans l'en-tête, les cartes, les profils ; annuaire filtrable sociétés / professionnels ; « Particulier » pour les annonces des clients.
- Barre mobile adaptée aux ingénieurs ; nom des fichiers choisis affiché dans les champs de téléversement.

## 0.12.0 — 2026-11-08 (Phase 9, avec sm-ai 0.1.0)

- Tableau de bord « Assistant IA » (entreprises et ingénieurs) :
  - trois outils : annonce, profilé, document ;
  - envoi de photos ou d'un document avec des notes, et bouton occupé pendant l'analyse ;
  - résultats avec confiance, masses du catalogue, tableau des articles pesés par le moteur ;
  - boutons « Créer l'annonce » et « Créer une demande de devis » ;
  - quotas restants et badge « Simulation ».
- Encarts « Préparer avec l'IA » sur le formulaire d'annonce et « Importer depuis un document » sur le formulaire de demande de devis.
- Recherche intelligente sur les pages de marché et de recherche, avec « Compris comme » (pastilles des filtres) ou repli sur la recherche simple.
- Les filtres prix minimum et maximum de la recherche sont maintenant transmis.
- `14-ai.css`, 1006 chaînes traduites, tests visuels étendus à 35 pages.

## 0.11.0 — 2026-11-08 (Phase 8.1, avec sm-core 0.4.0)

- Page « Contact » réelle (modèle `templates/contact.php`) : nom, e-mail, téléphone, objet (suggestions), message ; erreurs par champ, confirmation, champs préremplis pour un membre connecté ; adresse de contact de la plateforme.
- Tests visuels étendus à la page Contact.
- Traductions : 945 chaînes.

## 0.10.0 — 2026-11-08 (Phase 8, services, avec sm-services 0.1.0)

- Pages « Fabrication et montage » et « Ingénierie » réelles : services en pastilles, filtres mot-clé et wilaya, cartes avec services, zone et prix indicatif ; la page Ingénierie garde l'annuaire des bureaux d'études.
- Page d'un service (`/service/{numéro}/`) : photos, services proposés, prix, délai, zone, prestataire (vérification, expérience), description, capacités techniques, équipements, références, formulaire de demande de devis.
- Tableau de bord : « Mes services » (formulaire en quatre étapes, liste avec pause et suppression), « Demandes de service reçues » (réponse par devis ou refus, erreurs affichées dans la demande concernée), « Mes demandes de service » (accepter, refuser, annuler ; téléphone après acceptation), « Références ».
- Menu : groupe « Services » pour les entreprises de fabrication ; menu ingénieur avec services, demandes de service, messages et références.
- Page entreprise : onglet « Références », années d'expérience ; le bloc qualifications vide n'est plus affiché.
- Tests visuels étendus à 31 pages.
- Traductions : 929 chaînes.

## 0.9.0 — 2026-11-08 (Phase 7, demandes de devis, avec sm-rfq 0.1.0)

- Page « Demandes de devis » réelle : filtres (mot-clé ou profil, marché, wilaya, tri), cartes avec quantité, poids, date limite et nombre d'offres, encadré « Comment ça marche ».
- Page d'une demande (`/demandes-de-devis/{numéro}/`) : besoin, tableau des profils, description, plans ; selon le visiteur : formulaire d'offre, offre envoyée, actions de l'acheteur et **comparaison des offres** (tri, meilleures valeurs signalées, appel et e-mail du fournisseur, présélection, refus, acceptation).
- Tableau de bord : « Mes demandes de devis » (formulaire avec lignes de profils pesées en direct ou description libre, liste par statut) et « Devis envoyés ».
- Dates affichées en français ou en arabe (mois algériens).
- Traductions : 816 chaînes.

## 0.8.0 — 2026-11-07 (Phase 6, bâtiments, avec sm-buildings 0.1.0)

- Publication d'un bâtiment : choix monté / démonté en deux cartes, dimensions (longueur, largeur, hauteur à l'égout, portiques, pente) avec surface en direct, état, système porteur, toiture, couverture, bardage, « ce que comprend le prix » en cases à cocher, poids estimé ou inventaire des éléments (type d'élément, profil, quantité, longueur), prix du bâtiment ou au kg, résumé avec prix au kg et au m².
- Inventaire commun (`product-lines`) au réemploi et aux bâtiments.
- Fiche : schéma indicatif plan + façade avec mention « ce n'est pas une étude structurelle », inventaire des éléments, avis d'ingénieur avant remontage.
- Choix du marché sur trois colonnes ; état « neuf » sur l'échelle des états.
- Traductions : 690 chaînes.

## 0.7.0 — 2026-11-07 (Phase 5, réemploi, avec sm-reuse 0.1.0)

- « Publier une annonce » : choix du marché (produits neufs / réemploi) puis de la catégorie ; le marché verrouillé pour le compte affiche la raison.
- Formulaire de réemploi : échelle d'état en cinq cartes colorées, origine, état de surface, découpe à la demande, chargement ; inventaire ligne par ligne (type, profil, pièces, longueur) avec poids de chaque ligne en direct, nouvelle ligne vide ajoutée automatiquement, suppression d'une ligne en douceur ; lots au poids (pesé ou estimé) ; prix au kg, à la pièce ou pour le lot entier ; rappel de l'avis d'ingénieur.
- Fiche : « Inventaire du lot », bloc prix « Prix du lot entier », poids estimé distingué, avis d'ingénieur fourni par l'extension (lien vers les ingénieurs, avertissement renforcé pour « à inspecter », note de recyclage pour la ferraille).
- Cartes : « 9 pcs · jusqu'à 4 m » pour un inventaire, mention « estimé ».
- Page Réemploi : filtres longueur minimale et nombre minimal de pièces transmis au moteur.
- Les transitions de page interrompues par le navigateur ne produisent plus d'erreur.
- Traductions : 674 chaînes.

## 0.6.0 — 2026-11-07 (nom ReuSteel, animations)

- La plateforme s'appelle **ReuSteel** : nom par défaut, logo-texte « Reu » + « Steel » en couleur d'accent, titre du site. Un nom déjà choisi dans le Personnalisateur est conservé.
- Animations douces (150 à 450 ms, une seule courbe) : apparition progressive des sections au défilement, transition fondue entre les pages (navigateurs compatibles), en-tête translucide au défilement, cartes et boutons qui se soulèvent au survol, menu mobile qui glisse à l'ouverture et à la fermeture, méga-menu, messages et étapes qui apparaissent en fondu, mises à jour en direct du calculateur et du formulaire en fondu.
- Lumière douce dans le bandeau d'accueil.
- Tout est désactivé quand l'appareil demande moins d'animations ; sans JavaScript, rien n'est caché.

## 0.5.0 — 2026-11-07 (Phase 4, avec sm-products 0.1.0)

- « Publier une annonce » : formulaire réel par catégorie (catégorie → données techniques avec schéma, quantité en barres ou en poids total, prix et résumé en direct → titre et description → photos et fiche technique PDF → wilaya), brouillon ou envoi pour publication. Fonctionne sans JavaScript (bouton « Mettre à jour »).
- « Mes annonces » : filtre par statut, modifier, marquer vendu, retirer, republier, supprimer (avec confirmation), motif des modifications demandées.
- Fiche produit : galerie des vraies photos, caractéristiques propres à la catégorie, schéma exact, documents techniques, avis « vendu / expiré / aperçu ».
- Prix par unité (kg, pièce, m², ml) dans les cartes, le bloc prix et la barre mobile.
- Les valeurs saisies pendant une mise à jour en direct ne sont plus perdues (calculateur et formulaire).

## 0.4.0 — 2026-11-07 (Phase 3, avec sm-weight-engine 0.1.0)

- Nouvelle page **Calculateur de poids** (`/calcul-poids/`, créée automatiquement après la mise à jour) : choix du produit parmi 16 types illustrés par leur section, choix de la dimension du catalogue européen (ou saisie des cotes pour les tubes, fers plats, barres et tôles), schéma exact à l'échelle avec ses cotes et le poids au mètre, quantité en nombre de barres ou en poids total, longueur 12 m, 6 m ou libre.
- Résultat : poids total, ou nombre de barres (exact, barres entières inférieure et supérieure), poids d'une barre, mention « poids théorique ».
- Fonctionne sans JavaScript (formulaire GET) ; avec JavaScript, mise à jour instantanée. Chaque calcul a une adresse partageable.
- Liens « Calculateur de poids » dans l'en-tête, le menu mobile et le pied de page. Schémas toujours de gauche à droite en arabe.
- 48 nouvelles chaînes traduites (fr, ar).

## 0.3.0 — 2026-11-07 (carte de l'entreprise, avec sm-core 0.2.0)

- Fiche entreprise / profil : carte de positionnement. Le choix de la wilaya ouvre la carte dessus ; on fait glisser le repère (ou on clique sur la carte) jusqu'à l'adresse exacte ; bouton « Utiliser ma position actuelle » (géolocalisation du navigateur, précision affichée) ; bouton « Supprimer la position » ; case « Afficher la carte sur ma page publique ».
- Repère déplaçable au clavier (flèches, Maj + flèches), messages d'état lus par les lecteurs d'écran, explication en cas de refus de localisation.
- Page publique de l'entreprise : carte de l'emplacement, liens « Itinéraire » (Google Maps) et « Ouvrir la carte » (OpenStreetMap).
- Bibliothèque Leaflet 1.9.4 hébergée dans le thème (`assets/vendor/leaflet`, licence BSD-2), chargée seulement sur les pages avec une carte ; fond OpenStreetMap.
- Icônes `locate` et `navigation`. 24 nouvelles chaînes traduites (fr, ar).

## 0.2.0 — 2026-11-07 (Phase 2, avec sm-core)

- Branché sur sm-core : vrais comptes, entreprises, annuaire, wilayas (69), notifications.
- Formulaires réels : inscription, connexion, mot de passe oublié, code de vérification, fiche entreprise / profil ingénieur, paramètres (langue, mot de passe), documents de vérification, demandes (annonce et page entreprise). Erreurs affichées sous chaque champ, saisie conservée.
- Tableau de bord : nouvelles vues Entreprise, Vérification, Paramètres, Demandes reçues / Demandes clients, Vérifier le compte ; étapes de vérification ; notifications ; publication bloquée pour l'ingénierie et expliquée avant vérification.
- En-tête connecté : « Mon compte », pastille de notifications ; liens de publication adaptés au type de compte.
- Adresses propres via sm-core (`/annonce/302/`, `/entreprise/slug/`, `/compte/annonces/`, `/ar/...`), hreflang sur les adresses propres.
- Le paramètre d'affichage liste/grille devient `?display=list`.
- Sans sm-core, le thème garde son mode démonstration.

## 0.1.1 — 2026-11-07

- Cache de page (Hostinger LiteSpeed / CDN) : les pages qui dépendent d'un paramètre d'URL (`?lang=`, `?id=`, `?view=`, filtres) et les pages de compte ne sont plus mises en cache. Corrige des filtres sans effet, une langue erronée ou la mauvaise annonce affichée.
- Un visiteur ayant choisi l'arabe qui ouvre une adresse sans `?lang=` est redirigé vers la version `?lang=ar` : le contenu d'une URL ne dépend plus d'un cookie.
- URL du sélecteur de langue fiable aussi en installation dans un sous-dossier.

## 0.1.0 — 2026-11-05 (Phase 1)

- Thème classique sur mesure, sans constructeur de pages ni thème commercial.
- En-tête avec recherche, méga-menu des marchés, tiroir mobile, barre d'onglets mobile, pied de page.
- Pages : accueil, marchés (produits, réemploi, bâtiments, recherche), détail d'annonce (produit, lot de réemploi, bâtiment monté / démonté avec inventaire), services, ingénierie, annuaire et fiche entreprise (commerciale ou bureau d'études), demandes de devis, indice des prix, abonnements, connexion, inscription (compte professionnel ou ingénierie), mot de passe oublié, tableau de bord commercial (vue d'ensemble, annonces, publication en 4 étapes) et ingénieur, charte graphique, 404.
- Couleurs de marque, nom et logo modifiables dans le Personnalisateur, avec aperçu en direct.
- Bilingue arabe RTL / français LTR : 447 chaînes traduites, CSS logique + RTLCSS, unités et désignations isolées.
- Contrat de données par filtres + fournisseur de démonstration ; installateur de pages non destructif.
- Outils : build CSS, compilation des traductions, tests visuels responsive automatisés.

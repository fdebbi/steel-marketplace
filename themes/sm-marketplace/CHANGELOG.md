# Changelog — sm-marketplace

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

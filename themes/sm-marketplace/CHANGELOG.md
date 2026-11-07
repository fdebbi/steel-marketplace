# Changelog — sm-marketplace

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

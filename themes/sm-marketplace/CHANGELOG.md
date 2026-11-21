# Changelog — sm-marketplace

## 0.55.0 — 2026-11-21 (avec sm-ai 0.7.0)

- Le prix d'un outil IA affiché là où on l'utilise suit le prix réglé dans l'administration : `sm_ai_price()` (« 1 crédit », « 3 crédits », « Gratuit ») sur « Reconnaître depuis mes photos », « Évaluer l'état », l'import de l'optimiseur, la rédaction de la description et la lecture d'un document (plus de « 1 crédit » écrit en dur).

## 0.54.2 — 2026-11-21

- Accueil, nouvelle promesse : « Nous donnons **une nouvelle vie** à l’acier… » (titre) puis « …et transformons les ressources usagées en valeur et en opportunités durables. » (sous-titre) ; en arabe « نمنح الفولاذ حياة جديدة... » / « ...ونحوّل الموارد المستعملة إلى قيمة وفرص متجددة. », en anglais « We give steel a new life… ».

## 0.54.1 — 2026-11-21

- Accueil : la recherche n'a plus le choix de la wilaya (profilé ou produit, marché, Rechercher) ; la wilaya reste dans les filtres des résultats. Les chiffres viennent de sm-products 0.22.1 (plus de chiffres de démonstration).

## 0.54.0 — 2026-11-20

- **« Explorer la marketplace »** (page de tous les marchés, `/recherche/`), au niveau des pages de chaque marché :
  - titre « Explorer la marketplace » quand on arrive sans recherche (« Résultats de recherche » après un mot-clé) ;
  - 3 cartes de marché (Produits sidérurgiques, Réemploi, Bâtiments : couleur, description, nombre d'annonces, « Voir le marché ») ; sur téléphone, une ligne compacte par marché ;
  - « Types avec des annonces » : les types de tous les marchés qui ont des annonces, du plus fourni au moins fourni, avec l'étiquette du marché ; un type ouvre la page de son marché déjà filtrée ;
  - panneau Filtres : « Type de produit » groupé par marché ; choisir un marché ouvre sa page (mot-clé, wilaya, prix, vente et vendeurs vérifiés gardés).
  - Après une recherche par mot-clé, les cartes du haut laissent la place aux résultats (les types restent dans le panneau).
- `sm_theme_explore()` (contrat de données) ; `type-tiles` accepte `href`, `tag` et `market`.

## 0.53.0 — 2026-11-20 (avec sm-insights 0.1.0, sm-ai 0.6.0, sm-subscriptions 0.5.0, sm-products 0.22.0)

- **Rapport Business** (`template-parts/dashboard/report.php`, `assets/css/39-report.css`, `assets/js/report.js`), menu « Rapport Business » de toute entreprise (`/compte/rapport-business/`) :
  - formule sans le rapport : « Le Rapport Business est inclus dans la formule Business » et « Passer à la formule Business » ;
  - filtres période / marché / région (liens), « Télécharger en PDF » (impression du navigateur : menu et filtres masqués, toutes les langues) ;
  - « Ce qu'il faut retenir » (✦ IA) à côté du **cercle animé** de l'acier sur le marché (neuf, réemploi, bâtiments : t et %, total au centre ; immobile si les animations sont réduites et à l'impression) ;
  - annonces en ligne, recherches, demandes de devis, bâtiments en vente — pas de nombre de ventes ;
  - prix moyens demandés (DA/kg) : neuf, évolution, réemploi, écart, votre prix ; un type s'ouvre sur ses dimensions et sa courbe 12 mois ; « pas assez d'annonces » ;
  - les plus vendus (classement seulement), les plus recherchés (barres), demandé et peu proposé (« Vous en avez … t »), par région.
- Page Assistant IA : carte « Rapport Business » avec « Ouvrir le rapport » (formule Business) ou « Voir la formule Business » ; « Sans crédit ».
- Carte des formules : ligne « Rapport Business du marché ».
- Formulaire de publication : bordures des pastilles de l'aide IA (variable `--sm-line`).

## 0.52.0 — 2026-11-20 (avec sm-ai 0.5.0)

- **Formulaire de publication — aide IA depuis les photos** (composant `ai-photo-tools`, `assets/js/ai-photo.js`), en tête de l'étape 2, hors de la partie rafraîchie :
  - **« Reconnaître depuis mes photos · 1 crédit »** (catégories du moteur, pas les tôles) : jusqu'à 3 profilés du catalogue (Probable / Possible / Peu probable, barre de confiance, raison), « Choisir » remplit le type et la dimension (le poids vient du moteur de poids) ; les questions « Pour lever le doute » ; un profilé d'une autre catégorie est montré sans bouton.
  - **« Évaluer l'état depuis mes photos · 1 crédit »** (réemploi, pas la Ferraille) : suggestion d'état et d'état de surface, « Ce que l'IA voit » / « Ce qu'elle ne peut pas voir », « Appliquer « … » et la surface » ou « Ignorer » ; si elle voit de la ferraille : lien vers la catégorie Ferraille. Avertissement permanent « Estimation visuelle, pas une inspection » avec le lien Ingénierie.
  - Rien n'est rempli sans clic. Photos utilisées : celles gardées (`keep_photos[]`) et les nouvelles du champ Photos ; sans photo : « Ajoutez d'abord vos photos ». Attente IA commune sur l'outil, bouton bloqué pendant l'appel, crédits restants affichés.
- `product-form.js` : `window.smpSet( { family, section } )` met à jour la partie technique comme un clic (un autre type efface ce qui appartenait à l'ancien).

## 0.51.0 — 2026-11-20 (avec sm-ai 0.4.0 et sm-stock 0.3.0)

- **Lire un document (DQE IA)** : vérification commune (composant `ai-doc-review`, remplace `cutting-ai-review`) avec **colonne Poids** (« du document » ou « calculé ») ; pour une ligne donnée par son poids, la longueur (12 m, liste 6 / 12) recalcule le nombre (`assets/js/ai-review.js`) : « 13,04 barres → 14 (au magasin : 13) ». Trois destinations : Optimiser la découpe, Demande de devis, Ajouter à mon magasin (Neuf / Réemploi, emplacement) ; celle de la page d'origine est mise en avant.
- Entrées : assistant IA (onglet « Lire un document », Excel/PDF/photo), optimiseur, formulaire de demande de devis (« Remplir depuis un document »), Magasin (« Ajouter depuis un document »). Page des outils IA : carte « Lire un document ».
- **Attente de l'IA** commune (`sm_ai_wait_attrs()`, `window.smAiWait`) : voile sur l'outil, repère ✦ animé, « L'IA lit votre document… Cela peut prendre jusqu'à une minute. Ne fermez pas la page. », barre indéterminée, nom du fichier et secondes ; après 60 s « La réponse tarde » ; boutons bloqués (un seul crédit) ; mouvement réduit respecté. Appliquée à la lecture de document, à la reconnaissance, à l'assistant d'annonce et à la recherche intelligente ; « L'IA rédige… 6 s » et reflet dans le champ pour la description.
- Mouvements du magasin : « Lu dans un document ».

## 0.50.0 — 2026-11-20 (avec sm-ai 0.3.0 et sm-weight-engine 0.7.0)

- Optimiseur : **« Importer un document »** (✦ IA, 1 crédit) au début de « 2. Pièces à couper » — « Choisir le fichier » (Excel, PDF, photo) et « Prendre une photo » ; le fichier part dès qu'il est choisi (« Lecture du document… »). Visiteur : « Connectez-vous pour utiliser cet outil » ; sans crédit : la raison et le lien vers les formules et packs.
- **Vérification** (composant `cutting-ai-review`) : barres avec profilé, dimension, longueur et nombre modifiables, « sûr » cochées et « à vérifier » décochées avec la raison ; « En mètres, sans longueurs de pièces » et « Tôles » vers la demande de devis ; lignes hors charpente ; « Ajouter les lignes cochées » garde la liste déjà saisie et les réglages. Document illisible : conseil pour reprendre la photo.
- Résultat : « Sans plan de coupe : vers la demande de devis » (mètres → barres, tôles avec leur poids), bouton de demande de devis même sans barre à acheter.
- Page des outils IA : carte « DQE pour l'optimiseur de découpe ».

## 0.49.2 — 2026-11-20

- Téléphones (360–390 px) : plus de défilement de côté.
  - Annuaire des entreprises : colonne des résultats limitée à l'écran (`minmax(0, 1fr)`), « Trier par » sous le nombre d'entreprises, numéros de page qui passent à la ligne (`.sm-pagination` en `flex-wrap`, utile à toutes les listes longues).
  - Listes des marchés (bâtiments…) : colonne des résultats limitée à l'écran ; le panneau des filtres fermé ne compte plus dans la largeur.

## 0.49.1 — 2026-11-20

- Optimiseur : « 4. Chutes déjà à disposition » n'apparaît plus pour les comptes qui ont un magasin (entreprises, professionnels) : à la place, « Vos chutes sont dans votre magasin : elles sont cherchées en premier » et le lien « Ajouter des chutes au magasin ». Les visiteurs et les clients gardent le point 4 et voient « Mon magasin » désactivé. Un lien partagé qui contient des chutes saisies les affiche toujours (le plan reste le même).

## 0.49.0 — 2026-11-20 (avec sm-weight-engine 0.6.0, sm-stock 0.2.0, sm-products 0.21.0)

- Optimiseur de découpe : **Mon magasin** (coché par défaut pour les entreprises et professionnels) avec Acier neuf, Réemploi, « Ne pas prendre les articles publiés sur le marché ». Visiteur ou client : grisé, « Connectez-vous avec un compte entreprise ou professionnel ».
- Résultat : chiffre « Dans mon magasin », section violette « Mon magasin » en premier (repère, état, nuance, emplacement, publié ou non, barres prises sur le total, pièces, « Voir dans le magasin »), bouton « Sortir n barres du magasin » avec confirmation ; « À acheter : Rien » quand tout vient du magasin.
- « Chutes réutilisables » : liste cochée, emplacement, « Ajouter à mon magasin réemploi ».
- Plan : étiquette « Mon magasin · N-0007 » sur chaque barre ; titre par profilé (magasin + marché + à acheter).
- Mouvements : « Sortie pour découpe » et « Chutes de l'optimiseur ».
- Détail d'une commande : « Repères dans votre magasin » (vendeur, avec l'emplacement) / « Repères pour l'enlèvement » (acheteur, après acceptation).
- Messages affichés sur la page de l'optimiseur.

## 0.48.0 — 2026-11-19 (avec sm-stock 0.1.0, sm-products 0.20.0, sm-subscriptions 0.4.0)

- **Magasin** dans le compte (entreprises et professionnels) : onglets Neuf, Réemploi, Mouvements ; page « Prix pour tout un type ».
- Composant `stock-warehouse` : chiffres clés et jauge de capacité, filtres, recherche (repère, profilé, emplacement), interrupteur « Sur le marché » (publier / garder en stock seulement), panneau repère + emplacement + correction de quantité avec motif, derniers mouvements, mise en évidence `?ref=` (QR des étiquettes). Réemploi : un repère par variante.
- Boutons « Prix pour tout un type » et « Étiquettes (PDF) » verrouillés sans les outils de la formule.
- Formulaire de publication : bouton « Garder en stock seulement » ; « Mes annonces » : statut « Au magasin seulement ».
- Formules : capacité du magasin en tonnes et outils (oui / non). CSS `assets/css/38-stock.css` (préfixe `smst-`).

## 0.47.0 — 2026-11-19 (avec sm-weight-engine 0.5.0 et sm-products 0.19.0)

- Optimiseur de découpe : « 3. Où chercher les barres ? » — Mon stock (bientôt, avec le Magasin), Marché ReuSteel (coché par défaut) avec ses filtres (nuance minimale, états acceptés, wilaya, lots entiers), barres neuves pour le reste. « 4. Chutes déjà à disposition » reste disponible.
- Résultat par provenance (`cutting-sources`) : Marché ReuSteel (annonce, vendeur vérifié, wilaya, état et nuance, prix affiché, barres et poids pris, coût estimé, « Acheter / Faire une offre », « Voir l'annonce ») puis À acheter (barres neuves, demande de devis). Chiffres clés : sur le marché, à acheter, poids à acquérir, chutes. Rappel « photo du marché, rien n'est réservé ». Dans le plan, chaque barre porte sa provenance.

## 0.46.0 — 2026-11-19 (avec sm-weight-engine 0.4.0)

- **Optimiseur de découpe** (page `/optimiseur-decoupe/`, gabarit `templates/cutting-optimizer.php`, composant `cutting-optimizer`, `assets/js/cutting.js`, `assets/css/37-cutting.css`) : barres du commerce, trait de scie, chute réutilisable dès…, pièces à couper (profilé puis dimension), « Coller une liste (Excel) », chutes déjà en stock (facultatif). Résultat : 4 chiffres clés, plan barre par barre (pièces en couleur, chutes hachurées, chutes réutilisables en jaune), demande de devis pré-remplie, PDF, impression, lien à partager. Formulaire GET (marche sans JavaScript).
- Lien « Optimiseur de découpe » dans la barre du haut, le menu mobile et le pied de page.

## 0.45.1 — 2026-11-19

- Page « Assistant IA » : carte **Rapport Business** annoncée (« Inclus dans la formule Business », « Bientôt », lien « Voir la formule Business ») ; aucun bouton d'utilisation avant la livraison de l'outil (étape 4).

## 0.45.0 — 2026-11-19 (avec sm-ai 0.2.0 et sm-subscriptions 0.3.0)

- **Page publique « Assistant IA »** (`/assistant-ia/`, gabarit `templates/ai-assistant.php`, créée à la mise à jour du thème) : chaque outil avec ce qu'il fait, son coût (« Gratuit · pour tous » ou « 1 crédit par utilisation ») et où il se trouve. Selon la situation (composant `ai-gate`) :
  - visiteur : bouton grisé, « Connectez-vous pour utiliser cet outil. » + Se connecter · Créer un compte ;
  - membre sans crédit : « Améliorez votre formule pour bénéficier de l'assistant IA, ou achetez un pack de crédits IA. » + Voir les abonnements · Acheter des crédits ;
  - membre avec crédits : il ouvre l'outil et voit ses crédits restants.
  La recherche intelligente reste ouverte à tous. Le même message remplace le bandeau « verrouillé » de l'assistant du compte.
- Accueil : « Essayer l'assistant » ouvre cette page (avant : la recherche pour un visiteur, l'assistant du compte pour un membre).
- Formulaire de publication : **« ✦ Rédiger avec l'IA »** au-dessus de la description (`ai-describe-button`, `assets/js/ai-describe.js`). Les informations saisies et les chiffres déjà calculés sont envoyés ; le texte arrive dans le champ, modifiable, avec « Annuler » pour revenir au texte précédent ; confirmation avant de remplacer une description existante. Sans crédit ou sans compte, le bouton mène à la page de l'assistant.

## 0.44.0 — 2026-11-19

- Accueil : nouvelle rangée « Outils » juste sous « Tout l'acier, au même endroit » :
  - **demande de devis en carré clair** (seul bloc clair de l'accueil sombre), avec les 3 étapes Décrivez / Recevez / Comparez, « Demander un devis » (formulaire du compte, ou connexion pour un visiteur) et « Voir les demandes ouvertes » ;
  - **carré « Assistant ✦ IA »** (affiché seulement si SM AI est actif) : les 4 outils, le rappel « suggestions à vérifier, poids du moteur », « Essayer l'assistant » (recherche intelligente pour un visiteur, assistant du compte pour un membre) ;
  - indice des prix (sombre). L'ancien bloc devis + indice du bas de page est supprimé.
- Repère « ✦ IA » (`sm_ai_badge()`, couleur violet-bleu réservée à l'IA, `assets/css/36-tools-ai.css`) sur : la recherche intelligente (et son bouton), le menu « Assistant IA » de l'espace compte, le titre de chaque outil de l'assistant, les encadrés « Importer depuis un document » (demande de devis) et « Préparer avec l'IA » / « Remplir depuis mes photos ». Jamais sur le calcul des poids.
- Page « Publier » : le texte « Bientôt : l'assistant IA… » est remplacé par le vrai lien « Remplir depuis mes photos » (affiché si l'assistant d'annonce est disponible pour le compte).

## 0.43.0 — 2026-11-18

- Tous les messages s'ouvrent au centre de l'écran, au style de la plateforme (fenêtre de SM Core 0.8.6) :
  - questions avant une action (`data-sm-confirm`) — accepter une offre, supprimer, retirer… — au lieu de la fenêtre grise du navigateur ; bouton rouge et « Annuler » par défaut pour les actions définitives ;
  - messages après l'envoi d'un formulaire : réussite (se ferme seule après 4 s ou « OK »), erreurs et avertissements (restent jusqu'au clic). Sans JavaScript, les bandeaux restent.
- Informations manquantes (`assets/js/forms.js`) : le message liste les cases à compléter ou corriger ; « Compléter » ou un clic sur la ligne fait défiler jusqu'à la case, la place au centre, y met le curseur et l'entoure ; l'étape d'un formulaire en étapes s'ouvre (`sm-reveal`, écouté par `wizard.js`), comme une section repliée. Avant l'envoi, les cases obligatoires vides sont signalées de la même façon (sauf « Enregistrer le brouillon »).
- Offre sur une demande de devis : le montant total s'affiche pendant la saisie du prix au kg (prix × poids), modifiable, avec « Arrondir au millier » / « Annuler l'arrondi » ; le prix au kg se recalcule (« Soit 98,50 DA/kg pour 12 650 kg »). En mode « Prix total », le prix au kg s'affiche. Prix, délai et validité obligatoires dès le navigateur.

## 0.42.0 — 2026-11-18

- Comptes à rebours des études (composant `mission-clock`, `assets/js/clock.js` mis à jour chaque minute) : bandeau vert, orange dans les dernières heures (ou les 3 derniers jours de l'étude), rouge « En retard de … » une fois dépassé, avec barre de progression.
  - Ingénieur : « Il reste 18 h 42 min pour répondre » (24 h après l'attribution), puis le délai de l'étude dès l'acceptation (« À remettre avant le … »). Pastille dans la liste « Mes missions ».
  - Client, pendant l'étude : « Livraison prévue le 8 déc. 2026 — il reste 19 jours · délai de votre devis : 20 jours ». Pastille dans la liste « Mes études ». Le client ne voit jamais le délai de l'ingénieur.
- Le schéma indicatif (`study-sketch.js`) et ses textes passent dans SM Engineering (utilisés aussi dans l'administration) ; le thème charge le script enregistré par l'extension.

## 0.41.0 — 2026-11-15

- La commune se choisit dans la liste de la wilaya partout où il y a une wilaya et une carte : profil d'entreprise, formulaire d'annonce (neuf, réemploi, bâtiments) et demande d'étude (composant `commune-field`). Changer de wilaya recharge la liste ; une commune tapée avant reste proposée.
- La carte se déplace sur la commune choisie (zoom ville) avec un repère suggéré non enregistré ; un repère enregistré à plus de 25 km de la nouvelle commune est retiré (« Commune changée : placez à nouveau le repère »). À l'ouverture, la carte s'ouvre sur la commune déjà choisie.
- Fiche d'annonce sans repère précis : la carte de la zone se centre sur la commune (zoom 12) au lieu de la wilaya.

## 0.40.0 — 2026-11-15

- Demande d'étude, charpente en portiques : couverture et bardage séparés. Bardage : tôle simple, panneau sandwich, mur, mixte (hauteur du mur + bardage au-dessus) ou ouvert. Le mur est hachuré sur la vue de face.
- Portes sur les 4 côtés (fermés par défaut) : long pan avant / arrière — on coche les travées T1…Tn ou on clique la travée sur la vue en plan ; pignons — 1 porte centrée ou 2 réparties. Les portes sont dessinées au centre de leur travée ; la porte du pignon gauche apparaît sur la vue de face.
- Bâtiment à niveaux : un tableau RDC, R+1… avec la hauteur et l'usage de chaque étage (« + Ajouter un étage ») ; le schéma écrit l'usage et la hauteur de chaque étage, la hauteur totale se calcule.
- Étape 4 en trois zones : Localisation (wilaya, commune obligatoire choisie dans la liste de la wilaya, repère facultatif sur la carte, vu seulement par la plateforme et l'ingénieur), Projet (intitulé, description), Documents. La date « souhaité pour » est retirée.
- La page de l'étude montre la carte de l'emplacement au client et à l'ingénieur.
- `location-map` (sélecteur) : options `no_public` et `note`.

## 0.39.0 — 2026-11-15

- Demande d'étude en 4 étapes : 1. besoin (Étude technique, Suivi et assistance, ou Expertise et inspection — un seul choix par demande ; l'étude ouvre ses 4 prestations), 2. type de projet en cartes illustrées, 3. dimensions et schéma (ou les questions du suivi / de l'expertise), 4. lieu, documents et envoi.
- Charpente en portiques (hangar, usine, agricole, élevage, équipement) : largeur, longueur, hauteur des poteaux (obligatoires), faîtage, nefs, entraxe, forme de toiture (6 formes), pont roulant (capacité, rail, nombre), mezzanine (dimensions, hauteur, usage), couverture et bardage, portes. Bâtiment à niveaux (bureaux, habitation, commerce) : largeur, longueur, niveaux, hauteur d'étage, plancher.
- Schéma indicatif dessiné pendant la saisie (`assets/js/study-sketch.js`) : vue de face cotée (poteaux, faîtage, pente, mezzanine, pont roulant) et vue en plan (portiques, travées, nefs, mezzanine, portes), surface, volume, nombre de portiques. Les cotes restent dans le bon ordre en arabe.
- Le besoin et son schéma apparaissent sur la page de l'étude, chez le client et chez l'ingénieur.
- Étude livrée : bouton « Demander le suivi de ce projet » qui reprend le projet, la wilaya et les dimensions.
- Expertise : au moins 2 photos (sauf demande faite depuis une annonce, dont les photos sont déjà en ligne).
- Titre et description deviennent facultatifs (titre automatique « Hangar de stockage 20 × 40 m »), sauf pour le type « Autre ».
- Le formulaire marche sans JavaScript (parties et formes affichées par CSS `:has`).

## 0.38.0 — 2026-11-15

- Achat de barres de plusieurs longueurs (réemploi) : deux choix, « Barres » ou « Lot complet ». L'acheteur choisit une longueur de l'inventaire et un nombre plafonné au stock (« Maximum atteint »), puis « + Ajouter une autre longueur » propose les longueurs restantes ; poids et prix calculés ligne par ligne.
- Plus de champs nom / téléphone / e-mail dans l'achat : la commande part avec les informations du compte (carte « La commande sera envoyée… »). Sans téléphone dans le compte, un lien vers les paramètres.
- « Contacter » ouvre une fenêtre partout (fiche d'annonce, bâtiment, profil d'entreprise ou de professionnel) ; comptes connectés seulement, un visiteur passe par la connexion et revient avec la fenêtre ouverte. Le formulaire « Envoyer une demande » en bas des fiches disparaît ; la fenêtre se rouvre après un envoi (message de confirmation ou erreur).
- Profil d'entreprise : téléphone (bouton « Afficher ») et e-mail retirés ; il reste le site web et « Contacter ».
- Inventaire d'un lot : une ligne de titre par produit (désignation, état, nombre de pièces, poids), puis une ligne par longueur (tôles : par format).
- « Mes annonces » : onglets Tout / Neuf / Réemploi / Bâtiments au-dessus des filtres d'état ; les cases « Regrouper en lot » n'apparaissent que dans Réemploi. Texte des champs du cadre de regroupement lisible (il était blanc sur blanc).
- « %s pièces » au lieu de « morceaux ».

## 0.37.0 — 2026-11-15

- Réemploi : tableau Longueur | Nombre (tôles : Longueur | Largeur | Nombre) avec « + Ajouter une longueur », poids de chaque ligne et total ; mode « Au poids total ». Origine, état et état de surface après les données géométriques, puis le prix et ce qu'il inclut. 2 photos demandées.
- Boulonnerie : choix en boutons, titre automatique.
- « Mes annonces » : cocher des annonces de réemploi et « Regrouper en lot » (titre, prix proposé = somme) ; « Défaire le lot » ; statut « Dans un lot ».
- Fiche d'un lot : inventaire de chaque produit avec ses longueurs, son état et son poids. Fiche d'une annonce à une dimension : tableau des longueurs et du nombre.
- Avertissement des boulons précontraints sur la fiche.

## 0.36.0 — 2026-11-15

- Liste des dimensions avec recherche (`assets/js/size-picker.js`) : tubes carrés, rectangulaires et ronds regroupés par dimension extérieure (40 × 40, 80 × 40, Ø 48,3), fers plats par largeur ; recherche « 80x40 », « 80 × 40 » ou « 80*40 » ; « + Autre dimension » ouvre la saisie des cotes. Aussi dans le calculateur de poids.
- Tôles (`plate-fields.php`) : 8 types en cartes, épaisseurs et formats en boutons, schéma de la feuille, poids estimé d'une feuille et poids réel modifiable.
- Le curseur devient une main sur toutes les cartes à choix (« Comment le vendez-vous ? »…).
- Le lien « Tableau source » du calculateur de poids est retiré : seule la norme reste affichée.
- Fiche d'une tôle : nombre de feuilles au lieu de « barres × longueur ».

## 0.35.0 — 2026-11-14

- Vue d'ensemble refaite (première page après connexion) : salutation, choix de période (30 jours, 3 mois, 12 mois, tout), 4 chiffres clés (montant vendu, montant acheté, acier en vente, acier échangé) avec évolution et petites courbes, grande courbe animée « Ventes et achats » (montant, poids, nombre ; détail d'un mois au survol ou au toucher), anneau de l'acier en vente par marché, « À traiter », annonces récentes avec leurs vues, compte et abonnement, actions rapides.
- `assets/js/overview.js` : courbes tracées à l'ouverture, chiffres qui montent, changement de période et de mesure sans recharger ; rien ne bouge avec « réduire les animations ». Dessin à la taille réelle de l'écran (texte jamais étiré).
- Icônes `arrow-up-right` et `arrow-down-left`.
- La liste des notifications n'est plus répétée sur la Vue d'ensemble (cloche et page Notifications).

## 0.34.0 — 2026-11-14

- Fiche d'une annonce de produits et de réemploi refaite (`listing-product`), sur le modèle du bâtiment : à droite état, titre, localisation, prix, actions, vendeur, carte et visite, inspection (réemploi) ; à gauche galerie, « Le lot en vente » en 4 chiffres clés (le reste à déplier), inventaire d'un lot à déplier, documents, description, message au vendeur.
- Prix : le prix au kg en grand, « soit … la barre de 12 m », ce qui est disponible, puis « ou le lot complet ».
- Achat au détail en nombre de barres (poids et total calculés) ; offre au kg sur des barres ; choix « Au détail » / « Lot complet ».
- Galerie : la fiche de la section reste une image même quand il y a des photos (en premier pour l'acier neuf, en dernier pour le réemploi).
- Demande d'inspection : type de projet pré-sélectionné pour un bâtiment ; titre du bloc selon le cas (bâtiment ou acier).
- Bouton « Contacter » dans la petite ligne d'actions (bâtiment et produits).

## 0.33.0 — 2026-11-14

- Fiche d'un bâtiment refaite (`listing-building`) : à droite le titre, la localisation, le prix, les actions, le vendeur, la carte et la demande d'inspection ; à gauche les photos, les caractéristiques géométriques (4 chiffres clés et schéma, le reste à déplier), l'inventaire (titre, pièces et poids, tableau à déplier), ce que comprend le prix (écrit en clair, plus de texte barré), la description. Sur téléphone : photos, titre, prix et actions d'abord.
- Prix d'un bâtiment : le total en grand (« Prix du bâtiment complet »), dessous « soit … DA/kg », puis « ou achat par élément » si le vendeur l'autorise ; prix de l'acier neuf seulement si l'indice des prix a une valeur. Même logique sur les cartes d'annonces.
- Achat et offre : plus de quantité en kg pour un bâtiment. Lot entier : total et offre en prix total. Par élément : choix des pièces de l'inventaire, poids et total calculés, offre au kg.
- Publication : « Lot entier » et / ou « Par élément », deux prix liés (au kg, bâtiment entier).
- Carte : sans position exacte visible, carte de la wilaya sans repère ni itinéraire.
- Demande d'étude : ouverte depuis un bâtiment, elle arrive pré-remplie (bâtiment, dimensions, lien).
- Message au vendeur : pas de « quantité souhaitée » pour un bâtiment. Compte : pièces d'une commande par élément.
- Composants extraits : `listing-gallery`, `listing-inventory`, `listing-seller`.

## 0.32.0 — 2026-11-14 (avec sm-products 0.13.0 et sm-buildings 0.3.0)

- Formulaire bâtiment, étape 3 : les 4 systèmes porteurs en cases de même largeur, le choix actif en couleur pleine (2 × 2 sur téléphone) ; case « Le bâtiment a un pont roulant » avec capacité (t) facultative.
- Étape 4 : éléments proposés automatiquement à partir des dimensions pour un portique poteau / traverse (bandeau « Proposition calculée… »). La proposition suit les dimensions tant que le vendeur n'a touché aucune ligne ; ensuite « Recalculer à partir des dimensions » remplace les lignes après confirmation. Colonne « Nbr pièces » (tous les marchés).
- Étape 5 : cartes « Monté » / « Démonté » sans phrase sur la vente ; état de l'acier en cases égales sur toute la largeur (2 × 2 sur téléphone) ; prix au kg possible pour un bâtiment monté, avec « Total indicatif » quand le poids vient des seuls éléments déclarés (formulaire et page de l'annonce) ; explication sous le titre automatique supprimée.
- Poids affiché « 41,79 t — (41 789 kg) » partout.
- Même espace avant chaque bloc et chaque titre de champ du formulaire de publication.
- Nouvelle donnée `product_form_state` (les valeurs du formulaire complétées par un plugin de marché).

## 0.31.0 — 2026-11-13 (avec sm-products 0.12.0 et sm-buildings 0.2.0)

- Publier un bâtiment métallique : formulaire en 7 étapes — 1 type, 2 géométrie (longueur, largeur, portiques, hauteur au poteau, hauteur de toiture, étages, mezzanine ; surface, entraxe et pente calculés), 3 système porteur et enveloppe, 4 éléments de la structure, 5 état, poids et prix (ce que comprend le prix, titre automatique), 6 description, photos et fiche technique, 7 localisation.
- Localisation de toute annonce : commune, adresse ou lieu-dit, position sur la carte (obligatoire pour un bâtiment, facultative ailleurs), case « Afficher la carte au public » (décochée par défaut).
- Page de l'annonce : bloc « Localisation » (adresse ; carte seulement pour ceux qui y ont droit), « Demander une visite » (date, heure, message) pour les bâtiments et les ventes en lot, suivi de la demande.
- Compte : « Demandes de visite » (vendeur : accepter, refuser, proposer une autre date) et « Mes visites » (acheteur : confirmer, annuler, voir la carte).
- Poids « éléments déclarés » affiché comme tel (bloc prix, caractéristiques, inventaire).

## 0.30.1 — 2026-11-13

- Cache de pages : un membre connecté ne doit plus voir « Se connecter » / « Créer un compte » dans le menu quand le cache de l'hébergeur lui sert une page faite pour un visiteur.
  - LiteSpeed reçoit la consigne de garder une version distincte pour les membres connectés (`X-LiteSpeed-Vary`).
  - Sécurité : la page indique pour qui elle a été faite (`data-sm-user`), un petit témoin lisible par la page (`sm_in`, sans identité ni droit d'accès) indique si le navigateur est connecté ; s'ils diffèrent, le menu du haut et le bas du menu mobile sont redemandés hors cache (`admin-ajax.php?action=sm_user_nav`) et remplacés. Aucune requête en plus dans le cas normal.
- Les actions de l'en-tête et le bas du menu mobile sont des parties de gabarit (`template-parts/header-actions.php`, `drawer-foot.php`).
- Test navigateur `tests/e2e-cache.py` (17 vérifications).

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

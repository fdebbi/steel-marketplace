# SM Marketplace — thème WordPress

Thème de présentation sur mesure de la plateforme **Steel Marketplace**, bilingue arabe (RTL) / français (LTR), responsive mobile → desktop.
Le thème ne contient **aucune logique métier** : pas de SQL, pas de calcul de poids ni de prix. Les données viennent des extensions `sm-*` via un contrat de données documenté ; tant qu'elles n'existent pas, un fournisseur de démonstration affiche des annonces réalistes.

- Version : 0.4.0 — WordPress 6.4+ (testé 7.1.4), PHP 8.1+ (testé 8.3)
- Fonctionne avec l'extension **sm-core** (comptes, formulaires, langues `/ar/`) ; sans elle, mode démonstration
- Domaine de traduction : `sm-marketplace` — langues livrées : `fr_FR`, `ar`
- Aucune dépendance externe en production (polices, icônes et bibliothèque de carte Leaflet auto-hébergées, pas de jQuery, pas de constructeur de pages). Seules les images de fond de carte viennent d'OpenStreetMap, sur les pages qui affichent une carte.

## Installation (Hostinger ou tout hébergement WordPress)

1. **Apparence → Thèmes → Ajouter → Téléverser** : choisir `sm-marketplace.zip`, puis **Activer**.
   (Ou envoyer le dossier `sm-marketplace/` dans `wp-content/themes/` via le gestionnaire de fichiers Hostinger.)
2. À l'activation, le thème crée les pages manquantes (Accueil, Produits, Réemploi, Bâtiments, Annonce, Compte, etc.) et définit la page d'accueil. Rien n'est écrasé. Pour relancer : **Apparence → Pages SM**.
3. **Réglages → Permaliens** : choisir « Titre de la publication » puis enregistrer.
4. **Réglages → Général → Langue du site** : installer aussi l'**arabe** une fois (Hostinger le télécharge automatiquement). Le thème fonctionne sans, mais les textes internes de WordPress (connexion, messages système) seront alors en anglais côté arabe.
5. **Apparence → Personnaliser → Marque SM** : couleurs principale, accent, secondaire, fond ; nom de marque ; logo dans « Identité du site ». Tout le site suit ces couleurs.
6. Désactiver les annonces de démonstration (même écran, case « Afficher les annonces de démonstration ») avant la mise en ligne publique.

## Langues

- Français par défaut (décision Phase 0). Sélecteur dans l'en-tête, le menu mobile et le pied de page.
- Avec sm-core : l'arabe est servi sous `/ar/` (ex. `/ar/produits/`), le français sans préfixe ; le choix est mémorisé (cookie et compte). Sans sm-core : `?lang=ar` + cookie.
- Les désignations techniques (IPE 200, HEA, S275JR), unités (mm, m, kg, m²) et chiffres (0-9) restent identiques dans les deux langues et sont isolées (`<bdi>`) pour un affichage correct dans les phrases arabes.

## Développement

```bash
npm install          # installe RTLCSS (outil de build uniquement)
npm run build        # assets/css/*.css -> dist/main.css + dist/main-rtl.css
python3 tools/compile-translations.py   # tools/translations.py -> languages/*.po|mo  (pip install polib)
python3 tools/visual-test.py http://localhost:8080 ./out   # tests responsive AR/FR (pip install playwright)
```

Régénérer le POT après ajout de chaînes : `wp i18n make-pot . languages/sm-marketplace.pot --domain=sm-marketplace --exclude=node_modules,dist,tools --skip-js`.

## Structure

```
sm-marketplace/
├── style.css, functions.php, index.php, page.php, 404.php, header.php, footer.php
├── inc/
│   ├── compat/language.php     langue provisoire (remplacée par sm-core en Phase 2)
│   ├── setup.php               supports, menus, tailles d'images, hreflang, dir
│   ├── customizer.php          couleurs de marque modifiables (wp-admin)
│   ├── assets.php              CSS/JS, RTL, polices, sprite d'icônes
│   ├── template-tags.php       helpers d'affichage (sm_icon, sm_component, sm_money, sm_per_unit, sm_tech…)
│   ├── data-contract.php       contrat de données lu par les gabarits
│   ├── menus.php               méga-menu et sélecteur de langue
│   └── demo/                   données de démonstration + installateur de pages
├── templates/                  gabarits de pages (accueil, marchés, annonce, entreprise, auth, tableau de bord…)
├── template-parts/
│   ├── components/             cartes, bloc prix, badge d'état, filtres, état vide
│   └── dashboard/              vues du tableau de bord (commercial, ingénieur, publication)
├── assets/ (css source, js, fonts, icons, demo)
├── dist/                       CSS compilé LTR + RTL
├── languages/                  .pot, fr_FR.po/.mo, ar.po/.mo
├── docs/                       système de design, contrat de données, rapport de tests
└── tools/                      build, traductions, tests visuels
```

Voir `docs/DESIGN-SYSTEM.md`, `docs/DATA-CONTRACT.md` et `docs/TEST-REPORT.md`.

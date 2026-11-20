# SM Insights

Rapport Business du marché de l'acier de ReuSteel (formule Business, sans crédit).

- **Filtres** : période (30 jours, 3 mois, 12 mois), marché (Tous, Neuf, Réemploi), région (Algérie, Centre, Est, Ouest, Sud — filtre `sm_insights_regions`).
- **Ce qu'il faut retenir** : 3 ou 4 points écrits par l'IA (sm-ai, filtre `sm_ai_report_summary`) à partir des chiffres calculés ici ; jamais de nombre de ventes ; gardés pour la journée. Une phrase sur les prix de l'entreprise, écrite par la plateforme, s'y ajoute.
- **Acier sur le marché** : poids mis en vente en neuf, réemploi et bâtiments (t et %), toujours les trois marchés.
- **Chiffres** : annonces en ligne, recherches, demandes de devis, bâtiments en vente, avec l'évolution sur la période précédente.
- **Prix moyens demandés (DA/kg)** par type de profilé, neuf et réemploi, évolution, écart du réemploi, « Votre prix » ; au clic : les dimensions et la courbe sur 12 mois.
- **Les plus vendus** (classement seulement), **les plus recherchés** (recherches du catalogue + lignes des demandes de devis), **demandé, peu proposé** (avec « Vous en avez … t » d'après le magasin), **par région**.

## Règles internes (jamais affichées telles quelles)

- Seulement des prix demandés, des poids mis en vente et des nombres d'annonces, de recherches et de demandes ; aucun nom d'entreprise, aucune quantité ni montant de vente.
- Une moyenne seulement avec au moins 5 annonces (`sm_insights_min_sources`) ; à partir de 10, les 10 % extrêmes sont écartés.
- Une annonce compte pour une période si elle a été en ligne pendant celle-ci (publiée avant la fin, encore en ligne, ou vendue / expirée / en pause après le début).
- Les recherches sont comptées par profilé, marché, wilaya et jour (table `sm_search_counts`), sans savoir qui cherche : un visiteur compte une fois par jour et par profilé, les robots et les compteurs en direct ne comptent pas ; gardées 400 jours.

## Données du thème

- `insights_enabled` : la page est dans le menu (toute entreprise ; un professionnel si sa formule l'inclut).
- `insights_report` ( period, market, region ) → `{ state: ok|login|none|upgrade, args, options, report, summary, mine }`.

## Filtres

- `sm_insights_access( true, $company )` — rapport permis (sm-subscriptions : case « Rapport Business » des formules, Business par défaut).
- `sm_insights_min_sources( 5 )`, `sm_insights_regions( $map )`.
- Écoute `sm_products_searched` (sm-products 0.22.0).

## Dépendances

SM Core, SM Products ≥ 0.22.0 ; facultatifs : SM AI ≥ 0.6.0 (résumé), SM Subscriptions ≥ 0.5.0 (formule Business), SM RFQ, SM Trade, SM Stock. Thème ≥ 0.53.0.

## Tests

`wp eval-file wp-content/plugins/sm-insights/tests/run.php` (site de test) ; navigateur : `tests/e2e-insights.py`.

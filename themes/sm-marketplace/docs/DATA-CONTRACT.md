# Contrat de données thème ↔ extensions

Les gabarits ne lisent jamais la base. Ils appellent `sm_theme_data( $ressource, $args )` (voir `inc/data-contract.php`), qui interroge le filtre `sm_theme_data_{ressource}`. Une extension répond en renvoyant des données dans la forme ci-dessous ; si personne ne répond et que le mode démo est actif, `SM_Theme_Demo_Data` répond.

```php
// Exemple pour sm-products (Phase 4)
add_filter( 'sm_theme_data_listings', function ( $result, $args ) {
	if ( null !== $result || ! in_array( 'products', (array) $args['market'], true ) ) {
		return $result;
	}
	return SM\Products\Service\ListingQuery::for_theme( $args ); // ['items'=>[], 'total'=>int, 'pages'=>int]
}, 10, 2 );
```

## Ressources

| Ressource | Arguments | Retour |
| --- | --- | --- |
| `listings` | market (string ou tableau), category, condition, mode, wilaya, company, q, featured, sort, per_page, page | `items`, `total`, `pages` |
| `listing` | id | une annonce ou `null` |
| `companies` / `company` | class / slug | tableau / une entreprise |
| `services` | kind (fabrication, engineering), company | tableau |
| `categories` | market | `slug => [fr, ar]` |
| `wilayas` | — | `code => [fr, ar]` |
| `account` | — | compte connecté : class, company, plan, usage, kpis |
| `stats`, `plans` | — | chiffres d'accueil, abonnements (prix fournis par sm-subscriptions) |

## Forme d'une annonce

Un seul enregistrement par annonce, toutes langues comprises : les champs textuels sont `['fr' => '…', 'ar' => '…']`, affichés par `sm_t()` / `sm_e()` avec repli sur la langue disponible.

| Champ | Exemple | Remarque |
| --- | --- | --- |
| id, market, category | 101, products, beams | market ∈ products, reuse, buildings |
| designation, grade | IPE 200, S275JR | jamais traduits |
| title, description | `['fr'=>…, 'ar'=>…]` | multilingue |
| length_m, quantity | 12, 24 | |
| weight_kg, weight_source | 6451, theoretical | `theoretical` ou `actual`, **calculé par sm-weight-engine** |
| price_per_kg, total_price | 245, 1 580 495 | DZD ; total calculé côté serveur |
| condition | good | réemploi : new_surplus, excellent, good, inspection, scrap |
| mode, length, width, height, portals, elements[] | dismantled… | bâtiments ; `elements` = type, designation, qty, length, unit_weight, total |
| includes | dismantling, transport, reinstall, foundations | booléens |
| wilaya, company, featured, date, image | 28, acier-hodna… | image = ID de pièce jointe ou URL |

## Autres points d'intégration

- `sm_page_url( $clé )` / filtre `sm_page_url` : les extensions fournissent leurs vraies routes.
- `do_action( 'sm_slot', $nom, $ctx )` : emplacements d'interface — `listing.aside`, `publish.summary`, `price-index.body`.
- `do_action( 'sm_dashboard_view', $vue, $compte )` : vues du tableau de bord fournies par les extensions.
- Filtres `sm_languages`, `sm_default_language`, `sm_theme_nav_items`, `sm_component_path`, `sm_login_action`.
- Fonctions de langue reprises par sm-core si elles existent : `sm_current_language()`, `sm_language_url()`.

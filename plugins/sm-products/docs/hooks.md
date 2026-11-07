# Points d'intégration — sm-products

| Élément | Usage |
| --- | --- |
| `sm_theme_data_listings` (market = `products`) | Liste publiée : `items` (forme « annonce »), `total`, `pages`. Arguments : category, family, wilaya, company, q, min_price, max_price, sort (`price-asc`, `price-desc`, `weight`), per_page, page. |
| `sm_theme_data_listing` (`id` = numéro public) | Fiche : forme annonce + `photos`, `sheets` [{name, url, size}], `spec_rows` [[libellé, HTML sûr]], `drawing` (SVG), `status`, `unavailable` (vendu / expiré). Visible pour tous si publiée ; aperçu pour l'entreprise et les modérateurs. |
| `sm_theme_data_categories` (market = `products`) | `slug => [fr, ar]`. |
| `sm_theme_data_product_categories` | Catégories complètes : kind (`engine` / `specs`), families, units, fields. |
| `sm_theme_data_product_form` (`id`) | Valeurs d'un produit modifiable par l'utilisateur, statut, motif de refus. |
| `sm_theme_data_product_preview` (valeurs du formulaire) | `errors`, `summary` (total_weight, pieces, total_price, price_per_kg…), `calc` (résultat du moteur). |
| `sm_theme_data_my_products` (`status`, `page`) | Produits de l'entreprise connectée + `counts` par statut. |
| `sm_theme_data_product_settings` | grades, max_photos, photo_mb, sheet_mb, moderation. |
| `admin-post.php?action=sm_product_save` | Champs : category, product_id, family, section, dims[], grade, length / length_custom, width, plate_length, qty_mode, bars, total_kg, weight_source, actual_weight, specs[], quantity, unit, price, price_unit, title[fr\|ar], description, wilaya, photos[], sheets[], keep_photos[], keep_sheets[], intent (`draft`, `submit`, `refresh`). |
| `admin-post.php?action=sm_product_action` | product_id, do (`sold`, `withdraw`, `republish`, `delete`). |
| Notifications | `product.submitted` (modérateurs), `product.approved`, `product.rejected`, `product.expired`. |
| Tables | `{prefix}sm_product_categories`, `{prefix}sm_products` ; textes dans `sm_translations` (entity `product`), fichiers dans `sm_media_links` (roles `photo`, `sheet`), index dans `sm_listing_index` (module `products`). |
| Cron | `sm_products_daily` : expiration des annonces. |

Chaque produit enregistre `section_id`, `engine_version` et `ref_version` du moteur de poids : un poids peut toujours être retracé.

## Marchés (0.2.0)

| Élément | Usage |
| --- | --- |
| `sm_products_markets` (filtre) | `code => [label {fr, ar}, intro {fr, ar}, icon, cap, conditions {code => {fr, ar}}, fields [{key, type select\|bool, label, options, required}]]`. |
| `sm_products_spec_check` (filtre) | `($errors, $category, $data, $input)` → erreurs supplémentaires `champ => message`. |
| `sm_products_listing_detail` (filtre) | `($detail, $product)` → données de fiche (sm-reuse ajoute `advice`). |
| `sm_theme_data_product_markets` | Marchés avec `allowed`, `reason`, `count`. |
| `sm_theme_data_product_categories` (`market`) | Catégories d'un marché (toutes sinon), avec `market`. |
| `sm_theme_data_listings` | `market` : un code ou une liste ; filtres `condition`, `length_min` (m), `qty_min`. Une recherche qui mêle un marché non servi est laissée au mode démo tant qu'il est actif. |
| `Installer::insert_categories( $market, $categories )` | Création initiale des catégories d'un marché (une seule fois ; modifiables ensuite dans wp-admin). |
| Champs du formulaire | `condition`, `attrs[clé]`, `lines[i][family\|section\|qty\|length]`, `total_kg`, `weight_source` (`theoretical`, `actual`, `estimated`), `price_unit` `lot`. |

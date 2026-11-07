# SM Reuse — marché du réemploi de l'acier

Extension de **ReuSteel** (Phase 5). Nécessite **SM Core 0.3.0**, **SM Weight Engine 0.1.0** et **SM Products 0.2.0**.

## Ce qu'elle fait

- Ajoute le marché **Réemploi** au moteur d'annonces de SM Products (même formulaire, mêmes photos, modération, recherche, fiche et « Mes annonces »).
- **Six catégories** créées à l'activation, modifiables ensuite dans wp-admin (*SM Products → Catégories*) :
  - *Chutes de profilés et barres*, *Profilés de démontage*, *Tubes de réemploi* : **inventaire ligne par ligne** (type, profil du catalogue européen, nombre de pièces, longueur). Le poids de chaque ligne et du lot est calculé par SM Weight Engine ; le vendeur peut donner le poids pesé.
  - *Chutes de tôles* (épaisseur obligatoire), *Lots mixtes*, *Ferraille* (lourde, légère, mélangée) : **lot au poids**, pesé ou estimé — l'acheteur voit la différence.
  - Prix au kg, à la pièce ou **pour le lot entier** (avec équivalent au kg).
- **Échelle d'état** du cahier des charges : surplus neuf non utilisé, occasion excellent état, occasion bon état, à inspecter ou à traiter, ferraille / recyclage uniquement.
- Champs communs : origine (obligatoire), état de surface, découpe à la demande, chargement sur place.
- **Règles** contrôlées par le serveur : la ferraille se publie uniquement dans la catégorie *Ferraille* et avec l'état « Ferraille » ; la découpe à la demande ne concerne que les barres et profilés ; l'état et l'origine sont obligatoires.
- **Avis d'ingénieur** sur chaque fiche de réemploi : l'acier d'occasion ne se réemploie dans une structure qu'après vérification par un ingénieur qualifié ; les informations sont déclarées par le vendeur et ne sont pas certifiées par la plateforme. Avertissement renforcé pour « à inspecter » ; note « recyclage uniquement » pour la ferraille. Lien vers l'annuaire des ingénieurs.
- Permission `listing.reuse` de SM Core : les entreprises commerciales publient (y compris les ateliers de fabrication) ; un compte ingénierie ne publie jamais.

## Installation

1. Activer SM Core, SM Weight Engine, SM Products, puis SM Reuse.
2. Les catégories sont créées une seule fois. La page publique est « Réemploi » (`/reemploi/`) du thème.

## Points d'intégration

| Élément | Usage |
| --- | --- |
| `sm_reuse_market` (filtre) | Modifier la définition du marché (libellés, états, champs). |
| `sm_products_markets` | Utilisé pour ajouter le marché `reuse`. |
| `sm_products_spec_check` | Règles ferraille / état / découpe. |
| `sm_products_listing_detail` | Ajoute `advice` {level, title, text, link_label, link_page} à la fiche. |
| Option | `sm_reuse_db_version`. |

## Tests

```
wp eval-file wp-content/plugins/sm-reuse/tests/run.php     # 38 tests (site de TEST)
python3 tests/e2e-reuse.py URL WP_PATH OUT                 # 26 vérifications navigateur
```

Les poids attendus sont lus dans le moteur de poids : aucune donnée de référence n'est écrite dans les tests.

## Traductions

`languages/` : fr_FR et ar (14 chaînes). Les libellés des états, champs et catégories sont stockés en français et en arabe. Régénérer : `python3 tools/compile-translations.py`.

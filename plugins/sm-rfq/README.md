# SM RFQ — demandes de devis

Extension de **ReuSteel** (Phase 7). Nécessite **SM Core 0.3.0**, **SM Weight Engine 0.1.0** et **SM Products 0.3.0**.

## Ce qu'elle fait

- **L'acheteur** (entreprise commerciale, permission `rfq.create`) publie une demande depuis *Mon compte → Mes demandes de devis* :
  - marché (produits neufs, réemploi, bâtiments, autre) et catégorie facultative ;
  - **lignes de profils** (type, profil du catalogue européen, quantité, longueur) pesées par SM Weight Engine, ou **description libre** (produit, dimensions, quantité et unité, poids s'il est connu) ;
  - titre FR/AR, description technique, notes ;
  - wilaya et lieu de livraison, date de livraison souhaitée, **date limite des offres** (proposée et bornée par les réglages) ;
  - plans et documents (PDF, images), visibles uniquement par les entreprises connectées.
- **Les fournisseurs et fabricants** (permission `rfq.offer`) répondent depuis la page de la demande : prix total ou prix au kg, poids sur lequel porte le prix (sinon celui de la demande), délai, livraison incluse, validité, conditions de paiement, message, documents. Une offre par entreprise, modifiable tant que l'acheteur n'a pas décidé, ou retirable.
- **Confidentialité** : l'acheteur voit toutes les offres ; chaque fournisseur ne voit que la sienne ; les visiteurs ne voient aucune offre.
- **Comparaison** pour l'acheteur : offres côte à côte, tri par total, prix au kg, délai ou fournisseurs vérifiés ; le moins cher, le meilleur prix au kg et le plus rapide sont signalés ; téléphone et e-mail du fournisseur ; présélectionner, refuser, **accepter** (la demande est attribuée, les autres offres refusées, tout le monde prévenu).
- **Règles** : pas d'offre sur sa propre demande ; un compte ingénierie ne publie ni ne répond ; une demande qui a reçu des offres ne se modifie plus (on la clôture) ; après la date limite, plus d'offres, mais l'acheteur peut encore accepter.
- **Notifications** (dans l'application et par e-mail, dans la langue de chacun) : offre reçue, mise à jour, retirée ; offre acceptée, refusée ; demande attribuée à un autre, clôturée, expirée ; modération.
- **Tâche quotidienne** `sm_rfq_daily` : demandes dont la date limite est passée → « délai dépassé ».
- **Administration** (*SM → Demandes de devis*) : demandes par statut, modération facultative (publier, demander des modifications, dépublier), réglages (délais, fichiers, limite quotidienne, prix minimal et maximal).
- **REST** : `GET sm/v1/rfqs`, `GET sm/v1/rfqs/{id}` (sans les offres).

## Données

Tables `{prefix}sm_rfqs` et `{prefix}sm_rfq_offers` ; textes dans `sm_translations` (entité `rfq` : title, description, notes) ; fichiers dans `sm_media_links` (`rfq` / `rfq_offer`, rôle `file`). Adresse publique : `/demandes-de-devis/{numéro}/`.

## Contrat de données (thème)

`rfqs`, `rfq` (avec `viewer`, `my_offer`, `offers` pour l'acheteur), `rfq_form`, `rfq_preview`, `my_rfqs`, `my_offers`, `rfq_settings`. Formulaires : `sm_rfq_save`, `sm_rfq_action` (close, delete), `sm_rfq_offer`, `sm_rfq_offer_action` (withdraw, shortlist, decline, accept).

## Tests

```
wp eval-file wp-content/plugins/sm-rfq/tests/run.php   # 39 tests (site de TEST)
python3 tests/e2e-rfq.py URL WP_PATH OUT               # 25 vérifications navigateur
```

## Traductions

`languages/` : fr_FR et ar (106 chaînes). Régénérer : `python3 tools/compile-translations.py`.

# SM Services — services de fabrication, de chantier et d'ingénierie

Extension de **ReuSteel** (Phase 8). Nécessite **SM Core 0.3.3**.

## Ce qu'elle fait

- **Deux catalogues séparés** (`data/catalogue.php`) :
  - *Fabrication et chantier* (permission `service.fabrication`, comptes entreprise ayant l'activité fabrication, montage…) : découpe, perçage, soudage, charpente, poutres PRS, poteaux, assemblage, sablage, peinture, galvanisation, montage, démontage, coordination du transport ; prix par projet, kg, t, m², ml, pièce, heure ou jour.
  - *Ingénierie* (permission `service.engineering`, comptes ingénierie uniquement) : conception, notes de calcul, assemblages, plans techniques, plans d'atelier, plans de fabrication, inspections, expertise de structures d'occasion, conseil ; prix par projet, m², jour ou heure — **jamais au kg**.
  - Un compte ingénierie ne voit que le catalogue ingénierie ; un compte entreprise que la fabrication. Les deux catalogues ont chacun leur page publique (`/services/`, `/ingenierie/`).
- **Pages de service** (*Mon compte → Mes services*) : services cochés (6 au maximum par page), titre FR/AR, description, capacités techniques, équipements, zone d'intervention (toute l'Algérie ou liste de wilayas), délai habituel, prix « sur devis » ou « à partir de », photos. Pause, remise en ligne, suppression. Modération facultative. Adresse publique : `/service/{numéro}/`.
- **Recherche** : par catalogue, service, wilaya desservie (liste, toute l'Algérie, ou wilaya de l'entreprise) et mot-clé dans les textes FR/AR.
- **Demandes de service** : un client connecté (permission `service.request`) décrit son projet (description, wilaya, date souhaitée, budget indicatif, plans PDF/images). Une seule demande ouverte par client et par service ; limite quotidienne réglable.
  - Le prestataire répond par un **devis** (montant, délai, message) ou décline ; il peut mettre à jour son devis tant que le client n'a pas décidé.
  - Le client **accepte**, refuse ou annule. Le téléphone et l'e-mail du prestataire ne lui sont donnés qu'après acceptation ; le prestataire voit ceux du client dès la demande.
- **Profil d'ingénieur** : années d'expérience (SM Core 0.3.3), qualifications et spécialités (déjà dans SM Core), **références** (*Mon compte → Références*) : titre FR/AR, rôle, année, wilaya, description, photo ; affichées sur la page de l'entreprise et sur chaque page de service.
- **Notifications** (application + e-mail, langue de chacun) : page soumise, publiée, à modifier ; demande reçue ; devis reçu ; demande déclinée ; devis accepté, refusé ; demande annulée.
- **Administration** (*SM → Services*) : pages par statut et catalogue, modération (publier, demander des modifications, dépublier), réglages (modération, pages par entreprise, photos, fichiers, demandes par jour, références, prix maximal).
- **REST** : `GET sm/v1/services`, `GET sm/v1/services/{id}`.

## Ce qu'elle ne fait pas

- Aucune vente d'acier : les comptes ingénierie restent sans permission commerciale (`listing.*`, `rfq.*`, `order.*`, `buy`, `service.fabrication`).
- Aucun calcul : la plateforme met en relation ; une note de calcul ou un plan reste l'œuvre et la responsabilité de l'ingénieur.

## Données

Tables `{prefix}sm_services`, `{prefix}sm_service_requests`, `{prefix}sm_portfolio` ; textes dans `sm_translations` (entités `service` : title, description, capabilities, equipment ; `portfolio` : title, description, role) ; fichiers dans `sm_media_links` (`service` / `portfolio` rôle `photo`, `service_request` rôle `file`).

## Contrat de données (thème)

`services`, `service` (avec `viewer`, `capabilities`, `equipment`, `portfolio`), `service_form`, `my_services`, `service_requests` (box `received` / `sent`), `portfolio`, `services_settings`. Formulaires : `sm_service_save`, `sm_service_action` (pause, resume, delete), `sm_service_request`, `sm_service_reply` (quote, decline), `sm_service_decide` (accept, decline, cancel), `sm_portfolio_save`, `sm_portfolio_delete`.

## Tests

```
wp eval-file wp-content/plugins/sm-services/tests/run.php   # 32 tests (site de TEST)
python3 tests/e2e-services.py URL WP_PATH OUT                # 39 vérifications navigateur
```

## Traductions

`languages/` : fr_FR et ar (107 chaînes). Régénérer : `python3 tools/compile-translations.py`.

# API REST `sm/v1`

Base : `https://votre-site/wp-json/sm/v1`. Langue des réponses : `?lang=fr|ar`. Authentification : cookie WordPress + en-tête `X-WP-Nonce` (site) ou mots de passe d'application en HTTPS (applications mobiles, Phase 12).

| Méthode | Route | Accès | Réponse |
| --- | --- | --- | --- |
| GET | `/reference/wilayas` | public | `[{code, name, names:{fr,ar}, centre:[lat,lng]}]` (69) |
| GET | `/reference/activities` | public | `[{code, class, label}]` |
| GET | `/companies?class=&activity=&wilaya=&search=&page=&per_page=` | public | Entreprises actives, en-têtes `X-WP-Total`, `X-WP-TotalPages` |
| GET | `/companies/{slug}` | public | Fiche publique (aucun numéro RC/NIF/NIS ni e-mail privé) ; `location: {lat, lng}` ou `null` si l'entreprise masque sa carte |
| GET | `/me` | connecté | Utilisateur, entreprise + rôle, carte des droits, notifications non lues |
| PATCH | `/me` | connecté | `{language}` |
| PATCH | `/me/company` | titulaire / gestionnaire | Champs de profil ; `activities` (comptes professionnels) ; `lat` + `lng` (dans l'Algérie, sinon 400 `sm_location` ; vides = suppression), `map_public`. La classe et le niveau ne sont jamais modifiables |
| GET | `/me/can/{cap}` | connecté | `{cap, allowed, reason, message}` |
| GET | `/me/notifications` | connecté | 50 dernières |
| POST | `/me/notifications/{id}/read` | connecté | `{updated}` |
| GET | `/me/enquiries` | connecté | `{received, sent}` |

Exemple :

```bash
curl https://votre-site/wp-json/sm/v1/companies?class=engineering&wilaya=25&lang=ar
```

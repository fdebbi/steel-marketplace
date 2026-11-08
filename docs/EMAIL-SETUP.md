# Mise en place des e-mails (WP Mail SMTP)

Sans configuration, WordPress envoie les e-mails par le serveur web : ils arrivent souvent en spam, ou n’arrivent pas. La plateforme passe par **WP Mail SMTP**, qui fait partir chaque e-mail par un compte e-mail authentifié du domaine **reusteel.com**.

Ce guide se fait une fois, en 20 à 30 minutes. Il faut l’accès à WordPress (administrateur) et à la zone DNS de reusteel.com.

## 1. Créer l’adresse d’envoi

Créez la boîte **contact@reusteel.com** chez votre hébergeur e-mail. Si le site est chez Hostinger : *hPanel → E-mails → Comptes e-mail → Créer*.

Notez son mot de passe : WP Mail SMTP en aura besoin.

## 2. Installer WP Mail SMTP

1. *Extensions → Ajouter* → chercher **WP Mail SMTP** (éditeur WPForms) → Installer → Activer.
2. L’assistant s’ouvre. Choisissez le service d’envoi :
   - **Option A — la boîte de votre hébergeur** (simple, suffisant au lancement) : choisissez **Other SMTP**, puis :

     | Réglage | Valeur (Hostinger) |
     | --- | --- |
     | SMTP Host | `smtp.hostinger.com` |
     | Encryption | SSL |
     | SMTP Port | 465 |
     | Authentication | activée |
     | Username | `contact@reusteel.com` |
     | Password | le mot de passe de la boîte |

     Chez un autre hébergeur, les valeurs se trouvent dans ses « paramètres de configuration e-mail ».
   - **Option B — un service d’envoi** (meilleure délivrabilité quand le volume grandit) : choisissez **Brevo** dans la liste et collez la clé API créée dans votre compte Brevo (*SMTP & API → Clés API*). L’offre gratuite de Brevo permet 300 e-mails par jour.
3. **From Email** : `contact@reusteel.com`, cochez *Force From Email*.
   **From Name** : `ReuSteel`.
4. Terminez l’assistant, puis *WP Mail SMTP → Outils → Email Test* : envoyez un test vers une adresse Gmail et vérifiez qu’il arrive **dans la boîte de réception**.

## 3. Authentifier le domaine (DNS)

Ces trois enregistrements disent aux messageries que les e-mails de reusteel.com sont légitimes. Ajoutez-les dans la zone DNS du domaine (chez Hostinger : *hPanel → Domaines → DNS*).

| Type | Nom | Valeur | Rôle |
| --- | --- | --- | --- |
| TXT | `@` | `v=spf1 include:_spf.mail.hostinger.com ~all` | **SPF** : serveurs autorisés à envoyer (valeur Hostinger ; avec Brevo, ajoutez `include:spf.brevo.com` avant `~all`) |
| TXT ou CNAME | donné par le fournisseur | donnée par le fournisseur | **DKIM** : signature des e-mails (hPanel ou Brevo affichent la valeur exacte à copier) |
| TXT | `_dmarc` | `v=DMARC1; p=none; rua=mailto:contact@reusteel.com` | **DMARC** : politique en cas d’échec (commencer par `p=none`, passer à `p=quarantine` après quelques semaines sans problème) |

Un seul enregistrement SPF par domaine : s’il en existe déjà un, complétez-le au lieu d’en créer un second. La propagation DNS peut prendre jusqu’à quelques heures.

Vérification : envoyez un e-mail de test vers Gmail, ouvrez-le, *⋮ → Afficher l’original* : SPF, DKIM et DMARC doivent indiquer **PASS**.

## 4. Régler les e-mails de la plateforme

*SM → E-mails* :

- **Expéditeur et destinataires** : expéditeur `ReuSteel <contact@reusteel.com>` (la même adresse que dans WP Mail SMTP), administrateurs à prévenir (une ou plusieurs adresses), langue des alertes, heure du résumé quotidien, durée de conservation des journaux. Bouton **Envoyer un e-mail de test**.
- **Événements de la plateforme** : pour chaque événement, alerte aux administrateurs (immédiate, dans le résumé quotidien, ou non) et e-mail de confirmation à l’utilisateur (oui / non). Boutons pour modifier les modèles.
- **Notifications aux utilisateurs** : e-mails que reçoivent les utilisateurs sur leur propre activité (offre reçue, devis accepté…), activables et modifiables.
- **Journal des e-mails** : chaque e-mail envoyé ou en échec, lisible et renvoyable.

Le journal de tout ce qui se passe sur la plateforme est dans *SM → Journal d’activité* (filtres, export CSV).

## En cas de problème

- **Statut « Échec » dans le journal des e-mails** : l’erreur est affichée sous le statut ; vérifiez l’identifiant et le mot de passe dans WP Mail SMTP, puis refaites *Email Test*.
- **Les e-mails arrivent en spam** : vérifiez SPF, DKIM et DMARC (étape 3) et que l’adresse d’expéditeur est bien `@reusteel.com`.
- **Le résumé quotidien n’arrive pas** : il part à la première visite du site après l’heure choisie (tâches planifiées de WordPress). Sur un site peu visité, ajoutez une tâche cron chez l’hébergeur qui appelle `https://reusteel.com/wp-cron.php` toutes les 15 minutes, ou utilisez *Envoyer le résumé maintenant*.

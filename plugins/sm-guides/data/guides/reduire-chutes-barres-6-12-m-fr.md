# Réduire les chutes : bien commander ses barres de 6 m et 12 m

Les chutes viennent d'un mauvais accord entre la longueur des pièces et la longueur des barres achetées. Avant de commander, faites placer vos pièces sur des barres de 6 m et de 12 m : la différence peut être de plusieurs barres pour une même liste.

Chaque coupe consomme aussi un peu d'acier (le trait de scie). Une barre qui porte n pièces demande n − 1 traits de scie entre elles, et un dernier pour détacher la chute.

## 1. Un exemple : 6 pièces de 3,90 m

Pour 6 pièces de 3,90 m dans le même profilé, avec un trait de scie de 3 mm, des barres de 12 m demandent 12 m d'acier de moins que des barres de 6 m.

| Barres achetées | Nombre de barres | Longueur achetée | Pièces par barre | Chute par barre |
| --- | --- | --- | --- | --- |
| 6 m | 6 | 36 m | 1 | environ 2,10 m |
| 12 m | 2 | 24 m | 3 | environ 0,29 m |

Avec des barres de 6 m, chaque barre ne porte qu'une pièce et laisse une chute de 2,10 m. Avec des barres de 12 m, trois pièces tiennent sur une barre.

L'inverse arrive aussi : selon les longueurs, les barres de 6 m peuvent donner le meilleur résultat, et elles sont plus faciles à transporter. Il n'y a pas de règle générale : il faut calculer votre liste.

## 2. Utiliser l'optimiseur de découpe

L'optimiseur de découpe de ReuSteel est gratuit et ouvert à tous, sans compte. Le calcul est exact (il n'utilise pas l'IA) : il cherche le plus petit nombre de barres neuves.

1. **Saisissez vos pièces** : pour chaque ligne, le profilé, la longueur d'une pièce et le nombre (jusqu'à 40 lignes et 300 pièces par profilé).
2. **Choisissez la longueur des barres** : 6 m, 12 m ou une autre longueur.
3. **Réglez le trait de scie** (3 mm par défaut) et la longueur à partir de laquelle une chute est réutilisable (1 m par défaut).
4. **Ajoutez vos chutes en stock**, si vous en avez : elles sont utilisées en premier.
5. **Lisez le résultat** : barres à acheter, plan de coupe barre par barre, chutes en mètres, en kilos et en pourcentage, chutes réutilisables.
6. **Gardez le plan** : impression, PDF ou lien à partager avec l'atelier.
7. **Demandez un devis** pour les barres à acheter : la demande est pré-remplie (compte nécessaire).

Comparez deux calculs, l'un en 6 m et l'autre en 12 m, avant de commander. Les poids sont théoriques, tirés du catalogue, et le plan est un calcul indicatif à vérifier avant de couper.

## 3. Partir d'un DQE

Si votre liste est déjà dans un document, l'outil **Lire un document (DQE IA)** la transforme en liste de pièces pour l'optimiseur. Il faut un compte, et la lecture utilise un crédit IA.

- Documents acceptés : DQE, devis, bon de commande, métré, marché… en Excel (.xlsx), CSV, PDF ou photo. Les plans ne sont pas lus.
- Chaque ligne lue est marquée **« sûr »** si tout est écrit dans le document, ou **« à vérifier »** si le profilé est inconnu, si la longueur ou le nombre manque, si la ligne est déduite, ou si la source est une photo.
- Rien n'est ajouté sans vous : vous cochez les lignes à garder.
- Une ligne donnée en mètres ou en poids reçoit des barres de 12 m (modifiables) ; le nombre est calculé et marqué « à vérifier ».
- Les tôles et platines partent vers la demande de devis, avec leur poids.

Relisez toujours les lignes « à vérifier » avant de lancer le calcul.

## 4. Réemployer avant d'acheter

L'optimiseur peut chercher l'acier dans cet ordre, et chaque étape ne reçoit que les pièces que la précédente n'a pas placées :

1. **Vos chutes et votre magasin** : les chutes que vous saisissez, et le magasin de votre entreprise si vous l'utilisez sur ReuSteel. Une barre déjà entamée est remplie avant d'en prendre une autre.
2. **Le marché ReuSteel** : les barres publiées du même profilé, de longueur égale ou la plus proche au-dessus, puis l'annonce la moins chère au kilo. Vous choisissez la nuance minimale, les états acceptés (jamais la ferraille), la wilaya, et si les lots vendus en entier sont admis. Vos propres annonces ne sont pas proposées.
3. **Les barres neuves** pour le reste.

Le résultat indique, pour chaque annonce du marché, les barres prises, les pièces coupées, le poids et un coût estimé d'après le prix affiché. C'est une photo du marché au moment du calcul : rien n'est réservé, il faut ensuite acheter ou faire une offre.

Après la coupe, les chutes réutilisables sont listées avec leur longueur : rangez-les au magasin pour le prochain chantier, ou publiez-les en annonce de réemploi.

## Liste de vérification

- [ ] Ma liste de pièces est complète : profilé, longueur, nombre
- [ ] J'ai comparé un calcul en barres de 6 m et un en 12 m
- [ ] Le trait de scie correspond à ma machine
- [ ] J'ai saisi mes chutes en stock
- [ ] J'ai regardé ce que le marché ReuSteel propose avant d'acheter du neuf
- [ ] J'ai relu les lignes « à vérifier » du DQE
- [ ] L'atelier a le plan de coupe (PDF ou lien)
- [ ] Je sais où vont les chutes réutilisables

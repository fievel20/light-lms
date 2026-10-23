# Guide utilisateur

Light LMS distingue deux rôles :

- **le formateur**, compte unique qui administre tout ;
- **les apprenants**, créés et invités par le formateur.

Il n'existe pas d'inscription publique.

---

## Parcours du formateur

### 1. Créer le compte formateur

Sur le serveur, une seule fois :

```bash
php artisan lms:create-trainer
```

La commande demande prénom, nom, email et mot de passe (saisie masquée). Elle refuse de créer un second formateur. En production, le mot de passe doit faire au moins 12 caractères avec majuscules, minuscules, chiffres et symboles, et ne pas figurer dans une fuite connue.

Options non interactives : `--first-name`, `--last-name`, `--email`, `--password` (déconseillé : le mot de passe reste dans l'historique du shell).

### 2. Créer un référentiel de compétences

Menu **Référentiels**.

- **Nouveau référentiel** : nom et description, puis ajout des compétences une par une.
- **Importer un référentiel (JSON)** : crée le référentiel et toutes ses compétences en une fois. Voir le [format JSON](formats-import.md#référentiel-json) et l'exemple [`exemples/referentiel-dwwm.json`](exemples/referentiel-dwwm.json). L'import est « tout ou rien » : à la moindre erreur, rien n'est créé et chaque erreur indique la compétence concernée.

Chaque compétence a un **code** (unique dans le référentiel), un **intitulé**, une description facultative et un **type** :

| Type                | Valeurs possibles                       | Acquise quand…                                   |
| ------------------- | --------------------------------------- | ------------------------------------------------ |
| Acquis / non acquis | 0 (non acquis) ou 1 (acquis)            | la validation du formateur vaut 1                |
| Échelle de niveaux  | 2 à 10 niveaux (valeur 0-100 + libellé) | la validation atteint le **seuil d'acquisition** |

Le seuil d'acquisition vaut par défaut le niveau le plus élevé. Sur la fiche du référentiel, les niveaux qui valent « acquis » sont mis en évidence.

> **Compétence déjà évaluée** : son type et les valeurs de ses niveaux sont verrouillés et elle ne peut plus être supprimée. L'intitulé, la description, les libellés des niveaux et le seuil restent modifiables.
>
> Un référentiel affecté à une promotion ne peut pas être supprimé.

### 3. Créer une promotion

Menu **Promotions** → **Nouvelle promotion** : nom (unique), dates de début et de fin, référentiel (facultatif, modifiable plus tard).

- Une promotion n'est jamais supprimée : on l'**archive** (onglet « Archivées », bouton « Désarchiver »). Dans une promotion archivée, les évaluations sont en lecture seule et on ne peut plus ajouter d'apprenants.
- Le référentiel d'une promotion ne peut plus changer dès qu'une évaluation y a été saisie.

### 4. Inscrire et inviter les apprenants

Cliquez sur le nom d'une promotion pour ouvrir sa fiche.

- **Ajouter un apprenant** : prénom, nom, email.
- **Importer un fichier CSV** : colonnes `nom;prenom;email` (voir le [format CSV](formats-import.md#apprenants-csv)). Un rapport indique les apprenants créés, ceux déjà connus ajoutés à la promotion et les lignes en erreur.

Pour chaque **nouvel** apprenant, un email d'invitation part automatiquement. Il contient un lien personnel valable **7 jours** pour choisir son mot de passe. Un apprenant déjà existant (même email) est simplement ajouté à la promotion, sans nouvelle invitation : un apprenant peut appartenir à plusieurs promotions.

Sur la fiche promotion, chaque apprenant porte le badge **Activé** ou **Invité le …**. Le bouton **Renvoyer l'invitation** génère un nouveau lien. **Retirer** enlève l'apprenant de la promotion sans supprimer son compte.

### 5. Valider les compétences

Deux points d'entrée, dans le menu de la promotion active :

- **Par compétence** : la liste des compétences (dans l'ordre du référentiel, avec « acquis / apprenants » et le nombre d'autoévaluations à examiner), puis, pour une compétence, **tous les apprenants** ;
- **Par apprenant** : la liste des apprenants (progression, à examiner), puis, pour un apprenant, **toutes ses compétences dans l'ordre**.

Les flèches ‹ › en haut à droite passent à la compétence ou à l'apprenant précédent / suivant.

Chaque ligne montre l'autoévaluation (« s'estime : … », point ambre si elle n'a pas encore été examinée) et un sélecteur de niveau : **un appui enregistre la validation** (pas de bouton, pas de confirmation ; une coche apparaît sur la ligne, en vert si le niveau atteint le seuil). L'icône 💬 ouvre un commentaire, enregistré en quittant le champ (il faut d'abord avoir choisi un niveau). Le libellé de chaque niveau s'affiche au survol et dans la légende en tête de la vue par compétence.

Chaque validation est conservée dans l'historique ; c'est **la plus récente** qui compte.

| Indicateur     | Signification                                                                      |
| -------------- | ---------------------------------------------------------------------------------- |
| **À examiner** | L'apprenant s'est autoévalué et vous n'avez pas validé depuis                      |
| **Écart**      | Autoévaluation et validation diffèrent                                             |
| **Acquise**    | Votre dernière validation atteint le seuil (ou vaut 1 pour une compétence binaire) |

### 6. Suivre les promotions

- **Promotion active** : le sélecteur en haut du menu (en-tête sur mobile) choisit la promotion sur laquelle tu travailles ; ce choix est mémorisé. Les promotions archivées sont regroupées en bas, en gris.
- **Accueil** : vue d’ensemble de la promotion active (apprenants, progression moyenne, autoévaluations à examiner, invitations en attente) et accès au suivi et aux apprenants.
- **Suivi de la promotion** (bouton « Suivi » sur la fiche promotion) :
    - tri des apprenants par nom ou par progression, filtre « À examiner seulement » ;
    - sur mobile, une carte par apprenant avec sa progression et une bande de couleurs (une par compétence) ;
    - sur grand écran, une **carte de chaleur apprenants × compétences** : vert = acquise, ambre plus ou moins soutenu = niveau validé plus ou moins proche du seuil, violet clair = autoévaluée seulement, pointillés = pas encore évaluée, point orange = à examiner ; la dernière ligne compte les apprenants ayant acquis chaque compétence.

La progression d'un apprenant est le pourcentage de compétences acquises ; celle de la promotion est la moyenne de ses apprenants.

---

## Parcours de l'apprenant

### 1. Activer son compte

Ouvrir le lien reçu par email, choisir un mot de passe : le compte est activé et l'apprenant est connecté. Le lien ne sert qu'une fois ; s'il a expiré, demander au formateur de renvoyer l'invitation. Ensuite, connexion classique avec email et mot de passe (« Mot de passe oublié » disponible).

### 2. Accueil

Pour chaque promotion en cours : un **anneau de progression** (pourcentage de compétences acquises), trois chiffres clés (acquises, autoévaluées, en attente du formateur) et une seule action proposée, par exemple « 2 compétences t'attendent », qui ouvre directement les compétences à faire. Les promotions archivées sont listées plus bas.

**Célébration** : quand le formateur a validé une compétence au niveau requis, l'apprenant est félicité (confettis et liste des compétences nouvellement acquises) à sa prochaine visite. Chaque compétence n'est célébrée qu'une fois.

### 3. Mes compétences

Onglet **Compétences** (choix de la promotion s'il y en a plusieurs).

- Des **filtres** avec compteur : _Toutes_, _À faire_ (pas encore autoévaluée), _En attente_ (autoévaluation pas encore examinée par le formateur), _Acquises_.
- Chaque compétence affiche son statut et ses **pastilles de niveau** : toucher une pastille enregistre son autoévaluation. 🏁 marque le niveau à atteindre, la coche verte le niveau validé par le formateur. Le commentaire du formateur s'affiche dessous.
- On peut se repositionner à tout moment tant que la promotion n'est pas archivée.

Une compétence n'est **acquise** que lorsque le formateur l'a validée au niveau requis : l'autoévaluation sert à lui signaler où l'on pense en être.

### 4. Activité récente

Sur l'accueil, **Activité récente** liste les dernières validations du formateur (niveau, commentaire), de la plus récente à la plus ancienne.

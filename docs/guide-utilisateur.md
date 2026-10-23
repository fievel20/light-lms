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

Depuis la fiche promotion ou le suivi, cliquez sur un apprenant. Sa fiche montre :

- un résumé : compétences acquises, autoévaluées, **à examiner**, **écarts** ;
- pour chaque compétence, l'autoévaluation de l'apprenant et votre dernière validation (avec date et commentaire) ;
- un formulaire de validation : choix du niveau, commentaire facultatif.

Chaque validation est conservée dans l'historique ; c'est **la plus récente** qui compte.

| Indicateur     | Signification                                                                      |
| -------------- | ---------------------------------------------------------------------------------- |
| **À examiner** | L'apprenant s'est autoévalué et vous n'avez pas validé depuis                      |
| **Écart**      | Autoévaluation et validation diffèrent                                             |
| **Acquise**    | Votre dernière validation atteint le seuil (ou vaut 1 pour une compétence binaire) |

**Validation rapide** : quand des autoévaluations attendent, le bouton **Validation rapide (N)** de la fiche apprenant (ou **Examiner** depuis l'accueil et le suivi) les présente une par une : le niveau est pré-rempli avec l'autoévaluation, un commentaire est possible, puis **Valider et suivant** ou **Passer**. À la fin, un bouton ouvre directement l'apprenant suivant qui a des autoévaluations à examiner.

### 6. Suivre les promotions

- **Tableau de bord** (accueil) : promotions en cours, progression moyenne, nombre d'autoévaluations à examiner (cliquable pour lancer la validation rapide) et d'invitations en attente.
- **Suivi de la promotion** (bouton « Suivi » sur la fiche promotion) :
    - tri des apprenants par nom ou par progression, filtre « À examiner seulement », bouton **Examiner (N)** ;
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

### 4. Badges et activité

Onglet **Badges** : les 8 badges, débloqués (médaille colorée et date) ou à débloquer (grisés). Ils sont attribués automatiquement et ne sont jamais retirés ; chaque nouveau badge est fêté dans la fenêtre « Bravo ».

| Badge             | Condition                                                     |
| ----------------- | ------------------------------------------------------------- |
| Premier pas       | Première autoévaluation                                       |
| Première réussite | Première compétence acquise                                   |
| Assidu            | Toutes les compétences d'une promotion autoévaluées           |
| Lucide            | 5 autoévaluations confirmées à l'identique par le formateur   |
| Expert            | Une compétence validée au niveau le plus élevé de son échelle |
| Mi-parcours       | 50 % des compétences d'une promotion acquises                 |
| Sprint final      | 75 % des compétences d'une promotion acquises                 |
| Diplômable        | 100 % des compétences d'une promotion acquises                |

Sur l'accueil, **Activité récente** liste les dernières validations du formateur (niveau, commentaire) et les badges gagnés, du plus récent au plus ancien.

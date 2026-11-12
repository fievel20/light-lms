# Light LMS — Brief phase 2 : séquences pédagogiques et parcours

> Le MVP suit la **validation** des compétences. La phase 2 apporte les **contenus** : le formateur écrit ses ateliers en Markdown, les regroupe en parcours et les ouvre aux apprenants au rythme de la promotion.

## Point de départ : les ateliers existants

Les ateliers actuels sont des PDF de deux pages (« Utilisation souris », « Envoyer un mail », « Google Lens », « Traduire avec Brave », « Coder »…). Ils ont tous la même forme, que l'éditeur doit rendre simple à reproduire :

| Élément des PDF                                      | Équivalent dans une séquence                                    |
| ---------------------------------------------------- | --------------------------------------------------------------- |
| Bandeau « Numérique » + titre                        | Titre de la séquence (et parcours auquel elle appartient)       |
| Étapes numérotées `1/`, `2/`, `3/`…                  | Liste numérotée Markdown                                        |
| Liens vers un exercice ou un sondage                 | Liens, ouverts dans un nouvel onglet                            |
| Captures d'écran                                     | Images importées dans la séquence                               |
| 🔥 à 🔥🔥🔥🔥 pour la difficulté, 🎯 pour l'objectif | Emojis tapés directement, ou insérés depuis la palette d'icônes |
| Encadré « Cadre commun » (entraide…)                 | Bloc de mise en avant (citation `>`)                            |

## Rôles

Inchangés. Le **formateur** crée et gère séquences et parcours, les affecte et ouvre les séquences. L'**apprenant** lit les séquences ouvertes pour sa promotion.

## User stories

### Séquences (formateur)

- **US13** — Je crée une **séquence** : titre, résumé facultatif, contenu en **Markdown**.
- **US14** — J'écris dans un éditeur avec **barre d'outils** (titre, gras, liste numérotée, lien, image, icône) et **aperçu** du rendu final ; sur mobile, l'aperçu est un onglet.
- **US15** — J'**intègre des images** : bouton, glisser-déposer ou collage d'une capture. L'image est importée et sa balise Markdown insérée à l'endroit du curseur.
- **US16** — J'**insère des icônes** : emojis directement, ou palette d'icônes courantes (🎯 objectif, 🔥 difficulté, 💡 astuce, ⚠️ attention, 🔗 lien, ✅ fait…).
- **US17** — Je **modifie** une séquence : le changement est visible partout où elle est utilisée.
- **US18** — Je **supprime** une séquence, après une confirmation qui liste les parcours qui la contiennent ; elle en est retirée et ses images sont effacées.
- **US19** — Je **duplique** une séquence pour en faire une variante (ex. « Google Lens 1 » → « Google Lens 2 »).

### Parcours (formateur)

- **US20** — Je crée un **parcours** : nom, description facultative, liste **ordonnée** de séquences choisies dans la bibliothèque.
- **US21** — J'ajoute, retire et **réordonne** les séquences d'un parcours. Une même séquence peut appartenir à plusieurs parcours.
- **US22** — Je modifie ou supprime un parcours. La suppression est **bloquée** tant qu'il est affecté à une promotion (comme un référentiel).

### Affectation et ouverture (formateur)

- **US23** — J'**affecte un parcours à une promotion**. À l'affectation, **toutes ses séquences sont masquées** pour cette promotion.
- **US24** — Depuis la promotion, je **rends visible** (ou masque à nouveau) chaque séquence, **une par une**. La date d'ouverture est conservée.
- **US25** — Une séquence ajoutée plus tard à un parcours déjà affecté arrive **masquée** dans chaque promotion.
- **US26** — Je retire un parcours d'une promotion ; ses réglages d'ouverture pour cette promotion sont perdus (confirmation demandée).

### Lecture (apprenant)

- **US27** — Un onglet **Parcours** liste les parcours de ma promotion avec leurs **séquences ouvertes**, dans l'ordre du parcours.
- **US28** — J'ouvre une séquence pour la lire : rendu Markdown lisible sur téléphone, images agrandissables, liens dans un nouvel onglet.
- **US29** — Une séquence masquée m'est **invisible** : ni dans la liste, ni par son adresse (404).

## Règles

- **Visibilité** : une séquence est visible par un apprenant si elle appartient à un parcours affecté à sa promotion **et** qu'elle a été ouverte pour cette promotion. L'ouverture vaut pour la promotion, quel que soit le parcours qui contient la séquence.
- **Promotion archivée** : les séquences ouvertes restent lisibles, plus aucune ouverture ni affectation.
- **Markdown sûr** : rendu côté serveur (`league/commonmark`, déjà présent avec Laravel), **HTML brut échappé**, liens `javascript:` refusés, liens externes en `target="_blank" rel="noopener"`.
- **Images** : JPEG, PNG, WebP ou GIF, 5 Mo maximum, rangées par séquence dans `storage/app/private`. Elles sont servies par une **route authentifiée** (les captures peuvent contenir des adresses ou noms réels) ; un apprenant n'accède qu'aux images des séquences qu'il peut lire.

## Modèle de données

```
sequences                  id, title, summary?, content (Markdown), timestamps
sequence_images            id, sequence_id, path, original_name, mime_type, size, timestamps
learning_paths             id, name (unique), description?, timestamps        (parcours)
learning_path_sequence     learning_path_id, sequence_id, position             unique (parcours, séquence)
learning_path_promotion    learning_path_id, promotion_id, timestamps          unique (parcours, promotion)
sequence_releases          promotion_id, sequence_id, released_at, released_by unique (promotion, séquence)
```

- Pas de ligne dans `sequence_releases` = séquence masquée ; masquer à nouveau supprime la ligne.
- Retirer une séquence d'un parcours ne touche pas aux ouvertures : si elle revient, elle reste ouverte là où elle l'était.

## Écrans

| Qui       | Écran                        | Contenu                                                                                                |
| --------- | ---------------------------- | ------------------------------------------------------------------------------------------------------ |
| Formateur | **Séquences** (bibliothèque) | Liste avec recherche, parcours d'appartenance, actions créer / dupliquer / supprimer                   |
| Formateur | Édition d'une séquence       | Titre, résumé, éditeur Markdown + barre d'outils + aperçu, import d'images                             |
| Formateur | **Parcours**                 | Liste des parcours, nombre de séquences, promotions affectées                                          |
| Formateur | Détail d'un parcours         | Séquences ordonnées (glisser-déposer, ou flèches sur mobile), ajout depuis la bibliothèque             |
| Formateur | Promotion → onglet Parcours  | Parcours affectés ; pour chaque séquence, un interrupteur **Visible / Masquée** et sa date d'ouverture |
| Apprenant | **Parcours**                 | Parcours de la promotion, séquences ouvertes ; les nouvelles mises en avant                            |
| Apprenant | Lecture d'une séquence       | Titre, contenu, séquence précédente / suivante parmi celles ouvertes                                   |

Navigation : « Séquences » et « Parcours » rejoignent « Référentiels » dans le menu du formateur ; « Parcours » devient un onglet de l'apprenant.

## Exemple : « Envoyer un mail » en Markdown

```markdown
Envoie ton premier mail depuis l'ordinateur, sans aide. 🎯

1. Connecte-toi sur l'ordinateur.
2. Ouvre Internet.
3. Va sur ta boîte mail.

    ![La boîte de réception](image:boite-de-reception.png)

4. Envoie le mail suivant à **formateur@example.fr** :

    > Objet : Mon premier mail
    >
    > Bonjour, ceci est mon premier mail. À bientôt !

💡 Le bouton **Nouveau message** est souvent en haut à gauche.
```

`image:` est la référence interne d'une image importée : l'éditeur l'insère, le rendu la remplace par l'adresse protégée.

## Déploiement

- Les images vont dans `shared/storage` : elles survivent aux déploiements. La sauvegarde quotidienne doit couvrir ce dossier en plus de la base.
- Envoi de fichiers : relever `LimitRequestBody` dans `deploy/apache.conf` (2 Mo aujourd'hui) et `upload_max_filesize` / `post_max_size` dans le pool PHP-FPM `light-lms` (via `deploy/server-setup.sh`).

## Hors périmètre (idées pour plus tard)

- Lien entre une séquence et les **compétences** du référentiel (« cette séquence travaille C6 »).
- Suivi de lecture : « séquence terminée » côté apprenant, avancement par parcours.
- Ouverture **programmée** à une date.
- Import des PDF existants ; vidéos intégrées ; quiz internes (les sondages restent des liens externes).

## Feuille de route

8. US13–US14 — Séquences : CRUD, éditeur Markdown, rendu sûr, aperçu
9. US15–US16, US19 — Images (import, collage, route protégée), palette d'icônes, duplication
10. US20–US22 — Parcours : CRUD, ordre des séquences
11. US23–US26 — Affectation à une promotion, ouverture séquence par séquence
12. US27–US29 — Espace apprenant : parcours et lecture
13. Déploiement : limites d'envoi, sauvegarde des images, guide utilisateur

## Points à valider

1. **Plusieurs parcours par promotion** (proposé : oui), ou un seul comme pour le référentiel ?
2. **Séquences masquées totalement invisibles** pour l'apprenant (proposé, conforme à la demande), ou affichées grisées « à venir » sans leur contenu ?
3. **Supprimer une séquence utilisée** dans un parcours : autorisé après confirmation (proposé), ou bloqué ?

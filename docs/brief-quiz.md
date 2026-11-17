# Light LMS — Brief : quiz dans les séquences

> Les séquences apportent les contenus ; les sondages restent des liens externes (kwizou…). Ce module ajoute des **quiz internes** : le formateur les écrit dans la séquence, l'apprenant y répond question par question, chronomètre en tête, et le formateur suit les résultats de la promotion.

## Point de départ

Les quiz utilisés aujourd'hui ont la forme d'un jeu : une question à la fois, quatre réponses A, B, C, D en grosses tuiles de couleur, un compte à rebours et une barre de progression. On garde cette **mécanique**, mais avec la **charte de Light LMS** (encre indigo, violet, vert acide, papier) plutôt que les couleurs de l'outil d'origine.

Les questions existent souvent déjà sous forme de JSON :

```json
{
    "title": "Test de positionnement",
    "questions": [
        {
            "question": "Quelle est la capitale de l'Australie ?",
            "choice_a": "Sydney",
            "choice_b": "Melbourne",
            "choice_c": "Canberra",
            "choice_d": "Perth",
            "correct": "c"
        }
    ]
}
```

## User stories

### Créer un quiz (formateur)

- **US30** — Dans l'édition d'une séquence, je crée **son quiz** : un titre et le **temps de réponse** par question (10 s à 5 min, ou sans limite).
- **US31** — J'ajoute les questions **à la main** : énoncé, réponses A à D, bonne réponse. Je les modifie, les supprime et les **réordonne**.
- **US32** — J'ajoute des questions en **collant un JSON** au format ci-dessus. Un aperçu liste les questions reconnues et signale les erreurs ligne par ligne avant l'import ; je choisis d'**ajouter** les questions à la suite ou de **remplacer** les existantes.
- **US33** — Une question peut avoir une **image** (capture d'écran, logo à reconnaître…), importée comme les images des séquences.
- **US34** — Je place le bouton **Lancer le quiz** où je veux dans le texte avec le code court `[Quizz]` (ou `[Quiz]`). Sans code court, le bouton apparaît à la fin de la séquence.
- **US35** — Je **teste** le quiz depuis la séquence, comme un apprenant ; mes réponses ne sont pas enregistrées.

### Répondre (apprenant)

- **US36** — Dans une séquence ouverte, **Lancer le quiz** ouvre le quiz en plein écran.
- **US37** — Les questions s'affichent **une par une** : « Question 1 / 8 », barre de progression, compte à rebours au-dessus de la question, quatre tuiles A à D. Un appui choisit la réponse et passe à la suivante.
- **US38** — Quand le temps est écoulé, la question compte comme **sans réponse** et la suivante s'affiche.
- **US39** — À la fin, je vois mon **score** (« 6 / 8 ») puis la **correction** : pour chaque question, ma réponse, la bonne réponse, en vert ou en rouge.
- **US40** — Je peux **recommencer** autant de fois que je veux ; mes tentatives précédentes et leur correction restent consultables.

### Suivre (formateur)

- **US41** — Un écran **Quiz** de la promotion liste les séquences ouvertes qui ont un quiz : nombre d'apprenants qui l'ont fait, score moyen de la dernière tentative.
- **US42** — Le détail d'un quiz montre, pour chaque apprenant, **toutes ses tentatives** dans l'ordre, pour voir son **évolution** (ex. 3/8 → 5/8 → 7/8) ; et pour chaque question, le **taux de bonnes réponses** (pour repérer les questions mal comprises).
- **US43** — Je consulte la correction d'une tentative d'un apprenant.

## Règles

- **Visibilité** : le quiz suit sa séquence. Un apprenant ne le lance, et ne voit ses résultats, que dans une séquence ouverte à sa promotion.
- **Correction côté serveur** : les bonnes réponses ne sont **jamais envoyées** au navigateur avant la fin de la tentative ; le serveur calcule le score.
- **Chronomètre** : affiché et appliqué dans le navigateur (quiz formatif, pas un examen surveillé).
- **Tentatives figées** : une tentative garde une **copie** des questions telles qu'elles étaient. Modifier le quiz plus tard ne change ni les scores ni les corrections passées.
- **Tentative interrompue** (page fermée) : elle n'est pas enregistrée ; seules les tentatives terminées comptent.
- **Promotion archivée** : les résultats restent consultables, plus de nouvelle tentative.
- **Mode observateur** : le formateur voit les résultats de l'apprenant observé, mais ne peut pas lancer de tentative à sa place (lecture seule).
- **Images** : mêmes règles que les images des séquences (5 Mo, route protégée).

## Modèle de données

```
quizzes          id, sequence_id (unique), title, seconds_per_question?, timestamps
quiz_questions   id, quiz_id, position, question, image_path?, choice_a, choice_b, choice_c?, choice_d?, correct (a–d)
quiz_attempts    id, quiz_id, promotion_id, user_id, score, total, questions (JSON : copie + réponses), finished_at
```

- `seconds_per_question` vide = sans limite.
- Les tentatives sont rattachées à la promotion, comme les évaluations et les séquences terminées.

## Écrans

| Qui       | Écran                              | Contenu                                                                                        |
| --------- | ---------------------------------- | ---------------------------------------------------------------------------------------------- |
| Formateur | Édition d'une séquence → **Quiz**  | Titre, temps par question, questions (ajout, réordonnancement, image), import JSON avec aperçu |
| Formateur | Séquence → **Tester le quiz**      | Le quiz tel que le voit l'apprenant, sans enregistrement                                       |
| Apprenant | Séquence                           | Bouton **Lancer le quiz** à l'emplacement de `[Quizz]` ; meilleur score s'il l'a déjà fait     |
| Apprenant | Quiz (plein écran)                 | Question n / total, progression, chronomètre, tuiles A–D                                       |
| Apprenant | Résultat                           | Score, correction question par question, **Recommencer**, tentatives précédentes               |
| Formateur | Promotion → **Quiz**               | Séquences avec quiz : participants, score moyen                                                |
| Formateur | Détail d'un quiz pour la promotion | Apprenants et toutes leurs tentatives (évolution des scores), taux de réussite par question    |

Les tuiles A, B, C, D prennent quatre couleurs de la charte (violet, indigo, vert acide, ambre), avec la lettre dans un carré, comme sur l'original.

## Feuille de route

14. US30–US33 — Quiz d'une séquence : réglages, questions à la main et par JSON, images
15. US34–US38 — Code court `[Quizz]`, lecteur plein écran, chronomètre, correction serveur, test par le formateur
16. US39–US40 — Score, correction, tentatives multiples
17. US41–US43 — Résultats de la promotion pour le formateur
18. Guide utilisateur, architecture

## Choix validés

1. **Un quiz par séquence**, appelé par `[Quizz]`.
2. **Temps par question** : le compteur repart à chaque question ; à zéro, la question compte sans réponse.
3. **De 2 à 4 réponses** par question : C et D facultatives (vrai/faux possible), le JSON existant reste compatible.
4. **Toutes les tentatives** sont montrées au formateur, dans l'ordre, pour suivre l'évolution de chaque apprenant.

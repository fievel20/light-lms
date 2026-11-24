# Light LMS — Brief : quiz express

> Le quiz des séquences se fait seul, à son rythme. Le **quiz express** se joue **en salle, tous ensemble** : le formateur projette un QR code, les participants le scannent sans créer de compte, et le formateur fait défiler les questions. Bonnes réponses et rapidité rapportent des points ; à la fin, un classement puis la correction.

## Point de départ

- Même **format** que le quiz des séquences : une question à la fois, 2 à 4 réponses en tuiles A, B, C, D aux couleurs de la charte, compte à rebours, images possibles, import JSON au même format.
- Mais le quiz express **n'appartient à aucune séquence** et les participants **n'ont pas de compte** : un nom suffit.
- Le formateur **pilote** : il lance, il passe à la question suivante, il affiche le classement puis la correction. Son écran est fait pour être projeté.

## User stories

### Préparer (formateur)

- **US44** — Dans **Administration > Quiz express**, je crée un quiz express : un titre et le **temps de réponse** par question (10 s à 5 min, 20 s par défaut ; pas de « sans limite », le bonus de rapidité en dépend).
- **US45** — J'ajoute les questions **à la main** ou en **collant un JSON**, avec aperçu, ajout à la suite ou remplacement, images et réordonnancement : le même éditeur que pour le quiz d'une séquence.
- **US46** — Je modifie, duplique ou supprime un quiz express. Le supprimer supprime aussi ses sessions passées (avec confirmation).

### Lancer (formateur)

- **US47** — **Lancer** ouvre une **session** : un écran plein écran à projeter avec un grand **QR code**, l'adresse courte et le **code** de la session (ex. `K7P2QX`), pour ceux qui le tapent.
- **US48** — Sous le QR code, la **salle d'attente** : les noms des participants apparaissent à mesure qu'ils arrivent, avec leur nombre. Je peux **retirer** un participant (nom inapproprié).
- **US49** — **Démarrer** lance la première question pour tout le monde.

### Rejoindre (participant)

- **US50** — Je scanne le QR code : sans compte ni mot de passe, la page me demande **mon nom** (30 caractères au plus, différent de ceux déjà pris dans la session).
- **US51** — J'attends ensuite sur un écran « **Patiente, le quiz va commencer** » qui affiche mon nom ; il passe tout seul à la première question quand le formateur démarre.
- **US52** — Si je recharge la page ou si mon téléphone se met en veille, je retrouve ma place (même nom, mêmes points) sans ressaisir mon nom.

### Jouer (tous)

- **US53** — Pendant une question, l'écran projeté montre « Question 3 / 10 », la question, l'image, les tuiles A–D, le **compte à rebours** et le nombre de participants qui ont répondu (« 12 / 18 »). Sur un **côté de l'écran**, un petit **QR code** et le **code** de la session restent affichés jusqu'au classement, pour les retardataires.
- **US54** — Mon téléphone montre la **même question**, l'image, les tuiles et le compte à rebours. Un appui choisit ma réponse, **sans retour possible** ; l'écran affiche alors « Réponse enregistrée ».
- **US63** — Sur mon téléphone, la question et chaque réponse sont des **images** du texte, et non du texte : la **traduction automatique** du navigateur (ou d'une extension) ne peut pas les traduire. Seuls la lettre A–D et la couleur de chaque tuile restent du texte. Ma correction, à la fin, est affichée de la même façon.
- **US55** — Une bonne réponse rapporte **500 points + un bonus de rapidité jusqu'à 500** : 1000 points pour une réponse immédiate, 500 pour une réponse à la dernière seconde. Une mauvaise réponse ou pas de réponse : **0**.
- **US56** — À la fin du temps, les réponses sont fermées : « Temps écoulé ». La bonne réponse **n'est pas montrée** à ce moment-là (elle vient avec la correction, à la fin).
- **US57** — **Question suivante** passe à la question d'après, pour tout le monde. Le formateur peut aussi l'utiliser avant la fin du temps, par exemple quand tout le monde a répondu.

### Finir (tous)

- **US58** — Après la dernière question, **Classement** affiche le **podium** (trois premiers) puis la liste complète avec les points. Sur mon téléphone : mon rang et mes points (« 4e sur 18 · 3 250 points »).
- **US59** — **Correction** fait défiler les questions sur l'écran projeté : la bonne réponse en vert et la **répartition des réponses** par tuile (A : 3, B : 12, C : 2, D : 1). Le formateur avance et recule question par question.
- **US60** — Sur mon téléphone, je vois ma **correction** : pour chaque question, ma réponse, la bonne réponse et les points gagnés.
- **US61** — **Terminer** ferme la session. Les téléphones affichent « Session terminée » ; on ne peut plus la rejoindre.

### Retrouver (formateur)

- **US62** — Chaque quiz express liste ses **sessions passées** : date, nombre de participants, et pour chacune le classement et la correction. Je peux supprimer une session.

## Règles

- **Sans compte** : le participant est reconnu par un jeton gardé dans un cookie de son navigateur (US52). Aucune donnée personnelle hors du nom choisi.
- **Rejoindre** : possible jusqu'au classement. Arrivé en retard, on commence à la question en cours ; les questions déjà passées comptent 0.
- **Correction côté serveur** : les bonnes réponses ne sont **jamais envoyées** aux téléphones avant le classement ; le serveur calcule les points.
- **Temps** : le serveur note l'heure de début de chaque question et l'heure de chaque réponse ; c'est lui qui calcule le bonus et refuse une réponse arrivée après la fin du temps.
- **Mise à jour en direct** : chaque téléphone et l'écran projeté interrogent le serveur **chaque seconde** (aucun service à ajouter sur le VPS). Un téléphone peut donc voir une question jusqu'à une seconde après l'écran projeté : on accorde **une seconde de grâce** sur le bonus de rapidité.
- **Session figée** : une session garde une **copie** des questions au lancement ; modifier le quiz ensuite ne change ni la session en cours ni les sessions passées.
- **Une session à la fois** par quiz express. Une session oubliée se ferme d'elle-même au bout de 12 heures.
- **Protection** : saisie du nom et réponses limitées en nombre de requêtes par minute ; le code de session est aléatoire (6 caractères sans ambiguïté : pas de O/0 ni de I/1).
- **Images** : mêmes règles que les images des séquences (5 Mo) ; servies aux participants de la session en cours seulement.
- **Texte en images (US63)** : le serveur dessine chaque énoncé et chaque réponse en **image PNG** (police fournie avec l'application, retour à la ligne automatique, largeur fixe réduite à l'écran du téléphone). Le texte des questions et des réponses **n'est jamais envoyé** au téléphone, pas même dans la description de l'image. Les images sont créées au lancement de la session, à partir de sa copie des questions, puis gardées en cache. Celles d'une question ne sont servies qu'une fois la question lancée, aux seuls participants de la session.
- **Limites du texte en images** : un lecteur d'écran ne peut pas lire la question ; les émojis ne s'affichent pas ; une extension qui traduit le contenu des images (lecture optique) reste possible. L'écran projeté, lui, reste en texte : c'est le navigateur du formateur.
- **Serveur** : dessiner du texte demande l'extension PHP **GD avec FreeType**, à vérifier sur le VPS avant la mise en production.

## Modèle de données

```
express_quizzes        id, title, seconds_per_question (10–300), timestamps
express_questions      id, express_quiz_id, position, question, image_path?, choice_a, choice_b, choice_c?, choice_d?, correct (a–d)
express_sessions       id, express_quiz_id, code (unique), status, current_position?, question_started_at?, questions (JSON : copie),
                       started_at?, finished_at?, timestamps
express_participants   id, express_session_id, name, token (haché), score, timestamps — unique (session, nom)
express_answers        id, express_participant_id, position, choice?, points, answered_at — unique (participant, position)
```

- `status` : `lobby` (salle d'attente) → `question` → `ranking` (classement) → `correction` → `closed`.
- Le score d'un participant est la somme de ses points ; il est gardé dans `express_participants.score` pour le classement.

## Écrans

| Qui         | Écran                                | Contenu                                                                                                                  |
| ----------- | ------------------------------------ | ------------------------------------------------------------------------------------------------------------------------ |
| Formateur   | Administration → **Quiz express**    | Liste des quiz express : nombre de questions, dernière session, **Lancer**                                               |
| Formateur   | Édition d'un quiz express            | Titre, temps par question, questions (même éditeur que les séquences), import JSON                                       |
| Formateur   | Session — salle d'attente (projetée) | QR code, adresse et code, noms des participants, **Démarrer**                                                            |
| Formateur   | Session — question (projetée)        | Question n / total, image, tuiles, compte à rebours, « 12 / 18 ont répondu », **Suivante** ; QR code et code sur le côté |
| Formateur   | Session — classement (projeté)       | Podium, liste complète, **Correction**                                                                                   |
| Formateur   | Session — correction (projetée)      | Question, bonne réponse, répartition des réponses, précédente / suivante, **Terminer**                                   |
| Participant | `/q/{code}` — nom                    | Champ « Ton nom », **Rejoindre**                                                                                         |
| Participant | Attente                              | « Patiente, le quiz va commencer », son nom                                                                              |
| Participant | Question                             | Question et réponses **en images**, image de la question, tuiles A–D, compte à rebours ; puis « Réponse enregistrée »    |
| Participant | Fin                                  | Son rang et ses points, puis sa correction                                                                               |
| Formateur   | Sessions passées d'un quiz express   | Date, participants ; classement et correction de chaque session                                                          |

Écrans projetés : grands caractères, lisibles du fond de la salle ; ils occupent tout l'écran, sans menu. Écrans des participants : pensés pour le téléphone d'abord.

## Feuille de route

19. US44–US46 — Quiz express : liste, éditeur (repris de celui des séquences), import JSON, images
20. US47–US52 — Session : lancement, QR code, salle d'attente, nom du participant, retour après rechargement
21. US53–US57, US63 — Déroulé en direct : questions, chronomètre, réponses, points et bonus, question suivante, texte en images sur le téléphone, QR code sur le côté
22. US58–US61 — Classement, correction projetée, correction sur le téléphone, fin de session
23. US62 — Sessions passées ; guide utilisateur, architecture

## Choix validés

1. **Classement à la fin seulement** : les questions s'enchaînent sans révéler la bonne réponse ; après la dernière, classement puis correction.
2. **Points** : bonne réponse = 500 + jusqu'à 500 de bonus selon la rapidité ; mauvaise réponse ou pas de réponse = 0.
3. **Le téléphone affiche la question et les réponses** : on peut jouer sans voir l'écran projeté.
4. **Mise à jour par interrogation chaque seconde**, sans WebSockets : rien à installer sur le VPS.
5. **Arrivée en retard** possible jusqu'au classement, avec le QR code et le code gardés sur un côté de l'écran projeté.
6. **Questions et réponses en images** sur le téléphone, dessinées par le serveur, pour que le navigateur des participants ne les traduise pas.

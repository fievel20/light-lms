# Formats d'import

## Apprenants (CSV)

Fiche promotion → **Importer un fichier CSV**.

```csv
nom;prenom;email
Dupont;Marie;marie.dupont@example.com
Martin;Paul;paul.martin@example.com
```

| Règle               | Détail                                                                                                 |
| ------------------- | ------------------------------------------------------------------------------------------------------ |
| En-tête obligatoire | Colonnes `nom`, `prenom`, `email`, dans n'importe quel ordre                                           |
| Variantes acceptées | Majuscules, accents, tirets : `Nom`, `Prénom`, `E-mail`, `mail`, `courriel`, `last_name`, `first_name` |
| Séparateur          | `;` (Excel français), `,` ou tabulation, détecté automatiquement                                       |
| Encodage            | UTF-8 (avec ou sans BOM) ou Windows-1252 (export Excel par défaut)                                     |
| Fins de ligne       | Windows ou Unix ; les lignes vides sont ignorées                                                       |
| Taille              | 1 Mo maximum, extension `.csv` ou `.txt`                                                               |

Traitement ligne par ligne :

- **email inconnu** → compte apprenant créé, inscrit, invitation envoyée ;
- **email d'un apprenant existant** → simplement ajouté à la promotion (nom et prénom existants conservés, pas de nouvelle invitation) ;
- **erreur** → ligne ignorée et signalée avec son numéro : champ manquant, email invalide, apprenant déjà dans la promotion (y compris un doublon dans le fichier), adresse du formateur.

L'import est refusé en bloc si l'en-tête ne contient pas les trois colonnes, si le fichier ne contient aucun apprenant ou si la promotion est archivée.

## Référentiel (JSON)

Menu **Référentiels** → **Importer un référentiel (JSON)**. Exemple complet : [`exemples/referentiel-dwwm.json`](exemples/referentiel-dwwm.json).

```json
{
    "name": "Développeur Web",
    "description": "Facultatif",
    "competencies": [
        { "code": "C1", "name": "Maquetter une application", "type": "binary" },
        {
            "code": "C2",
            "name": "Développer une interface",
            "description": "Facultatif",
            "type": "scale",
            "validation_threshold": 3,
            "levels": [
                { "value": 1, "label": "Découverte" },
                { "value": 2, "label": "Guidé" },
                { "value": 3, "label": "Autonome" },
                { "value": 4, "label": "Expert" }
            ]
        }
    ]
}
```

| Champ                                 | Obligatoire | Règle                                                               |
| ------------------------------------- | ----------- | ------------------------------------------------------------------- |
| `name`                                | oui         | 255 caractères max, unique parmi les référentiels                   |
| `description`                         | non         | 2000 caractères max                                                 |
| `competencies`                        | oui         | liste de 1 à 500 compétences, dans l'ordre d'affichage              |
| `competencies[].code`                 | oui         | 20 caractères max, unique dans le fichier                           |
| `competencies[].name`                 | oui         | 255 caractères max                                                  |
| `competencies[].description`          | non         | 2000 caractères max                                                 |
| `competencies[].type`                 | oui         | `binary` ou `scale`                                                 |
| `competencies[].levels`               | si `scale`  | 2 à 10 niveaux ; ignoré pour `binary`                               |
| `levels[].value`                      | si `scale`  | entier de 0 à 100, unique dans la compétence                        |
| `levels[].label`                      | si `scale`  | 100 caractères max                                                  |
| `competencies[].validation_threshold` | non         | doit être l'une des valeurs de `levels` ; par défaut la plus élevée |

L'import est **tout ou rien** (transaction) : à la moindre erreur, rien n'est créé et chaque message précise la compétence, par exemple « Le champ code (compétence n°3) a une valeur en double. ». Fichier `.json` de 1 Mo maximum.

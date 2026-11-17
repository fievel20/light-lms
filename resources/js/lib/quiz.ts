export const letters = ['a', 'b', 'c', 'd'] as const;

export type Letter = (typeof letters)[number];

/**
 * The four answer tiles, in the colours of the app (the original quiz uses
 * its own palette; here: violet, indigo, acid green, amber).
 */
export const letterTiles: Record<Letter, string> = {
    a: 'bg-primary text-primary-foreground',
    b: 'bg-night text-white dark:bg-[#2b2363]',
    c: 'bg-cta text-cta-foreground',
    d: 'bg-warning text-night',
};

/** A question in the editor, saved or not yet. */
export type QuestionDraft = {
    // Stable key for the list, also for unsaved questions.
    key: string;
    id: number | null;
    question: string;
    choice_a: string;
    choice_b: string;
    choice_c: string;
    choice_d: string;
    correct: Letter;
    image_id: number | null;
    image_url: string | null;
};

let counter = 0;

export function newKey(): string {
    counter += 1;

    return `q${Date.now()}-${counter}`;
}

export function emptyQuestion(): QuestionDraft {
    return {
        key: newKey(),
        id: null,
        question: '',
        choice_a: '',
        choice_b: '',
        choice_c: '',
        choice_d: '',
        correct: 'a',
        image_id: null,
        image_url: null,
    };
}

export type ParsedQuiz = {
    title: string | null;
    questions: QuestionDraft[];
    errors: string[];
};

const text = (value: unknown): string =>
    typeof value === 'string' ? value.trim() : '';

/**
 * Reads a quiz pasted as JSON: {"title": …, "questions": [{"question",
 * "choice_a" … "choice_d", "correct": "a"…"d"}]}. Questions with a mistake
 * are listed in errors and left out; the server checks everything again.
 */
export function parseQuizJson(source: string): ParsedQuiz {
    let data: unknown;

    try {
        data = JSON.parse(source);
    } catch {
        return {
            title: null,
            questions: [],
            errors: [
                'Ce n’est pas du JSON valide : vérifie les guillemets et les virgules.',
            ],
        };
    }

    const root = (Array.isArray(data) ? { questions: data } : data) as {
        title?: unknown;
        questions?: unknown;
    } | null;

    if (!root || !Array.isArray(root.questions)) {
        return {
            title: null,
            questions: [],
            errors: ['Le JSON doit contenir une liste « questions ».'],
        };
    }

    const questions: QuestionDraft[] = [];
    const errors: string[] = [];

    root.questions.forEach((item: Record<string, unknown>, index: number) => {
        const number = index + 1;
        const draft: QuestionDraft = {
            ...emptyQuestion(),
            question: text(item?.question),
            choice_a: text(item?.choice_a),
            choice_b: text(item?.choice_b),
            choice_c: text(item?.choice_c),
            choice_d: text(item?.choice_d),
            correct: text(item?.correct).toLowerCase() as Letter,
        };

        if (!draft.question) {
            errors.push(`Question ${number} : l’énoncé est vide.`);
        } else if (!draft.choice_a || !draft.choice_b) {
            errors.push(
                `Question ${number} : il faut au moins les réponses A et B.`,
            );
        } else if (draft.choice_d && !draft.choice_c) {
            errors.push(
                `Question ${number} : la réponse D est remplie mais pas la C.`,
            );
        } else if (
            !letters.includes(draft.correct) ||
            !draft[`choice_${draft.correct}`]
        ) {
            errors.push(
                `Question ${number} : « correct » doit valoir a, b, c ou d, et désigner une réponse remplie.`,
            );
        } else {
            questions.push(draft);
        }
    });

    return {
        title: text(root.title) || null,
        questions,
        errors,
    };
}

/** The quiz of a sequence, played in its page. */
export type QuizSummary = {
    title: string;
    questions_count: number;
    seconds_per_question: number | null;
    questions: PlayQuestion[];
    // Null in the trainer's test, or when the promotion is archived.
    submit_url: string | null;
    attempts?: AttemptLink[];
    // The attempt whose correction is shown (?tentative=id).
    correction?: FinishedAttempt | null;
};

export type AttemptLink = {
    id: number;
    score: number;
    total: number;
    finished_at: string;
    url: string;
};

/** A question being answered; `correct` only in the trainer's test. */
export type PlayQuestion = {
    id: number;
    question: string;
    image_url: string | null;
    choices: Partial<Record<Letter, string>>;
    correct?: Letter;
};

/** A question of a finished attempt, with the answer given. */
export type CorrectedQuestion = Required<PlayQuestion> & {
    answer: Letter | null;
};

/** A finished attempt, with its correction. */
export type FinishedAttempt = {
    id: number;
    score: number;
    total: number;
    finished_at: string;
    questions: CorrectedQuestion[];
};

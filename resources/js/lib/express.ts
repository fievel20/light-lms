import { jsonHeaders } from '@/lib/markdown';
import type { Letter } from '@/lib/quiz';

export type ExpressStatus =
    | 'lobby'
    | 'question'
    | 'ranking'
    | 'correction'
    | 'closed';

/** Shared by the projected screen and the phones. */
type Common = {
    status: ExpressStatus;
    code: string;
    title: string | null;
    position: number | null;
    total: number;
    seconds: number;
    remaining_ms: number;
};

export type RankingRow = {
    id: number;
    name: string;
    score: number;
    rank: number;
};

export type HostState = Common & {
    participants: { id: number; name: string }[];
    question: {
        question: string;
        image_url: string | null;
        choices: Partial<Record<Letter, string>>;
    } | null;
    answered: number;
    ranking: RankingRow[] | null;
    correction:
        | {
              position: number;
              question: string;
              image_url: string | null;
              choices: Partial<Record<Letter, string>>;
              correct: Letter;
              counts: Partial<Record<Letter, number>>;
          }[]
        | null;
};

/** On a phone, text comes as images (text_url), or as text when not drawn. */
export type PlayerQuestion = {
    position: number;
    text: string | null;
    text_url: string | null;
    image_url: string | null;
    choices: { letter: Letter; text: string | null; text_url: string | null }[];
};

export type PlayerState = Common & {
    text_mode: 'image' | 'text';
    me: { name: string } | null;
    question: (PlayerQuestion & { answer: Letter | null }) | null;
    result: { rank: number; of: number; score: number } | null;
    correction:
        | (PlayerQuestion & {
              correct: Letter;
              answer: Letter | null;
              points: number;
          })[]
        | null;
};

/**
 * Tiles of the quiz express, in fixed colours (the text images are drawn
 * for them): violet, night, acid green, amber.
 */
export const expressTiles: Record<Letter, string> = {
    a: 'bg-[#7c5cff] text-white',
    b: 'bg-[#0e0926] text-white ring-1 ring-white/25',
    c: 'bg-[#c8ff1a] text-[#15093a]',
    d: 'bg-[#f5a524] text-[#0e0926]',
};

/**
 * Asks the server for the state every interval, until stopped; a failed
 * request is simply tried again at the next tick.
 */
export function poll<T>(
    url: string,
    onState: (state: T) => void,
    intervalMs = 1000,
): () => void {
    let stopped = false;
    let timer: ReturnType<typeof setTimeout> | undefined;

    const tick = async (): Promise<void> => {
        try {
            const response = await fetch(url, { headers: jsonHeaders() });

            if (response.ok && !stopped) {
                onState((await response.json()) as T);
            }
        } catch {
            // Network hiccup (phone asleep, wifi): next tick.
        }

        if (!stopped) {
            timer = setTimeout(() => void tick(), intervalMs);
        }
    };

    timer = setTimeout(() => void tick(), intervalMs);

    return () => {
        stopped = true;
        clearTimeout(timer);
    };
}

/**
 * Sends an action; resolves with the new state, or rejects with the
 * server's message.
 */
export async function send<T>(
    url: string,
    method: 'POST' | 'DELETE',
    body?: Record<string, unknown>,
): Promise<T> {
    const response = await fetch(url, {
        method,
        headers: { ...jsonHeaders(), 'Content-Type': 'application/json' },
        body: body ? JSON.stringify(body) : undefined,
    });
    const data = (await response.json().catch(() => ({}))) as T & {
        message?: string;
    };

    if (!response.ok) {
        throw new Error(data.message ?? `Erreur ${response.status}`);
    }

    return data;
}

/**
 * A countdown fed by the server's "milliseconds left": the deadline is kept
 * unless the server disagrees by more than a second (no jumps every poll).
 */
export class Countdown {
    deadline = 0;
    key: string | null = null;

    sync(key: string | null, remainingMs: number): void {
        const deadline = Date.now() + remainingMs;

        if (key !== this.key || Math.abs(deadline - this.deadline) > 1000) {
            this.key = key;
            this.deadline = deadline;
        }
    }

    secondsLeft(now: number): number {
        return Math.max(0, Math.ceil((this.deadline - now) / 1000));
    }
}

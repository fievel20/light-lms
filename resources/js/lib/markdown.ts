import { preview } from '@/routes/sequences';
import { store as storeImage } from '@/routes/sequences/images';

/**
 * Server-side rendering of the editor's content, so the preview is exactly
 * the final page (same Markdown flavour, same escaping).
 */
export async function renderMarkdown(
    content: string,
    sequenceId: number | null,
    signal?: AbortSignal,
): Promise<string> {
    const response = await fetch(preview().url, {
        method: 'POST',
        headers: { ...jsonHeaders(), 'Content-Type': 'application/json' },
        body: JSON.stringify({ content, sequence_id: sequenceId }),
        signal,
    });

    if (!response.ok) {
        throw new Error(`Aperçu indisponible (${response.status})`);
    }

    return ((await response.json()) as { html: string }).html;
}

/**
 * Uploads an image to a sequence; resolves with the Markdown to insert, or
 * rejects with the validation message.
 */
export async function uploadImage(
    sequenceId: number,
    file: File,
): Promise<string> {
    const body = new FormData();
    body.append('image', file);

    const response = await fetch(storeImage(sequenceId).url, {
        method: 'POST',
        headers: jsonHeaders(),
        body,
    });
    const data = (await response.json().catch(() => ({}))) as {
        markdown?: string;
        message?: string;
    };

    if (!response.ok || !data.markdown) {
        throw new Error(
            data.message ??
                (response.status === 413
                    ? 'Image trop lourde.'
                    : `Envoi impossible (${response.status}).`),
        );
    }

    return data.markdown;
}

function jsonHeaders(): Record<string, string> {
    const xsrf = document.cookie
        .split('; ')
        .find((cookie) => cookie.startsWith('XSRF-TOKEN='))
        ?.slice('XSRF-TOKEN='.length);

    return {
        Accept: 'application/json',
        'X-XSRF-TOKEN': decodeURIComponent(xsrf ?? ''),
    };
}

export type TextEdit = { text: string; start: number; end: number };

/**
 * Wraps the selection (or a placeholder) with a marker, e.g. ** for bold.
 */
export function wrapSelection(
    text: string,
    start: number,
    end: number,
    before: string,
    after: string,
    placeholder: string,
): TextEdit {
    const selected = text.slice(start, end) || placeholder;
    const inserted = `${before}${selected}${after}`;

    return {
        text: text.slice(0, start) + inserted + text.slice(end),
        start: start + before.length,
        end: start + before.length + selected.length,
    };
}

/**
 * Prefixes every line touched by the selection, e.g. "1. " for a numbered
 * list (numbers follow each other) or "> " for a quote.
 */
export function prefixLines(
    text: string,
    start: number,
    end: number,
    prefix: (index: number) => string,
): TextEdit {
    const lineStart = text.lastIndexOf('\n', start - 1) + 1;
    const nextBreak = text.indexOf('\n', end);
    const lineEnd = nextBreak === -1 ? text.length : nextBreak;

    const lines = text
        .slice(lineStart, lineEnd)
        .split('\n')
        .map((line, index) => prefix(index) + line);
    const replaced = lines.join('\n');

    return {
        text: text.slice(0, lineStart) + replaced + text.slice(lineEnd),
        start: lineStart,
        end: lineStart + replaced.length,
    };
}

/**
 * Inserts text at the cursor, replacing the selection; the cursor ends after it.
 */
export function insertText(
    text: string,
    start: number,
    end: number,
    inserted: string,
): TextEdit {
    const cursor = start + inserted.length;

    return {
        text: text.slice(0, start) + inserted + text.slice(end),
        start: cursor,
        end: cursor,
    };
}

/** Icons of the palette, as in the paper workshops. */
export const icons: { emoji: string; label: string }[] = [
    { emoji: '🎯', label: 'Objectif' },
    { emoji: '🔥', label: 'Difficulté' },
    { emoji: '💡', label: 'Astuce' },
    { emoji: '⚠️', label: 'Attention' },
    { emoji: '✅', label: 'Fait' },
    { emoji: '❌', label: 'À éviter' },
    { emoji: '👉', label: 'À faire' },
    { emoji: '🔗', label: 'Lien' },
    { emoji: '📎', label: 'Pièce jointe' },
    { emoji: '📧', label: 'Mail' },
    { emoji: '🌐', label: 'Internet' },
    { emoji: '🔍', label: 'Rechercher' },
    { emoji: '🖱️', label: 'Souris' },
    { emoji: '⌨️', label: 'Clavier' },
    { emoji: '💻', label: 'Ordinateur' },
    { emoji: '📱', label: 'Smartphone' },
    { emoji: '📄', label: 'Document' },
    { emoji: '📁', label: 'Dossier' },
    { emoji: '📅', label: 'Agenda' },
    { emoji: '🗺️', label: 'Se déplacer' },
    { emoji: '🏛️', label: 'Démarches' },
    { emoji: '🤝', label: 'Entraide' },
    { emoji: '⭐', label: 'Étoile' },
    { emoji: '🏁', label: 'Arrivée' },
];

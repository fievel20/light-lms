import { preview } from '@/routes/sequences';

/**
 * Server-side rendering of the editor's content, so the preview is exactly
 * the final page (same Markdown flavour, same escaping).
 */
export async function renderMarkdown(
    content: string,
    signal?: AbortSignal,
): Promise<string> {
    const xsrf = document.cookie
        .split('; ')
        .find((cookie) => cookie.startsWith('XSRF-TOKEN='))
        ?.slice('XSRF-TOKEN='.length);

    const response = await fetch(preview().url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-XSRF-TOKEN': decodeURIComponent(xsrf ?? ''),
        },
        body: JSON.stringify({ content }),
        signal,
    });

    if (!response.ok) {
        throw new Error(`Aperçu indisponible (${response.status})`);
    }

    return ((await response.json()) as { html: string }).html;
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

import { jsonHeaders, postImage } from '@/lib/markdown';
import { store as storeAttachment } from '@/routes/sequences/attachments';
import { store as storeImage } from '@/routes/sequences/images';
import {
    store as storeImport,
    update as updateImport,
} from '@/routes/sequences/import';

/**
 * Importing a folder of Markdown files: one sequence per .md file, with the
 * images and attachments its links point to (paths relative to the file,
 * e.g. ![Capture](images/a.png) or [Fiche](pieces-jointes/fiche.pdf)).
 */

/** Same limit as the server (and the upload limit of the VPS). */
export const MAX_FILE_SIZE = 5 * 1024 * 1024;

const IMAGES = ['png', 'jpg', 'jpeg', 'gif', 'webp'];

const ATTACHMENTS = [
    'pdf',
    'doc',
    'docx',
    'xls',
    'xlsx',
    'ppt',
    'pptx',
    'odt',
    'ods',
    'odp',
    'txt',
    'csv',
    'zip',
    'mp3',
    'mp4',
];

/** ![alt](target "title") or [text](target): group 1 is "!" for an image. */
const LINK =
    /(!?)\[((?:[^\][]|\[[^\]]*\])*)\]\(\s*(<[^>]*>|[^)\s]+)((?:\s+(?:"[^"]*"|'[^']*'))?\s*)\)/g;

export type FileRef = {
    // As written in the Markdown, e.g. images/a.png or <mon image.png>.
    target: string;
    // Inside the dropped folder, e.g. cours/images/a.png.
    path: string;
    kind: 'image' | 'attachment';
    file: File | null;
    problem: string | null;
};

export type PlannedSequence = {
    path: string;
    title: string;
    content: string;
    refs: FileRef[];
};

export type ImportResult = {
    path: string;
    title: string;
    url: string | null;
    errors: string[];
};

/** The files of a drop, folders opened, by path inside what was dropped. */
export async function filesFromDrop(
    transfer: DataTransfer,
): Promise<Map<string, File>> {
    const files = new Map<string, File>();
    const entries = [...transfer.items]
        .map((item) => item.webkitGetAsEntry())
        .filter((entry): entry is FileSystemEntry => entry !== null);

    for (const entry of entries) {
        await walk(entry, files);
    }

    return files;
}

/** From <input type="file" webkitdirectory>, which gives relative paths. */
export function filesFromInput(list: FileList): Map<string, File> {
    return new Map(
        [...list].map((file) => [file.webkitRelativePath || file.name, file]),
    );
}

async function walk(
    entry: FileSystemEntry,
    files: Map<string, File>,
): Promise<void> {
    const path = entry.fullPath.replace(/^\//, '');

    if (entry.isFile) {
        const file = await new Promise<File>((resolve, reject) =>
            (entry as FileSystemFileEntry).file(resolve, reject),
        );
        files.set(path, file);

        return;
    }

    const reader = (entry as FileSystemDirectoryEntry).createReader();

    // A directory is read in batches, until an empty one.
    for (;;) {
        const batch = await new Promise<FileSystemEntry[]>((resolve, reject) =>
            reader.readEntries(resolve, reject),
        );

        if (batch.length === 0) {
            return;
        }

        for (const child of batch) {
            await walk(child, files);
        }
    }
}

/** The sequences to create, in the order of their paths (1-…, 2-…, 10-…). */
export async function planImport(
    files: Map<string, File>,
): Promise<PlannedSequence[]> {
    const markdown = [...files.keys()]
        .filter((path) => /\.(md|markdown)$/i.test(path))
        .sort((a, b) => a.localeCompare(b, 'fr', { numeric: true }));

    return Promise.all(
        markdown.map(async (path) => {
            const { title, content } = splitTitle(
                await files.get(path)!.text(),
                path,
            );

            return {
                path,
                title,
                content,
                refs: findRefs(content, path, files),
            };
        }),
    );
}

/**
 * The title: a front matter "title:", else the first "# " heading (then
 * removed from the text), else the file name.
 */
export function splitTitle(
    source: string,
    path: string,
): { title: string; content: string } {
    let text = source.replace(/^﻿/, '').replace(/\r\n?/g, '\n');
    let title: string | null = null;

    const front = text.match(/^---\n([\s\S]*?)\n---\n?/);

    if (front) {
        title = front[1].match(/^title:\s*["']?(.+?)["']?\s*$/m)?.[1] ?? null;
        text = text.slice(front[0].length);
    }

    const heading = text.match(/^\s*# +(.+?)\s*#*\s*$/m);

    if (heading && heading.index !== undefined) {
        // Only when nothing but blank lines comes before it.
        if (text.slice(0, heading.index).trim() === '') {
            title ??= heading[1];
            text = text.slice(heading.index + heading[0].length);
        }
    }

    title ??= fileTitle(path);

    return {
        title: title.slice(0, 255),
        content: text.replace(/^\s*\n/, '').trimEnd() || title,
    };
}

function fileTitle(path: string): string {
    const name = (path.split('/').pop() ?? path)
        .replace(/\.[^.]+$/, '')
        .replace(/[-_]+/g, ' ')
        .trim();

    return name.charAt(0).toUpperCase() + name.slice(1);
}

function findRefs(
    content: string,
    mdPath: string,
    files: Map<string, File>,
): FileRef[] {
    const refs = new Map<string, FileRef>();
    collectRefs(content, mdPath, files, refs);

    return [...refs.values()];
}

/**
 * The links of a text, and those inside their text: a clickable image is
 * written [![Photo](a.jpg)](a.jpg).
 */
function collectRefs(
    content: string,
    mdPath: string,
    files: Map<string, File>,
    refs: Map<string, FileRef>,
): void {
    for (const match of content.matchAll(LINK)) {
        collectRefs(match[2], mdPath, files, refs);

        const target = match[3];
        const clean = cleanTarget(target);

        if (clean === null || refs.has(target)) {
            continue;
        }

        const path = resolvePath(mdPath, clean);
        const extension = path.split('.').pop()?.toLowerCase() ?? '';
        // A link to a picture ([Photo](a.jpg)) is sent as an image too: the
        // page then links to it.
        const kind = IMAGES.includes(extension) ? 'image' : 'attachment';
        const file = files.get(path) ?? null;
        let problem: string | null = null;

        if (
            match[1] === '!'
                ? kind !== 'image'
                : kind === 'attachment' && !ATTACHMENTS.includes(extension)
        ) {
            if (match[1] !== '!' && /^(md|markdown)$/.test(extension)) {
                // A link to another lesson: left as it is.
                continue;
            }

            problem = `type de fichier refusé (.${extension})`;
        } else if (!file) {
            problem = 'fichier introuvable dans le dossier';
        } else if (file.size > MAX_FILE_SIZE) {
            problem = 'plus de 5 Mo';
        }

        refs.set(target, { target, path, kind, file, problem });
    }
}

/** The text with its links pointing to the uploaded files, inner links included. */
function rewriteLinks(
    content: string,
    replacements: Map<string, string>,
): string {
    return content.replace(
        LINK,
        (whole, bang: string, text: string, target: string, title: string) => {
            const inner = rewriteLinks(text, replacements);
            const replaced = replacements.get(target);

            return replaced === undefined && inner === text
                ? whole
                : `${bang}[${inner}](${replaced ?? target}${title})`;
        },
    );
}

/** A link to a local file, decoded; null for web links and anchors. */
function cleanTarget(target: string): string | null {
    const raw = target.startsWith('<') ? target.slice(1, -1) : target;

    if (
        raw === '' ||
        raw.startsWith('#') ||
        raw.startsWith('/') ||
        /^[a-z][a-z0-9+.-]*:/i.test(raw)
    ) {
        return null;
    }

    try {
        return decodeURIComponent(raw.split(/[?#]/)[0]);
    } catch {
        return raw;
    }
}

function resolvePath(mdPath: string, target: string): string {
    const parts = mdPath.split('/').slice(0, -1);

    for (const part of target.split('/')) {
        if (part === '..') {
            parts.pop();
        } else if (part !== '.' && part !== '') {
            parts.push(part);
        }
    }

    return parts.join('/');
}

/**
 * Creates the sequence, sends its files one by one, then saves the text
 * with its links pointing to them. A file that fails is reported; its link
 * stays as written.
 */
export async function importSequence(
    planned: PlannedSequence,
): Promise<ImportResult> {
    const errors: string[] = [];
    const created = await sendJson<{ id: number; url: string }>(
        storeImport().url,
        'POST',
        { title: planned.title, content: planned.content },
    );
    const replacements = new Map<string, string>();

    for (const ref of planned.refs) {
        if (ref.problem || !ref.file) {
            errors.push(`${ref.path} : ${ref.problem}`);
            continue;
        }

        try {
            const { id } =
                ref.kind === 'image'
                    ? await postImage(storeImage(created.id).url, ref.file)
                    : await postImage(
                          storeAttachment(created.id).url,
                          ref.file,
                          'file',
                      );
            replacements.set(
                ref.target,
                `${ref.kind === 'image' ? 'image' : 'fichier'}:${id}`,
            );
        } catch (error) {
            errors.push(
                `${ref.path} : ${error instanceof Error ? error.message : 'envoi impossible'}`,
            );
        }
    }

    if (replacements.size > 0) {
        await sendJson(updateImport(created.id).url, 'PUT', {
            content: rewriteLinks(planned.content, replacements),
        });
    }

    return {
        path: planned.path,
        title: planned.title,
        url: created.url,
        errors,
    };
}

async function sendJson<T>(
    url: string,
    method: 'POST' | 'PUT',
    body: Record<string, unknown>,
): Promise<T> {
    const response = await fetch(url, {
        method,
        headers: { ...jsonHeaders(), 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
    });
    const data = (await response.json().catch(() => ({}))) as T & {
        message?: string;
    };

    if (!response.ok) {
        throw new Error(data.message ?? `Erreur ${response.status}`);
    }

    return data;
}

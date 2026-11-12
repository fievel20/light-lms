<script lang="ts">
    import Bold from '@lucide/svelte/icons/bold';
    import Heading2 from '@lucide/svelte/icons/heading-2';
    import ImagePlus from '@lucide/svelte/icons/image-plus';
    import Italic from '@lucide/svelte/icons/italic';
    import Link2 from '@lucide/svelte/icons/link-2';
    import List from '@lucide/svelte/icons/list';
    import ListOrdered from '@lucide/svelte/icons/list-ordered';
    import Quote from '@lucide/svelte/icons/quote';
    import Smile from '@lucide/svelte/icons/smile';
    import { tick } from 'svelte';
    import type { Component } from 'svelte';
    import { toast } from 'svelte-sonner';
    import IconGlyph from '@/components/icons/IconGlyph.svelte';
    import { Spinner } from '@/components/ui/spinner';
    import { nativeFieldClass } from '@/lib/form';
    import {
        icons,
        insertText,
        prefixLines,
        renderMarkdown,
        uploadImage,
        wrapSelection,
    } from '@/lib/markdown';
    import type { TextEdit } from '@/lib/markdown';
    import { cn } from '@/lib/utils';
    import type { Icon } from '@/types/icon';

    let {
        name,
        value = $bindable(''),
        maxlength,
        sequenceId = null,
        library = [],
    }: {
        name: string;
        value?: string;
        maxlength: number;
        // Images need a saved sequence to belong to.
        sequenceId?: number | null;
        // Imported icons (Administration > Icônes), shown before the emojis.
        library?: Icon[];
    } = $props();

    let textarea: HTMLTextAreaElement | undefined = $state();
    let fileInput: HTMLInputElement | undefined = $state();
    // On phones, one pane at a time; side by side from lg.
    let tab: 'write' | 'preview' = $state('write');
    let html = $state('');
    let previewError = $state(false);
    let iconsOpen = $state(false);
    let uploading = $state(0);
    let dragging = $state(false);

    // Keep the preview in sync with the server rendering, 300 ms after typing stops.
    $effect(() => {
        const content = value;
        const id = sequenceId;
        const controller = new AbortController();
        const timer = setTimeout(async () => {
            try {
                html = content.trim()
                    ? await renderMarkdown(content, id, controller.signal)
                    : '';
                previewError = false;
            } catch (error) {
                if (!controller.signal.aborted) {
                    previewError = true;
                    console.error(error);
                }
            }
        }, 300);

        return () => {
            clearTimeout(timer);
            controller.abort();
        };
    });

    type Tool = {
        label: string;
        icon: Component;
        apply: (text: string, start: number, end: number) => TextEdit;
    };

    const tools: Tool[] = [
        {
            label: 'Titre',
            icon: Heading2,
            apply: (t, s, e) => prefixLines(t, s, e, () => '## '),
        },
        {
            label: 'Gras',
            icon: Bold,
            apply: (t, s, e) => wrapSelection(t, s, e, '**', '**', 'texte'),
        },
        {
            label: 'Italique',
            icon: Italic,
            apply: (t, s, e) => wrapSelection(t, s, e, '_', '_', 'texte'),
        },
        {
            label: 'Étapes numérotées',
            icon: ListOrdered,
            apply: (t, s, e) => prefixLines(t, s, e, (i) => `${i + 1}. `),
        },
        {
            label: 'Liste à puces',
            icon: List,
            apply: (t, s, e) => prefixLines(t, s, e, () => '- '),
        },
        {
            label: 'Lien',
            icon: Link2,
            apply: (t, s, e) =>
                wrapSelection(t, s, e, '[', '](https://)', 'texte du lien'),
        },
        {
            label: 'Encadré',
            icon: Quote,
            apply: (t, s, e) => prefixLines(t, s, e, () => '> '),
        },
    ];

    async function applyEdit(
        edit: (text: string, start: number, end: number) => TextEdit,
    ): Promise<void> {
        if (!textarea) {
            return;
        }

        const result = edit(
            value,
            textarea.selectionStart,
            textarea.selectionEnd,
        );

        value = result.text;
        tab = 'write';
        await tick();
        textarea.focus();
        textarea.setSelectionRange(result.start, result.end);
    }

    function insertIcon(text: string): void {
        iconsOpen = false;
        void applyEdit((t, s, e) => insertText(t, s, e, text));
    }

    async function addImages(files: Iterable<File>): Promise<void> {
        if (sequenceId === null) {
            toast.error(
                'Enregistre la séquence une première fois pour ajouter des images.',
            );

            return;
        }

        for (const file of files) {
            if (!file.type.startsWith('image/')) {
                continue;
            }

            uploading++;

            try {
                const markdown = await uploadImage(sequenceId, file);
                // On its own line, so it shows as a block under the step.
                await applyEdit((t, s, e) =>
                    insertText(
                        t,
                        s,
                        e,
                        `${s > 0 && t[s - 1] !== '\n' ? '\n\n' : ''}${markdown}\n`,
                    ),
                );
            } catch (error) {
                toast.error(
                    error instanceof Error
                        ? error.message
                        : "L'image n'a pas pu être envoyée.",
                );
            } finally {
                uploading--;
            }
        }
    }

    function onpaste(event: ClipboardEvent): void {
        const files = [...(event.clipboardData?.files ?? [])];

        if (files.some((file) => file.type.startsWith('image/'))) {
            event.preventDefault();
            void addImages(files);
        }
    }

    function ondrop(event: DragEvent): void {
        dragging = false;
        const files = [...(event.dataTransfer?.files ?? [])];

        if (files.length > 0) {
            event.preventDefault();
            void addImages(files);
        }
    }

    const toolClass =
        'inline-flex size-11 items-center justify-center rounded-md text-muted-foreground hover:bg-muted hover:text-foreground sm:size-9';
</script>

<div class="surface overflow-hidden">
    <div
        class="relative flex flex-wrap items-center gap-1 border-b border-border p-1"
        role="toolbar"
        aria-label="Mise en forme"
    >
        {#each tools as tool (tool.label)}
            <button
                type="button"
                class={toolClass}
                title={tool.label}
                aria-label={tool.label}
                onclick={() => applyEdit(tool.apply)}
            >
                <tool.icon class="size-4" />
            </button>
        {/each}

        <button
            type="button"
            class={cn(toolClass, iconsOpen && 'bg-muted text-foreground')}
            title="Icônes"
            aria-label="Icônes"
            aria-expanded={iconsOpen}
            onclick={() => (iconsOpen = !iconsOpen)}
        >
            <Smile class="size-4" />
        </button>

        <button
            type="button"
            class={toolClass}
            title={sequenceId === null
                ? 'Image (après le premier enregistrement)'
                : 'Image (ou colle / glisse une capture)'}
            aria-label="Image"
            onclick={() =>
                sequenceId === null
                    ? addImages([])
                    : fileInput?.click()}
        >
            {#if uploading > 0}<Spinner />{:else}<ImagePlus
                    class="size-4"
                />{/if}
        </button>
        <input
            bind:this={fileInput}
            type="file"
            accept="image/png,image/jpeg,image/webp,image/gif"
            multiple
            class="hidden"
            onchange={(event) => {
                void addImages(event.currentTarget.files ?? []);
                event.currentTarget.value = '';
            }}
        />

        <div class="ml-auto flex lg:hidden" role="tablist">
            {#each [['write', 'Écrire'], ['preview', 'Aperçu']] as const as [key, label] (key)}
                <button
                    type="button"
                    role="tab"
                    aria-selected={tab === key}
                    class={cn(
                        'h-11 rounded-md px-3 text-sm font-medium sm:h-9',
                        tab === key
                            ? 'bg-accent text-accent-foreground'
                            : 'text-muted-foreground',
                    )}
                    onclick={() => (tab = key)}
                >
                    {label}
                </button>
            {/each}
        </div>

        {#if iconsOpen}
            <div
                class="absolute top-full left-1 z-20 mt-1 grid max-h-80 w-[min(20rem,calc(100vw-3rem))] grid-cols-6 gap-1 overflow-y-auto surface p-2"
                role="menu"
                aria-label="Icônes"
            >
                {#each library as icon (icon.id)}
                    <button
                        type="button"
                        role="menuitem"
                        class="flex aspect-square items-center justify-center rounded-md text-xl hover:bg-muted"
                        title={icon.name}
                        aria-label={icon.name}
                        onclick={() => insertIcon(icon.markdown)}
                    >
                        <IconGlyph {icon} />
                    </button>
                {/each}
                {#if library.length > 0}
                    <hr class="col-span-6 my-1 border-border" />
                {/if}
                {#each icons as icon (icon.emoji)}
                    <button
                        type="button"
                        role="menuitem"
                        class="flex aspect-square items-center justify-center rounded-md text-xl hover:bg-muted"
                        title={icon.label}
                        aria-label={icon.label}
                        onclick={() => insertIcon(icon.emoji)}
                    >
                        {icon.emoji}
                    </button>
                {/each}
            </div>
        {/if}
    </div>

    <div class="grid lg:grid-cols-2">
        <textarea
            bind:this={textarea}
            bind:value
            id={name}
            {name}
            {maxlength}
            required
            rows="18"
            spellcheck="true"
            placeholder={'1. Lance le jeu\n2. Atteins 12/20 en mode débutant 🎯'}
            {onpaste}
            {ondrop}
            ondragover={(event) => {
                event.preventDefault();
                dragging = true;
            }}
            ondragleave={() => (dragging = false)}
            class={cn(
                nativeFieldClass,
                'min-h-80 resize-y rounded-none border-0 font-mono shadow-none focus-visible:ring-0 focus-visible:ring-offset-0 lg:block lg:border-r lg:border-border',
                tab === 'write' ? 'block' : 'hidden',
                dragging && 'bg-accent/40',
            )}
        ></textarea>

        <div
            class={cn(
                'min-h-80 p-4 lg:block',
                tab === 'preview' ? 'block' : 'hidden',
            )}
            aria-live="polite"
        >
            <p class="mb-3 hidden eyebrow lg:block">Aperçu</p>
            {#if previewError}
                <p class="text-sm text-destructive">
                    Aperçu indisponible pour le moment.
                </p>
            {:else if html}
                <!-- Rendered and escaped by the server (MarkdownRenderer). -->
                <div class="markdown">{@html html}</div>
            {:else}
                <p class="text-sm text-muted-foreground">
                    L'aperçu s'affiche ici pendant que tu écris.
                </p>
            {/if}
        </div>
    </div>
</div>

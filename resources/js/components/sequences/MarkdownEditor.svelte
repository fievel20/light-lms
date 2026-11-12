<script lang="ts">
    import Bold from '@lucide/svelte/icons/bold';
    import Heading2 from '@lucide/svelte/icons/heading-2';
    import Italic from '@lucide/svelte/icons/italic';
    import Link2 from '@lucide/svelte/icons/link-2';
    import List from '@lucide/svelte/icons/list';
    import ListOrdered from '@lucide/svelte/icons/list-ordered';
    import Quote from '@lucide/svelte/icons/quote';
    import { tick } from 'svelte';
    import type { Component } from 'svelte';
    import { nativeFieldClass } from '@/lib/form';
    import {
        prefixLines,
        renderMarkdown,
        wrapSelection,
    } from '@/lib/markdown';
    import type { TextEdit } from '@/lib/markdown';
    import { cn } from '@/lib/utils';

    let {
        name,
        value = $bindable(''),
        maxlength,
    }: { name: string; value?: string; maxlength: number } = $props();

    let textarea: HTMLTextAreaElement | undefined = $state();
    // On phones, one pane at a time; side by side from lg.
    let tab: 'write' | 'preview' = $state('write');
    let html = $state('');
    let previewError = $state(false);

    // Keep the preview in sync with the server rendering, 300 ms after typing stops.
    $effect(() => {
        const content = value;
        const controller = new AbortController();
        const timer = setTimeout(async () => {
            try {
                html = content.trim()
                    ? await renderMarkdown(content, controller.signal)
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

    async function use(tool: Tool): Promise<void> {
        if (!textarea) {
            return;
        }

        const edit = tool.apply(
            value,
            textarea.selectionStart,
            textarea.selectionEnd,
        );

        value = edit.text;
        tab = 'write';
        await tick();
        textarea.focus();
        textarea.setSelectionRange(edit.start, edit.end);
    }
</script>

<div class="surface overflow-hidden">
    <div
        class="flex flex-wrap items-center gap-1 border-b border-border p-1"
        role="toolbar"
        aria-label="Mise en forme"
    >
        {#each tools as tool (tool.label)}
            <button
                type="button"
                class="inline-flex size-11 items-center justify-center rounded-md text-muted-foreground hover:bg-muted hover:text-foreground sm:size-9"
                title={tool.label}
                aria-label={tool.label}
                onclick={() => use(tool)}
            >
                <tool.icon class="size-4" />
            </button>
        {/each}

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
            class={cn(
                nativeFieldClass,
                'min-h-80 resize-y rounded-none border-0 font-mono shadow-none focus-visible:ring-0 focus-visible:ring-offset-0 lg:block lg:border-r lg:border-border',
                tab === 'write' ? 'block' : 'hidden',
            )}
        ></textarea>

        <div
            class={cn(
                'min-h-80 p-4 lg:block',
                tab === 'preview' ? 'block' : 'hidden',
            )}
            aria-live="polite"
        >
            <p class="eyebrow mb-3 hidden lg:block">Aperçu</p>
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

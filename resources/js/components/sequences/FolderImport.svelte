<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import CircleAlert from '@lucide/svelte/icons/circle-alert';
    import CircleCheck from '@lucide/svelte/icons/circle-check';
    import FolderUp from '@lucide/svelte/icons/folder-up';
    import Paperclip from '@lucide/svelte/icons/paperclip';
    import ImageIcon from '@lucide/svelte/icons/image';
    import { Button } from '@/components/ui/button';
    import { Spinner } from '@/components/ui/spinner';
    import {
        filesFromDrop,
        filesFromInput,
        importSequence,
        planImport,
    } from '@/lib/folder-import';
    import type { ImportResult, PlannedSequence } from '@/lib/folder-import';
    import { cn } from '@/lib/utils';

    // Drop a folder (or choose one): each .md becomes a sequence, with the
    // images and attachments its links point to.
    let dragging = $state(false);
    let reading = $state(false);
    let plan: PlannedSequence[] | null = $state(null);
    let running = $state(false);
    let done = $state(0);
    let results: ImportResult[] = $state([]);
    let input: HTMLInputElement | undefined = $state();

    const count = (item: PlannedSequence, kind: 'image' | 'attachment') =>
        item.refs.filter((ref) => ref.kind === kind && !ref.problem).length;

    async function read(files: Promise<Map<string, File>> | Map<string, File>) {
        reading = true;
        results = [];

        try {
            plan = await planImport(await files);
        } finally {
            reading = false;
        }
    }

    function ondrop(event: DragEvent): void {
        event.preventDefault();
        dragging = false;

        if (event.dataTransfer && !running) {
            void read(filesFromDrop(event.dataTransfer));
        }
    }

    async function start(): Promise<void> {
        if (!plan) {
            return;
        }

        running = true;
        done = 0;
        results = [];

        // One after the other: each sequence sends its files in turn.
        for (const item of plan) {
            try {
                results.push(await importSequence(item));
            } catch (error) {
                results.push({
                    path: item.path,
                    title: item.title,
                    url: null,
                    errors: [
                        error instanceof Error
                            ? error.message
                            : 'Import impossible.',
                    ],
                });
            }

            done++;
        }

        running = false;
        plan = null;
        router.reload({ only: ['sequences'] });
    }
</script>

<section class="grid gap-3" aria-label="Importer un dossier">
    <!-- svelte-ignore a11y_no_static_element_interactions -->
    <div
        class={cn(
            'flex flex-col items-center gap-3 rounded-2xl border-2 border-dashed p-6 text-center transition-colors',
            dragging ? 'border-primary bg-primary-soft' : 'border-border',
        )}
        ondragover={(event) => {
            event.preventDefault();
            dragging = true;
        }}
        ondragleave={() => (dragging = false)}
        {ondrop}
    >
        {#if reading}
            <Spinner class="size-6" />
        {:else}
            <FolderUp class="size-8 text-primary" />
        {/if}
        <p class="font-medium">Glisse ici un dossier de cours</p>
        <p class="max-w-md text-sm text-muted-foreground">
            Chaque fichier Markdown (.md) devient une séquence, avec les
            images et les pièces jointes de ses liens (PDF, documents… 5 Mo
            au plus chacun).
        </p>
        <Button
            type="button"
            variant="outline"
            class="h-11 sm:h-9"
            disabled={running}
            onclick={() => input?.click()}
        >
            Choisir un dossier
        </Button>
        <input
            bind:this={input}
            type="file"
            webkitdirectory
            multiple
            class="hidden"
            onchange={(event) => {
                const files = event.currentTarget.files;

                if (files?.length) {
                    void read(filesFromInput(files));
                }

                event.currentTarget.value = '';
            }}
        />
    </div>

    {#if plan}
        <div class="grid gap-3 surface p-4">
            {#if plan.length === 0}
                <p class="text-sm text-muted-foreground">
                    Aucun fichier .md dans ce dossier.
                </p>
            {:else}
                <p class="font-medium">
                    {plan.length} séquence{plan.length > 1 ? 's' : ''} à créer
                </p>
                <ul class="grid gap-2 text-sm">
                    {#each plan as item (item.path)}
                        {@const problems = item.refs.filter((ref) => ref.problem)}
                        <li class="grid gap-1 rounded-md bg-muted p-3">
                            <span class="font-medium">{item.title}</span>
                            <span
                                class="flex flex-wrap items-center gap-x-3 text-muted-foreground"
                            >
                                <span class="font-mono text-xs">{item.path}</span>
                                <span class="inline-flex items-center gap-1"
                                    ><ImageIcon class="size-3.5" />
                                    {count(item, 'image')}</span
                                >
                                <span class="inline-flex items-center gap-1"
                                    ><Paperclip class="size-3.5" />
                                    {count(item, 'attachment')}</span
                                >
                            </span>
                            {#each problems as ref (ref.target)}
                                <span class="text-destructive"
                                    >{ref.path} : {ref.problem}</span
                                >
                            {/each}
                        </li>
                    {/each}
                </ul>
                <div class="flex flex-wrap items-center gap-2">
                    <Button
                        type="button"
                        class="h-11 sm:h-9"
                        disabled={running}
                        onclick={start}
                    >
                        {#if running}<Spinner />{/if}
                        {running
                            ? `Import ${done + 1} / ${plan.length}…`
                            : `Importer ${plan.length} séquence${plan.length > 1 ? 's' : ''}`}
                    </Button>
                    {#if !running}
                        <Button
                            type="button"
                            variant="ghost"
                            class="h-11 sm:h-9"
                            onclick={() => (plan = null)}>Annuler</Button
                        >
                    {/if}
                </div>
            {/if}
        </div>
    {/if}

    {#if results.length > 0}
        <ul class="grid gap-2 surface p-4 text-sm" aria-live="polite">
            {#each results as result (result.path)}
                <li class="grid gap-1">
                    <span class="flex items-center gap-2">
                        {#if result.url && result.errors.length === 0}
                            <CircleCheck class="size-4 shrink-0 text-success-ink" />
                        {:else}
                            <CircleAlert class="size-4 shrink-0 text-destructive" />
                        {/if}
                        {#if result.url}
                            <Link href={result.url} class="font-medium underline"
                                >{result.title}</Link
                            >
                        {:else}
                            <span class="font-medium">{result.title}</span>
                        {/if}
                    </span>
                    {#each result.errors as error (error)}
                        <span class="pl-6 text-destructive">{error}</span>
                    {/each}
                </li>
            {/each}
        </ul>
    {/if}
</section>

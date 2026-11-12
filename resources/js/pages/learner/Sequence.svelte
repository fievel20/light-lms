<script module lang="ts">
    import { index } from '@/routes/my-learning-paths';

    export const layout = {
        breadcrumbs: [{ title: 'Mes parcours', href: index() }],
    };
</script>

<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import ArrowLeft from '@lucide/svelte/icons/arrow-left';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import CircleCheck from '@lucide/svelte/icons/circle-check';
    import X from '@lucide/svelte/icons/x';
    import MyLearningPathController from '@/actions/App/Http/Controllers/MyLearningPathController';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Spinner } from '@/components/ui/spinner';
    import { formatDate } from '@/lib/dates';
    import { cn, toUrl } from '@/lib/utils';
    import { show } from '@/routes/my-learning-paths';

    type Neighbour = { id: number; title: string } | null;

    let {
        promotion,
        learningPath,
        sequence,
        previous,
        next,
        position,
        count,
    }: {
        promotion: { id: number; name: string; archived: boolean };
        learningPath: { id: number; name: string };
        sequence: {
            id: number;
            title: string;
            summary: string | null;
            html: string;
            completed_at: string | null;
        };
        previous: Neighbour;
        next: Neighbour;
        position: number;
        count: number;
    } = $props();

    // Tapping an image opens it full screen (screenshots are often small on a phone).
    let zoomed: string | null = $state(null);

    function onclick(event: MouseEvent): void {
        const target = event.target;

        if (
            target instanceof HTMLImageElement &&
            !target.classList.contains('icon')
        ) {
            zoomed = target.src;
        }
    }

    const sequenceHref = (id: number) =>
        toUrl(show([promotion.id, learningPath.id, id]));
</script>

<svelte:window
    onkeydown={(event) => event.key === 'Escape' && (zoomed = null)}
/>

<AppHead title={sequence.title} />

<div class="mx-auto flex w-full max-w-3xl flex-col gap-5 p-4 sm:p-6">
    <header class="space-y-2">
        <p class="eyebrow">
            // {learningPath.name} · {position} / {count}
        </p>
        <h1 class="display-title text-3xl break-words sm:text-4xl">
            {sequence.title}
        </h1>
        {#if sequence.summary}
            <p class="text-muted-foreground">{sequence.summary}</p>
        {/if}
    </header>

    <!-- Rendered and escaped by the server (MarkdownRenderer). -->
    <!-- svelte-ignore a11y_click_events_have_key_events, a11y_no_noninteractive_element_interactions -->
    <article
        class="markdown surface p-4 text-[1.0625rem] sm:p-6 [&_img:not(.icon)]:cursor-zoom-in"
        {onclick}
    >
        {@html sequence.html}
    </article>

    <!-- The learner validates the sequence themself when they finish it. -->
    <Form
        {...MyLearningPathController.complete.form([
            promotion.id,
            learningPath.id,
            sequence.id,
        ])}
        options={{ preserveScroll: true }}
        class={cn(
            'flex flex-col items-center gap-3 surface p-4 text-center sm:flex-row sm:text-left',
            sequence.completed_at && 'border-success bg-success-soft',
        )}
    >
        {#snippet children({ errors, processing })}
            <input
                type="hidden"
                name="completed"
                value={sequence.completed_at ? '0' : '1'}
            />
            {#if sequence.completed_at}
                <CircleCheck class="size-8 shrink-0 text-success-ink" />
                <p class="flex-1">
                    <span class="block font-semibold">Séquence terminée</span>
                    <span class="block text-sm text-muted-foreground"
                        >Validée le {formatDate(sequence.completed_at)}</span
                    >
                </p>
                {#if !promotion.archived}
                    <Button
                        type="submit"
                        variant="ghost"
                        disabled={processing}
                        class="h-11 text-muted-foreground sm:h-9"
                    >
                        {#if processing}<Spinner />{/if}
                        Annuler
                    </Button>
                {/if}
            {:else if promotion.archived}
                <p class="flex-1 text-sm text-muted-foreground">
                    Cette promotion est archivée : les séquences ne peuvent plus
                    être validées.
                </p>
            {:else}
                <p class="flex-1">
                    <span class="block font-semibold">Tu as fini ?</span>
                    <span class="block text-sm text-muted-foreground"
                        >Valide la séquence : ton formateur le verra.</span
                    >
                </p>
                <Button
                    type="submit"
                    disabled={processing}
                    class="h-12 w-full sm:h-10 sm:w-auto"
                >
                    {#if processing}<Spinner />{:else}<CircleCheck
                            class="size-4"
                        />{/if}
                    J'ai terminé
                </Button>
            {/if}
            {#if errors.completed}
                <p class="text-sm text-destructive">{errors.completed}</p>
            {/if}
        {/snippet}
    </Form>

    <nav class="grid grid-cols-2 gap-3" aria-label="Séquences du parcours">
        {#if previous}
            <Link
                href={sequenceHref(previous.id)}
                class="flex min-h-16 items-center gap-2 surface p-3 hover:bg-muted/50"
            >
                <ArrowLeft class="size-4 shrink-0" />
                <span class="min-w-0">
                    <span class="block eyebrow">Précédente</span>
                    <span class="block truncate text-sm font-medium"
                        >{previous.title}</span
                    >
                </span>
            </Link>
        {:else}
            <span></span>
        {/if}
        {#if next}
            <Link
                href={sequenceHref(next.id)}
                class="flex min-h-16 items-center justify-end gap-2 surface p-3 text-right hover:bg-muted/50"
            >
                <span class="min-w-0">
                    <span class="block eyebrow">Suivante</span>
                    <span class="block truncate text-sm font-medium"
                        >{next.title}</span
                    >
                </span>
                <ArrowRight class="size-4 shrink-0" />
            </Link>
        {/if}
    </nav>

    <Link
        href={toUrl(index({ query: { promotion: promotion.id } }))}
        class="mono-link text-muted-foreground">← Tous mes parcours</Link
    >
</div>

{#if zoomed}
    <button
        type="button"
        class={cn(
            'fixed inset-0 z-50 grid place-items-center bg-night/90 p-4',
        )}
        aria-label="Fermer l'image"
        onclick={() => (zoomed = null)}
    >
        <img
            src={zoomed}
            alt=""
            class="max-h-full max-w-full rounded-md bg-card object-contain"
        />
        <X class="absolute top-4 right-4 size-6 text-white" />
    </button>
{/if}

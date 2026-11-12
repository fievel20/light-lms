<script module lang="ts">
    import { index } from '@/routes/my-learning-paths';

    export const layout = {
        breadcrumbs: [{ title: 'Mes parcours', href: index() }],
    };
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowLeft from '@lucide/svelte/icons/arrow-left';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import X from '@lucide/svelte/icons/x';
    import AppHead from '@/components/AppHead.svelte';
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
        promotion: { id: number; name: string };
        learningPath: { id: number; name: string };
        sequence: {
            id: number;
            title: string;
            summary: string | null;
            html: string;
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

        if (target instanceof HTMLImageElement) {
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
        class="markdown surface p-4 text-[1.0625rem] sm:p-6 [&_img]:cursor-zoom-in"
        {onclick}
    >
        {@html sequence.html}
    </article>

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

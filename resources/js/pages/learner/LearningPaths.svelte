<script module lang="ts">
    import { index } from '@/routes/my-learning-paths';

    export const layout = {
        breadcrumbs: [{ title: 'Mes parcours', href: index() }],
    };
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import Check from '@lucide/svelte/icons/check';
    import ChevronRight from '@lucide/svelte/icons/chevron-right';
    import Route from '@lucide/svelte/icons/route';
    import AppHead from '@/components/AppHead.svelte';
    import EmptyState from '@/components/design/EmptyState.svelte';
    import PageHeader from '@/components/design/PageHeader.svelte';
    import { formatDate } from '@/lib/dates';
    import { cn, toUrl } from '@/lib/utils';
    import { show } from '@/routes/my-learning-paths';

    type PromotionOption = { id: number; name: string };

    type OpenedSequence = {
        id: number;
        title: string;
        summary: string | null;
        released_at: string;
        is_new: boolean;
        completed_at: string | null;
    };

    let {
        promotions,
        promotion,
        learningPaths,
    }: {
        promotions: PromotionOption[];
        promotion: (PromotionOption & { archived_at: string | null }) | null;
        learningPaths: {
            id: number;
            name: string;
            description: string | null;
            sequences: OpenedSequence[];
        }[];
    } = $props();

    const done = (sequences: OpenedSequence[]) =>
        sequences.filter((sequence) => sequence.completed_at).length;
</script>

<AppHead title="Mes parcours" />

<div class="mx-auto flex w-full max-w-3xl flex-col gap-5 p-4 sm:p-6">
    <PageHeader
        eyebrow="Parcours"
        title="Mes parcours"
        subtitle={promotion?.name}
    />

    {#if promotions.length > 1}
        <nav
            class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1"
            aria-label="Choisir une promotion"
        >
            {#each promotions as option (option.id)}
                <Link
                    href={toUrl(index({ query: { promotion: option.id } }))}
                    aria-current={option.id === promotion?.id
                        ? 'page'
                        : undefined}
                    class={cn(
                        'shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition-colors',
                        option.id === promotion?.id
                            ? 'bg-foreground text-background'
                            : 'bg-muted text-muted-foreground hover:text-foreground',
                    )}
                >
                    {option.name}
                </Link>
            {/each}
        </nav>
    {/if}

    {#if !promotion}
        <EmptyState
            icon={Route}
            title="Pas encore de promotion"
            description="Tu n'es inscrit(e) dans aucune promotion pour l'instant."
        />
    {:else if learningPaths.length === 0}
        <EmptyState
            icon={Route}
            title="Rien à lire pour l'instant"
            description="Les ateliers apparaîtront ici dès que ton formateur les ouvrira."
        />
    {:else}
        {#each learningPaths as path (path.id)}
            <section class="flex flex-col gap-3">
                <div>
                    <h2 class="font-display text-xl font-bold">{path.name}</h2>
                    <p class="eyebrow">
                        {done(path.sequences)} / {path.sequences.length} terminée{done(
                            path.sequences,
                        ) > 1
                            ? 's'
                            : ''}
                    </p>
                    {#if path.description}
                        <p class="text-sm text-muted-foreground">
                            {path.description}
                        </p>
                    {/if}
                </div>

                <ol class="flex flex-col gap-2">
                    {#each path.sequences as sequence, i (sequence.id)}
                        <li>
                            <Link
                                href={toUrl(
                                    show([promotion.id, path.id, sequence.id]),
                                )}
                                class="flex min-h-16 items-center gap-3 surface p-3 transition-colors hover:bg-muted/50"
                            >
                                {#if sequence.completed_at}
                                    <span
                                        class="grid size-9 shrink-0 place-items-center rounded-md bg-success text-success-foreground"
                                        title="Terminée"
                                    >
                                        <Check class="size-5" />
                                        <span class="sr-only">Terminée :</span>
                                    </span>
                                {:else}
                                    <span
                                        class="grid size-9 shrink-0 place-items-center rounded-md bg-primary-soft figures font-semibold text-primary"
                                        >{i + 1}</span
                                    >
                                {/if}
                                <span class="min-w-0 flex-1">
                                    <span
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <span class="font-medium"
                                            >{sequence.title}</span
                                        >
                                        {#if sequence.is_new}
                                            <span
                                                class="rounded-sm bg-cta px-1.5 py-0.5 font-mono text-[0.6875rem] font-semibold text-cta-foreground uppercase"
                                                >Nouveau</span
                                            >
                                        {/if}
                                    </span>
                                    {#if sequence.summary}
                                        <span
                                            class="block text-sm text-muted-foreground"
                                            >{sequence.summary}</span
                                        >
                                    {/if}
                                    <span class="block text-xs text-muted-foreground"
                                        >Ouverte le {formatDate(
                                            sequence.released_at,
                                        )}</span
                                    >
                                </span>
                                <ChevronRight
                                    class="size-5 shrink-0 text-muted-foreground"
                                />
                            </Link>
                        </li>
                    {/each}
                </ol>
            </section>
        {/each}
    {/if}
</div>

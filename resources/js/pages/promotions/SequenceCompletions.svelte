<script module lang="ts">
    import { index } from '@/routes/promotions';

    export const layout = {
        breadcrumbs: [{ title: 'Promotions', href: index() }],
    };
</script>

<script lang="ts">
    import Check from '@lucide/svelte/icons/check';
    import ListChecks from '@lucide/svelte/icons/list-checks';
    import AppHead from '@/components/AppHead.svelte';
    import EmptyState from '@/components/design/EmptyState.svelte';
    import PageHeader from '@/components/design/PageHeader.svelte';
    import { formatDate } from '@/lib/dates';
    import { cn } from '@/lib/utils';

    type Sequence = { id: number; title: string; released_at: string };
    type Learner = { id: number; name: string; activated: boolean };
    type Completion = {
        learner_id: number;
        sequence_id: number;
        completed_at: string;
    };

    let {
        promotion,
        learningPaths,
        learners,
        completions,
    }: {
        promotion: { id: number; name: string };
        learningPaths: { id: number; name: string; sequences: Sequence[] }[];
        learners: Learner[];
        completions: Completion[];
    } = $props();

    let view: 'sequence' | 'learner' = $state('sequence');

    // "learnerId:sequenceId" → completion date.
    const completedAt = $derived(
        new Map(
            completions.map((completion) => [
                `${completion.learner_id}:${completion.sequence_id}`,
                completion.completed_at,
            ]),
        ),
    );

    // A sequence in two paths counts once per learner.
    const sequences = $derived(
        [
            ...new Map(
                learningPaths
                    .flatMap((path) => path.sequences)
                    .map((sequence) => [sequence.id, sequence]),
            ).values(),
        ],
    );

    const dateOf = (learner: Learner, sequence: Sequence) =>
        completedAt.get(`${learner.id}:${sequence.id}`);

    const doneBy = (sequence: Sequence) =>
        learners.filter((learner) => dateOf(learner, sequence));

    const doneCount = (learner: Learner) =>
        sequences.filter((sequence) => dateOf(learner, sequence)).length;

    const percent = (count: number, total: number) =>
        total === 0 ? 0 : Math.round((count / total) * 100);
</script>

<AppHead title={`Séquences réalisées · ${promotion.name}`} />

<div class="mx-auto flex w-full max-w-3xl flex-col gap-5 p-4 sm:p-6">
    <PageHeader
        eyebrow="Promotion"
        title="Séquences réalisées"
        subtitle={`${promotion.name} · validées par les apprenants`}
    />

    {#if sequences.length === 0 || learners.length === 0}
        <EmptyState
            icon={ListChecks}
            title="Rien à suivre pour l'instant"
            description={learners.length === 0
                ? 'Cette promotion n’a pas encore d’apprenant.'
                : 'Ouvre des séquences à la promotion : les apprenants les valideront en les terminant.'}
        />
    {:else}
        <div class="flex gap-2" role="tablist" aria-label="Affichage">
            {#each [['sequence', 'Par séquence'], ['learner', 'Par apprenant']] as const as [key, label] (key)}
                <button
                    type="button"
                    role="tab"
                    aria-selected={view === key}
                    class={cn(
                        'rounded-full px-4 py-2 text-sm font-semibold transition-colors',
                        view === key
                            ? 'bg-foreground text-background'
                            : 'bg-muted text-muted-foreground hover:text-foreground',
                    )}
                    onclick={() => (view = key)}
                >
                    {label}
                </button>
            {/each}
        </div>

        {#if view === 'sequence'}
            {#each learningPaths as path (path.id)}
                <section class="flex flex-col gap-2">
                    <h2 class="font-display text-xl font-bold">{path.name}</h2>
                    <ol class="flex flex-col gap-2">
                        {#each path.sequences as sequence (sequence.id)}
                            {@const done = doneBy(sequence)}
                            <li>
                                <details class="group surface">
                                    <summary
                                        class="flex min-h-16 cursor-pointer list-none flex-col gap-2 p-3"
                                    >
                                        <span
                                            class="flex items-baseline justify-between gap-3"
                                        >
                                            <span class="font-medium"
                                                >{sequence.title}</span
                                            >
                                            <span
                                                class="shrink-0 figures text-sm font-semibold"
                                                >{done.length} / {learners.length}</span
                                            >
                                        </span>
                                        <span
                                            class="block h-1.5 overflow-hidden rounded-full bg-muted"
                                        >
                                            <span
                                                class="block h-full rounded-full bg-primary"
                                                style:width={`${percent(done.length, learners.length)}%`}
                                            ></span>
                                        </span>
                                    </summary>
                                    <ul
                                        class="grid gap-1 border-t border-border p-3 text-sm"
                                    >
                                        {#each learners as learner (learner.id)}
                                            {@const date = dateOf(
                                                learner,
                                                sequence,
                                            )}
                                            <li
                                                class="flex items-center gap-2"
                                            >
                                                {#if date}
                                                    <Check
                                                        class="size-4 shrink-0 text-success-ink"
                                                    />
                                                    <span class="flex-1"
                                                        >{learner.name}</span
                                                    >
                                                    <span
                                                        class="text-xs text-muted-foreground"
                                                        >{formatDate(
                                                            date,
                                                        )}</span
                                                    >
                                                {:else}
                                                    <span
                                                        class="size-4 shrink-0"
                                                    ></span>
                                                    <span
                                                        class="flex-1 text-muted-foreground"
                                                        >{learner.name}</span
                                                    >
                                                {/if}
                                            </li>
                                        {/each}
                                    </ul>
                                </details>
                            </li>
                        {/each}
                    </ol>
                </section>
            {/each}
        {:else}
            <ol class="flex flex-col gap-2">
                {#each learners as learner (learner.id)}
                    {@const count = doneCount(learner)}
                    <li>
                        <details class="surface">
                            <summary
                                class="flex min-h-16 cursor-pointer list-none flex-col gap-2 p-3"
                            >
                                <span
                                    class="flex items-baseline justify-between gap-3"
                                >
                                    <span
                                        class={cn(
                                            'font-medium',
                                            !learner.activated &&
                                                'text-muted-foreground',
                                        )}
                                        >{learner.name}{learner.activated
                                            ? ''
                                            : ' (invité)'}</span
                                    >
                                    <span
                                        class="shrink-0 figures text-sm font-semibold"
                                        >{count} / {sequences.length}</span
                                    >
                                </span>
                                <span
                                    class="block h-1.5 overflow-hidden rounded-full bg-muted"
                                >
                                    <span
                                        class="block h-full rounded-full bg-primary"
                                        style:width={`${percent(count, sequences.length)}%`}
                                    ></span>
                                </span>
                            </summary>
                            <ul
                                class="grid gap-1 border-t border-border p-3 text-sm"
                            >
                                {#each sequences as sequence (sequence.id)}
                                    {@const date = dateOf(learner, sequence)}
                                    <li class="flex items-center gap-2">
                                        {#if date}
                                            <Check
                                                class="size-4 shrink-0 text-success-ink"
                                            />
                                            <span class="flex-1"
                                                >{sequence.title}</span
                                            >
                                            <span
                                                class="text-xs text-muted-foreground"
                                                >{formatDate(date)}</span
                                            >
                                        {:else}
                                            <span class="size-4 shrink-0"
                                            ></span>
                                            <span
                                                class="flex-1 text-muted-foreground"
                                                >{sequence.title}</span
                                            >
                                        {/if}
                                    </li>
                                {/each}
                            </ul>
                        </details>
                    </li>
                {/each}
            </ol>
        {/if}
    {/if}
</div>

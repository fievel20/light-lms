<script module lang="ts">
    import { index } from '@/routes/promotions';

    export const layout = {
        breadcrumbs: [{ title: 'Promotions', href: index() }],
    };
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import AppHead from '@/components/AppHead.svelte';
    import PageHeader from '@/components/design/PageHeader.svelte';
    import { formatDate } from '@/lib/dates';
    import type { AttemptLink } from '@/lib/quiz';
    import { cn, toUrl } from '@/lib/utils';
    import { index as quizzes } from '@/routes/promotions/quizzes';

    let {
        promotion,
        sequence,
        quiz,
        learners,
        questions,
    }: {
        promotion: { id: number; name: string };
        sequence: { id: number; title: string };
        quiz: { title: string };
        learners: { id: number; name: string; attempts: AttemptLink[] }[];
        questions: {
            id: number;
            question: string;
            correct: number;
            answered: number;
        }[];
    } = $props();

    let view: 'learners' | 'questions' = $state('learners');

    const percent = (part: number, whole: number) =>
        whole === 0 ? 0 : Math.round((part / whole) * 100);

    // Colour of a score: red below half, amber below 80 %, green above.
    const tone = (value: number) =>
        value < 50
            ? 'bg-destructive text-white'
            : value < 80
              ? 'bg-warning text-night'
              : 'bg-success text-success-foreground';
</script>

<AppHead title={`${quiz.title} · ${promotion.name}`} />

<div class="mx-auto flex w-full max-w-3xl flex-col gap-5 p-4 sm:p-6">
    <PageHeader
        eyebrow={sequence.title}
        title={quiz.title}
        subtitle={`${promotion.name} · toutes les tentatives`}
    />

    <div class="flex gap-2" role="tablist" aria-label="Affichage">
        {#each [['learners', 'Par apprenant'], ['questions', 'Par question']] as const as [key, label] (key)}
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

    {#if view === 'learners'}
        <ul class="divide-y divide-border/70">
            {#each learners as learner (learner.id)}
                <li class="flex flex-col gap-2 py-3">
                    <div class="flex items-baseline justify-between gap-3">
                        <span class="font-medium">{learner.name}</span>
                        <span class="text-xs text-muted-foreground">
                            {learner.attempts.length === 0
                                ? 'Pas encore fait'
                                : `${learner.attempts.length} tentative${learner.attempts.length > 1 ? 's' : ''}`}
                        </span>
                    </div>
                    {#if learner.attempts.length > 0}
                        <!-- The evolution, oldest first: each score opens its correction. -->
                        <ol class="flex flex-wrap items-center gap-1">
                            {#each learner.attempts as attempt, index (attempt.id)}
                                {#if index > 0}
                                    <ArrowRight
                                        class="size-3 text-muted-foreground"
                                    />
                                {/if}
                                <li>
                                    <Link
                                        href={attempt.url}
                                        title={`Tentative du ${formatDate(attempt.finished_at)}`}
                                        class={cn(
                                            'inline-flex h-8 items-center rounded-md px-2 figures text-sm font-semibold',
                                            tone(
                                                percent(
                                                    attempt.score,
                                                    attempt.total,
                                                ),
                                            ),
                                        )}
                                        >{attempt.score}/{attempt.total}</Link
                                    >
                                </li>
                            {/each}
                        </ol>
                    {/if}
                </li>
            {/each}
        </ul>
    {:else}
        <p class="text-sm text-muted-foreground">
            Réussite à chaque question, sur la dernière tentative de chaque
            apprenant.
        </p>
        <ol class="flex flex-col gap-2">
            {#each questions as question, index (question.id)}
                {@const rate = percent(question.correct, question.answered)}
                <li class="grid gap-2 surface p-3">
                    <div class="flex items-baseline justify-between gap-3">
                        <span class="min-w-0"
                            ><span class="eyebrow">Q{index + 1}</span>
                            {question.question}</span
                        >
                        <span class="shrink-0 figures text-sm font-semibold"
                            >{question.correct} / {question.answered}</span
                        >
                    </div>
                    <span class="block h-2 overflow-hidden rounded-full bg-muted">
                        <span
                            class={cn('block h-full rounded-full', tone(rate))}
                            style:width={`${question.answered === 0 ? 0 : rate}%`}
                        ></span>
                    </span>
                </li>
            {/each}
        </ol>
    {/if}

    <Link
        href={toUrl(quizzes(promotion.id))}
        class="mono-link text-muted-foreground">← Tous les quiz</Link
    >
</div>

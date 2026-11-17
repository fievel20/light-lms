<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import RotateCcw from '@lucide/svelte/icons/rotate-ccw';
    import AppHead from '@/components/AppHead.svelte';
    import QuizCorrection from '@/components/quiz/QuizCorrection.svelte';
    import { formatDate } from '@/lib/dates';
    import type { AttemptLink, CorrectedQuestion } from '@/lib/quiz';
    import { cn } from '@/lib/utils';

    let {
        quiz,
        sequence,
        attempt,
        attempts,
        playUrl,
        backUrl,
        learner = null,
    }: {
        quiz: { title: string };
        sequence: { id: number; title: string };
        attempt: {
            id: number;
            score: number;
            total: number;
            finished_at: string;
            questions: CorrectedQuestion[];
        };
        attempts: AttemptLink[];
        // Null when the promotion is archived.
        playUrl: string | null;
        backUrl: string;
        // Set when the trainer reads a learner's attempt.
        learner?: string | null;
    } = $props();

    const percent = (item: { score: number; total: number }) =>
        item.total === 0 ? 0 : Math.round((item.score / item.total) * 100);
</script>

<AppHead title={`Correction · ${quiz.title}`} />

<div class="mx-auto flex w-full max-w-3xl flex-col gap-5 p-4 sm:p-6">
    <header class="space-y-1">
        <p class="eyebrow">
            // {sequence.title}{learner ? ` · ${learner}` : ''}
        </p>
        <h1 class="display-title text-3xl">{quiz.title}</h1>
    </header>

    <div class="surface p-6 text-center">
        <p class="eyebrow">
            Tentative du {formatDate(attempt.finished_at)}
        </p>
        <p class="display-title text-5xl">
            {attempt.score} / {attempt.total}
        </p>
        <p class="text-muted-foreground" class:hidden={!!learner}>
            {percent(attempt) === 100
                ? 'Sans faute, bravo !'
                : percent(attempt) >= 50
                  ? 'Bien joué, regarde les questions manquées ci-dessous.'
                  : 'Relis la correction, puis retente ta chance.'}
        </p>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row">
        {#if playUrl}
            <Link
                href={playUrl}
                class="inline-flex h-12 items-center justify-center gap-2 rounded-md bg-cta px-5 font-mono text-sm font-semibold text-cta-foreground uppercase"
            >
                <RotateCcw class="size-4" />
                Recommencer
            </Link>
        {/if}
        <Link
            href={backUrl}
            class="inline-flex h-12 items-center justify-center rounded-md border border-input px-5 font-medium"
            >{learner ? 'Retour aux résultats' : 'Retour à la séquence'}</Link
        >
    </div>

    {#if attempts.length > 1}
        <section class="grid gap-2 surface p-4">
            <h2 class="font-medium">
                {learner ? 'Ses tentatives' : 'Mes tentatives'}
            </h2>
            <ol class="grid gap-1">
                {#each attempts as item, index (item.id)}
                    <li>
                        <Link
                            href={item.url}
                            class={cn(
                                'flex items-center gap-3 rounded-md px-2 py-1.5 text-sm hover:bg-muted',
                                item.id === attempt.id && 'bg-muted font-semibold',
                            )}
                        >
                            <span class="w-6 text-muted-foreground"
                                >{index + 1}.</span
                            >
                            <span class="w-24 text-muted-foreground"
                                >{formatDate(item.finished_at)}</span
                            >
                            <span class="h-2 flex-1 overflow-hidden rounded-full bg-muted">
                                <span
                                    class="block h-full rounded-full bg-primary"
                                    style:width={`${percent(item)}%`}
                                ></span>
                            </span>
                            <span class="w-12 text-right figures"
                                >{item.score} / {item.total}</span
                            >
                        </Link>
                    </li>
                {/each}
            </ol>
        </section>
    {/if}

    <h2 class="font-display text-xl font-bold">Correction</h2>
    <QuizCorrection questions={attempt.questions} />
</div>

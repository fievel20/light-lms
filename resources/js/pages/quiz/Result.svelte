<script lang="ts">
    import { Link } from '@inertiajs/svelte';
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
        backUrl,
        learner,
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
        backUrl: string;
        learner: string;
    } = $props();

    const percent = (item: { score: number; total: number }) =>
        item.total === 0 ? 0 : Math.round((item.score / item.total) * 100);
</script>

<AppHead title={`Correction · ${quiz.title}`} />

<div class="mx-auto flex w-full max-w-3xl flex-col gap-5 p-4 sm:p-6">
    <header class="space-y-1">
        <p class="eyebrow">
            // {sequence.title} · {learner}
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
    </div>

    <Link
        href={backUrl}
        class="inline-flex h-12 items-center justify-center self-start rounded-md border border-input px-5 font-medium"
        >Retour aux résultats</Link
    >

    {#if attempts.length > 1}
        <section class="grid gap-2 surface p-4">
            <h2 class="font-medium">
                Ses tentatives
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

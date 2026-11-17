<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import CirclePlay from '@lucide/svelte/icons/circle-play';
    import { formatDate } from '@/lib/dates';
    import type { QuizSummary } from '@/lib/quiz';


    let {
        quiz,
        label = 'Lancer le quiz',
        closed = false,
    }: { quiz: QuizSummary; label?: string; closed?: boolean } = $props();

    const last = $derived(quiz.attempts?.at(-1));
</script>

<!-- Placed where the sequence says [Quizz]; at its end otherwise. -->
<div
    class="not-prose my-4 flex flex-col gap-3 rounded-xl border border-primary/30 bg-primary-soft p-4 sm:flex-row sm:items-center"
>
    <div class="min-w-0 flex-1">
        <p class="font-display text-lg font-bold">{quiz.title}</p>
        <p class="text-sm text-muted-foreground">
            {quiz.questions_count} question{quiz.questions_count > 1 ? 's' : ''}
            {#if quiz.seconds_per_question}
                · {quiz.seconds_per_question} s par question
            {/if}
        </p>
        {#if last}
            <p class="text-sm">
                Dernier score : <span class="font-semibold"
                    >{last.score} / {last.total}</span
                >
                · {quiz.attempts?.length} tentative{(quiz.attempts?.length ?? 0) >
                1
                    ? 's'
                    : ''} ·
                <Link
                    href={last.url}
                    class="font-semibold text-primary underline underline-offset-4"
                    >voir la correction</Link
                >
            </p>
        {/if}
    </div>
    {#if closed}
        <p class="text-sm text-muted-foreground">
            Promotion archivée : quiz fermé.
        </p>
    {:else}
        <Link
            href={quiz.play_url}
            class="inline-flex h-12 items-center justify-center gap-2 rounded-md bg-cta px-5 font-mono text-sm font-semibold text-cta-foreground uppercase no-underline shadow-sm transition hover:brightness-95"
        >
            <CirclePlay class="size-5" />
            {last ? 'Recommencer' : label}
        </Link>
    {/if}
</div>

{#if last}
    <span class="sr-only">Dernière tentative le {formatDate(last.finished_at)}</span>
{/if}

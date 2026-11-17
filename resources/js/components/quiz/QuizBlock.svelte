<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import CirclePlay from '@lucide/svelte/icons/circle-play';
    import RotateCcw from '@lucide/svelte/icons/rotate-ccw';
    import X from '@lucide/svelte/icons/x';
    import { onDestroy, tick } from 'svelte';
    import QuizCorrection from '@/components/quiz/QuizCorrection.svelte';
    import { Spinner } from '@/components/ui/spinner';
    import { formatDate } from '@/lib/dates';
    import { letterTiles } from '@/lib/quiz';
    import type { CorrectedQuestion, Letter, QuizSummary } from '@/lib/quiz';
    import { cn } from '@/lib/utils';

    let {
        quiz,
        test = false,
        closed = false,
    }: {
        quiz: QuizSummary;
        // The trainer's test: scored here, nothing saved.
        test?: boolean;
        closed?: boolean;
    } = $props();

    // Played where the sequence says [Quizz], the text around stays in view.
    type Step = 'idle' | 'question' | 'sending' | 'done';

    let step: Step = $state('idle');
    let current = $state(0);
    let answers: Record<number, Letter | null> = $state({});
    let chosen: Letter | null = $state(null);
    let remaining = $state(0);
    let hiddenCorrection: number | null = $state(null);
    let timer: ReturnType<typeof setInterval> | undefined;
    let box: HTMLElement | undefined = $state();

    const question = $derived(quiz.questions[current]);
    const total = $derived(quiz.questions.length);
    const limit = $derived(quiz.seconds_per_question);
    const last = $derived(quiz.attempts?.at(-1));
    const submitError = $derived(page.props.errors?.answers);
    const correction = $derived(
        quiz.correction && quiz.correction.id !== hiddenCorrection
            ? quiz.correction
            : null,
    );

    // Ring of the countdown: full at the start, empty at zero.
    const RADIUS = 26;
    const CIRCUMFERENCE = 2 * Math.PI * RADIUS;
    const ringOffset = $derived(
        limit ? CIRCUMFERENCE * (1 - remaining / limit) : 0,
    );

    // The trainer's test is scored here, with the answers it received.
    const corrected: CorrectedQuestion[] = $derived(
        quiz.questions.map((item) => ({
            ...item,
            correct: item.correct ?? 'a',
            answer: answers[item.id] ?? null,
        })),
    );
    const score = $derived(
        corrected.filter((item) => item.answer === item.correct).length,
    );

    const percent = (item: { score: number; total: number }) =>
        item.total === 0 ? 0 : Math.round((item.score / item.total) * 100);

    async function start(): Promise<void> {
        answers = {};
        current = 0;
        step = 'question';
        startTimer();

        await tick();
        box?.scrollIntoView({ block: 'start', behavior: 'smooth' });
    }

    function startTimer(): void {
        stopTimer();
        chosen = null;

        if (!limit) {
            return;
        }

        remaining = limit;
        timer = setInterval(() => {
            remaining -= 1;

            if (remaining <= 0) {
                // Time is up: no answer for this one.
                answer(null);
            }
        }, 1000);
    }

    function stopTimer(): void {
        clearInterval(timer);
        timer = undefined;
    }

    function answer(letter: Letter | null): void {
        if (chosen !== null || step !== 'question') {
            return;
        }

        stopTimer();
        chosen = letter;
        answers[question.id] = letter;

        // A short pause shows the choice before the next question.
        setTimeout(
            () => {
                if (current + 1 < total) {
                    current += 1;
                    startTimer();
                } else {
                    finish();
                }
            },
            letter === null ? 600 : 350,
        );
    }

    function finish(): void {
        if (test || !quiz.submit_url) {
            step = 'done';

            return;
        }

        step = 'sending';
        // Back to this page, with the correction of the new attempt.
        router.post(
            quiz.submit_url,
            { answers },
            {
                preserveScroll: true,
                preserveState: 'errors',
                onSuccess: () => {
                    step = 'idle';
                    hiddenCorrection = null;
                },
                onError: () => (step = 'done'),
            },
        );
    }

    function quit(): void {
        if (
            step === 'question' &&
            !confirm('Quitter le quiz ? Tes réponses ne seront pas gardées.')
        ) {
            return;
        }

        stopTimer();
        step = 'idle';
    }

    onDestroy(stopTimer);
</script>

{#snippet replay(label: string)}
    <button
        type="button"
        class="inline-flex h-12 items-center justify-center gap-2 rounded-md bg-cta px-5 font-mono text-sm font-semibold text-cta-foreground uppercase shadow-sm transition hover:brightness-95"
        onclick={start}
    >
        {#if label === 'Recommencer'}
            <RotateCcw class="size-4" />
        {:else}
            <CirclePlay class="size-5" />
        {/if}
        {label}
    </button>
{/snippet}

<section
    bind:this={box}
    class="my-6 flex scroll-mt-4 flex-col gap-4 rounded-xl border border-primary/30 bg-primary-soft p-4 sm:p-5"
    aria-label={`Quiz : ${quiz.title}`}
>
    {#if step === 'idle' && correction}
        <!-- The correction of an attempt, in the sequence. -->
        <div class="flex flex-wrap items-center gap-4">
            <div class="min-w-0 flex-1">
                <p class="eyebrow">
                    {quiz.title} · tentative du {formatDate(
                        correction.finished_at,
                    )}
                </p>
                <p class="display-title text-4xl">
                    {correction.score} / {correction.total}
                </p>
                <p class="text-sm text-muted-foreground">
                    {percent(correction) === 100
                        ? 'Sans faute, bravo !'
                        : percent(correction) >= 50
                          ? 'Bien joué, regarde les questions manquées ci-dessous.'
                          : 'Relis la séquence et la correction, puis retente ta chance.'}
                </p>
            </div>
            <div class="flex w-full flex-col gap-2 sm:w-auto">
                {#if !closed && quiz.submit_url}
                    {@render replay('Recommencer')}
                {/if}
                <button
                    type="button"
                    class="h-11 rounded-md border border-input bg-background px-4 text-sm font-medium"
                    onclick={() => (hiddenCorrection = correction.id)}
                    >Masquer la correction</button
                >
            </div>
        </div>

        {#if (quiz.attempts?.length ?? 0) > 1}
            <ol class="grid gap-1" aria-label="Mes tentatives">
                {#each quiz.attempts ?? [] as item, index (item.id)}
                    <li>
                        <Link
                            href={item.url}
                            preserveScroll
                            class={cn(
                                'flex items-center gap-3 rounded-md px-2 py-1.5 text-sm hover:bg-background/60',
                                item.id === correction.id &&
                                    'bg-background font-semibold',
                            )}
                        >
                            <span class="w-6 text-muted-foreground"
                                >{index + 1}.</span
                            >
                            <span class="w-24 text-muted-foreground"
                                >{formatDate(item.finished_at)}</span
                            >
                            <span
                                class="h-2 flex-1 overflow-hidden rounded-full bg-muted"
                            >
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
        {/if}

        <QuizCorrection questions={correction.questions} />
    {:else if step === 'idle'}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="min-w-0 flex-1">
                <p class="font-display text-lg font-bold">{quiz.title}</p>
                <p class="text-sm text-muted-foreground">
                    {total} question{total > 1 ? 's' : ''}, une à la fois
                    {#if limit}
                        · {limit} s par question
                    {/if}
                    {#if test}
                        · test : rien n'est enregistré
                    {/if}
                </p>
                {#if last}
                    <p class="text-sm">
                        Dernier score : <span class="font-semibold"
                            >{last.score} / {last.total}</span
                        >
                        · {quiz.attempts?.length} tentative{(quiz.attempts
                            ?.length ?? 0) > 1
                            ? 's'
                            : ''} ·
                        <Link
                            href={last.url}
                            preserveScroll
                            class="font-semibold text-primary underline underline-offset-4"
                            >voir la correction</Link
                        >
                    </p>
                {/if}
            </div>
            {#if closed || (!test && !quiz.submit_url)}
                <p class="text-sm text-muted-foreground">
                    Promotion archivée : quiz fermé.
                </p>
            {:else}
                {@render replay(
                    last ? 'Recommencer' : test ? 'Tester le quiz' : 'Lancer le quiz',
                )}
            {/if}
        </div>
    {:else if step === 'question' && question}
        <div class="flex items-center gap-3" aria-live="polite">
            <button
                type="button"
                class="grid size-10 shrink-0 place-items-center rounded-full text-muted-foreground hover:bg-background"
                aria-label="Quitter le quiz"
                onclick={quit}
            >
                <X class="size-5" />
            </button>
            <p class="min-w-0 flex-1">
                <span class="block truncate eyebrow">{quiz.title}</span>
                <span class="block font-display font-bold"
                    >Question {current + 1} / {total}</span
                >
            </p>
            {#if limit}
                <!-- Countdown above the question. -->
                <div
                    class="relative grid size-14 shrink-0 place-items-center"
                    role="timer"
                    aria-label={`${remaining} secondes restantes`}
                >
                    <svg viewBox="0 0 60 60" class="absolute inset-0 -rotate-90">
                        <circle
                            cx="30"
                            cy="30"
                            r={RADIUS}
                            fill="none"
                            stroke-width="6"
                            class="stroke-muted"
                        />
                        <circle
                            cx="30"
                            cy="30"
                            r={RADIUS}
                            fill="none"
                            stroke-width="6"
                            stroke-linecap="round"
                            stroke-dasharray={CIRCUMFERENCE}
                            stroke-dashoffset={ringOffset}
                            class={cn(
                                'transition-[stroke-dashoffset] duration-1000 ease-linear',
                                remaining <= 5
                                    ? 'stroke-destructive'
                                    : 'stroke-primary',
                            )}
                        />
                    </svg>
                    <span class="relative figures font-bold">{remaining}</span>
                </div>
            {/if}
        </div>

        <div class="h-2 overflow-hidden rounded-full bg-background">
            <div
                class="h-full rounded-full bg-primary transition-[width]"
                style:width={`${(current / total) * 100}%`}
            ></div>
        </div>

        <div class="rounded-lg bg-card p-4 sm:p-5">
            <p class="font-display text-lg font-bold sm:text-xl">
                {question.question}
            </p>
            {#if question.image_url}
                <img
                    src={question.image_url}
                    alt=""
                    class="mt-3 max-h-64 rounded-md border border-border"
                />
            {/if}
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            {#each Object.entries(question.choices) as [letter, text] (`${question.id}-${letter}`)}
                <button
                    type="button"
                    disabled={chosen !== null}
                    class={cn(
                        'flex min-h-16 items-center gap-3 rounded-xl p-3 text-left font-semibold shadow-[0_4px_0_rgb(0_0_0/0.25)] transition active:translate-y-0.5 active:shadow-none sm:text-lg',
                        letterTiles[letter as Letter],
                        chosen !== null && chosen !== letter && 'opacity-40',
                        chosen === letter && 'ring-4 ring-ring',
                    )}
                    onclick={() => answer(letter as Letter)}
                >
                    <span
                        class="grid size-10 shrink-0 place-items-center rounded-lg bg-white/25 font-display text-xl font-bold uppercase"
                        >{letter}</span
                    >
                    <span>{text}</span>
                </button>
            {/each}
        </div>
    {:else if step === 'sending'}
        <p class="flex items-center justify-center gap-3 py-6">
            <Spinner /> Correction en cours…
        </p>
    {:else}
        {#if submitError && !test}
            <p
                class="rounded-md border border-destructive/40 bg-background p-3 text-destructive"
            >
                {submitError}
            </p>
        {:else}
            <div class="text-center">
                <p class="eyebrow">{test ? 'Test · ' : ''}Score</p>
                <p class="display-title text-4xl">{score} / {total}</p>
            </div>
            <QuizCorrection questions={corrected} />
        {/if}
        <div class="flex flex-col gap-2 sm:flex-row">
            {@render replay('Recommencer')}
            <button
                type="button"
                class="h-12 rounded-md border border-input bg-background px-5 text-sm font-medium"
                onclick={() => (step = 'idle')}>Fermer</button
            >
        </div>
    {/if}
</section>

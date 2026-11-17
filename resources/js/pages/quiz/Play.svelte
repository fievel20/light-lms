<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import CirclePlay from '@lucide/svelte/icons/circle-play';
    import RotateCcw from '@lucide/svelte/icons/rotate-ccw';
    import X from '@lucide/svelte/icons/x';
    import { onDestroy } from 'svelte';
    import AppHead from '@/components/AppHead.svelte';
    import QuizCorrection from '@/components/quiz/QuizCorrection.svelte';
    import { Spinner } from '@/components/ui/spinner';
    import { letterTiles } from '@/lib/quiz';
    import type { CorrectedQuestion, Letter, PlayQuestion } from '@/lib/quiz';
    import { cn } from '@/lib/utils';

    let {
        mode,
        quiz,
        sequence,
        backUrl,
        submitUrl,
        closed,
    }: {
        // "test": the trainer tries the quiz, scored here, nothing saved.
        mode: 'learner' | 'test';
        quiz: {
            title: string;
            seconds_per_question: number | null;
            questions: PlayQuestion[];
        };
        sequence: { id: number; title: string };
        backUrl: string;
        submitUrl: string | null;
        closed: boolean;
    } = $props();

    type Step = 'intro' | 'question' | 'sending' | 'done';

    let step: Step = $state('intro');
    let current = $state(0);
    let answers: Record<number, Letter | null> = $state({});
    let chosen: Letter | null = $state(null);
    let remaining = $state(0);
    let timer: ReturnType<typeof setInterval> | undefined;

    const question = $derived(quiz.questions[current]);
    const total = $derived(quiz.questions.length);
    const limit = $derived(quiz.seconds_per_question);
    const submitError = $derived(page.props.errors?.answers);

    // Ring of the countdown: full at the start, empty at zero.
    const RADIUS = 26;
    const CIRCUMFERENCE = 2 * Math.PI * RADIUS;
    const ringOffset = $derived(
        limit ? CIRCUMFERENCE * (1 - remaining / limit) : 0,
    );

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

    function start(): void {
        answers = {};
        current = 0;
        step = 'question';
        startTimer();
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
        if (mode === 'test' || !submitUrl) {
            step = 'done';

            return;
        }

        step = 'sending';
        router.post(
            submitUrl,
            { answers },
            {
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
        router.visit(backUrl);
    }

    onDestroy(stopTimer);
</script>

<AppHead title={quiz.title} />

<div class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-5 p-4 sm:p-6">
    <header class="flex items-center gap-3">
        <button
            type="button"
            class="grid size-11 shrink-0 place-items-center rounded-full text-muted-foreground hover:bg-muted"
            aria-label="Quitter le quiz"
            onclick={quit}
        >
            <X class="size-5" />
        </button>
        <p class="min-w-0 flex-1 truncate eyebrow">
            {mode === 'test' ? 'Test formateur · ' : ''}{sequence.title}
        </p>
    </header>

    {#if step === 'intro'}
        <section
            class="flex flex-1 flex-col items-center justify-center gap-5 text-center"
        >
            <h1 class="display-title text-3xl sm:text-4xl">{quiz.title}</h1>
            <p class="text-muted-foreground">
                {total} question{total > 1 ? 's' : ''}, une à la fois{limit
                    ? `, ${limit} secondes pour chacune`
                    : ''}. Touche la bonne réponse.
            </p>
            {#if mode === 'test'}
                <p class="text-sm text-muted-foreground">
                    Test : tes réponses ne sont pas enregistrées.
                </p>
            {/if}
            {#if closed}
                <p class="text-sm text-muted-foreground">
                    Cette promotion est archivée : le quiz est fermé.
                </p>
            {:else}
                <button
                    type="button"
                    class="inline-flex h-14 items-center gap-2 rounded-xl bg-cta px-8 font-mono text-base font-semibold text-cta-foreground uppercase shadow-sm transition hover:brightness-95"
                    onclick={start}
                >
                    <CirclePlay class="size-6" />
                    Commencer
                </button>
            {/if}
        </section>
    {:else if step === 'question' && question}
        <section class="flex flex-col gap-4" aria-live="polite">
            <div class="flex items-center justify-between gap-4">
                <p class="font-display text-lg font-bold text-muted-foreground">
                    Question {current + 1} / {total}
                </p>
                {#if limit}
                    <!-- Countdown above the question. -->
                    <div
                        class="relative grid size-16 shrink-0 place-items-center"
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
                        <span class="relative figures text-lg font-bold"
                            >{remaining}</span
                        >
                    </div>
                {/if}
            </div>

            <div class="h-2 overflow-hidden rounded-full bg-muted">
                <div
                    class="h-full rounded-full bg-primary transition-[width]"
                    style:width={`${(current / total) * 100}%`}
                ></div>
            </div>

            <div class="surface p-5 sm:p-6">
                <h1 class="font-display text-xl font-bold sm:text-2xl">
                    {question.question}
                </h1>
                {#if question.image_url}
                    <img
                        src={question.image_url}
                        alt=""
                        class="mt-4 max-h-64 rounded-md border border-border"
                    />
                {/if}
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                {#each Object.entries(question.choices) as [letter, text] (`${question.id}-${letter}`)}
                    <button
                        type="button"
                        disabled={chosen !== null}
                        class={cn(
                            'flex min-h-20 items-center gap-4 rounded-xl p-4 text-left text-lg font-semibold shadow-[0_4px_0_rgb(0_0_0/0.25)] transition active:translate-y-0.5 active:shadow-none',
                            letterTiles[letter as Letter],
                            chosen !== null &&
                                chosen !== letter &&
                                'opacity-40',
                            chosen === letter && 'ring-4 ring-ring',
                        )}
                        onclick={() => answer(letter as Letter)}
                    >
                        <span
                            class="grid size-12 shrink-0 place-items-center rounded-lg bg-white/25 font-display text-2xl font-bold uppercase"
                            >{letter}</span
                        >
                        <span>{text}</span>
                    </button>
                {/each}
            </div>
        </section>
    {:else if step === 'sending'}
        <section class="flex flex-1 items-center justify-center gap-3">
            <Spinner /> Correction en cours…
        </section>
    {:else}
        <section class="flex flex-col gap-5">
            {#if submitError}
                <p class="rounded-md border border-destructive/40 p-3 text-destructive">
                    {submitError}
                </p>
            {:else}
                <div class="surface p-6 text-center">
                    <p class="eyebrow">Ton score</p>
                    <p class="display-title text-5xl">{score} / {total}</p>
                </div>
                <QuizCorrection questions={corrected} />
            {/if}
            <div class="flex flex-col gap-3 sm:flex-row">
                <button
                    type="button"
                    class="inline-flex h-12 items-center justify-center gap-2 rounded-md bg-cta px-5 font-mono text-sm font-semibold text-cta-foreground uppercase"
                    onclick={start}
                >
                    <RotateCcw class="size-4" />
                    Recommencer
                </button>
                <Link
                    href={backUrl}
                    class="inline-flex h-12 items-center justify-center rounded-md border border-input px-5 font-medium"
                    >Retour à la séquence</Link
                >
            </div>
        </section>
    {/if}
</div>

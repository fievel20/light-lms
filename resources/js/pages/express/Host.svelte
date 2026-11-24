<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowLeft from '@lucide/svelte/icons/arrow-left';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import Check from '@lucide/svelte/icons/check';
    import Maximize from '@lucide/svelte/icons/maximize';
    import Play from '@lucide/svelte/icons/play';
    import Trophy from '@lucide/svelte/icons/trophy';
    import X from '@lucide/svelte/icons/x';
    import { toast } from 'svelte-sonner';
    import AppHead from '@/components/AppHead.svelte';
    import CountdownRing from '@/components/express/CountdownRing.svelte';
    import { Spinner } from '@/components/ui/spinner';
    import { Countdown, expressTiles, poll, send } from '@/lib/express';
    import type { HostState } from '@/lib/express';
    import type { Letter } from '@/lib/quiz';
    import { cn, toUrl } from '@/lib/utils';
    import { edit, index } from '@/routes/express-quizzes';
    import {
        close as closeRoute,
        correct as correctRoute,
        next as nextRoute,
        state as stateRoute,
    } from '@/routes/express-sessions';
    import { destroy as removeRoute } from '@/routes/express-sessions/participants';

    let {
        state: initial,
        joinUrl,
        shortUrl,
        qrCode,
        quizId,
        sessionId,
    }: {
        state: HostState;
        joinUrl: string;
        shortUrl: string;
        qrCode: string;
        quizId: number;
        sessionId: number;
    } = $props();

    // svelte-ignore state_referenced_locally
    let game: HostState = $state(initial);
    let busy = $state(false);
    let now = $state(Date.now());
    // Correction: the trainer moves from question to question on this screen.
    let shown = $state(0);
    const countdown = new Countdown();

    const left = $derived(
        game.status === 'question' ? countdown.secondsLeft(now) : 0,
    );
    const lastQuestion = $derived(game.position === game.total);
    const correction = $derived(game.correction?.[shown] ?? null);
    const podium = $derived(game.ranking?.slice(0, 3) ?? []);

    function update(next: HostState): void {
        countdown.sync(
            next.status === 'question' ? `q${next.position}` : null,
            next.remaining_ms,
        );
        game = next;
    }

    // svelte-ignore state_referenced_locally
    update(initial);

    $effect(() => {
        const stop = poll<HostState>(stateRoute(sessionId).url, update);
        const clock = setInterval(() => (now = Date.now()), 250);

        return () => {
            stop();
            clearInterval(clock);
        };
    });

    async function act(
        url: string,
        method: 'POST' | 'DELETE' = 'POST',
    ): Promise<void> {
        busy = true;

        try {
            update(await send<HostState>(url, method));
        } catch (error) {
            toast.error(error instanceof Error ? error.message : 'Erreur.');
        } finally {
            busy = false;
        }
    }

    function finish(): void {
        if (confirm('Terminer la session ? Les téléphones afficheront « Session terminée ».')) {
            void act(closeRoute(sessionId).url);
        }
    }

    function fullscreen(): void {
        if (document.fullscreenElement) {
            void document.exitFullscreen();
        } else {
            void document.documentElement.requestFullscreen();
        }
    }

    const plural = (count: number, word: string) =>
        `${count} ${word}${count > 1 ? 's' : ''}`;
</script>

<AppHead title={`${game.title ?? 'Quiz express'} · ${game.code}`} />

{#snippet primary(label: string, onclick: () => void)}
    <button
        type="button"
        disabled={busy}
        class="inline-flex h-14 items-center justify-center gap-2 rounded-xl bg-cta px-8 font-mono text-lg font-semibold text-cta-foreground uppercase shadow-sm transition hover:brightness-95 disabled:opacity-60"
        {onclick}
    >
        {#if busy}<Spinner />{/if}
        {label}
    </button>
{/snippet}

{#snippet joinBox(large: boolean)}
    <!-- Always in sight until the ranking, for those who arrive late. -->
    <div
        class={cn(
            'flex flex-col items-center gap-3 rounded-2xl bg-white text-[#0e0926]',
            large ? 'p-6' : 'p-3',
        )}
    >
        <div
            class={cn('[&_svg]:h-full [&_svg]:w-full', large ? 'size-72' : 'size-36')}
            aria-label={`QR code vers ${joinUrl}`}
            role="img"
        >
            {@html qrCode}
        </div>
        <p class={cn('font-mono font-bold tracking-[0.25em]', large ? 'text-5xl' : 'text-2xl')}>
            {game.code}
        </p>
        <p class={cn('text-center', large ? 'text-base' : 'text-xs')}>
            ou sur <span class="font-semibold">{shortUrl.replace(/^https?:\/\//, '')}</span>
        </p>
    </div>
{/snippet}

<div class="flex min-h-svh flex-col gap-6 p-6 lg:p-10">
    <header class="flex items-center gap-4">
        <Link
            href={toUrl(index())}
            class="grid size-11 place-items-center rounded-full text-muted-foreground hover:bg-white/10"
            aria-label="Retour aux quiz express (la session continue)"
        >
            <ArrowLeft class="size-5" />
        </Link>
        <p class="min-w-0 flex-1 truncate eyebrow text-primary">
            // {game.title ?? 'Quiz express'}
        </p>
        <button
            type="button"
            class="grid size-11 place-items-center rounded-full text-muted-foreground hover:bg-white/10"
            aria-label="Plein écran"
            onclick={fullscreen}
        >
            <Maximize class="size-5" />
        </button>
    </header>

    {#if game.status === 'lobby'}
        <main class="grid flex-1 items-center gap-10 lg:grid-cols-[auto_1fr]">
            {@render joinBox(true)}
            <section class="flex flex-col gap-6">
                <h1 class="display-title text-4xl lg:text-6xl">
                    Scanne le QR code pour jouer
                </h1>
                <p class="text-2xl font-semibold">
                    {plural(game.participants.length, 'participant')}
                </p>
                <ul class="flex flex-wrap gap-2">
                    {#each game.participants as participant (participant.id)}
                        <li
                            class="group inline-flex items-center gap-1 rounded-full bg-white/10 py-1 pr-1 pl-4 text-lg font-semibold"
                        >
                            {participant.name}
                            <button
                                type="button"
                                class="grid size-8 place-items-center rounded-full text-muted-foreground hover:bg-destructive hover:text-white"
                                aria-label={`Retirer ${participant.name}`}
                                onclick={() =>
                                    void act(
                                        removeRoute([sessionId, participant.id]).url,
                                        'DELETE',
                                    )}
                            >
                                <X class="size-4" />
                            </button>
                        </li>
                    {/each}
                </ul>
                <div>
                    {@render primary('Démarrer', () => void act(nextRoute(sessionId).url))}
                </div>
            </section>
        </main>
    {:else if game.status === 'question' && game.question}
        <main class="grid flex-1 gap-8 lg:grid-cols-[1fr_auto]">
            <section class="flex flex-col gap-6">
                <div class="flex items-center justify-between gap-6">
                    <p class="font-display text-3xl font-bold">
                        Question {game.position} / {game.total}
                    </p>
                    <CountdownRing {left} total={game.seconds} class="size-24 text-3xl" />
                </div>
                <h1 class="font-display text-3xl font-bold lg:text-5xl">
                    {game.question.question}
                </h1>
                {#if game.question.image_url}
                    <img
                        src={game.question.image_url}
                        alt=""
                        class="max-h-[35vh] self-start rounded-xl bg-white"
                    />
                {/if}
                <div class="grid gap-4 lg:grid-cols-2">
                    {#each Object.entries(game.question.choices) as [letter, text] (letter)}
                        <div
                            class={cn(
                                'flex min-h-24 items-center gap-4 rounded-2xl p-4 text-2xl font-semibold',
                                expressTiles[letter as Letter],
                            )}
                        >
                            <span
                                class="grid size-14 shrink-0 place-items-center rounded-xl bg-white/25 font-display text-3xl font-bold uppercase"
                                >{letter}</span
                            >
                            {text}
                        </div>
                    {/each}
                </div>
                <div class="mt-auto flex flex-wrap items-center justify-between gap-4">
                    <p class="text-2xl font-semibold">
                        {#if left === 0}Temps écoulé ·{' '}{/if}Réponses : {game.answered} / {game.participants.length}
                    </p>
                    {@render primary(
                        lastQuestion ? 'Voir le classement' : 'Question suivante',
                        () => void act(nextRoute(sessionId).url),
                    )}
                </div>
            </section>
            <aside class="hidden lg:block">
                {@render joinBox(false)}
            </aside>
        </main>
    {:else if game.status === 'ranking' && game.ranking}
        <main class="flex flex-1 flex-col items-center gap-8">
            <h1 class="display-title text-5xl">Classement</h1>
            <!-- Podium: 2nd, 1st, 3rd. -->
            <ol class="flex items-end justify-center gap-4">
                {#each [podium[1], podium[0], podium[2]] as row, place (place)}
                    {#if row}
                        <li class="flex w-48 flex-col items-center gap-2 text-center">
                            <Trophy
                                class={cn(
                                    'size-10',
                                    row.rank === 1 ? 'text-cta' : 'text-white/70',
                                )}
                            />
                            <span class="w-full truncate text-2xl font-bold">{row.name}</span>
                            <span class="figures text-lg">{row.score.toLocaleString('fr-FR')} pts</span>
                            <span
                                class={cn(
                                    'grid w-full place-items-center rounded-t-xl font-display text-5xl font-bold',
                                    row.rank === 1
                                        ? 'h-40 bg-cta text-cta-foreground'
                                        : row.rank === 2
                                          ? 'h-28 bg-[#7c5cff] text-white'
                                          : 'h-20 bg-[#f5a524] text-[#0e0926]',
                                )}>{row.rank}</span
                            >
                        </li>
                    {/if}
                {/each}
            </ol>
            {#if game.ranking.length > 3}
                <ol class="grid w-full max-w-2xl gap-1 text-lg">
                    {#each game.ranking.slice(3) as row (row.id)}
                        <li class="flex items-center gap-4 rounded-lg bg-white/5 px-4 py-2">
                            <span class="w-10 figures text-muted-foreground">{row.rank}</span>
                            <span class="min-w-0 flex-1 truncate font-semibold">{row.name}</span>
                            <span class="figures">{row.score.toLocaleString('fr-FR')} pts</span>
                        </li>
                    {/each}
                </ol>
            {/if}
            {@render primary('Correction', () => {
                shown = 0;
                void act(correctRoute(sessionId).url);
            })}
        </main>
    {:else if (game.status === 'correction' || game.status === 'closed') && correction}
        <main class="flex flex-1 flex-col gap-6">
            <p class="font-display text-3xl font-bold">
                Correction · question {correction.position} / {game.total}
            </p>
            <h1 class="font-display text-3xl font-bold lg:text-5xl">
                {correction.question}
            </h1>
            {#if correction.image_url}
                <img
                    src={correction.image_url}
                    alt=""
                    class="max-h-[30vh] self-start rounded-xl bg-white"
                />
            {/if}
            <div class="grid gap-4 lg:grid-cols-2">
                {#each Object.entries(correction.choices) as [letter, text] (letter)}
                    {@const right = letter === correction.correct}
                    <div
                        class={cn(
                            'flex min-h-24 items-center gap-4 rounded-2xl p-4 text-2xl font-semibold',
                            expressTiles[letter as Letter],
                            !right && 'opacity-35',
                            right && 'ring-4 ring-white',
                        )}
                    >
                        <span
                            class="grid size-14 shrink-0 place-items-center rounded-xl bg-white/25 font-display text-3xl font-bold uppercase"
                            >{#if right}<Check class="size-8" />{:else}{letter}{/if}</span
                        >
                        <span class="min-w-0 flex-1">{text}</span>
                        <span class="figures text-3xl font-bold" title="Réponses données"
                            >{correction.counts[letter as Letter] ?? 0}</span
                        >
                    </div>
                {/each}
            </div>
            <div class="mt-auto flex flex-wrap items-center justify-between gap-4">
                <div class="flex gap-2">
                    <button
                        type="button"
                        disabled={shown === 0}
                        class="inline-flex h-14 items-center gap-2 rounded-xl border border-white/30 px-6 text-lg font-semibold disabled:opacity-40"
                        onclick={() => (shown -= 1)}
                    >
                        <ArrowLeft class="size-5" /> Précédente
                    </button>
                    <button
                        type="button"
                        disabled={shown >= game.total - 1}
                        class="inline-flex h-14 items-center gap-2 rounded-xl border border-white/30 px-6 text-lg font-semibold disabled:opacity-40"
                        onclick={() => (shown += 1)}
                    >
                        Suivante <ArrowRight class="size-5" />
                    </button>
                </div>
                {#if game.status === 'correction'}
                    {@render primary('Terminer la session', finish)}
                {:else}
                    <Link href={toUrl(edit(quizId))} class="mono-link text-muted-foreground"
                        >Session terminée · retour au quiz</Link
                    >
                {/if}
            </div>
        </main>
    {:else}
        <main class="flex flex-1 flex-col items-center justify-center gap-4 text-center">
            {#if game.status === 'closed'}
                <h1 class="display-title text-5xl">Session terminée</h1>
            {:else}
                <Spinner class="size-10" />
            {/if}
            <Link href={toUrl(index())} class="mono-link text-muted-foreground">← Quiz express</Link>
        </main>
    {/if}

    {#if game.status === 'lobby'}
        <p class="text-center text-sm text-muted-foreground">
            <Play class="inline size-4" /> Tu peux aussi ouvrir {joinUrl} sur un téléphone pour tester.
        </p>
    {/if}
</div>

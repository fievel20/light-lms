<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import Check from '@lucide/svelte/icons/check';
    import Clock from '@lucide/svelte/icons/clock';
    import Trophy from '@lucide/svelte/icons/trophy';
    import X from '@lucide/svelte/icons/x';
    import { toast } from 'svelte-sonner';
    import ExpressPlayController from '@/actions/App/Http/Controllers/ExpressPlayController';
    import AppHead from '@/components/AppHead.svelte';
    import CountdownRing from '@/components/express/CountdownRing.svelte';
    import TextPicture from '@/components/express/TextPicture.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Spinner } from '@/components/ui/spinner';
    import { Countdown, expressTiles, poll, send } from '@/lib/express';
    import type { PlayerQuestion, PlayerState } from '@/lib/express';
    import type { Letter } from '@/lib/quiz';
    import { cn } from '@/lib/utils';
    import { answer as answerRoute, state as stateRoute } from '@/routes/express/play';

    let {
        state: initial,
        maxName,
    }: { state: PlayerState; maxName: number } = $props();

    // svelte-ignore state_referenced_locally
    let game: PlayerState = $state(initial);
    let sending: Letter | null = $state(null);
    let now = $state(Date.now());
    const countdown = new Countdown();

    const question = $derived(game.question);
    const left = $derived(
        game.status === 'question' ? countdown.secondsLeft(now) : 0,
    );
    const timeUp = $derived(game.status === 'question' && left === 0);

    function update(next: PlayerState): void {
        countdown.sync(
            next.status === 'question' ? `q${next.position}` : null,
            next.remaining_ms,
        );
        game = next;
    }

    // svelte-ignore state_referenced_locally
    update(initial);

    // The server leads: its state is asked every second.
    $effect(() => {
        const stop = poll<PlayerState>(stateRoute(game.code).url, update);
        const clock = setInterval(() => (now = Date.now()), 250);

        return () => {
            stop();
            clearInterval(clock);
        };
    });

    async function choose(letter: Letter): Promise<void> {
        if (!question || question.answer || sending || timeUp) {
            return;
        }

        sending = letter;

        try {
            update(
                await send<PlayerState>(answerRoute(game.code).url, 'POST', {
                    position: question.position,
                    choice: letter,
                }),
            );
        } catch (error) {
            toast.error(
                error instanceof Error ? error.message : 'Réponse non envoyée.',
            );
        } finally {
            sending = null;
        }
    }

    const rankLabel = (rank: number) => (rank === 1 ? '1er' : `${rank}e`);
</script>

<AppHead title={game.title ?? 'Quiz express'} />

{#snippet questionText(item: PlayerQuestion)}
    <!-- Drawn by the server: the browser has no text to translate. -->
    <div class="rounded-xl bg-white p-4 text-[#0e0926] shadow-sm" translate="no">
        {#if item.text_url}
            <TextPicture src={item.text_url} scale={0.43} />
        {:else}
            <p class="font-display text-lg font-bold">{item.text}</p>
        {/if}
        {#if item.image_url}
            <img
                src={item.image_url}
                alt=""
                class="mt-3 max-h-56 rounded-md border border-[#0e0926]/15"
            />
        {/if}
    </div>
{/snippet}

{#snippet choiceText(choice: PlayerQuestion['choices'][number])}
    {#if choice.text_url}
        <span class="min-w-0 flex-1">
            <TextPicture src={choice.text_url} scale={0.42} />
        </span>
    {:else}
        <span class="min-w-0 flex-1 text-left">{choice.text}</span>
    {/if}
{/snippet}

<div class="mx-auto flex w-full max-w-xl flex-1 flex-col gap-5 p-4">
    <header class="flex items-center justify-between gap-3">
        <p class="min-w-0 truncate eyebrow text-primary">
            // {game.title ?? 'Quiz express'}
        </p>
        {#if game.me}
            <p class="truncate text-sm font-semibold">{game.me.name}</p>
        {/if}
    </header>

    {#if !game.me}
        {#if game.status === 'lobby' || game.status === 'question'}
            <!-- The QR code leads here: a name is enough to play. -->
            <section class="flex flex-1 flex-col justify-center gap-6">
                <h1 class="display-title text-3xl">Bienvenue !</h1>
                <Form
                    {...ExpressPlayController.join.form(game.code)}
                    class="grid gap-3"
                >
                    {#snippet children({ errors, processing })}
                        <label for="name" class="font-medium">Ton nom</label>
                        <Input
                            id="name"
                            name="name"
                            required
                            maxlength={maxName}
                            autocomplete="nickname"
                            class="h-14 text-lg"
                        />
                        <InputError message={errors.name} />
                        <Button
                            type="submit"
                            disabled={processing}
                            class="h-14 bg-cta font-mono text-base font-semibold text-cta-foreground uppercase hover:bg-cta/90"
                        >
                            {#if processing}<Spinner />{/if}
                            Rejoindre
                        </Button>
                    {/snippet}
                </Form>
            </section>
        {:else}
            <section class="flex flex-1 flex-col justify-center gap-3 text-center">
                <h1 class="display-title text-3xl">Quiz terminé</h1>
                <p class="text-muted-foreground">
                    On ne peut plus rejoindre ce quiz.
                </p>
            </section>
        {/if}
    {:else if game.status === 'lobby'}
        <section
            class="flex flex-1 flex-col items-center justify-center gap-5 text-center"
        >
            <Spinner class="size-10 text-cta" />
            <h1 class="display-title text-3xl">
                Patiente, le quiz va commencer
            </h1>
            <p class="text-muted-foreground">
                Tu joues sous le nom <span class="font-semibold text-foreground"
                    >{game.me.name}</span
                >. Garde cette page ouverte.
            </p>
        </section>
    {:else if game.status === 'question' && question}
        <section class="flex flex-col gap-4" aria-live="polite">
            <div class="flex items-center justify-between gap-4">
                <p class="font-display text-lg font-bold">
                    Question {question.position} / {game.total}
                </p>
                <CountdownRing
                    {left}
                    total={game.seconds}
                    class="size-14 text-lg"
                />
            </div>

            {@render questionText(question)}

            <div class="grid gap-3">
                {#each question.choices as choice (`${question.position}-${choice.letter}`)}
                    {@const picked = question.answer === choice.letter}
                    <button
                        type="button"
                        disabled={!!question.answer || timeUp || !!sending}
                        class={cn(
                            'flex min-h-16 items-center gap-3 rounded-xl p-3 shadow-[0_4px_0_rgb(0_0_0/0.35)] transition active:translate-y-0.5 active:shadow-none',
                            expressTiles[choice.letter],
                            (question.answer || timeUp) && !picked && 'opacity-35',
                            (picked || sending === choice.letter) &&
                                'ring-4 ring-white',
                        )}
                        onclick={() => void choose(choice.letter)}
                    >
                        <span
                            class="grid size-10 shrink-0 place-items-center rounded-lg bg-white/25 font-display text-xl font-bold uppercase"
                            >{choice.letter}</span
                        >
                        {@render choiceText(choice)}
                    </button>
                {/each}
            </div>

            {#if question.answer}
                <p class="flex items-center justify-center gap-2 font-semibold text-cta">
                    <Check class="size-5" /> Réponse enregistrée : attends la suite.
                </p>
            {:else if timeUp}
                <p class="flex items-center justify-center gap-2 font-semibold">
                    <Clock class="size-5" /> Temps écoulé : attends la suite.
                </p>
            {/if}
        </section>
    {:else if game.status === 'question'}
        <section class="flex flex-1 items-center justify-center text-center">
            <p class="text-muted-foreground">Attends la question suivante…</p>
        </section>
    {:else if game.result}
        <section class="grid gap-2 rounded-2xl bg-card p-6 text-center">
            <Trophy class="mx-auto size-10 text-cta" />
            <p class="display-title text-4xl">
                {rankLabel(game.result.rank)}
                <span class="text-xl text-muted-foreground"
                    >sur {game.result.of}</span
                >
            </p>
            <p class="figures text-2xl font-bold">
                {game.result.score.toLocaleString('fr-FR')} points
            </p>
            {#if game.status === 'closed'}
                <p class="text-sm text-muted-foreground">Session terminée.</p>
            {/if}
        </section>

        {#if game.correction}
            <h2 class="font-display text-xl font-bold">Ta correction</h2>
            <ol class="grid gap-4">
                {#each game.correction as item (item.position)}
                    {@const right = item.answer === item.correct}
                    <li class="grid gap-3 rounded-2xl bg-card p-3">
                        <div class="flex items-center justify-between gap-3">
                            <span class="eyebrow">Question {item.position}</span>
                            <span
                                class={cn(
                                    'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-sm font-semibold',
                                    right
                                        ? 'bg-success text-success-foreground'
                                        : 'bg-destructive text-white',
                                )}
                            >
                                {#if right}<Check class="size-4" />{:else}<X
                                        class="size-4"
                                    />{/if}
                                {item.points} pts
                            </span>
                        </div>
                        {@render questionText(item)}
                        <ul class="grid gap-2">
                            {#each item.choices as choice (choice.letter)}
                                {@const isRight = choice.letter === item.correct}
                                {@const isMine = choice.letter === item.answer}
                                {#if isRight || isMine}
                                    <li
                                        class={cn(
                                            'flex items-center gap-3 rounded-xl p-2',
                                            expressTiles[choice.letter],
                                        )}
                                    >
                                        <span
                                            class="grid size-9 shrink-0 place-items-center rounded-lg bg-white/25 font-display font-bold uppercase"
                                            >{choice.letter}</span
                                        >
                                        {@render choiceText(choice)}
                                        <span
                                            class="shrink-0 rounded-full bg-white px-2 py-0.5 text-xs font-semibold text-[#0e0926]"
                                            >{isRight
                                                ? isMine
                                                    ? 'Ta réponse, juste'
                                                    : 'Bonne réponse'
                                                : 'Ta réponse'}</span
                                        >
                                    </li>
                                {/if}
                            {/each}
                            {#if !item.answer}
                                <li class="text-sm text-muted-foreground">
                                    Pas de réponse.
                                </li>
                            {/if}
                        </ul>
                    </li>
                {/each}
            </ol>
        {/if}
    {/if}
</div>

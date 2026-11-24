<script module lang="ts">
    import { index } from '@/routes/express-quizzes';

    export const layout = {
        breadcrumbs: [{ title: 'Quiz express', href: index() }],
    };
</script>

<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import Check from '@lucide/svelte/icons/check';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import ExpressSessionController from '@/actions/App/Http/Controllers/ExpressSessionController';
    import AppHead from '@/components/AppHead.svelte';
    import PageHeader from '@/components/design/PageHeader.svelte';
    import { Button } from '@/components/ui/button';
    import { formatDate } from '@/lib/dates';
    import type { HostState } from '@/lib/express';
    import { letterTiles } from '@/lib/quiz';
    import type { Letter } from '@/lib/quiz';
    import { cn, toUrl } from '@/lib/utils';
    import { index as sessionsIndex } from '@/routes/express-quizzes/sessions';

    let {
        quiz,
        session,
        ranking,
        correction,
    }: {
        quiz: { id: number; title: string | null };
        session: { id: number; code: string; status: string; created_at: string };
        ranking: HostState['ranking'];
        correction: HostState['correction'];
    } = $props();

    const answered = (counts: Partial<Record<Letter, number>>) =>
        Object.values(counts).reduce((sum, count) => sum + (count ?? 0), 0);
</script>

<AppHead title={`Résultats · ${quiz.title ?? 'Quiz express'}`} />

<div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 sm:p-6">
    <PageHeader
        eyebrow={`Session du ${formatDate(session.created_at)}`}
        title={quiz.title ?? 'Quiz express'}
        subtitle={`Code ${session.code}`}
    />

    {#if !ranking || !correction}
        <p class="text-muted-foreground">
            La session n’est pas allée jusqu’au classement : pas de résultats.
        </p>
    {:else}
        <section class="grid gap-2">
            <h2 class="font-display text-xl font-bold">Classement</h2>
            {#if ranking.length === 0}
                <p class="text-sm text-muted-foreground">Aucun participant.</p>
            {/if}
            <ol class="divide-y divide-border/70 surface">
                {#each ranking as row (row.id)}
                    <li class="flex items-center gap-3 px-4 py-2">
                        <span class="w-8 figures text-muted-foreground"
                            >{row.rank}</span
                        >
                        <span class="min-w-0 flex-1 truncate font-medium"
                            >{row.name}</span
                        >
                        <span class="figures"
                            >{row.score.toLocaleString('fr-FR')} pts</span
                        >
                    </li>
                {/each}
            </ol>
        </section>

        <section class="grid gap-3">
            <h2 class="font-display text-xl font-bold">Correction</h2>
            <ol class="grid gap-3">
                {#each correction as item (item.position)}
                    {@const total = answered(item.counts)}
                    <li class="grid gap-3 surface p-4">
                        <p>
                            <span class="eyebrow">Q{item.position}</span>
                            <span class="font-medium">{item.question}</span>
                        </p>
                        <ul class="grid gap-2">
                            {#each Object.entries(item.choices) as [letter, text] (letter)}
                                {@const count = item.counts[letter as Letter] ?? 0}
                                <li class="grid gap-1">
                                    <div class="flex items-center gap-2 text-sm">
                                        <span
                                            class={cn(
                                                'grid size-7 shrink-0 place-items-center rounded-md font-display font-bold uppercase',
                                                letter === item.correct
                                                    ? letterTiles[letter as Letter]
                                                    : 'border border-input text-muted-foreground',
                                            )}
                                            >{#if letter === item.correct}<Check
                                                    class="size-4"
                                                />{:else}{letter}{/if}</span
                                        >
                                        <span class="min-w-0 flex-1">{text}</span>
                                        <span class="figures">{count}</span>
                                    </div>
                                    <span
                                        class="ml-9 block h-1.5 overflow-hidden rounded-full bg-muted"
                                    >
                                        <span
                                            class={cn(
                                                'block h-full rounded-full',
                                                letter === item.correct
                                                    ? 'bg-success'
                                                    : 'bg-muted-foreground/40',
                                            )}
                                            style:width={`${total === 0 ? 0 : (count / total) * 100}%`}
                                        ></span>
                                    </span>
                                </li>
                            {/each}
                        </ul>
                    </li>
                {/each}
            </ol>
        </section>
    {/if}

    <div class="flex flex-wrap items-center justify-between gap-3">
        <Link
            href={toUrl(sessionsIndex(quiz.id))}
            class="mono-link text-muted-foreground">← Sessions du quiz</Link
        >
        <Form
            {...ExpressSessionController.destroy.form(session.id)}
            onBefore={() => confirm('Supprimer cette session et ses résultats ?')}
        >
            {#snippet children({ processing })}
                <Button
                    type="submit"
                    variant="ghost"
                    disabled={processing}
                    class="h-11 text-destructive sm:h-9"
                >
                    <Trash2 class="size-4" />
                    Supprimer la session
                </Button>
            {/snippet}
        </Form>
    </div>
</div>

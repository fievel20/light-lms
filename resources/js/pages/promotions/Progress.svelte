<script module lang="ts">
    import { index } from '@/routes/promotions';

    export const layout = {
        breadcrumbs: [{ title: 'Promotions', href: index() }],
    };
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowLeft from '@lucide/svelte/icons/arrow-left';
    import Check from '@lucide/svelte/icons/check';
    import AppHead from '@/components/AppHead.svelte';
    import ProgressBar from '@/components/progress/ProgressBar.svelte';
    import { Badge } from '@/components/ui/badge';
    import { valueLabel } from '@/lib/competencies';
    import { cn, toUrl } from '@/lib/utils';
    import { show } from '@/routes/promotions';
    import { show as learnerPage } from '@/routes/promotions/learners';
    import type { Competency } from '@/types/framework';

    type Cell = {
        self: number | null;
        trainer: number | null;
        acquired: boolean;
        gap: boolean;
        to_review: boolean;
    };

    type LearnerProgress = {
        id: number;
        name: string;
        activated_at: string | null;
        total: number;
        acquired: number;
        self_assessed: number;
        to_review: number;
        gaps: number;
        percent: number;
        cells: Record<string, Cell>;
    };

    let {
        promotion,
        framework,
        competencies,
        learners,
        summary,
    }: {
        promotion: { id: number; name: string; archived_at: string | null };
        framework: { id: number; name: string } | null;
        competencies: (Competency & { acquired_count: number })[];
        learners: LearnerProgress[];
        summary: {
            learners: number;
            competencies: number;
            average_percent: number;
            to_review: number;
        };
    } = $props();

    function cellTitle(competency: Competency, cell: Cell): string {
        const parts = [
            `Autoévaluation : ${cell.self === null ? '—' : valueLabel(competency, cell.self)}`,
            `Formateur : ${cell.trainer === null ? '—' : valueLabel(competency, cell.trainer)}`,
        ];

        if (cell.to_review) parts.push('À examiner');
        if (cell.gap) parts.push('Écart');

        return parts.join(' · ');
    }
</script>

<AppHead title={`Suivi ${promotion.name}`} />

<div class="flex w-full flex-col gap-6 p-4">
    <header class="mx-auto flex w-full max-w-4xl flex-col gap-2">
        <Link
            href={toUrl(show(promotion.id))}
            class="inline-flex min-h-11 items-center gap-1 self-start text-sm text-muted-foreground hover:text-foreground sm:min-h-0"
        >
            <ArrowLeft class="size-4" />
            {promotion.name}
        </Link>
        <h1 class="text-xl font-semibold tracking-tight">
            Suivi de {promotion.name}
        </h1>
        <p class="text-sm text-muted-foreground">
            {framework?.name ?? 'Aucun référentiel'} · {summary.learners} apprenant{summary.learners >
            1
                ? 's'
                : ''} · {summary.competencies} compétence{summary.competencies > 1
                ? 's'
                : ''}
        </p>
    </header>

    {#if !framework || learners.length === 0}
        <p
            class="mx-auto w-full max-w-4xl rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            {!framework
                ? 'Affectez un référentiel à cette promotion pour suivre la progression.'
                : 'Aucun apprenant dans cette promotion.'}
        </p>
    {:else}
        <section class="mx-auto grid w-full max-w-4xl gap-3 sm:grid-cols-2">
            <div class="rounded-xl border p-4">
                <ProgressBar
                    percent={summary.average_percent}
                    label="Progression moyenne"
                />
            </div>
            <div class="flex items-center justify-between rounded-xl border p-4 text-sm">
                <span class="text-muted-foreground">Autoévaluations à examiner</span>
                <span class="text-xl font-semibold tabular-nums">{summary.to_review}</span>
            </div>
        </section>

        <!-- Mobile et tablette : une carte par apprenant -->
        <ul class="mx-auto grid w-full max-w-4xl gap-3 lg:hidden">
            {#each learners as learner (learner.id)}
                <li class="min-w-0">
                    <Link
                        href={toUrl(learnerPage([promotion.id, learner.id]))}
                        class="flex flex-col gap-3 rounded-xl border p-4 transition-colors hover:bg-muted/50"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span class="truncate font-medium">{learner.name}</span>
                            {#if learner.to_review > 0}
                                <Badge>{learner.to_review} à examiner</Badge>
                            {:else if !learner.activated_at}
                                <Badge variant="outline">Invité</Badge>
                            {/if}
                        </div>
                        <ProgressBar
                            percent={learner.percent}
                            label={`${learner.acquired}/${learner.total} acquises`}
                        />
                    </Link>
                </li>
            {/each}
        </ul>

        <!-- Grand écran : matrice apprenants × compétences -->
        <div class="hidden overflow-x-auto rounded-xl border lg:block">
            <table class="w-full border-collapse text-sm">
                <caption class="sr-only">
                    Compétences acquises par apprenant
                </caption>
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th
                            scope="col"
                            class="sticky left-0 z-10 bg-muted p-3 text-left font-medium"
                            >Apprenant</th
                        >
                        <th scope="col" class="p-3 text-right font-medium">Acquises</th>
                        {#each competencies as competency (competency.id)}
                            <th
                                scope="col"
                                class="p-2 text-center font-mono text-xs font-medium"
                                title={competency.name}>{competency.code}</th
                            >
                        {/each}
                    </tr>
                </thead>
                <tbody>
                    {#each learners as learner (learner.id)}
                        <tr class="border-b last:border-0">
                            <th
                                scope="row"
                                class="sticky left-0 z-10 bg-background p-3 text-left font-normal"
                            >
                                <Link
                                    href={toUrl(
                                        learnerPage([promotion.id, learner.id]),
                                    )}
                                    class="underline-offset-4 hover:underline"
                                    >{learner.name}</Link
                                >
                            </th>
                            <td class="p-3 text-right tabular-nums">{learner.percent} %</td>
                            {#each competencies as competency (competency.id)}
                                {@const cell = learner.cells[competency.id]}
                                <td class="p-1 text-center">
                                    <span
                                        title={cellTitle(competency, cell)}
                                        class={cn(
                                            'relative mx-auto flex size-8 items-center justify-center rounded-md text-xs tabular-nums',
                                            cell.acquired
                                                ? 'bg-primary text-primary-foreground'
                                                : cell.trainer !== null
                                                  ? 'bg-muted'
                                                  : 'border border-dashed text-muted-foreground',
                                        )}
                                    >
                                        {#if cell.acquired}
                                            <Check class="size-4" />
                                        {:else if cell.trainer !== null}
                                            {cell.trainer}
                                        {:else if cell.self !== null}
                                            {cell.self}
                                        {/if}
                                        {#if cell.to_review}
                                            <span
                                                class="absolute -top-1 -right-1 size-2.5 rounded-full bg-amber-500"
                                            ></span>
                                        {/if}
                                    </span>
                                </td>
                            {/each}
                        </tr>
                    {/each}
                </tbody>
                <tfoot>
                    <tr class="border-t bg-muted/50 text-xs text-muted-foreground">
                        <th scope="row" class="sticky left-0 bg-muted p-3 text-left font-normal"
                            >Apprenants ayant acquis</th
                        >
                        <td></td>
                        {#each competencies as competency (competency.id)}
                            <td class="p-2 text-center tabular-nums"
                                >{competency.acquired_count}/{learners.length}</td
                            >
                        {/each}
                    </tr>
                </tfoot>
            </table>
        </div>

        <ul
            class="hidden flex-wrap gap-4 text-xs text-muted-foreground lg:flex"
            aria-label="Légende"
        >
            <li class="flex items-center gap-2">
                <span class="flex size-5 items-center justify-center rounded bg-primary text-primary-foreground"
                    ><Check class="size-3" /></span
                > Acquise
            </li>
            <li class="flex items-center gap-2">
                <span class="size-5 rounded bg-muted"></span> Validée, non acquise
                (niveau formateur)
            </li>
            <li class="flex items-center gap-2">
                <span class="size-5 rounded border border-dashed"></span> Pas encore
                validée (niveau autoévalué)
            </li>
            <li class="flex items-center gap-2">
                <span class="size-2.5 rounded-full bg-amber-500"></span> Autoévaluation
                à examiner
            </li>
        </ul>
    {/if}
</div>

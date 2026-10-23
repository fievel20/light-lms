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
    import Gauge from '@lucide/svelte/icons/gauge';
    import Inbox from '@lucide/svelte/icons/inbox';
    import Users from '@lucide/svelte/icons/users';
    import Zap from '@lucide/svelte/icons/zap';
    import AppHead from '@/components/AppHead.svelte';
    import EmptyState from '@/components/design/EmptyState.svelte';
    import StatTile from '@/components/design/StatTile.svelte';
    import ProgressBar from '@/components/progress/ProgressBar.svelte';
    import { Button } from '@/components/ui/button';
    import { valueLabel } from '@/lib/competencies';
    import { heatClass, type HeatCell } from '@/lib/heat';
    import { cn, toUrl } from '@/lib/utils';
    import { review, show } from '@/routes/promotions';
    import { show as learnerPage } from '@/routes/promotions/learners';
    import type { Competency } from '@/types/framework';

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
        cells: Record<string, HeatCell>;
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

    let sort = $state<'name' | 'progress'>('name');
    let onlyToReview = $state(false);

    const shown = $derived.by(() => {
        const list = onlyToReview ? learners.filter((learner) => learner.to_review > 0) : [...learners];

        return sort === 'progress' ? list.sort((a, b) => b.percent - a.percent) : list;
    });

    function cellTitle(competency: Competency, cell: HeatCell): string {
        const parts = [
            `Autoévaluation : ${cell.self === null ? '—' : valueLabel(competency, cell.self)}`,
            `Formateur : ${cell.trainer === null ? '—' : valueLabel(competency, cell.trainer)}`,
        ];

        if (cell.to_review) parts.push('À examiner');
        if (cell.gap) parts.push('Écart');

        return parts.join(' · ');
    }

    function learnerUrl(learner: LearnerProgress): string {
        return toUrl(learnerPage([promotion.id, learner.id]));
    }
</script>

<AppHead title={`Suivi ${promotion.name}`} />

<div class="flex w-full flex-col gap-5 p-4 sm:p-6">
    <header class="mx-auto flex w-full max-w-5xl flex-col gap-2">
        <Link
            href={toUrl(show(promotion.id))}
            class="inline-flex min-h-11 items-center gap-1 self-start text-sm text-muted-foreground hover:text-foreground sm:min-h-0"
        >
            <ArrowLeft class="size-4" />
            {promotion.name}
        </Link>
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Suivi</h1>
                <p class="text-sm text-muted-foreground">{framework?.name ?? 'Aucun référentiel'}</p>
            </div>
            {#if summary.to_review > 0 && !promotion.archived_at}
                <Button asChild class="h-11 w-full sm:w-auto">
                    {#snippet children(props)}
                        <Link {...props} href={toUrl(review(promotion.id))}>
                            <Zap class="size-4" />
                            Examiner ({summary.to_review})
                        </Link>
                    {/snippet}
                </Button>
            {/if}
        </div>
    </header>

    {#if !framework || learners.length === 0}
        <div class="mx-auto w-full max-w-5xl">
            <EmptyState
                icon={Users}
                title={!framework ? 'Aucun référentiel' : 'Aucun apprenant'}
                description={!framework
                    ? 'Affecte un référentiel à cette promotion pour suivre la progression.'
                    : 'Inscris des apprenants pour suivre leur progression.'}
            />
        </div>
    {:else}
        <section class="mx-auto grid w-full max-w-5xl grid-cols-3 gap-3">
            <StatTile label="Apprenants" value={summary.learners} icon={Users} />
            <StatTile label="Progression moyenne" value={`${summary.average_percent}%`} icon={Gauge} tone="primary" />
            <StatTile
                label="À examiner"
                value={summary.to_review}
                icon={Inbox}
                tone={summary.to_review > 0 ? 'warning' : 'success'}
            />
        </section>

        <div class="mx-auto flex w-full max-w-5xl flex-wrap items-center gap-2">
            <div class="flex rounded-xl bg-muted p-1 text-sm" role="group" aria-label="Trier les apprenants">
                {#each [{ key: 'name', label: 'Nom' }, { key: 'progress', label: 'Progression' }] as option (option.key)}
                    <button
                        type="button"
                        aria-pressed={sort === option.key}
                        onclick={() => (sort = option.key as 'name' | 'progress')}
                        class={cn(
                            'min-h-9 rounded-lg px-3 font-semibold transition-colors',
                            sort === option.key ? 'bg-card shadow-sm' : 'text-muted-foreground',
                        )}>{option.label}</button
                    >
                {/each}
            </div>
            <button
                type="button"
                aria-pressed={onlyToReview}
                onclick={() => (onlyToReview = !onlyToReview)}
                class={cn(
                    'min-h-11 rounded-xl px-3 text-sm font-semibold transition-colors',
                    onlyToReview
                        ? 'bg-warning-soft text-warning-foreground dark:text-warning'
                        : 'bg-muted text-muted-foreground',
                )}
            >
                À examiner seulement
            </button>
        </div>

        {#if shown.length === 0}
            <div class="mx-auto w-full max-w-5xl">
                <EmptyState icon={Check} title="Tout est à jour ✨" description="Aucune autoévaluation à examiner." />
            </div>
        {/if}

        <!-- Mobile et tablette : une carte par apprenant avec sa bande de chaleur -->
        <ul class="mx-auto grid w-full max-w-5xl gap-3 lg:hidden">
            {#each shown as learner (learner.id)}
                <li class="min-w-0">
                    <Link href={learnerUrl(learner)} class="surface flex flex-col gap-3 p-4 transition-colors hover:bg-muted/40">
                        <div class="flex items-center justify-between gap-2">
                            <span class="truncate font-semibold">{learner.name}</span>
                            {#if learner.to_review > 0}
                                <span
                                    class="shrink-0 rounded-full bg-warning-soft px-2.5 py-0.5 text-xs font-semibold text-warning-foreground dark:text-warning"
                                    >{learner.to_review} à examiner</span
                                >
                            {:else if !learner.activated_at}
                                <span class="shrink-0 rounded-full bg-muted px-2.5 py-0.5 text-xs font-semibold text-muted-foreground"
                                    >Invité</span
                                >
                            {/if}
                        </div>
                        <ProgressBar percent={learner.percent} label={`${learner.acquired}/${learner.total} acquises`} />
                        <div class="flex gap-1" aria-hidden="true">
                            {#each competencies as competency (competency.id)}
                                <span class={cn('h-2.5 flex-1 rounded-full', heatClass(competency, learner.cells[competency.id]))}></span>
                            {/each}
                        </div>
                    </Link>
                </li>
            {/each}
        </ul>

        <!-- Grand écran : carte de chaleur apprenants × compétences -->
        <div class="surface mx-auto hidden w-full max-w-full overflow-x-auto lg:block">
            <table class="w-full border-separate border-spacing-1 p-2 text-sm">
                <caption class="sr-only">Carte de chaleur des compétences par apprenant</caption>
                <thead>
                    <tr>
                        <th scope="col" class="sticky left-0 z-10 bg-card px-2 py-2 text-left font-semibold">Apprenant</th>
                        <th scope="col" class="px-2 py-2 text-right font-semibold">Acquis</th>
                        {#each competencies as competency (competency.id)}
                            <th scope="col" class="px-1 py-2 text-center font-mono text-xs font-semibold" title={competency.name}
                                >{competency.code}</th
                            >
                        {/each}
                    </tr>
                </thead>
                <tbody>
                    {#each shown as learner (learner.id)}
                        <tr>
                            <th scope="row" class="sticky left-0 z-10 bg-card px-2 text-left font-medium whitespace-nowrap">
                                <Link href={learnerUrl(learner)} class="underline-offset-4 hover:underline">{learner.name}</Link>
                                {#if learner.to_review > 0}
                                    <span class="ml-1 inline-block size-2 rounded-full bg-warning align-middle" title="À examiner"></span>
                                {/if}
                            </th>
                            <td class="figures px-2 text-right font-bold">{learner.percent}%</td>
                            {#each competencies as competency (competency.id)}
                                {@const cell = learner.cells[competency.id]}
                                <td class="p-0">
                                    <span
                                        title={cellTitle(competency, cell)}
                                        class={cn(
                                            'relative mx-auto flex h-9 min-w-9 items-center justify-center rounded-lg text-xs font-bold',
                                            heatClass(competency, cell),
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
                                            <span class="absolute -top-1 -right-1 size-2.5 rounded-full bg-warning ring-2 ring-card"></span>
                                        {/if}
                                    </span>
                                </td>
                            {/each}
                        </tr>
                    {/each}
                </tbody>
                <tfoot>
                    <tr class="text-xs text-muted-foreground">
                        <th scope="row" class="sticky left-0 bg-card px-2 py-2 text-left font-normal">Ont acquis</th>
                        <td></td>
                        {#each competencies as competency (competency.id)}
                            <td class="figures py-2 text-center">{competency.acquired_count}/{learners.length}</td>
                        {/each}
                    </tr>
                </tfoot>
            </table>
        </div>

        <ul class="mx-auto flex w-full max-w-5xl flex-wrap gap-x-4 gap-y-2 text-xs text-muted-foreground" aria-label="Légende">
            <li class="flex items-center gap-2"><span class="size-4 rounded bg-success"></span> Acquise</li>
            <li class="flex items-center gap-2">
                <span class="flex gap-0.5"
                    ><span class="size-4 rounded bg-warning/25"></span><span class="size-4 rounded bg-warning/50"></span><span
                        class="size-4 rounded bg-warning/80"
                    ></span></span
                > Validée, plus ou moins proche du seuil
            </li>
            <li class="flex items-center gap-2"><span class="size-4 rounded bg-primary-soft"></span> Autoévaluée seulement</li>
            <li class="flex items-center gap-2"><span class="size-4 rounded border border-dashed"></span> Pas encore évaluée</li>
            <li class="flex items-center gap-2"><span class="size-2.5 rounded-full bg-warning"></span> À examiner</li>
        </ul>
    {/if}
</div>

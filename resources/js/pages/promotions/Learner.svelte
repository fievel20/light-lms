<script module lang="ts">
    import { index } from '@/routes/promotions';

    export const layout = {
        breadcrumbs: [{ title: 'Promotions', href: index() }],
    };
</script>

<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import ArrowLeft from '@lucide/svelte/icons/arrow-left';
    import Inbox from '@lucide/svelte/icons/inbox';
    import PencilLine from '@lucide/svelte/icons/pencil-line';
    import Split from '@lucide/svelte/icons/split';
    import Zap from '@lucide/svelte/icons/zap';
    import AppHead from '@/components/AppHead.svelte';
    import AssessmentSummary from '@/components/assessments/AssessmentSummary.svelte';
    import QuickReview from '@/components/assessments/QuickReview.svelte';
    import TrainerValidationForm from '@/components/assessments/TrainerValidationForm.svelte';
    import EmptyState from '@/components/design/EmptyState.svelte';
    import ProgressRing from '@/components/design/ProgressRing.svelte';
    import StatTile from '@/components/design/StatTile.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { toUrl } from '@/lib/utils';
    import { show } from '@/routes/promotions';
    import type { AssessedCompetency } from '@/types/framework';
    import type { Learner } from '@/types/learner';

    let {
        promotion,
        learner,
        framework,
        competencies,
        summary,
        nextToReview,
    }: {
        promotion: { id: number; name: string; archived_at: string | null };
        learner: Learner;
        framework: { id: number; name: string } | null;
        competencies: AssessedCompetency[];
        summary: {
            total: number;
            acquired: number;
            self_assessed: number;
            to_review: number;
            gaps: number;
            percent: number;
        };
        nextToReview: { id: number; name: string; to_review: number } | null;
    } = $props();

    const canValidate = $derived(framework !== null && promotion.archived_at === null);

    // Quick mode can be opened from a link (?mode=rapide) or with the button.
    let quick = $state(new URL(page.url, 'http://localhost').searchParams.get('mode') === 'rapide');
</script>

<AppHead title={learner.name} />

<div class="mx-auto flex w-full max-w-3xl flex-col gap-5 p-4 sm:p-6">
    <header class="flex flex-col gap-3">
        <Link
            href={toUrl(show(promotion.id))}
            class="inline-flex min-h-11 items-center gap-1 self-start text-sm text-muted-foreground hover:text-foreground sm:min-h-0"
        >
            <ArrowLeft class="size-4" />
            {promotion.name}
        </Link>
        <div class="flex items-center gap-4">
            {#if framework}
                <ProgressRing percent={summary.percent} size={84} stroke={8} label={`Progression de ${learner.name}`}>
                    <span class="figures text-lg font-extrabold">{summary.percent}%</span>
                </ProgressRing>
            {/if}
            <div class="min-w-0">
                <h1 class="truncate text-2xl font-extrabold tracking-tight">{learner.name}</h1>
                <p class="truncate text-sm text-muted-foreground">{learner.email}</p>
                {#if framework}
                    <p class="text-sm text-muted-foreground">
                        {summary.acquired} / {summary.total} compétences acquises
                    </p>
                {/if}
            </div>
        </div>
    </header>

    {#if !framework}
        <EmptyState icon={Inbox} title="Aucun référentiel" description="Aucun référentiel n'est affecté à cette promotion." />
    {:else}
        <div class="grid grid-cols-3 gap-3">
            <StatTile label="Autoévaluées" value={`${summary.self_assessed}/${summary.total}`} icon={PencilLine} tone="primary" />
            <StatTile
                label="À examiner"
                value={summary.to_review}
                icon={Inbox}
                tone={summary.to_review > 0 ? 'warning' : 'success'}
            />
            <StatTile label="Écarts" value={summary.gaps} icon={Split} />
        </div>

        {#if canValidate && quick}
            <QuickReview
                promotionId={promotion.id}
                {learner}
                {competencies}
                {nextToReview}
                onclose={() => (quick = false)}
            />
        {:else if canValidate && summary.to_review > 0}
            <Button class="h-12 w-full text-base" onclick={() => (quick = true)}>
                <Zap class="size-4" />
                Validation rapide ({summary.to_review})
            </Button>
        {/if}

        <ol class="grid gap-3">
            {#each competencies as competency (competency.id)}
                <li class="surface flex min-w-0 flex-col gap-4 p-4">
                    <div class="flex items-start gap-3">
                        <Badge variant="outline" class="shrink-0 font-mono">{competency.code}</Badge>
                        <p class="min-w-0 flex-1 font-semibold">{competency.name}</p>
                    </div>

                    {#if competency.to_review || competency.gap}
                        <div class="flex flex-wrap gap-2">
                            {#if competency.to_review}
                                <span
                                    class="rounded-full bg-warning-soft px-2.5 py-1 text-xs font-semibold text-warning-foreground dark:text-warning"
                                    >Autoévaluation à examiner</span
                                >
                            {/if}
                            {#if competency.gap}
                                <span class="rounded-full bg-muted px-2.5 py-1 text-xs font-semibold text-muted-foreground"
                                    >Écart avec ta validation</span
                                >
                            {/if}
                        </div>
                    {/if}

                    <AssessmentSummary {competency} />

                    {#if canValidate && !quick}
                        <TrainerValidationForm promotionId={promotion.id} learnerId={learner.id} {competency} />
                    {/if}
                </li>
            {/each}
        </ol>
    {/if}
</div>

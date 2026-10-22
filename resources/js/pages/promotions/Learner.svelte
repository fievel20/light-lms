<script module lang="ts">
    import { index } from '@/routes/promotions';

    export const layout = {
        breadcrumbs: [{ title: 'Promotions', href: index() }],
    };
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowLeft from '@lucide/svelte/icons/arrow-left';
    import AppHead from '@/components/AppHead.svelte';
    import AssessmentSummary from '@/components/assessments/AssessmentSummary.svelte';
    import TrainerValidationForm from '@/components/assessments/TrainerValidationForm.svelte';
    import ProgressBar from '@/components/progress/ProgressBar.svelte';
    import { Badge } from '@/components/ui/badge';
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
    } = $props();
</script>

<AppHead title={learner.name} />

<div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
    <header class="flex flex-col gap-2">
        <Link
            href={toUrl(show(promotion.id))}
            class="inline-flex min-h-11 items-center gap-1 self-start text-sm text-muted-foreground hover:text-foreground sm:min-h-0"
        >
            <ArrowLeft class="size-4" />
            {promotion.name}
        </Link>
        <h1 class="text-xl font-semibold tracking-tight">{learner.name}</h1>
        <p class="text-sm text-muted-foreground">{learner.email}</p>
    </header>

    {#if framework}
        <section class="grid gap-3 surface p-4">
            <ProgressBar
                percent={summary.percent}
                label={`${summary.acquired} compétence${summary.acquired > 1 ? 's' : ''} acquise${summary.acquired > 1 ? 's' : ''} sur ${summary.total}`}
            />
            <dl class="grid grid-cols-3 gap-2 text-sm">
                <div class="rounded-lg bg-muted/50 p-2">
                    <dt class="text-xs text-muted-foreground">Autoévaluées</dt>
                    <dd class="font-semibold tabular-nums">
                        {summary.self_assessed}/{summary.total}
                    </dd>
                </div>
                <div class="rounded-lg bg-muted/50 p-2">
                    <dt class="text-xs text-muted-foreground">À examiner</dt>
                    <dd class="font-semibold tabular-nums">{summary.to_review}</dd>
                </div>
                <div class="rounded-lg bg-muted/50 p-2">
                    <dt class="text-xs text-muted-foreground">Écarts</dt>
                    <dd class="font-semibold tabular-nums">{summary.gaps}</dd>
                </div>
            </dl>
        </section>
    {/if}

    {#if !framework}
        <p class="rounded-2xl border border-dashed p-8 text-center text-sm text-muted-foreground">
            Aucun référentiel n'est affecté à cette promotion.
        </p>
    {:else}
        <ol class="grid gap-4">
            {#each competencies as competency (competency.id)}
                <li class="flex min-w-0 flex-col gap-4 surface p-4">
                    <div class="flex items-start gap-3">
                        <Badge variant="outline" class="shrink-0 font-mono"
                            >{competency.code}</Badge
                        >
                        <p class="min-w-0 flex-1 font-medium">{competency.name}</p>
                    </div>

                    {#if competency.to_review || competency.gap}
                        <div class="flex flex-wrap gap-2">
                            {#if competency.to_review}
                                <Badge class="bg-amber-500 text-white hover:bg-amber-500"
                                    >Autoévaluation à examiner</Badge
                                >
                            {/if}
                            {#if competency.gap}
                                <Badge variant="outline"
                                    >Écart entre autoévaluation et validation</Badge
                                >
                            {/if}
                        </div>
                    {/if}

                    <AssessmentSummary {competency} />

                    {#if !promotion.archived_at}
                        <TrainerValidationForm
                            promotionId={promotion.id}
                            learnerId={learner.id}
                            {competency}
                        />
                    {/if}
                </li>
            {/each}
        </ol>
    {/if}
</div>

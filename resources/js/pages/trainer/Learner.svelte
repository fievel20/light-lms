<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import AssessmentRow from '@/components/assessments/AssessmentRow.svelte';
    import PagerHeader from '@/components/trainer/PagerHeader.svelte';
    import { toUrl } from '@/lib/utils';
    import { index, show } from '@/routes/promotions/learners';
    import type { AssessedCompetency } from '@/types/framework';
    import type { Learner } from '@/types/learner';

    type Neighbour = { id: number; name: string } | null;

    let {
        promotion,
        learner,
        framework,
        competencies,
        summary,
        previous,
        next,
    }: {
        promotion: { id: number; name: string; archived_at: string | null };
        learner: Learner;
        framework: { id: number; name: string } | null;
        competencies: AssessedCompetency[];
        summary: { total: number; acquired: number; to_review: number; percent: number };
        previous: Neighbour;
        next: Neighbour;
    } = $props();
</script>

<AppHead title={learner.name} />

<div class="mx-auto flex w-full max-w-3xl flex-col gap-4 p-4 sm:p-6">
    <PagerHeader
        backHref={toUrl(index(promotion.id))}
        backLabel="Apprenants"
        previous={previous ? { href: toUrl(show([promotion.id, previous.id])), label: previous.name } : null}
        next={next ? { href: toUrl(show([promotion.id, next.id])), label: next.name } : null}
        title={learner.name}
    >
        <p class="mt-1 text-sm text-muted-foreground">
            {#if framework}
                {summary.acquired}/{summary.total} acquises · {summary.percent}%
                {#if summary.to_review > 0}
                    · <span class="font-semibold text-warning-foreground dark:text-warning">{summary.to_review} à examiner</span>
                {/if}
            {:else}
                Aucun référentiel affecté à cette promotion.
            {/if}
        </p>
    </PagerHeader>

    <ul class="divide-y divide-border/70">
        {#each competencies as competency (competency.id)}
            <AssessmentRow
                promotionId={promotion.id}
                learnerId={learner.id}
                {competency}
                title={competency.name}
                subtitle={competency.code}
                self={competency.self?.value ?? null}
                trainer={competency.trainer?.value ?? null}
                comment={competency.trainer?.comment ?? null}
                acquired={competency.acquired}
                toReview={competency.to_review}
                disabled={promotion.archived_at !== null}
            />
        {/each}
    </ul>
</div>

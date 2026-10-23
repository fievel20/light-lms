<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import AssessmentRow from '@/components/assessments/AssessmentRow.svelte';
    import PagerHeader from '@/components/trainer/PagerHeader.svelte';
    import { competencyOptions } from '@/lib/competencies';
    import { toUrl } from '@/lib/utils';
    import { index, show } from '@/routes/promotions/competencies';
    import type { Competency } from '@/types/framework';

    type LearnerRow = {
        id: number;
        name: string;
        activated: boolean;
        self: number | null;
        trainer: number | null;
        comment: string | null;
        acquired: boolean;
        to_review: boolean;
    };

    let {
        promotion,
        competency,
        previous,
        next,
        learners,
    }: {
        promotion: { id: number; name: string; archived_at: string | null };
        competency: Competency & { acquired_count: number };
        previous: { id: number; code: string } | null;
        next: { id: number; code: string } | null;
        learners: LearnerRow[];
    } = $props();

    const threshold = $derived(competency.type === 'binary' ? 1 : competency.validation_threshold);
</script>

<AppHead title={`${competency.code} · ${competency.name}`} />

<div class="mx-auto flex w-full max-w-3xl flex-col gap-4 p-4 sm:p-6">
    <PagerHeader
        backHref={toUrl(index(promotion.id))}
        backLabel="Compétences"
        previous={previous ? { href: toUrl(show([promotion.id, previous.id])), label: previous.code } : null}
        next={next ? { href: toUrl(show([promotion.id, next.id])), label: next.code } : null}
        title={competency.name}
    >
        <p class="mt-1 text-sm text-muted-foreground">
            <span class="font-mono font-semibold">{competency.code}</span> · {competency.acquired_count}/{learners.length}
            acquis
        </p>
        <p class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-muted-foreground">
            {#each competencyOptions(competency) as option (option.value)}
                <span class={option.value === threshold ? 'font-semibold text-success-ink' : ''}>
                    {competency.type === 'binary' ? (option.value === 1 ? 'Oui' : 'Non') : option.value} = {option.label}{option.value ===
                    threshold
                        ? ' 🏁'
                        : ''}
                </span>
            {/each}
        </p>
    </PagerHeader>

    <ul class="divide-y divide-border/70">
        {#each learners as learner (learner.id)}
            <AssessmentRow
                promotionId={promotion.id}
                learnerId={learner.id}
                {competency}
                title={learner.name}
                subtitle={learner.activated ? undefined : 'invité'}
                self={learner.self}
                trainer={learner.trainer}
                comment={learner.comment}
                acquired={learner.acquired}
                toReview={learner.to_review}
                disabled={promotion.archived_at !== null}
            />
        {/each}
    </ul>
</div>

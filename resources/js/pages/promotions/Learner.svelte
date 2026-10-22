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
    }: {
        promotion: { id: number; name: string; archived_at: string | null };
        learner: Learner;
        framework: { id: number; name: string } | null;
        competencies: AssessedCompetency[];
    } = $props();

    const acquiredCount = $derived(
        competencies.filter((competency) => competency.acquired).length,
    );
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
        <p class="text-sm text-muted-foreground">
            {learner.email}
            {#if framework}
                · {acquiredCount} compétence{acquiredCount > 1 ? 's' : ''}
                acquise{acquiredCount > 1 ? 's' : ''} sur {competencies.length}
            {/if}
        </p>
    </header>

    {#if !framework}
        <p class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
            Aucun référentiel n'est affecté à cette promotion.
        </p>
    {:else}
        <ol class="grid gap-4">
            {#each competencies as competency (competency.id)}
                <li class="flex flex-col gap-4 rounded-xl border p-4">
                    <div class="flex items-start gap-3">
                        <Badge variant="outline" class="shrink-0 font-mono"
                            >{competency.code}</Badge
                        >
                        <p class="min-w-0 font-medium">{competency.name}</p>
                    </div>

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

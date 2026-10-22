<script module lang="ts">
    import { index } from '@/routes/my-competencies';

    export const layout = {
        breadcrumbs: [{ title: 'Mes compétences', href: index() }],
    };
</script>

<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import MyCompetencyController from '@/actions/App/Http/Controllers/MyCompetencyController';
    import AppHead from '@/components/AppHead.svelte';
    import AssessmentSummary from '@/components/assessments/AssessmentSummary.svelte';
    import Heading from '@/components/Heading.svelte';
    import { Badge } from '@/components/ui/badge';
    import { competencyOptions } from '@/lib/competencies';
    import { cn, toUrl } from '@/lib/utils';
    import type { AssessedCompetency } from '@/types/framework';

    type PromotionOption = { id: number; name: string };

    let {
        promotions,
        promotion,
        framework,
        competencies,
    }: {
        promotions: PromotionOption[];
        promotion: (PromotionOption & { archived_at: string | null }) | null;
        framework: { id: number; name: string } | null;
        competencies: AssessedCompetency[];
    } = $props();

    let pending = $state<number | null>(null);

    const valueError = $derived(page.props.errors?.value);

    function selfAssess(competency: AssessedCompetency, value: number): void {
        if (!promotion || promotion.archived_at) {
            return;
        }

        pending = competency.id;
        router.visit(
            MyCompetencyController.store([promotion.id, competency.id]),
            {
                data: { value },
                preserveScroll: true,
                onFinish: () => (pending = null),
            },
        );
    }
</script>

<AppHead title="Mes compétences" />

<div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
    <Heading
        title="Mes compétences"
        description={framework
            ? `${promotion?.name} · ${framework.name}`
            : 'Positionnez-vous sur chaque compétence de votre formation.'}
    />

    {#if promotions.length > 1}
        <nav
            class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1"
            aria-label="Choisir une promotion"
        >
            {#each promotions as option (option.id)}
                <Link
                    href={toUrl(index({ query: { promotion: option.id } }))}
                    aria-current={option.id === promotion?.id ? 'page' : undefined}
                    class={cn(
                        'shrink-0 rounded-full border px-4 py-2 text-sm',
                        option.id === promotion?.id
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'text-muted-foreground',
                    )}
                >
                    {option.name}
                </Link>
            {/each}
        </nav>
    {/if}

    {#if !promotion}
        <p class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
            Vous n'êtes inscrit(e) dans aucune promotion pour l'instant.
        </p>
    {:else if !framework}
        <p class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
            Votre formateur n'a pas encore associé de référentiel à cette
            promotion.
        </p>
    {:else}
        {#if promotion.archived_at}
            <p class="rounded-xl border p-4 text-sm text-muted-foreground">
                Cette promotion est archivée : vos évaluations restent
                consultables mais ne peuvent plus être modifiées.
            </p>
        {/if}

        {#if valueError}
            <p class="rounded-xl border border-destructive p-4 text-sm text-destructive">
                {valueError}
            </p>
        {/if}

        <ol class="grid gap-4">
            {#each competencies as competency (competency.id)}
                <li class="flex flex-col gap-4 rounded-xl border p-4">
                    <div class="flex items-start gap-3">
                        <Badge variant="outline" class="shrink-0 font-mono"
                            >{competency.code}</Badge
                        >
                        <div class="min-w-0">
                            <p class="font-medium">{competency.name}</p>
                            {#if competency.description}
                                <p class="text-sm text-muted-foreground">
                                    {competency.description}
                                </p>
                            {/if}
                        </div>
                    </div>

                    <AssessmentSummary {competency} />

                    {#if !promotion.archived_at}
                        <fieldset>
                            <legend class="mb-2 text-sm font-medium">
                                Je me positionne
                            </legend>
                            <div class="flex flex-wrap gap-2">
                                {#each competencyOptions(competency) as option (option.value)}
                                    <button
                                        type="button"
                                        disabled={pending === competency.id}
                                        aria-pressed={competency.self?.value ===
                                            option.value}
                                        onclick={() =>
                                            selfAssess(competency, option.value)}
                                        class={cn(
                                            'min-h-11 flex-1 rounded-lg border px-3 text-sm transition-colors disabled:opacity-50 sm:min-h-9 sm:flex-none',
                                            competency.self?.value === option.value
                                                ? 'border-primary bg-primary text-primary-foreground'
                                                : 'hover:bg-muted',
                                        )}
                                    >
                                        {option.label}
                                    </button>
                                {/each}
                            </div>
                        </fieldset>
                    {/if}
                </li>
            {/each}
        </ol>
    {/if}
</div>

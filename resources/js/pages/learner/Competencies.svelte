<script module lang="ts">
    import { index } from '@/routes/my-competencies';

    export const layout = {
        breadcrumbs: [{ title: 'Mes compétences', href: index() }],
    };
</script>

<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import CircleCheck from '@lucide/svelte/icons/circle-check';
    import Hourglass from '@lucide/svelte/icons/hourglass';
    import MessageSquareQuote from '@lucide/svelte/icons/message-square-quote';
    import PartyPopper from '@lucide/svelte/icons/party-popper';
    import Target from '@lucide/svelte/icons/target';
    import MyCompetencyController from '@/actions/App/Http/Controllers/MyCompetencyController';
    import AppHead from '@/components/AppHead.svelte';
    import Celebrate from '@/components/design/Celebrate.svelte';
    import EmptyState from '@/components/design/EmptyState.svelte';
    import LevelPips from '@/components/design/LevelPips.svelte';
    import PageHeader from '@/components/design/PageHeader.svelte';
    import ProgressBar from '@/components/progress/ProgressBar.svelte';
    import { competencyOptions } from '@/lib/competencies';
    import { cn, toUrl } from '@/lib/utils';
    import type { BadgeInfo } from '@/types/badge';
    import type { AssessedCompetency } from '@/types/framework';

    type PromotionOption = { id: number; name: string };
    type Filter = 'toutes' | 'a-faire' | 'en-attente' | 'acquises';

    let {
        promotions,
        promotion,
        framework,
        competencies,
        celebrations,
        newBadges,
    }: {
        promotions: PromotionOption[];
        promotion: (PromotionOption & { archived_at: string | null }) | null;
        framework: { id: number; name: string } | null;
        competencies: AssessedCompetency[];
        celebrations: { promotion: string; code: string; name: string }[];
        newBadges: BadgeInfo[];
    } = $props();

    const filters: { key: Filter; label: string; matches: (c: AssessedCompetency) => boolean }[] = [
        { key: 'toutes', label: 'Toutes', matches: () => true },
        { key: 'a-faire', label: 'À faire', matches: (c) => c.self === null },
        { key: 'en-attente', label: 'En attente', matches: (c) => c.to_review },
        { key: 'acquises', label: 'Acquises', matches: (c) => c.acquired },
    ];

    function initialFilter(): Filter {
        const requested = new URL(page.url, 'http://localhost').searchParams.get('filtre');

        return filters.some((filter) => filter.key === requested) ? (requested as Filter) : 'toutes';
    }

    let active = $state<Filter>(initialFilter());
    let pending = $state<number | null>(null);

    const visible = $derived(
        competencies.filter(filters.find((filter) => filter.key === active)!.matches),
    );
    const acquired = $derived(competencies.filter((c) => c.acquired).length);
    const percent = $derived(
        competencies.length === 0 ? 0 : Math.round((acquired * 100) / competencies.length),
    );
    const locked = $derived(!promotion || promotion.archived_at !== null);
    const valueError = $derived(page.props.errors?.value);

    function selfAssess(competency: AssessedCompetency, value: number): void {
        if (!promotion || locked || competency.self?.value === value) {
            return;
        }

        pending = competency.id;
        router.visit(MyCompetencyController.store([promotion.id, competency.id]), {
            data: { value },
            preserveScroll: true,
            preserveState: true,
            onFinish: () => (pending = null),
        });
    }

    function status(competency: AssessedCompetency): { label: string; class: string } {
        if (competency.acquired) {
            return { label: 'Acquise', class: 'bg-success-soft text-success' };
        }
        if (competency.to_review) {
            return {
                label: 'En attente',
                class: 'bg-warning-soft text-warning-foreground dark:text-warning',
            };
        }
        if (competency.self === null) {
            return { label: 'À faire', class: 'bg-primary-soft text-primary' };
        }

        return { label: 'En cours', class: 'bg-muted text-muted-foreground' };
    }

    function threshold(competency: AssessedCompetency): number | null {
        return competency.type === 'binary' ? 1 : competency.validation_threshold;
    }
</script>

<AppHead title="Mes compétences" />
<Celebrate {celebrations} badges={newBadges} />

<div class="mx-auto flex w-full max-w-3xl flex-col gap-5 p-4 sm:p-6">
    <PageHeader
        title="Mes compétences"
        subtitle={framework ? `${promotion?.name} · ${framework.name}` : undefined}
    />

    {#if promotions.length > 1}
        <nav class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1" aria-label="Choisir une promotion">
            {#each promotions as option (option.id)}
                <Link
                    href={toUrl(index({ query: { promotion: option.id } }))}
                    aria-current={option.id === promotion?.id ? 'page' : undefined}
                    class={cn(
                        'shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition-colors',
                        option.id === promotion?.id
                            ? 'bg-foreground text-background'
                            : 'bg-muted text-muted-foreground hover:text-foreground',
                    )}
                >
                    {option.name}
                </Link>
            {/each}
        </nav>
    {/if}

    {#if !promotion}
        <EmptyState
            icon={Target}
            title="Pas encore de promotion"
            description="Tu n'es inscrit(e) dans aucune promotion pour l'instant."
        />
    {:else if !framework}
        <EmptyState
            icon={Target}
            title="Référentiel à venir"
            description="Ton formateur n'a pas encore associé de référentiel à cette promotion."
        />
    {:else}
        <div class="surface p-4">
            <ProgressBar {percent} label={`${acquired} / ${competencies.length} acquises`} />
        </div>

        {#if locked}
            <p class="rounded-2xl bg-muted p-4 text-sm text-muted-foreground">
                Cette promotion est archivée : tes évaluations restent consultables mais ne
                peuvent plus être modifiées.
            </p>
        {/if}

        {#if valueError}
            <p class="rounded-2xl bg-destructive/10 p-4 text-sm text-destructive">{valueError}</p>
        {/if}

        <div
            class="sticky top-14 z-20 -mx-4 bg-background/90 px-4 py-2 backdrop-blur-lg md:top-0"
            role="tablist"
            aria-label="Filtrer les compétences"
        >
            <div class="flex gap-1 rounded-2xl bg-muted p-1">
                {#each filters as filter (filter.key)}
                    {@const count = competencies.filter(filter.matches).length}
                    <button
                        type="button"
                        role="tab"
                        aria-selected={active === filter.key}
                        onclick={() => (active = filter.key)}
                        class={cn(
                            'flex min-h-10 flex-1 items-center justify-center gap-1.5 rounded-xl px-2 text-xs font-semibold transition-all sm:text-sm',
                            active === filter.key
                                ? 'bg-card text-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground',
                        )}
                    >
                        {filter.label}
                        <span
                            class={cn(
                                'figures rounded-full px-1.5 text-[11px]',
                                active === filter.key ? 'bg-primary-soft text-primary' : 'bg-background/60',
                            )}>{count}</span
                        >
                    </button>
                {/each}
            </div>
        </div>

        {#if visible.length === 0}
            <EmptyState
                icon={active === 'acquises' ? Target : PartyPopper}
                title={active === 'acquises' ? 'Aucune compétence acquise pour l\'instant' : 'Rien ici, bien joué !'}
                description={active === 'acquises'
                    ? 'Positionne-toi : ton formateur validera ensuite tes compétences.'
                    : 'Tout est à jour dans cette catégorie.'}
            />
        {:else}
            <ol class="grid gap-3">
                {#each visible as competency (competency.id)}
                    {@const state = status(competency)}
                    <li
                        class={cn(
                            'surface flex min-w-0 flex-col gap-4 p-4 transition-shadow',
                            competency.acquired && 'ring-2 ring-success/40',
                        )}
                    >
                        <div class="flex items-start gap-3">
                            <span
                                class={cn(
                                    'grid size-10 shrink-0 place-items-center rounded-xl font-mono text-xs font-bold',
                                    competency.acquired
                                        ? 'bg-success text-success-foreground'
                                        : 'bg-primary-soft text-primary',
                                )}
                            >
                                {#if competency.acquired}
                                    <CircleCheck class="size-5" />
                                {:else}
                                    {competency.code}
                                {/if}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="leading-snug font-semibold">{competency.name}</p>
                                {#if competency.description}
                                    <p class="mt-0.5 line-clamp-2 text-sm text-muted-foreground">
                                        {competency.description}
                                    </p>
                                {/if}
                            </div>
                            <span
                                class={cn(
                                    'inline-flex shrink-0 items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold',
                                    state.class,
                                )}
                            >
                                {#if competency.to_review && !competency.acquired}
                                    <Hourglass class="size-3" />
                                {/if}
                                {state.label}
                            </span>
                        </div>

                        <LevelPips
                            options={competencyOptions(competency)}
                            selected={competency.self?.value ?? null}
                            validated={competency.trainer?.value ?? null}
                            threshold={threshold(competency)}
                            disabled={locked || pending === competency.id}
                            label={`Mon niveau pour ${competency.code}`}
                            onselect={locked ? undefined : (value) => selfAssess(competency, value)}
                        />

                        {#if competency.trainer?.comment}
                            <p class="flex gap-2 rounded-xl bg-muted/60 p-3 text-sm">
                                <MessageSquareQuote class="mt-0.5 size-4 shrink-0 text-primary" />
                                <span class="text-muted-foreground">{competency.trainer.comment}</span>
                            </p>
                        {/if}
                    </li>
                {/each}
            </ol>

            <p class="text-center text-xs text-muted-foreground">
                Touche un niveau pour te positionner. 🏁 = niveau à atteindre, ✓ = niveau validé
                par ton formateur.
            </p>
        {/if}
    {/if}
</div>

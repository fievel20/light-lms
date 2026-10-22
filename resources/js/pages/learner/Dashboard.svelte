<script module lang="ts">
    import { dashboard } from '@/routes';

    export const layout = {
        breadcrumbs: [{ title: 'Accueil', href: dashboard() }],
    };
</script>

<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import Hourglass from '@lucide/svelte/icons/hourglass';
    import PencilLine from '@lucide/svelte/icons/pencil-line';
    import Sparkles from '@lucide/svelte/icons/sparkles';
    import Trophy from '@lucide/svelte/icons/trophy';
    import AppHead from '@/components/AppHead.svelte';
    import Celebrate from '@/components/design/Celebrate.svelte';
    import EmptyState from '@/components/design/EmptyState.svelte';
    import PageHeader from '@/components/design/PageHeader.svelte';
    import ProgressRing from '@/components/design/ProgressRing.svelte';
    import StatTile from '@/components/design/StatTile.svelte';
    import { Button } from '@/components/ui/button';
    import { toUrl } from '@/lib/utils';
    import { index as myCompetencies } from '@/routes/my-competencies';

    type PromotionProgress = {
        id: number;
        name: string;
        archived_at: string | null;
        framework: string | null;
        total: number;
        acquired: number;
        self_assessed: number;
        to_review: number;
        percent: number;
    };

    let {
        promotions,
        celebrations,
    }: {
        promotions: PromotionProgress[];
        celebrations: { promotion: string; code: string; name: string }[];
    } = $props();

    const firstName = $derived(page.props.auth.user.first_name);
    const current = $derived(promotions.filter((promotion) => !promotion.archived_at));
    const archived = $derived(promotions.filter((promotion) => promotion.archived_at));

    function competenciesUrl(promotion: PromotionProgress, filter?: string): string {
        return toUrl(
            myCompetencies({
                query: { promotion: promotion.id, ...(filter ? { filtre: filter } : {}) },
            }),
        );
    }

    function plural(count: number, word: string): string {
        return `${count} ${word}${count > 1 ? 's' : ''}`;
    }
</script>

<AppHead title="Accueil" />
<Celebrate {celebrations} />

<div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 sm:p-6">
    <PageHeader
        title={`Salut ${firstName} 👋`}
        subtitle="Voici où tu en es dans tes compétences."
    />

    {#if promotions.length === 0}
        <EmptyState
            icon={Sparkles}
            title="Pas encore de promotion"
            description="Ton formateur va bientôt t'inscrire. Tu retrouveras ici ta progression."
        />
    {/if}

    {#each current as promotion (promotion.id)}
        {@const toDo = promotion.total - promotion.self_assessed}
        <section class="surface flex flex-col gap-6 p-5 sm:p-6" aria-label={promotion.name}>
            <div class="flex items-center gap-5">
                <ProgressRing
                    percent={promotion.framework ? promotion.percent : 0}
                    size={120}
                    label={`Progression dans ${promotion.name}`}
                >
                    <span class="flex flex-col items-center leading-none">
                        <span class="figures text-3xl font-extrabold">{promotion.percent}%</span>
                        <span class="mt-1 text-[11px] font-medium text-muted-foreground">acquis</span>
                    </span>
                </ProgressRing>
                <div class="min-w-0 space-y-1">
                    <p class="text-xs font-semibold tracking-wide text-primary uppercase">
                        {promotion.name}
                    </p>
                    <h2 class="text-lg leading-tight font-bold">
                        {promotion.framework ?? 'Référentiel à venir'}
                    </h2>
                    {#if promotion.framework}
                        <p class="text-sm text-muted-foreground">
                            {promotion.acquired} / {plural(promotion.total, 'compétence')}
                            validée{promotion.acquired > 1 ? 's' : ''}
                        </p>
                    {/if}
                </div>
            </div>

            {#if promotion.framework}
                <div class="grid grid-cols-3 gap-3">
                    <StatTile label="Acquises" value={promotion.acquired} icon={Trophy} tone="success" />
                    <StatTile
                        label="Autoévaluées"
                        value={`${promotion.self_assessed}/${promotion.total}`}
                        icon={PencilLine}
                        tone="primary"
                    />
                    <StatTile
                        label="En attente"
                        value={promotion.to_review}
                        icon={Hourglass}
                        tone="warning"
                    />
                </div>

                <Button asChild class="h-12 w-full text-base">
                    {#snippet children(props)}
                        <Link
                            {...props}
                            href={competenciesUrl(promotion, toDo > 0 ? 'a-faire' : undefined)}
                        >
                            {#if promotion.percent >= 100}
                                Toutes tes compétences sont acquises 🏆
                            {:else if toDo > 0}
                                {plural(toDo, 'compétence')} t'attend{toDo > 1 ? 'ent' : ''}
                            {:else}
                                Voir mes compétences
                            {/if}
                            <ArrowRight class="size-4" />
                        </Link>
                    {/snippet}
                </Button>
            {/if}
        </section>
    {/each}

    {#if archived.length > 0}
        <section class="space-y-3">
            <h2 class="text-sm font-semibold text-muted-foreground">Promotions terminées</h2>
            <ul class="grid gap-2">
                {#each archived as promotion (promotion.id)}
                    <li>
                        <Link
                            href={competenciesUrl(promotion)}
                            class="surface flex items-center justify-between gap-3 p-4 transition-colors hover:bg-muted/40"
                        >
                            <span class="min-w-0 truncate font-medium">{promotion.name}</span>
                            <span class="figures shrink-0 text-sm font-bold text-muted-foreground"
                                >{promotion.percent}%</span
                            >
                        </Link>
                    </li>
                {/each}
            </ul>
        </section>
    {/if}
</div>

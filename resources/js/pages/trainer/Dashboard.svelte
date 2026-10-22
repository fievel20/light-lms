<script module lang="ts">
    import { dashboard } from '@/routes';

    export const layout = {
        breadcrumbs: [{ title: 'Tableau de bord', href: dashboard() }],
    };
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ChartColumn from '@lucide/svelte/icons/chart-column';
    import Inbox from '@lucide/svelte/icons/inbox';
    import Mail from '@lucide/svelte/icons/mail';
    import Users from '@lucide/svelte/icons/users';
    import StatTile from '@/components/design/StatTile.svelte';
    import Plus from '@lucide/svelte/icons/plus';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import ProgressBar from '@/components/progress/ProgressBar.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { formatDate } from '@/lib/dates';
    import { toUrl } from '@/lib/utils';
    import { create, progress, show } from '@/routes/promotions';

    type PromotionSummary = {
        id: number;
        name: string;
        framework: string | null;
        starts_on: string;
        ends_on: string;
        learners: number;
        competencies: number;
        average_percent: number;
        to_review: number;
    };

    let {
        promotions,
        pendingInvitations,
    }: {
        promotions: PromotionSummary[];
        pendingInvitations: number;
    } = $props();

    const toReview = $derived(
        promotions.reduce((sum, promotion) => sum + promotion.to_review, 0),
    );
</script>

<AppHead title="Tableau de bord" />

<div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4">
    <Heading title="Tableau de bord" description="Tes promotions en cours." />

    <div class="grid grid-cols-3 gap-3">
        <StatTile label="Promotions" value={promotions.length} icon={Users} tone="primary" />
        <StatTile
            label="À examiner"
            value={toReview}
            icon={Inbox}
            tone={toReview > 0 ? 'warning' : 'success'}
        />
        <StatTile label="Invitations" value={pendingInvitations} icon={Mail} />
    </div>

    {#if promotions.length === 0}
        <div
            class="flex flex-col items-center gap-4 rounded-2xl border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            Aucune promotion en cours.
            <Button asChild class="h-11 sm:h-9">
                {#snippet children(props)}
                    <Link {...props} href={toUrl(create())}>
                        <Plus class="size-4" />
                        Créer une promotion
                    </Link>
                {/snippet}
            </Button>
        </div>
    {:else}
        <ul class="grid gap-4 md:grid-cols-2">
            {#each promotions as promotion (promotion.id)}
                <li class="flex min-w-0 flex-col gap-4 surface p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <Link
                                href={toUrl(show(promotion.id))}
                                class="font-medium underline-offset-4 hover:underline"
                                >{promotion.name}</Link
                            >
                            <p class="text-sm text-muted-foreground">
                                {formatDate(promotion.starts_on)} → {formatDate(
                                    promotion.ends_on,
                                )}
                            </p>
                            <p class="truncate text-sm text-muted-foreground">
                                {promotion.framework ?? 'Aucun référentiel'}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {promotion.learners} apprenant{promotion.learners >
                                1
                                    ? 's'
                                    : ''}
                            </p>
                        </div>
                        {#if promotion.to_review > 0}
                            <Badge>{promotion.to_review} à examiner</Badge>
                        {/if}
                    </div>

                    {#if promotion.framework}
                        <ProgressBar
                            percent={promotion.average_percent}
                            label="Progression moyenne"
                        />

                        <Button
                            variant="outline"
                            asChild
                            class="mt-auto h-11 sm:h-9"
                        >
                            {#snippet children(props)}
                                <Link
                                    {...props}
                                    href={toUrl(progress(promotion.id))}
                                >
                                    <ChartColumn class="size-4" />
                                    Suivi de la promotion
                                </Link>
                            {/snippet}
                        </Button>
                    {/if}
                </li>
            {/each}
        </ul>
    {/if}
</div>

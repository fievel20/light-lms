<script module lang="ts">
    import { dashboard } from '@/routes';

    export const layout = {
        breadcrumbs: [{ title: 'Tableau de bord', href: dashboard() }],
    };
</script>

<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import ProgressBar from '@/components/progress/ProgressBar.svelte';
    import { Badge } from '@/components/ui/badge';
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

    let { promotions }: { promotions: PromotionProgress[] } = $props();

    const firstName = $derived(page.props.auth.user.first_name);
</script>

<AppHead title="Tableau de bord" />

<div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
    <Heading
        title={`Bonjour ${firstName} !`}
        description="Voici où vous en êtes dans vos compétences."
    />

    {#if promotions.length === 0}
        <p
            class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            Vous n'êtes inscrit(e) dans aucune promotion pour l'instant.
        </p>
    {:else}
        <ul class="grid gap-4">
            {#each promotions as promotion (promotion.id)}
                <li class="flex min-w-0 flex-col gap-4 rounded-xl border p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="min-w-0">
                            <p class="font-medium">{promotion.name}</p>
                            <p class="text-sm text-muted-foreground">
                                {promotion.framework ?? 'Référentiel à venir'}
                            </p>
                        </div>
                        {#if promotion.archived_at}
                            <Badge variant="secondary">Archivée</Badge>
                        {/if}
                    </div>

                    {#if promotion.framework}
                        <ProgressBar
                            percent={promotion.percent}
                            label={`${promotion.acquired} compétence${promotion.acquired > 1 ? 's' : ''} validée${promotion.acquired > 1 ? 's' : ''} sur ${promotion.total}`}
                        />

                        <dl class="grid grid-cols-2 gap-2 text-sm">
                            <div class="rounded-lg bg-muted/50 p-3">
                                <dt class="text-xs text-muted-foreground">
                                    Autoévaluées
                                </dt>
                                <dd class="font-semibold tabular-nums">
                                    {promotion.self_assessed} / {promotion.total}
                                </dd>
                            </div>
                            <div class="rounded-lg bg-muted/50 p-3">
                                <dt class="text-xs text-muted-foreground">
                                    En attente du formateur
                                </dt>
                                <dd class="font-semibold tabular-nums">
                                    {promotion.to_review}
                                </dd>
                            </div>
                        </dl>

                        <Button asChild class="h-11 sm:h-9 sm:self-end">
                            {#snippet children(props)}
                                <Link
                                    {...props}
                                    href={toUrl(
                                        myCompetencies({
                                            query: { promotion: promotion.id },
                                        }),
                                    )}
                                >
                                    {promotion.archived_at
                                        ? 'Voir mes compétences'
                                        : 'Me positionner'}
                                    <ArrowRight class="size-4" />
                                </Link>
                            {/snippet}
                        </Button>
                    {/if}
                </li>
            {/each}
        </ul>
    {/if}
</div>

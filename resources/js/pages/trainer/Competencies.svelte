<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ChevronRight from '@lucide/svelte/icons/chevron-right';
    import Library from '@lucide/svelte/icons/library';
    import AppHead from '@/components/AppHead.svelte';
    import EmptyState from '@/components/design/EmptyState.svelte';
    import PageHeader from '@/components/design/PageHeader.svelte';
    import { toUrl } from '@/lib/utils';
    import { show } from '@/routes/promotions/competencies';

    type Row = { id: number; code: string; name: string; acquired_count: number; to_review: number };

    let {
        promotion,
        competencies,
        learnersCount,
    }: {
        promotion: { id: number; name: string };
        competencies: Row[];
        learnersCount: number;
    } = $props();
</script>

<AppHead title="Par compétence" />

<div class="mx-auto flex w-full max-w-3xl flex-col gap-5 p-4 sm:p-6">
    <PageHeader title="Par compétence" subtitle={`${promotion.name} · ${learnersCount} apprenants`} />

    {#if competencies.length === 0}
        <EmptyState icon={Library} title="Aucune compétence" description="Affecte un référentiel à cette promotion." />
    {:else}
        <ol class="divide-y divide-border/70">
            {#each competencies as competency (competency.id)}
                <li>
                    <Link
                        href={toUrl(show([promotion.id, competency.id]))}
                        class="-mx-3 flex items-center gap-3 rounded-xl px-3 py-3.5 transition-colors hover:bg-muted/60"
                    >
                        <span class="w-12 shrink-0 font-mono text-xs font-semibold text-muted-foreground">{competency.code}</span>
                        <span class="min-w-0 flex-1 truncate font-medium">{competency.name}</span>
                        {#if competency.to_review > 0}
                            <span class="flex shrink-0 items-center gap-1 text-xs font-semibold text-warning-foreground dark:text-warning">
                                <span class="size-2 rounded-full bg-warning"></span>
                                {competency.to_review}
                            </span>
                        {/if}
                        <span class="figures w-12 shrink-0 text-right text-sm text-muted-foreground"
                            >{competency.acquired_count}/{learnersCount}</span
                        >
                        <ChevronRight class="size-4 shrink-0 text-muted-foreground/60" />
                    </Link>
                </li>
            {/each}
        </ol>
    {/if}
</div>

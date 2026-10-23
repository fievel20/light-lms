<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ChevronRight from '@lucide/svelte/icons/chevron-right';
    import Users from '@lucide/svelte/icons/users';
    import AppHead from '@/components/AppHead.svelte';
    import EmptyState from '@/components/design/EmptyState.svelte';
    import PageHeader from '@/components/design/PageHeader.svelte';
    import { cn, toUrl } from '@/lib/utils';
    import { show } from '@/routes/promotions/learners';

    type Row = {
        id: number;
        name: string;
        activated: boolean;
        percent: number;
        acquired: number;
        total: number;
        to_review: number;
    };

    let {
        promotion,
        learners,
    }: {
        promotion: { id: number; name: string };
        learners: Row[];
    } = $props();
</script>

<AppHead title="Par apprenant" />

<div class="mx-auto flex w-full max-w-3xl flex-col gap-5 p-4 sm:p-6">
    <PageHeader eyebrow="Promotion" title="Par apprenant" subtitle={`${promotion.name} · ${learners.length} apprenants`} />

    {#if learners.length === 0}
        <EmptyState icon={Users} title="Aucun apprenant" description="Inscris des apprenants depuis la fiche de la promotion." />
    {:else}
        <ol class="divide-y divide-border/70">
            {#each learners as learner (learner.id)}
                <li>
                    <Link
                        href={toUrl(show([promotion.id, learner.id]))}
                        class="-mx-3 flex items-center gap-3 rounded-xl px-3 py-3.5 transition-colors hover:bg-muted/60"
                    >
                        <span class="min-w-0 flex-1">
                            <span class={cn('block truncate font-medium', !learner.activated && 'text-muted-foreground')}
                                >{learner.name}</span
                            >
                            {#if !learner.activated}
                                <span class="block text-xs text-muted-foreground">invité</span>
                            {/if}
                        </span>
                        {#if learner.to_review > 0}
                            <span class="flex shrink-0 items-center gap-1 text-xs font-semibold text-warning-foreground dark:text-warning">
                                <span class="size-2 rounded-full bg-warning"></span>
                                {learner.to_review}
                            </span>
                        {/if}
                        <span class="hidden h-1.5 w-20 shrink-0 overflow-hidden rounded-full bg-muted sm:block">
                            <span class="block h-full rounded-full bg-primary" style:width={`${learner.percent}%`}></span>
                        </span>
                        <span class="figures w-10 shrink-0 text-right text-sm text-muted-foreground">{learner.percent}%</span>
                        <ChevronRight class="size-4 shrink-0 text-muted-foreground/60" />
                    </Link>
                </li>
            {/each}
        </ol>
    {/if}
</div>

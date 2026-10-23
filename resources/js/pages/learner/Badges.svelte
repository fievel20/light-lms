<script module lang="ts">
    import { index } from '@/routes/my-badges';

    export const layout = {
        breadcrumbs: [{ title: 'Mes badges', href: index() }],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import BadgeMedal from '@/components/design/BadgeMedal.svelte';
    import PageHeader from '@/components/design/PageHeader.svelte';
    import ProgressBar from '@/components/progress/ProgressBar.svelte';
    import { formatDate } from '@/lib/dates';
    import { cn } from '@/lib/utils';
    import type { BadgeProgress } from '@/types/badge';

    let { badges }: { badges: BadgeProgress[] } = $props();

    const earned = $derived(badges.filter((badge) => badge.earned).length);
</script>

<AppHead title="Mes badges" />

<div class="mx-auto flex w-full max-w-3xl flex-col gap-5 p-4 sm:p-6">
    <PageHeader
        eyebrow="Récompenses"
        title="Mes badges"
        subtitle="Débloque-les en avançant dans tes compétences."
    />

    <div class="surface p-4">
        <ProgressBar
            percent={Math.round((earned * 100) / badges.length)}
            label={`${earned} / ${badges.length} badges débloqués`}
        />
    </div>

    <ul class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        {#each badges as badge (badge.key)}
            <li
                class={cn(
                    'surface flex flex-col items-center gap-3 p-4 text-center',
                    !badge.earned && 'opacity-75',
                )}
            >
                <BadgeMedal badgeKey={badge.key} earned={badge.earned} size="lg" />
                <div class="space-y-1">
                    <p class="leading-tight font-bold">{badge.label}</p>
                    <p class="text-xs text-muted-foreground">{badge.description}</p>
                </div>
                <p
                    class={cn(
                        'mt-auto rounded-full px-2.5 py-0.5 text-[11px] font-semibold',
                        badge.earned
                            ? 'bg-success-soft text-success-ink'
                            : 'bg-muted text-muted-foreground',
                    )}
                >
                    {badge.earned && badge.earned_at
                        ? `Obtenu le ${formatDate(badge.earned_at)}`
                        : 'À débloquer'}
                </p>
            </li>
        {/each}
    </ul>
</div>

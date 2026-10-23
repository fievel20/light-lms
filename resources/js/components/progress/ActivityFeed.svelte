<script lang="ts">
    import CircleCheck from '@lucide/svelte/icons/circle-check';
    import CircleDot from '@lucide/svelte/icons/circle-dot';
    import BadgeMedal from '@/components/design/BadgeMedal.svelte';
    import { formatRelative } from '@/lib/dates';
    import { cn } from '@/lib/utils';
    import type { ActivityItem } from '@/types/badge';

    let { items }: { items: ActivityItem[] } = $props();
</script>

<ol class="relative grid gap-4 before:absolute before:top-2 before:bottom-2 before:left-[17px] before:w-px before:bg-border">
    {#each items as item, index (index)}
        <li class="relative flex gap-3">
            {#if item.type === 'badge'}
                <BadgeMedal badgeKey={item.badge.key} size="sm" />
                <div class="min-w-0 pt-0.5">
                    <p class="text-sm">
                        Badge débloqué : <span class="font-bold">{item.badge.label}</span>
                    </p>
                    <p class="text-xs text-muted-foreground">{formatRelative(item.at)}</p>
                </div>
            {:else}
                <span
                    class={cn(
                        'relative z-10 grid size-9 shrink-0 place-items-center rounded-xl',
                        item.acquired ? 'bg-success text-success-foreground' : 'bg-primary-soft text-primary',
                    )}
                >
                    {#if item.acquired}
                        <CircleCheck class="size-4" />
                    {:else}
                        <CircleDot class="size-4" />
                    {/if}
                </span>
                <div class="min-w-0 pt-0.5">
                    <p class="text-sm">
                        Ton formateur a validé <span class="font-bold">{item.code}</span> :
                        <span class={cn('font-semibold', item.acquired ? 'text-success-ink' : 'text-primary')}
                            >{item.level}</span
                        >
                    </p>
                    <p class="truncate text-xs text-muted-foreground">
                        {item.competency} · {formatRelative(item.at)}
                    </p>
                    {#if item.comment}
                        <p class="mt-1.5 rounded-lg bg-muted/60 px-2.5 py-1.5 text-xs text-muted-foreground">
                            « {item.comment} »
                        </p>
                    {/if}
                </div>
            {/if}
        </li>
    {/each}
</ol>

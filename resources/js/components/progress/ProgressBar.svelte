<script lang="ts">
    import { onMount } from 'svelte';
    import { cn } from '@/lib/utils';

    let {
        percent,
        label,
    }: {
        percent: number;
        label: string;
    } = $props();

    // Start empty and fill on mount, so the bar animates.
    let shown = $state(0);
    onMount(() => {
        requestAnimationFrame(() => (shown = 1));
    });
</script>

<div class="grid gap-1.5">
    <div class="flex items-baseline justify-between gap-2 text-sm">
        <span class="truncate text-muted-foreground">{label}</span>
        <span class="figures font-bold">{percent}%</span>
    </div>
    <div
        class="h-2.5 overflow-hidden rounded-full bg-muted"
        role="progressbar"
        aria-label={label}
        aria-valuemin={0}
        aria-valuemax={100}
        aria-valuenow={percent}
    >
        <div
            class={cn(
                'h-full rounded-full transition-[width] duration-700 ease-out',
                percent >= 100 ? 'bg-success' : 'bg-primary',
            )}
            style:width={`${percent * shown}%`}
        ></div>
    </div>
</div>

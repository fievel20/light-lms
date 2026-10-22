<script lang="ts">
    import { onMount } from 'svelte';
    import type { Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    let {
        percent,
        size = 112,
        stroke = 10,
        label,
        complete = percent >= 100,
        class: className = '',
        children,
    }: {
        percent: number;
        size?: number;
        stroke?: number;
        label: string;
        complete?: boolean;
        class?: string;
        children?: Snippet;
    } = $props();

    const radius = $derived((size - stroke) / 2);
    const circumference = $derived(2 * Math.PI * radius);

    // Start empty and fill on mount, so the ring animates.
    let shown = $state(0);
    onMount(() => {
        requestAnimationFrame(() => (shown = 1));
    });

    const offset = $derived(
        circumference * (1 - (Math.min(100, Math.max(0, percent)) / 100) * shown),
    );
</script>

<div
    class={cn('relative inline-grid shrink-0 place-items-center', className)}
    style:width={`${size}px`}
    style:height={`${size}px`}
    role="progressbar"
    aria-label={label}
    aria-valuemin={0}
    aria-valuemax={100}
    aria-valuenow={percent}
>
    <svg width={size} height={size} class="-rotate-90" aria-hidden="true">
        <circle
            cx={size / 2}
            cy={size / 2}
            r={radius}
            fill="none"
            stroke-width={stroke}
            class="stroke-muted"
        />
        <circle
            cx={size / 2}
            cy={size / 2}
            r={radius}
            fill="none"
            stroke-width={stroke}
            stroke-linecap="round"
            stroke-dasharray={circumference}
            stroke-dashoffset={offset}
            class={cn(
                'transition-[stroke-dashoffset] duration-1000 ease-out',
                complete ? 'stroke-success' : 'stroke-primary',
            )}
        />
    </svg>
    <div class="absolute inset-0 grid place-items-center text-center">
        {#if children}
            {@render children()}
        {:else}
            <span class="figures text-2xl font-extrabold">{percent}%</span>
        {/if}
    </div>
</div>

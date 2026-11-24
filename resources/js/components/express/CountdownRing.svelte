<script lang="ts">
    import { cn } from '@/lib/utils';

    let {
        left,
        total,
        class: className = '',
    }: { left: number; total: number; class?: string } = $props();

    // Full at the start, empty at zero.
    const RADIUS = 26;
    const CIRCUMFERENCE = 2 * Math.PI * RADIUS;
    const offset = $derived(
        total > 0 ? CIRCUMFERENCE * (1 - left / total) : CIRCUMFERENCE,
    );
</script>

<div
    class={cn('relative grid shrink-0 place-items-center', className)}
    role="timer"
    aria-label={`${left} secondes restantes`}
>
    <svg viewBox="0 0 60 60" class="absolute inset-0 -rotate-90">
        <circle
            cx="30"
            cy="30"
            r={RADIUS}
            fill="none"
            stroke-width="6"
            class="stroke-white/15"
        />
        <circle
            cx="30"
            cy="30"
            r={RADIUS}
            fill="none"
            stroke-width="6"
            stroke-linecap="round"
            stroke-dasharray={CIRCUMFERENCE}
            stroke-dashoffset={offset}
            class={cn(
                'transition-[stroke-dashoffset] duration-500 ease-linear',
                left <= 5 ? 'stroke-destructive' : 'stroke-cta',
            )}
        />
    </svg>
    <span class="relative figures font-bold">{left}</span>
</div>

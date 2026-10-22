<script lang="ts">
    import Check from '@lucide/svelte/icons/check';
    import Flag from '@lucide/svelte/icons/flag';
    import { cn } from '@/lib/utils';
    import type { ScaleLevel } from '@/types/framework';

    let {
        options,
        selected = null,
        validated = null,
        threshold = null,
        disabled = false,
        label,
        onselect,
    }: {
        options: ScaleLevel[];
        /** The learner's current self-assessment. */
        selected?: number | null;
        /** The trainer's current validation. */
        validated?: number | null;
        /** Level from which the competency is acquired. */
        threshold?: number | null;
        disabled?: boolean;
        label: string;
        onselect?: (value: number) => void;
    } = $props();
</script>

<div role="group" aria-label={label} class="flex flex-wrap gap-1.5">
    {#each options as option (option.value)}
        {@const isSelected = selected === option.value}
        {@const isValidated = validated === option.value}
        {@const isThreshold = threshold === option.value}
        <button
            type="button"
            disabled={disabled || !onselect}
            aria-pressed={isSelected}
            onclick={() => onselect?.(option.value)}
            class={cn(
                'relative flex min-h-11 flex-1 basis-20 items-center justify-center gap-1 rounded-xl px-3 text-sm font-semibold transition-all sm:min-h-9 sm:flex-none',
                isSelected
                    ? 'bg-primary text-primary-foreground shadow-md shadow-primary/25'
                    : 'bg-muted text-muted-foreground enabled:hover:bg-primary-soft enabled:hover:text-primary',
                'enabled:active:scale-95 disabled:cursor-default',
            )}
        >
            {#if isThreshold}
                <Flag class="size-3.5 shrink-0" aria-label="seuil d'acquisition" />
            {/if}
            <span class="truncate">{option.label}</span>
            {#if isValidated}
                <span
                    class="absolute -top-1.5 -right-1.5 grid size-5 place-items-center rounded-full bg-success text-success-foreground ring-2 ring-card"
                    title="Niveau validé par ton formateur"
                >
                    <Check class="size-3" aria-label="validé par le formateur" />
                </span>
            {/if}
        </button>
    {/each}
</div>

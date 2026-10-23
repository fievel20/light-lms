<script lang="ts">
    import type { Component } from 'svelte';
    import { cn } from '@/lib/utils';

    type Tone = 'default' | 'primary' | 'success' | 'warning';

    let {
        label,
        value,
        icon: Icon,
        tone = 'default',
        hint,
    }: {
        label: string;
        value: string | number;
        icon?: Component<{ class?: string }>;
        tone?: Tone;
        hint?: string;
    } = $props();

    const toneClass: Record<Tone, string> = {
        default: 'bg-muted text-muted-foreground',
        primary: 'bg-primary-soft text-primary',
        success: 'bg-success-soft text-success-ink',
        warning: 'bg-warning-soft text-warning-foreground dark:text-warning',
    };
</script>

<div class="surface flex flex-col gap-3 p-4">
    {#if Icon}
        <span class={cn('grid size-9 place-items-center rounded-xl', toneClass[tone])}>
            <Icon class="size-4" />
        </span>
    {/if}
    <div>
        <p class="figures text-2xl leading-none font-semibold sm:text-3xl">{value}</p>
        <p class="eyebrow mt-1.5 hyphens-auto [overflow-wrap:anywhere]" lang="fr">{label}</p>
        {#if hint}
            <p class="mt-0.5 text-xs text-muted-foreground/80">{hint}</p>
        {/if}
    </div>
</div>

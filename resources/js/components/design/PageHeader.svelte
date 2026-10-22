<script lang="ts">
    import type { Snippet } from 'svelte';

    let {
        title,
        subtitle,
        eyebrow,
        actions,
    }: {
        title: string;
        subtitle?: string;
        eyebrow?: Snippet;
        actions?: Snippet;
    } = $props();
</script>

<header class="flex flex-wrap items-end justify-between gap-4">
    <div class="min-w-0 space-y-1">
        {@render eyebrow?.()}
        <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">{title}</h1>
        {#if subtitle}
            <p class="text-sm text-muted-foreground">{subtitle}</p>
        {/if}
    </div>
    {#if actions}
        <div class="flex w-full gap-2 sm:w-auto">
            {@render actions()}
        </div>
    {/if}
</header>

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
        /** Small monospace label shown as "// LABEL" above the title. */
        eyebrow?: string;
        actions?: Snippet;
    } = $props();
</script>

<header class="flex flex-wrap items-end justify-between gap-4">
    <div class="min-w-0 space-y-2">
        {#if eyebrow}
            <p class="eyebrow">// {eyebrow}</p>
        {/if}
        <h1 class="display-title text-3xl break-words sm:text-4xl">{title}</h1>
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

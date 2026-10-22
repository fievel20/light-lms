<script lang="ts">
    import type { Component, Snippet } from 'svelte';

    let {
        icon: Icon,
        title,
        description,
        action,
    }: {
        icon?: Component<{ class?: string }>;
        title: string;
        description?: string;
        action?: Snippet;
    } = $props();
</script>

<div class="flex flex-col items-center gap-3 rounded-2xl border border-dashed px-6 py-12 text-center">
    {#if Icon}
        <span class="grid size-12 place-items-center rounded-2xl bg-primary-soft text-primary">
            <Icon class="size-6" />
        </span>
    {/if}
    <p class="font-semibold">{title}</p>
    {#if description}
        <p class="max-w-sm text-sm text-muted-foreground">{description}</p>
    {/if}
    {@render action?.()}
</div>

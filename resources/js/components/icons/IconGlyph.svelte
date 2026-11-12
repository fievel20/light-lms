<script lang="ts">
    import type { Icon } from '@/types/icon';

    // Same markup as the Markdown rendering (IconRenderer), sized by the font.
    let {
        icon,
        class: className = '',
    }: { icon: Pick<Icon, 'name' | 'url' | 'monochrome'>; class?: string } =
        $props();
</script>

{#if icon.monochrome}
    <span
        class={['icon icon-mono', className]}
        role="img"
        aria-label={icon.name}
        style:--icon={`url("${icon.url}")`}
    ></span>
{:else}
    <img class={['icon', className]} src={icon.url} alt={icon.name} />
{/if}

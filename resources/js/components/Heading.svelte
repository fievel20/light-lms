<script lang="ts">
    let {
        title,
        description = '',
        variant = 'default',
    }: {
        title: string;
        description?: string;
        variant?: 'default' | 'small';
    } = $props();
</script>

<header class={variant === 'small' ? '' : 'mb-6 space-y-1'}>
    <h2
        class={variant === 'small'
            ? 'mb-0.5 text-base font-semibold'
            : 'display-title text-3xl sm:text-4xl'}
    >
        {title}
    </h2>
    {#if description}
        <p class="text-sm text-muted-foreground">{description}</p>
    {/if}
</header>

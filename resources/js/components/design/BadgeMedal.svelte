<script lang="ts">
    import Lock from '@lucide/svelte/icons/lock';
    import { badgeStyle } from '@/lib/badges';
    import { cn } from '@/lib/utils';

    let {
        badgeKey,
        earned = true,
        size = 'md',
    }: {
        badgeKey: string;
        earned?: boolean;
        size?: 'sm' | 'md' | 'lg';
    } = $props();

    const style = $derived(badgeStyle(badgeKey));
    const sizes = {
        sm: { box: 'size-9 rounded-xl', icon: 'size-4' },
        md: { box: 'size-14 rounded-2xl', icon: 'size-7' },
        lg: { box: 'size-20 rounded-3xl', icon: 'size-10' },
    };
</script>

<span
    class={cn(
        'relative grid shrink-0 place-items-center',
        sizes[size].box,
        earned
            ? `bg-linear-to-br text-white shadow-lg ${style.gradient}`
            : 'bg-muted text-muted-foreground/50',
    )}
    aria-hidden="true"
>
    <style.icon class={sizes[size].icon} />
    {#if !earned}
        <span
            class="absolute -right-1 -bottom-1 grid size-5 place-items-center rounded-full bg-card text-muted-foreground ring-2 ring-card"
        >
            <Lock class="size-3" />
        </span>
    {/if}
</span>

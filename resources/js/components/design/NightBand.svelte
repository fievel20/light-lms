<script lang="ts">
    import type { Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    let {
        class: className = '',
        children,
    }: {
        class?: string;
        children?: Snippet;
    } = $props();
</script>

<!--
    Indigo night band with a violet glow. The "dark" class switches every
    design token inside the band to its dark value (cards, text, rules).
-->
<section
    class={cn('dark relative isolate overflow-hidden rounded-2xl bg-night text-foreground', className)}
>
    <div
        class="pointer-events-none absolute inset-0 -z-10"
        style="background: radial-gradient(60% 70% at 85% 0%, rgb(124 92 255 / 0.45), transparent 70%), radial-gradient(40% 60% at 0% 100%, rgb(200 255 26 / 0.12), transparent 70%);"
        aria-hidden="true"
    ></div>
    {@render children?.()}
</section>

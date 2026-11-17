<script lang="ts">
    import type { Snippet } from 'svelte';
    import ObserverBanner from '@/components/observer/ObserverBanner.svelte';
    import { Toaster } from '@/components/ui/sonner';

    // Full screen, without the menus: the quiz only.
    let { children }: { children?: Snippet } = $props();
</script>

<div class="flex min-h-svh flex-col bg-background">
    <ObserverBanner />
    {@render children?.()}
</div>
<Toaster position="top-center" />

<script lang="ts">
    import type { Snippet } from 'svelte';
    import { Toaster } from '@/components/ui/sonner';

    // Quiz express: no menus, the night theme of the login page, for the
    // projected screen and for the participants' phones alike.
    let { children }: { children?: Snippet } = $props();
</script>

<div
    class="dark relative isolate flex min-h-svh flex-col overflow-hidden bg-night text-foreground"
>
    <div
        class="pointer-events-none absolute inset-0 -z-10"
        style="background: radial-gradient(55% 45% at 50% 0%, rgb(124 92 255 / 0.45), transparent 70%), radial-gradient(35% 40% at 0% 100%, rgb(200 255 26 / 0.12), transparent 70%);"
        aria-hidden="true"
    ></div>
    {@render children?.()}
</div>
<Toaster position="top-center" />

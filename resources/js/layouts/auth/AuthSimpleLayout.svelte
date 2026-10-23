<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
    import { home } from '@/routes';

    let {
        title = '',
        description = '',
        children,
    }: {
        title?: string;
        description?: string;
        children?: Snippet;
    } = $props();

    const appName = $derived(page.props.name);
</script>

<!-- Night background with violet and lime glows; the form card stays light. -->
<div
    class="dark relative isolate flex min-h-svh flex-col items-center justify-center overflow-hidden bg-night p-6 text-foreground md:p-10"
>
    <div
        class="pointer-events-none absolute inset-0 -z-10"
        style="background: radial-gradient(55% 45% at 50% 0%, rgb(124 92 255 / 0.5), transparent 70%), radial-gradient(35% 40% at 0% 100%, rgb(200 255 26 / 0.14), transparent 70%);"
        aria-hidden="true"
    ></div>

    <div class="relative w-full max-w-sm">
        <div class="flex flex-col gap-8">
            <div class="flex flex-col items-center gap-5 text-center">
                <Link href={home()} class="flex items-center gap-2">
                    <span class="grid size-10 place-items-center rounded-md bg-cta text-night">
                        <AppLogoIcon class="size-6" />
                    </span>
                    <span class="font-display text-lg font-bold tracking-tight uppercase">{appName}</span>
                </Link>
                <p class="eyebrow text-primary">// Espace de formation</p>
                <div class="space-y-2">
                    <h1 class="display-title text-4xl">{title}</h1>
                    {#if description}
                        <p class="text-sm text-muted-foreground">{description}</p>
                    {/if}
                </div>
            </div>
            <!-- "light" switches the tokens back to their light values for the form. -->
            <div class="light rounded-md border border-ink bg-card p-6 text-foreground">
                {@render children?.()}
            </div>
        </div>
    </div>
</div>

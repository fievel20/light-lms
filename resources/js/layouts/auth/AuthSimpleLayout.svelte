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

<div
    class="relative flex min-h-svh flex-col items-center justify-center overflow-hidden bg-background p-6 md:p-10"
>
    <!-- Soft violet glow behind the card. -->
    <div
        class="pointer-events-none absolute -top-40 left-1/2 size-[36rem] -translate-x-1/2 rounded-full bg-primary/15 blur-3xl"
        aria-hidden="true"
    ></div>

    <div class="relative w-full max-w-sm">
        <div class="flex flex-col gap-8">
            <div class="flex flex-col items-center gap-5">
                <Link href={home()} class="flex flex-col items-center gap-3">
                    <span
                        class="grid size-14 place-items-center rounded-2xl bg-primary text-primary-foreground shadow-lg shadow-primary/30"
                    >
                        <AppLogoIcon class="size-8" />
                    </span>
                    <span class="text-sm font-extrabold tracking-tight"
                        >{appName}</span
                    >
                </Link>
                <div class="space-y-1.5 text-center">
                    <h1 class="text-2xl font-extrabold tracking-tight">{title}</h1>
                    {#if description}
                        <p class="text-sm text-muted-foreground">{description}</p>
                    {/if}
                </div>
            </div>
            <div class="surface p-6">
                {@render children?.()}
            </div>
        </div>
    </div>
</div>

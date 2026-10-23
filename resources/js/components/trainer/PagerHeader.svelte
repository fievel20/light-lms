<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowLeft from '@lucide/svelte/icons/arrow-left';
    import ChevronLeft from '@lucide/svelte/icons/chevron-left';
    import ChevronRight from '@lucide/svelte/icons/chevron-right';
    import type { Snippet } from 'svelte';

    let {
        backHref,
        backLabel,
        previous,
        next,
        title,
        children,
    }: {
        backHref: string;
        backLabel: string;
        previous: { href: string; label: string } | null;
        next: { href: string; label: string } | null;
        title: string;
        children?: Snippet;
    } = $props();
</script>

<header class="flex flex-col gap-3">
    <div class="flex items-center justify-between gap-2">
        <Link
            href={backHref}
            class="inline-flex min-h-11 items-center gap-1 text-sm text-muted-foreground hover:text-foreground sm:min-h-0"
        >
            <ArrowLeft class="size-4" />
            {backLabel}
        </Link>
        <nav class="flex items-center gap-1" aria-label="Navigation">
            {#if previous}
                <Link
                    href={previous.href}
                    class="grid size-10 place-items-center rounded-xl text-muted-foreground hover:bg-muted hover:text-foreground"
                    aria-label={`Précédent : ${previous.label}`}
                    title={previous.label}
                >
                    <ChevronLeft class="size-5" />
                </Link>
            {:else}
                <span class="size-10"></span>
            {/if}
            {#if next}
                <Link
                    href={next.href}
                    class="grid size-10 place-items-center rounded-xl text-muted-foreground hover:bg-muted hover:text-foreground"
                    aria-label={`Suivant : ${next.label}`}
                    title={next.label}
                >
                    <ChevronRight class="size-5" />
                </Link>
            {/if}
        </nav>
    </div>
    <div>
        <h1 class="display-title text-2xl break-words sm:text-3xl">{title}</h1>
        {@render children?.()}
    </div>
</header>

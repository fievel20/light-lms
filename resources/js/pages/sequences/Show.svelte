<script module lang="ts">
    import { index } from '@/routes/sequences';

    export const layout = {
        breadcrumbs: [{ title: 'Séquences', href: index() }],
    };
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import Pencil from '@lucide/svelte/icons/pencil';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { formatDate } from '@/lib/dates';
    import { toUrl } from '@/lib/utils';
    import { edit } from '@/routes/sequences';
    import type { Sequence } from '@/types/sequence';

    let { sequence }: { sequence: Sequence } = $props();
</script>

<AppHead title={sequence.title} />

<div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="eyebrow">// Séquence</p>
            <h1 class="mt-1 display-title text-3xl sm:text-4xl">
                {sequence.title}
            </h1>
            {#if sequence.summary}
                <p class="mt-2 text-muted-foreground">{sequence.summary}</p>
            {/if}
            {#if sequence.updated_at}
                <p class="mt-1 text-sm text-muted-foreground">
                    Modifiée le {formatDate(sequence.updated_at)}
                </p>
            {/if}
        </div>

        <Button variant="outline" asChild class="h-11 w-full sm:h-9 sm:w-auto">
            {#snippet children(props)}
                <Link {...props} href={toUrl(edit(sequence.id))}>
                    <Pencil class="size-4" />
                    Modifier
                </Link>
            {/snippet}
        </Button>
    </header>

    <!-- Rendered and escaped by the server (MarkdownRenderer). -->
    <article class="markdown surface p-4 sm:p-6">
        {@html sequence.html}
    </article>
</div>

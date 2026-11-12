<script module lang="ts">
    import { index } from '@/routes/sequences';

    export const layout = {
        breadcrumbs: [{ title: 'Séquences', href: index() }],
    };
</script>

<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import Copy from '@lucide/svelte/icons/copy';
    import Pencil from '@lucide/svelte/icons/pencil';
    import SequenceController from '@/actions/App/Http/Controllers/SequenceController';
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

        <div class="flex w-full gap-2 sm:w-auto">
            <Form
                {...SequenceController.duplicate.form(sequence.id)}
                class="flex-1"
            >
                {#snippet children({ processing })}
                    <Button
                        type="submit"
                        variant="outline"
                        disabled={processing}
                        class="h-11 w-full sm:h-9"
                    >
                        <Copy class="size-4" />
                        Dupliquer
                    </Button>
                {/snippet}
            </Form>
            <Button variant="outline" asChild class="h-11 flex-1 sm:h-9">
                {#snippet children(props)}
                    <Link {...props} href={toUrl(edit(sequence.id))}>
                        <Pencil class="size-4" />
                        Modifier
                    </Link>
                {/snippet}
            </Button>
        </div>
    </header>

    <!-- Rendered and escaped by the server (MarkdownRenderer). -->
    <article class="markdown surface p-4 sm:p-6">
        {@html sequence.html}
    </article>
</div>

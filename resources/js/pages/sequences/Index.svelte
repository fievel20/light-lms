<script module lang="ts">
    import { index } from '@/routes/sequences';

    export const layout = {
        breadcrumbs: [{ title: 'Séquences', href: index() }],
    };
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import Plus from '@lucide/svelte/icons/plus';
    import Search from '@lucide/svelte/icons/search';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { formatDate } from '@/lib/dates';
    import { toUrl } from '@/lib/utils';
    import { create, show } from '@/routes/sequences';
    import type { Sequence } from '@/types/sequence';

    let { sequences }: { sequences: Sequence[] } = $props();

    let search = $state('');

    const normalize = (text: string) =>
        text
            .normalize('NFD')
            .replace(/\p{Diacritic}/gu, '')
            .toLowerCase();

    const filtered = $derived(
        sequences.filter((sequence) =>
            normalize(`${sequence.title} ${sequence.summary ?? ''}`).includes(
                normalize(search.trim()),
            ),
        ),
    );
</script>

<AppHead title="Séquences" />

<div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <Heading
            title="Séquences"
            description="Tes ateliers, écrits en Markdown, à regrouper en parcours."
        />

        <Button asChild class="h-11 w-full sm:h-9 sm:w-auto">
            {#snippet children(props)}
                <Link {...props} href={toUrl(create())}>
                    <Plus class="size-4" />
                    Nouvelle séquence
                </Link>
            {/snippet}
        </Button>
    </div>

    {#if sequences.length === 0}
        <p
            class="rounded-2xl border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            Aucune séquence. Crée ton premier atelier.
        </p>
    {:else}
        <div class="relative">
            <Search
                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
            />
            <Input
                type="search"
                bind:value={search}
                placeholder="Rechercher une séquence"
                aria-label="Rechercher une séquence"
                class="h-11 pl-9 sm:h-9"
            />
        </div>

        {#if filtered.length === 0}
            <p class="text-sm text-muted-foreground">
                Aucune séquence ne correspond à « {search} ».
            </p>
        {:else}
            <ul class="grid gap-3 sm:grid-cols-2">
                {#each filtered as sequence (sequence.id)}
                    <li>
                        <Link
                            href={toUrl(show(sequence.id))}
                            class="flex h-full flex-col gap-1 surface p-4 transition-colors hover:bg-muted/50"
                        >
                            <span class="font-medium">{sequence.title}</span>
                            {#if sequence.summary}
                                <span class="text-sm text-muted-foreground"
                                    >{sequence.summary}</span
                                >
                            {/if}
                            {#if sequence.updated_at}
                                <span class="mt-auto eyebrow pt-2"
                                    >Modifiée le {formatDate(
                                        sequence.updated_at,
                                    )}</span
                                >
                            {/if}
                        </Link>
                    </li>
                {/each}
            </ul>
        {/if}
    {/if}
</div>

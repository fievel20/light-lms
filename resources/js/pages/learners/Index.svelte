<script module lang="ts">
    import { index } from '@/routes/learners';

    export const layout = {
        breadcrumbs: [{ title: 'Comptes apprenants', href: index() }],
    };
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ChevronRight from '@lucide/svelte/icons/chevron-right';
    import Search from '@lucide/svelte/icons/search';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import { Input } from '@/components/ui/input';
    import { cn, toUrl } from '@/lib/utils';
    import { edit } from '@/routes/learners';

    type Row = {
        id: number;
        name: string;
        email: string;
        activated: boolean;
        promotions: string[];
    };

    let { learners }: { learners: Row[] } = $props();

    let query = $state('');

    const normalise = (text: string) =>
        text
            .normalize('NFD')
            .replace(/\p{Diacritic}/gu, '')
            .toLowerCase();

    const shown = $derived.by(() => {
        const words = normalise(query).split(/\s+/).filter(Boolean);

        return learners.filter((learner) => {
            const haystack = normalise(
                `${learner.name} ${learner.email} ${learner.promotions.join(' ')}`,
            );

            return words.every((word) => haystack.includes(word));
        });
    });
</script>

<AppHead title="Comptes apprenants" />

<div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
    <Heading
        title="Comptes apprenants"
        description="Corriger un email, renvoyer un lien de mot de passe ou donner un mot de passe provisoire."
    />

    <label class="relative block">
        <span class="sr-only">Rechercher un apprenant</span>
        <Search
            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
        />
        <Input
            type="search"
            bind:value={query}
            placeholder="Nom, email ou promotion"
            class="h-11 pl-9 sm:h-9"
            autocomplete="off"
        />
    </label>

    {#if shown.length === 0}
        <p
            class="rounded-2xl border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            {learners.length === 0
                ? 'Aucun apprenant. Inscris-les depuis la fiche d’une promotion.'
                : 'Aucun apprenant ne correspond à ta recherche.'}
        </p>
    {:else}
        <ul class="divide-y divide-border/70">
            {#each shown as learner (learner.id)}
                <li>
                    <Link
                        href={toUrl(edit(learner.id))}
                        class="-mx-3 flex items-center gap-3 rounded-xl px-3 py-3 transition-colors hover:bg-muted/60"
                    >
                        <span class="min-w-0 flex-1">
                            <span
                                class={cn(
                                    'block truncate font-medium',
                                    !learner.activated &&
                                        'text-muted-foreground',
                                )}>{learner.name}</span
                            >
                            <span
                                class="block truncate text-sm text-muted-foreground"
                                >{learner.email}</span
                            >
                            <span
                                class="block truncate text-xs text-muted-foreground"
                            >
                                {learner.activated ? '' : 'invité · '}{learner.promotions.join(', ') ||
                                    'aucune promotion'}
                            </span>
                        </span>
                        <ChevronRight
                            class="size-4 shrink-0 text-muted-foreground"
                        />
                    </Link>
                </li>
            {/each}
        </ul>
    {/if}
</div>

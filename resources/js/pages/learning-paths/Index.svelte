<script module lang="ts">
    import { index } from '@/routes/learning-paths';

    export const layout = {
        breadcrumbs: [{ title: 'Parcours', href: index() }],
    };
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import Plus from '@lucide/svelte/icons/plus';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import { Button } from '@/components/ui/button';
    import { toUrl } from '@/lib/utils';
    import { create, show } from '@/routes/learning-paths';
    import type { LearningPath } from '@/types/learning-path';

    let { learningPaths }: { learningPaths: LearningPath[] } = $props();
</script>

<AppHead title="Parcours" />

<div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <Heading
            title="Parcours"
            description="Des séquences dans l'ordre, à affecter à tes promotions."
        />

        <Button asChild class="h-11 w-full sm:h-9 sm:w-auto">
            {#snippet children(props)}
                <Link {...props} href={toUrl(create())}>
                    <Plus class="size-4" />
                    Nouveau parcours
                </Link>
            {/snippet}
        </Button>
    </div>

    {#if learningPaths.length === 0}
        <p
            class="rounded-2xl border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            Aucun parcours. Crée-en un, puis ajoute-lui des séquences.
        </p>
    {:else}
        <ul class="grid gap-3 sm:grid-cols-2">
            {#each learningPaths as learningPath (learningPath.id)}
                <li>
                    <Link
                        href={toUrl(show(learningPath.id))}
                        class="flex h-full flex-col gap-1 surface p-4 transition-colors hover:bg-muted/50"
                    >
                        <span class="font-medium">{learningPath.name}</span>
                        {#if learningPath.description}
                            <span class="text-sm text-muted-foreground"
                                >{learningPath.description}</span
                            >
                        {/if}
                        <span class="mt-auto eyebrow pt-2">
                            {learningPath.sequences_count} séquence{(learningPath.sequences_count ??
                                0) > 1
                                ? 's'
                                : ''}
                        </span>
                    </Link>
                </li>
            {/each}
        </ul>
    {/if}
</div>

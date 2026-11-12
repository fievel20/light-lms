<script module lang="ts">
    import { create, index } from '@/routes/learning-paths';

    export const layout = {
        breadcrumbs: [
            { title: 'Parcours', href: index() },
            { title: 'Nouveau parcours', href: create() },
        ],
    };
</script>

<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import LearningPathController from '@/actions/App/Http/Controllers/LearningPathController';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import LearningPathFields from '@/components/learning-paths/LearningPathFields.svelte';
    import { Button } from '@/components/ui/button';
    import { Spinner } from '@/components/ui/spinner';
    import { toUrl } from '@/lib/utils';
</script>

<AppHead title="Nouveau parcours" />

<div class="mx-auto w-full max-w-xl p-4">
    <Heading
        title="Nouveau parcours"
        description="Tu ajouteras ses séquences à l'étape suivante."
    />

    <Form {...LearningPathController.store.form()} class="space-y-6">
        {#snippet children({ errors, processing })}
            <LearningPathFields {errors} />

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Button variant="outline" asChild class="h-11 sm:h-9">
                    {#snippet children(props)}
                        <Link {...props} href={toUrl(index())}>Annuler</Link>
                    {/snippet}
                </Button>
                <Button type="submit" disabled={processing} class="h-11 sm:h-9">
                    {#if processing}<Spinner />{/if}
                    Créer le parcours
                </Button>
            </div>
        {/snippet}
    </Form>
</div>

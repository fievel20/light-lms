<script module lang="ts">
    import { create, index } from '@/routes/frameworks';

    export const layout = {
        breadcrumbs: [
            { title: 'Référentiels', href: index() },
            { title: 'Nouveau référentiel', href: create() },
        ],
    };
</script>

<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import FrameworkController from '@/actions/App/Http/Controllers/FrameworkController';
    import AppHead from '@/components/AppHead.svelte';
    import FrameworkFields from '@/components/frameworks/FrameworkFields.svelte';
    import Heading from '@/components/Heading.svelte';
    import { Button } from '@/components/ui/button';
    import { Spinner } from '@/components/ui/spinner';
    import { toUrl } from '@/lib/utils';
</script>

<AppHead title="Nouveau référentiel" />

<div class="mx-auto w-full max-w-xl p-4">
    <Heading
        title="Nouveau référentiel"
        description="Vous ajouterez ses compétences à l'étape suivante."
    />

    <Form {...FrameworkController.store.form()} class="space-y-6">
        {#snippet children({ errors, processing })}
            <FrameworkFields {errors} />

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Button variant="outline" asChild class="h-11 sm:h-9">
                    {#snippet children(props)}
                        <Link {...props} href={toUrl(index())}>Annuler</Link>
                    {/snippet}
                </Button>
                <Button type="submit" disabled={processing} class="h-11 sm:h-9">
                    {#if processing}<Spinner />{/if}
                    Créer le référentiel
                </Button>
            </div>
        {/snippet}
    </Form>
</div>

<script module lang="ts">
    import { create, index } from '@/routes/sequences';

    export const layout = {
        breadcrumbs: [
            { title: 'Séquences', href: index() },
            { title: 'Nouvelle séquence', href: create() },
        ],
    };
</script>

<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import SequenceController from '@/actions/App/Http/Controllers/SequenceController';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import SequenceFields from '@/components/sequences/SequenceFields.svelte';
    import { Button } from '@/components/ui/button';
    import { Spinner } from '@/components/ui/spinner';
    import { toUrl } from '@/lib/utils';
</script>

<AppHead title="Nouvelle séquence" />

<div class="mx-auto w-full max-w-6xl p-4">
    <Heading
        title="Nouvelle séquence"
        description="Un atelier : des étapes, des liens, un objectif."
    />

    <Form {...SequenceController.store.form()} class="space-y-6">
        {#snippet children({ errors, processing })}
            <SequenceFields {errors} />

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Button variant="outline" asChild class="h-11 sm:h-9">
                    {#snippet children(props)}
                        <Link {...props} href={toUrl(index())}>Annuler</Link>
                    {/snippet}
                </Button>
                <Button type="submit" disabled={processing} class="h-11 sm:h-9">
                    {#if processing}<Spinner />{/if}
                    Créer la séquence
                </Button>
            </div>
        {/snippet}
    </Form>
</div>

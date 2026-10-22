<script module lang="ts">
    import { create, index } from '@/routes/promotions';

    export const layout = {
        breadcrumbs: [
            { title: 'Promotions', href: index() },
            { title: 'Nouvelle promotion', href: create() },
        ],
    };
</script>

<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import PromotionController from '@/actions/App/Http/Controllers/PromotionController';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import PromotionFields from '@/components/promotions/PromotionFields.svelte';
    import { Button } from '@/components/ui/button';
    import { Spinner } from '@/components/ui/spinner';
    import { toUrl } from '@/lib/utils';
</script>

<AppHead title="Nouvelle promotion" />

<div class="mx-auto w-full max-w-xl p-4">
    <Heading
        title="Nouvelle promotion"
        description="Créez une promotion pour y affecter des apprenants."
    />

    <Form {...PromotionController.store.form()} class="space-y-6">
        {#snippet children({ errors, processing })}
            <PromotionFields {errors} />

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Button variant="outline" asChild>
                    {#snippet children(props)}
                        <Link {...props} href={toUrl(index())}>Annuler</Link>
                    {/snippet}
                </Button>
                <Button type="submit" disabled={processing}>
                    {#if processing}<Spinner />{/if}
                    Créer la promotion
                </Button>
            </div>
        {/snippet}
    </Form>
</div>

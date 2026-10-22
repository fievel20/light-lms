<script module lang="ts">
    import { index } from '@/routes/promotions';

    export const layout = {
        breadcrumbs: [{ title: 'Promotions', href: index() }],
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
    import type { Promotion } from '@/types/promotion';

    let { promotion }: { promotion: Promotion } = $props();
</script>

<AppHead title={`Modifier ${promotion.name}`} />

<div class="mx-auto w-full max-w-xl p-4">
    <Heading title="Modifier la promotion" description={promotion.name} />

    <Form
        {...PromotionController.update.form(promotion.id)}
        class="space-y-6"
    >
        {#snippet children({ errors, processing })}
            <PromotionFields {errors} {promotion} />

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Button variant="outline" asChild>
                    {#snippet children(props)}
                        <Link {...props} href={toUrl(index())}>Annuler</Link>
                    {/snippet}
                </Button>
                <Button type="submit" disabled={processing}>
                    {#if processing}<Spinner />{/if}
                    Enregistrer
                </Button>
            </div>
        {/snippet}
    </Form>
</div>

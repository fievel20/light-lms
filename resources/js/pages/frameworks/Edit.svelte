<script module lang="ts">
    import { index } from '@/routes/frameworks';

    export const layout = {
        breadcrumbs: [{ title: 'Référentiels', href: index() }],
    };
</script>

<script lang="ts">
    import { Form, Link, page } from '@inertiajs/svelte';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import FrameworkController from '@/actions/App/Http/Controllers/FrameworkController';
    import AppHead from '@/components/AppHead.svelte';
    import FrameworkFields from '@/components/frameworks/FrameworkFields.svelte';
    import Heading from '@/components/Heading.svelte';
    import { Button } from '@/components/ui/button';
    import { Spinner } from '@/components/ui/spinner';
    import { toUrl } from '@/lib/utils';
    import { show } from '@/routes/frameworks';
    import type { Framework } from '@/types/framework';

    let { framework }: { framework: Framework } = $props();

    const deleteError = $derived(page.props.errors?.framework);
</script>

<AppHead title={`Modifier ${framework.name}`} />

<div class="mx-auto flex w-full max-w-xl flex-col gap-8 p-4">
    <div>
        <Heading title="Modifier le référentiel" description={framework.name} />

        <Form
            {...FrameworkController.update.form(framework.id)}
            class="space-y-6"
        >
            {#snippet children({ errors, processing })}
                <FrameworkFields {errors} {framework} />

                <div
                    class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"
                >
                    <Button variant="outline" asChild class="h-11 sm:h-9">
                        {#snippet children(props)}
                            <Link {...props} href={toUrl(show(framework.id))}
                                >Annuler</Link
                            >
                        {/snippet}
                    </Button>
                    <Button
                        type="submit"
                        disabled={processing}
                        class="h-11 sm:h-9"
                    >
                        {#if processing}<Spinner />{/if}
                        Enregistrer
                    </Button>
                </div>
            {/snippet}
        </Form>
    </div>

    <section class="space-y-3 rounded-xl border border-destructive/40 p-4">
        <h2 class="font-medium">Supprimer le référentiel</h2>
        <p class="text-sm text-muted-foreground">
            Ses compétences seront supprimées. Impossible s'il est affecté à
            une promotion.
        </p>
        {#if deleteError}
            <p class="text-sm text-destructive">{deleteError}</p>
        {/if}
        <Form
            {...FrameworkController.destroy.form(framework.id)}
            onBefore={() =>
                confirm(`Supprimer définitivement « ${framework.name} » ?`)}
        >
            {#snippet children({ processing })}
                <Button
                    type="submit"
                    variant="destructive"
                    disabled={processing}
                    class="h-11 w-full sm:h-9 sm:w-auto"
                >
                    <Trash2 class="size-4" />
                    Supprimer
                </Button>
            {/snippet}
        </Form>
    </section>
</div>

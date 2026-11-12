<script module lang="ts">
    import { index } from '@/routes/learning-paths';

    export const layout = {
        breadcrumbs: [{ title: 'Parcours', href: index() }],
    };
</script>

<script lang="ts">
    import { Form, Link, page } from '@inertiajs/svelte';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import LearningPathController from '@/actions/App/Http/Controllers/LearningPathController';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import LearningPathFields from '@/components/learning-paths/LearningPathFields.svelte';
    import { Button } from '@/components/ui/button';
    import { Spinner } from '@/components/ui/spinner';
    import { toUrl } from '@/lib/utils';
    import { show } from '@/routes/learning-paths';
    import type { LearningPath } from '@/types/learning-path';

    let { learningPath }: { learningPath: LearningPath } = $props();

    const deleteError = $derived(page.props.errors?.learning_path);
</script>

<AppHead title={`Modifier ${learningPath.name}`} />

<div class="mx-auto flex w-full max-w-xl flex-col gap-8 p-4">
    <div>
        <Heading title="Modifier le parcours" description={learningPath.name} />

        <Form
            {...LearningPathController.update.form(learningPath.id)}
            class="space-y-6"
        >
            {#snippet children({ errors, processing })}
                <LearningPathFields {errors} {learningPath} />

                <div
                    class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"
                >
                    <Button variant="outline" asChild class="h-11 sm:h-9">
                        {#snippet children(props)}
                            <Link
                                {...props}
                                href={toUrl(show(learningPath.id))}>Annuler</Link
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
        <h2 class="font-medium">Supprimer le parcours</h2>
        <p class="text-sm text-muted-foreground">
            Ses séquences sont conservées dans la bibliothèque.
        </p>
        {#if deleteError}
            <p class="text-sm text-destructive">{deleteError}</p>
        {/if}
        <Form
            {...LearningPathController.destroy.form(learningPath.id)}
            onBefore={() =>
                confirm(`Supprimer définitivement « ${learningPath.name} » ?`)}
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

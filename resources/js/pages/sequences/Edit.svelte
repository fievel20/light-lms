<script module lang="ts">
    import { index } from '@/routes/sequences';

    export const layout = {
        breadcrumbs: [{ title: 'Séquences', href: index() }],
    };
</script>

<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import SequenceController from '@/actions/App/Http/Controllers/SequenceController';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import SequenceFields from '@/components/sequences/SequenceFields.svelte';
    import { Button } from '@/components/ui/button';
    import { Spinner } from '@/components/ui/spinner';
    import { toUrl } from '@/lib/utils';
    import { show } from '@/routes/sequences';
    import type { Sequence } from '@/types/sequence';

    let {
        sequence,
        learningPaths,
    }: { sequence: Sequence; learningPaths: string[] } = $props();

    const pathsNotice = $derived(
        learningPaths.length === 0
            ? ''
            : `Elle sera retirée ${learningPaths.length > 1 ? 'des parcours' : 'du parcours'} : ${learningPaths.join(', ')}.`,
    );
</script>

<AppHead title={`Modifier ${sequence.title}`} />

<div class="mx-auto flex w-full max-w-6xl flex-col gap-8 p-4">
    <div>
        <Heading title="Modifier la séquence" description={sequence.title} />

        <Form
            {...SequenceController.update.form(sequence.id)}
            class="space-y-6"
        >
            {#snippet children({ errors, processing })}
                <SequenceFields {errors} {sequence} />

                <div
                    class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"
                >
                    <Button variant="outline" asChild class="h-11 sm:h-9">
                        {#snippet children(props)}
                            <Link {...props} href={toUrl(show(sequence.id))}
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

    <section
        class="max-w-xl space-y-3 rounded-xl border border-destructive/40 p-4"
    >
        <h2 class="font-medium">Supprimer la séquence</h2>
        <p class="text-sm text-muted-foreground">
            Son contenu et ses images seront définitivement perdus.
            {pathsNotice}
        </p>
        <Form
            {...SequenceController.destroy.form(sequence.id)}
            onBefore={() =>
                confirm(
                    `Supprimer définitivement « ${sequence.title} » ?\n\n${pathsNotice}`.trim(),
                )}
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

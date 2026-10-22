<script module lang="ts">
    import { index } from '@/routes/frameworks';

    export const layout = {
        breadcrumbs: [{ title: 'Référentiels', href: index() }],
    };
</script>

<script lang="ts">
    import { Form, Link, page } from '@inertiajs/svelte';
    import FileJson from '@lucide/svelte/icons/file-json';
    import Plus from '@lucide/svelte/icons/plus';
    import FrameworkImportController from '@/actions/App/Http/Controllers/FrameworkImportController';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { toUrl } from '@/lib/utils';
    import { create, show } from '@/routes/frameworks';
    import type { Framework } from '@/types/framework';

    let { frameworks }: { frameworks: Framework[] } = $props();

    // Import errors are keyed by JSON path (e.g. "competencies.1.levels").
    const importErrors = $derived(
        Object.entries(page.props.errors ?? {}).filter(
            ([key]) =>
                key === 'name' ||
                key === 'description' ||
                key.startsWith('competencies'),
        ),
    );

    const example = `{
  "name": "Développeur Web",
  "competencies": [
    { "code": "C1", "name": "Maquetter", "type": "binary" },
    { "code": "C2", "name": "Développer", "type": "scale",
      "validation_threshold": 3,
      "levels": [
        { "value": 1, "label": "Découverte" },
        { "value": 2, "label": "Guidé" },
        { "value": 3, "label": "Autonome" }
      ] }
  ]
}`;
</script>

<AppHead title="Référentiels" />

<div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <Heading
            title="Référentiels"
            description="Listes de compétences à affecter à tes promotions."
        />

        <Button asChild class="h-11 w-full sm:h-9 sm:w-auto">
            {#snippet children(props)}
                <Link {...props} href={toUrl(create())}>
                    <Plus class="size-4" />
                    Nouveau référentiel
                </Link>
            {/snippet}
        </Button>
    </div>

    <details class="surface p-4" open={importErrors.length > 0}>
        <summary
            class="flex min-h-11 cursor-pointer list-none items-center gap-2 font-medium"
        >
            <FileJson class="size-4" />
            Importer un référentiel (JSON)
        </summary>

        <Form
            {...FrameworkImportController.store.form()}
            class="mt-4 grid gap-4"
        >
            {#snippet children({ errors, processing })}
                <details class="text-sm">
                    <summary class="cursor-pointer text-muted-foreground">
                        Voir le format attendu
                    </summary>
                    <pre
                        class="mt-2 overflow-x-auto rounded-md bg-muted p-3 text-xs">{example}</pre>
                </details>

                <div class="grid gap-2">
                    <Label for="file">Fichier JSON</Label>
                    <input
                        id="file"
                        name="file"
                        type="file"
                        accept=".json,application/json"
                        required
                        class="block w-full text-sm file:mr-4 file:h-11 file:rounded-md file:border-0 file:bg-secondary file:px-4 file:text-sm file:font-medium sm:file:h-9"
                    />
                    <InputError message={errors.file} />
                </div>

                {#if importErrors.length > 0}
                    <ul
                        class="grid gap-1 rounded-md border border-destructive/40 p-3 text-sm text-destructive"
                    >
                        {#each importErrors as [key, message] (key)}
                            <li>{message}</li>
                        {/each}
                    </ul>
                {/if}

                <Button
                    type="submit"
                    disabled={processing}
                    class="h-11 sm:h-9 sm:justify-self-end"
                >
                    {#if processing}<Spinner />{/if}
                    Importer
                </Button>
            {/snippet}
        </Form>
    </details>

    {#if frameworks.length === 0}
        <p
            class="rounded-2xl border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            Aucun référentiel. Crée-en un ou importe un fichier JSON.
        </p>
    {:else}
        <ul class="grid gap-3 sm:grid-cols-2">
            {#each frameworks as framework (framework.id)}
                <li>
                    <Link
                        href={toUrl(show(framework.id))}
                        class="flex h-full flex-col gap-1 surface p-4 transition-colors hover:bg-muted/50"
                    >
                        <span class="font-medium">{framework.name}</span>
                        <span class="text-sm text-muted-foreground">
                            {framework.competencies_count} compétence{(framework.competencies_count ??
                                0) > 1
                                ? 's'
                                : ''} · {framework.promotions_count} promotion{(framework.promotions_count ??
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

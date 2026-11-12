<script module lang="ts">
    import { index } from '@/routes/icons';

    export const layout = {
        breadcrumbs: [{ title: 'Icônes', href: index() }],
    };
</script>

<script lang="ts">
    import { Form, page } from '@inertiajs/svelte';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import Upload from '@lucide/svelte/icons/upload';
    import IconController from '@/actions/App/Http/Controllers/IconController';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import IconGlyph from '@/components/icons/IconGlyph.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import type { Icon } from '@/types/icon';

    let { icons }: { icons: Icon[] } = $props();

    // One message per refused file (keys icons.0, icons.1…).
    const importErrors = $derived(
        Object.entries(page.props.errors ?? {}).filter(([key]) =>
            key.startsWith('icons'),
        ),
    );

    function confirmDelete(icon: Icon): boolean {
        const used = icon.sequences ?? [];

        return confirm(
            used.length > 0
                ? `« ${icon.name} » disparaîtra de : ${used.join(', ')}.\nSupprimer quand même ?`
                : `Supprimer « ${icon.name} » ?`,
        );
    }
</script>

<AppHead title="Icônes" />

<div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4">
    <Heading
        title="Icônes"
        description="Tes icônes SVG, à insérer dans les séquences. Elles s'affichent à la taille du texte, quelle que soit la taille du dessin."
    />

    <Form
        {...IconController.store.form()}
        resetOnSuccess
        class="grid gap-4 surface p-4"
    >
        {#snippet children({ processing })}
            <div class="grid gap-2">
                <Label for="icons">Fichiers SVG (plusieurs à la fois)</Label>
                <input
                    id="icons"
                    name="icons[]"
                    type="file"
                    accept=".svg,image/svg+xml"
                    multiple
                    required
                    class="block w-full text-sm file:mr-4 file:h-11 file:rounded-md file:border-0 file:bg-secondary file:px-4 file:text-sm file:font-medium sm:file:h-9"
                />
                <p class="text-xs text-muted-foreground">
                    Le nom du fichier devient le nom de l'icône. Une icône
                    d'une seule couleur prend la couleur du texte (mode sombre
                    compris).
                </p>
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
                {#if processing}<Spinner />{:else}<Upload
                        class="size-4"
                    />{/if}
                Importer
            </Button>
        {/snippet}
    </Form>

    {#if icons.length === 0}
        <p
            class="rounded-2xl border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            Aucune icône. Importe tes fichiers SVG pour les utiliser dans tes
            séquences.
        </p>
    {:else}
        <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            {#each icons as icon (icon.id)}
                <li class="flex flex-col gap-3 surface p-4">
                    <div class="flex items-center gap-4">
                        <span
                            class="flex size-16 shrink-0 items-center justify-center rounded-lg bg-muted text-5xl"
                        >
                            <IconGlyph {icon} class="max-w-none" />
                        </span>
                        <div class="min-w-0">
                            <p class="truncate font-medium">{icon.name}</p>
                            <!-- As it shows in a sequence. -->
                            <p class="text-sm text-muted-foreground">
                                <IconGlyph {icon} /> dans le texte
                            </p>
                        </div>
                    </div>

                    {#if icon.sequences && icon.sequences.length > 0}
                        <p class="text-xs text-muted-foreground">
                            Dans : {icon.sequences.join(', ')}
                        </p>
                    {/if}

                    <details class="text-sm">
                        <summary
                            class="flex min-h-11 cursor-pointer items-center font-medium text-primary sm:min-h-9"
                        >
                            Modifier
                        </summary>

                        <Form
                            {...IconController.update.form(icon.id)}
                            options={{ preserveScroll: true }}
                            class="mt-2 grid gap-3"
                        >
                            {#snippet children({ errors, processing })}
                                <div class="grid gap-2">
                                    <Label for={`name-${icon.id}`}>Nom</Label>
                                    <Input
                                        id={`name-${icon.id}`}
                                        name="name"
                                        value={icon.name}
                                        required
                                        maxlength={60}
                                        autocomplete="off"
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <label class="flex min-h-11 items-center gap-2">
                                    <input type="hidden" name="monochrome" value="0" />
                                    <input
                                        type="checkbox"
                                        name="monochrome"
                                        value="1"
                                        checked={icon.monochrome}
                                        class="size-4 accent-primary"
                                    />
                                    Prendre la couleur du texte
                                </label>

                                <Button
                                    type="submit"
                                    disabled={processing}
                                    class="h-11 sm:h-9"
                                >
                                    {#if processing}<Spinner />{/if}
                                    Enregistrer
                                </Button>
                            {/snippet}
                        </Form>

                        <Form
                            {...IconController.destroy.form(icon.id)}
                            options={{ preserveScroll: true }}
                            onBefore={() => confirmDelete(icon)}
                            class="mt-2 grid"
                        >
                            {#snippet children({ processing })}
                                <Button
                                    type="submit"
                                    variant="ghost"
                                    disabled={processing}
                                    class="h-11 text-destructive sm:h-9"
                                >
                                    <Trash2 class="size-4" />
                                    Supprimer
                                </Button>
                            {/snippet}
                        </Form>
                    </details>
                </li>
            {/each}
        </ul>
    {/if}
</div>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import PromotionLearnerImportController from '@/actions/App/Http/Controllers/PromotionLearnerImportController';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';

    let { promotionId }: { promotionId: number } = $props();
</script>

<Form
    {...PromotionLearnerImportController.store.form(promotionId)}
    resetOnSuccess
    options={{ preserveScroll: true }}
    class="grid gap-4"
>
    {#snippet children({ errors, processing })}
        <p class="text-sm text-muted-foreground">
            Fichier CSV avec une ligne d'en-tête <code
                class="rounded bg-muted px-1">nom;prenom;email</code
            >. Le séparateur peut être un point-virgule ou une virgule (export
            Excel accepté).
        </p>

        <div class="grid gap-2">
            <Label for="file">Fichier CSV</Label>
            <input
                id="file"
                name="file"
                type="file"
                accept=".csv,text/csv,text/plain"
                required
                class="block w-full text-sm file:mr-4 file:h-11 file:rounded-md file:border-0 file:bg-secondary file:px-4 file:text-sm file:font-medium sm:file:h-9"
            />
            <InputError message={errors.file} />
        </div>

        <Button type="submit" class="h-11 sm:h-9 sm:justify-self-end" disabled={processing}>
            {#if processing}<Spinner />{/if}
            Importer et inviter
        </Button>
    {/snippet}
</Form>

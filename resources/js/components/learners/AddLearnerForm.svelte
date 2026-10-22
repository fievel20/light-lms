<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import PromotionLearnerController from '@/actions/App/Http/Controllers/PromotionLearnerController';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';

    let { promotionId }: { promotionId: number } = $props();
</script>

<Form
    {...PromotionLearnerController.store.form(promotionId)}
    resetOnSuccess
    options={{ preserveScroll: true }}
    class="grid gap-4"
>
    {#snippet children({ errors, processing })}
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="first_name">Prénom</Label>
                <Input
                    id="first_name"
                    name="first_name"
                    required
                    autocomplete="off"
                />
                <InputError message={errors.first_name} />
            </div>
            <div class="grid gap-2">
                <Label for="last_name">Nom</Label>
                <Input
                    id="last_name"
                    name="last_name"
                    required
                    autocomplete="off"
                />
                <InputError message={errors.last_name} />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="email">Adresse email</Label>
            <Input
                id="email"
                name="email"
                type="email"
                inputmode="email"
                required
                autocomplete="off"
            />
            <InputError message={errors.email} />
        </div>

        <Button type="submit" class="h-11 sm:h-9 sm:justify-self-end" disabled={processing}>
            {#if processing}<Spinner />{/if}
            Ajouter et inviter
        </Button>
    {/snippet}
</Form>

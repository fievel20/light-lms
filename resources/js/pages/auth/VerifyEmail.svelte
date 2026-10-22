<script module lang="ts">
    export const layout = {
        title: 'Vérification de l’email',
        description:
            'Clique sur le lien que nous venons de t’envoyer par email pour vérifier ton adresse.',
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import TextLink from '@/components/TextLink.svelte';
    import { Button } from '@/components/ui/button';
    import { Spinner } from '@/components/ui/spinner';
    import { logout } from '@/routes';
    import { send } from '@/routes/verification';

    let {
        status = '',
    }: {
        status?: string;
    } = $props();
</script>

<AppHead title="Vérification de l’email" />

{#if status === 'verification-link-sent'}
    <div class="mb-4 text-center text-sm font-medium text-green-600">
        Un nouveau lien de vérification vient de t’être envoyé.
    </div>
{/if}

<Form {...send.form()} class="space-y-6 text-center">
    {#snippet children({ processing })}
        <Button type="submit" disabled={processing} variant="secondary">
            {#if processing}<Spinner />{/if}
            Renvoyer l’email de vérification
        </Button>

        <TextLink href={logout()} as="button" class="mx-auto block text-sm">
            Se déconnecter
        </TextLink>
    {/snippet}
</Form>

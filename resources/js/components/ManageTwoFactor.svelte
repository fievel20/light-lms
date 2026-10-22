<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import ShieldCheck from '@lucide/svelte/icons/shield-check';
    import { onDestroy } from 'svelte';
    import Heading from '@/components/Heading.svelte';
    import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.svelte';
    import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.svelte';
    import { Button } from '@/components/ui/button';
    import { twoFactorAuthState } from '@/lib/twoFactorAuth.svelte';
    import { disable, enable } from '@/routes/two-factor';

    export type Props = {
        canManageTwoFactor?: boolean;
        requiresConfirmation?: boolean;
        twoFactorEnabled?: boolean;
    };

    let {
        canManageTwoFactor = false,
        requiresConfirmation = false,
        twoFactorEnabled = false,
    }: Props = $props();

    const twoFactorAuth = twoFactorAuthState();
    let showSetupModal = $state(false);

    onDestroy(() => twoFactorAuth.clearTwoFactorAuthData());
</script>

{#if canManageTwoFactor}
    <div class="space-y-6">
        <Heading
            variant="small"
            title="Double authentification"
            description="Ajoute une protection supplémentaire à ton compte."
        />

        {#if !twoFactorEnabled}
            <div class="flex flex-col items-start justify-start space-y-4">
                <p class="text-muted-foreground text-sm">
                    Une fois activée, un code à 6 chiffres te sera demandé à chaque connexion. Tu le trouveras dans une application d’authentification (TOTP) sur ton téléphone.
                </p>

                <div>
                    {#if twoFactorAuth.hasSetupData()}
                        <Button onclick={() => (showSetupModal = true)}>
                            <ShieldCheck class="size-4" />Reprendre la configuration
                        </Button>
                    {:else}
                        <Form
                            {...enable.form()}
                            onSuccess={() => (showSetupModal = true)}
                        >
                            {#snippet children({ processing })}
                                <Button type="submit" disabled={processing}>
                                    Activer
                                </Button>
                            {/snippet}
                        </Form>
                    {/if}
                </div>
            </div>
        {:else}
            <div class="flex flex-col items-start justify-start space-y-4">
                <p class="text-muted-foreground text-sm">
                    Un code à 6 chiffres t’est demandé à chaque connexion, depuis ton application d’authentification.
                </p>

                <div class="relative inline">
                    <Form {...disable.form()}>
                        {#snippet children({ processing })}
                            <Button
                                variant="destructive"
                                type="submit"
                                disabled={processing}
                            >
                                Désactiver
                            </Button>
                        {/snippet}
                    </Form>
                </div>

                <TwoFactorRecoveryCodes />
            </div>
        {/if}

        <TwoFactorSetupModal
            bind:isOpen={showSetupModal}
            {requiresConfirmation}
            {twoFactorEnabled}
        />
    </div>
{/if}

<script module lang="ts">
    import { index } from '@/routes/learners';

    export const layout = {
        breadcrumbs: [{ title: 'Comptes apprenants', href: index() }],
    };
</script>

<script lang="ts">
    import { Form, page } from '@inertiajs/svelte';
    import Dices from '@lucide/svelte/icons/dices';
    import Mail from '@lucide/svelte/icons/mail';
    import LearnerAccountController from '@/actions/App/Http/Controllers/LearnerAccountController';
    import LearnerInvitationController from '@/actions/App/Http/Controllers/LearnerInvitationController';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { readablePassword } from '@/lib/password';

    let {
        learner,
    }: {
        learner: {
            id: number;
            name: string;
            email: string;
            activated: boolean;
        };
    } = $props();

    let password = $state('');

    const resetLinkError = $derived(page.props.errors?.reset_link);
    const invitationError = $derived(page.props.errors?.invitation);
</script>

<AppHead title={`Compte de ${learner.name}`} />

<div class="mx-auto flex w-full max-w-xl flex-col gap-6 p-4">
    <Heading
        title={learner.name}
        description={learner.activated
            ? 'Compte activé'
            : 'Invité : le compte n’est pas encore activé'}
    />

    <section class="grid gap-4 surface p-4">
        <h2 class="font-medium">Email</h2>
        <Form
            {...LearnerAccountController.updateEmail.form(learner.id)}
            options={{ preserveScroll: true }}
            class="grid gap-3"
        >
            {#snippet children({ errors, processing })}
                <div class="grid gap-2">
                    <Label for="email">Adresse email</Label>
                    <Input
                        id="email"
                        name="email"
                        type="email"
                        value={learner.email}
                        required
                        maxlength={255}
                        autocomplete="off"
                        class="h-11 sm:h-9"
                    />
                    <InputError message={errors.email} />
                    {#if !learner.activated}
                        <p class="text-xs text-muted-foreground">
                            L’invitation sera renvoyée à la nouvelle adresse.
                        </p>
                    {/if}
                </div>
                <Button
                    type="submit"
                    disabled={processing}
                    class="h-11 sm:h-9 sm:justify-self-end"
                >
                    {#if processing}<Spinner />{/if}
                    Enregistrer l’email
                </Button>
            {/snippet}
        </Form>
    </section>

    <section class="grid gap-3 surface p-4">
        <h2 class="font-medium">Mot de passe oublié</h2>
        {#if learner.activated}
            <p class="text-sm text-muted-foreground">
                L’apprenant reçoit à {learner.email} un lien pour choisir\n                lui-même un nouveau mot de passe.
            </p>
            {#if resetLinkError}
                <p class="text-sm text-destructive">{resetLinkError}</p>
            {/if}
            <Form
                {...LearnerAccountController.sendResetLink.form(learner.id)}
                options={{ preserveScroll: true }}
                class="grid"
            >
                {#snippet children({ processing })}
                    <Button
                        type="submit"
                        variant="outline"
                        disabled={processing}
                        class="h-11 sm:h-9 sm:justify-self-end"
                    >
                        {#if processing}<Spinner />{:else}<Mail
                                class="size-4"
                            />{/if}
                        Envoyer le lien
                    </Button>
                {/snippet}
            </Form>
        {:else}
            <p class="text-sm text-muted-foreground">
                Le compte n’est pas activé : l’invitation permet de choisir un
                premier mot de passe.
            </p>
            {#if invitationError}
                <p class="text-sm text-destructive">{invitationError}</p>
            {/if}
            <Form
                {...LearnerInvitationController.store.form(learner.id)}
                options={{ preserveScroll: true }}
                class="grid"
            >
                {#snippet children({ processing })}
                    <Button
                        type="submit"
                        variant="outline"
                        disabled={processing}
                        class="h-11 sm:h-9 sm:justify-self-end"
                    >
                        {#if processing}<Spinner />{:else}<Mail
                                class="size-4"
                            />{/if}
                        Renvoyer l’invitation
                    </Button>
                {/snippet}
            </Form>
        {/if}
    </section>

    <section class="grid gap-4 surface p-4">
        <div class="grid gap-1">
            <h2 class="font-medium">Mot de passe provisoire</h2>
            <p class="text-sm text-muted-foreground">
                Pour un apprenant qui n’a pas accès à sa boîte mail : donne-lui
                ce mot de passe de vive voix, il pourra le changer dans son
                profil. Il est déconnecté de ses autres appareils{learner.activated
                    ? ''
                    : ', et son compte est activé'}.
            </p>
        </div>
        <Form
            {...LearnerAccountController.updatePassword.form(learner.id)}
            options={{ preserveScroll: true }}
            onSuccess={() => (password = '')}
            class="grid gap-3"
        >
            {#snippet children({ errors, processing })}
                <div class="grid gap-2">
                    <Label for="password">Nouveau mot de passe</Label>
                    <div class="flex gap-2">
                        <!-- Shown in clear: the trainer reads it out. -->
                        <Input
                            id="password"
                            name="password"
                            type="text"
                            bind:value={password}
                            required
                            autocomplete="off"
                            spellcheck="false"
                            class="h-11 font-mono sm:h-9"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            class="h-11 shrink-0 sm:h-9"
                            onclick={() => (password = readablePassword())}
                        >
                            <Dices class="size-4" />
                            Générer
                        </Button>
                    </div>
                    <InputError message={errors.password} />
                </div>
                <Button
                    type="submit"
                    disabled={processing || password === ''}
                    class="h-11 sm:h-9 sm:justify-self-end"
                >
                    {#if processing}<Spinner />{/if}
                    Enregistrer le mot de passe
                </Button>
            {/snippet}
        </Form>
    </section>
</div>

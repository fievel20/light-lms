<script module lang="ts">
    export const layout = {
        title: 'Activer votre compte',
        description: 'Choisissez votre mot de passe pour accéder à vos compétences.',
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';

    let {
        email,
        firstName,
        action,
        passwordRules,
    }: {
        email: string;
        firstName: string;
        action: string;
        passwordRules: string;
    } = $props();
</script>

<AppHead title="Activer votre compte" />

<p class="mb-6 text-center text-sm">Bienvenue {firstName} !</p>

<Form
    {action}
    method="post"
    resetOnError={['password', 'password_confirmation']}
>
    {#snippet children({ errors, processing })}
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">Adresse email</Label>
                <Input
                    id="email"
                    type="email"
                    autocomplete="username"
                    value={email}
                    readonly
                />
            </div>

            <div class="grid gap-2">
                <Label for="password">Mot de passe</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    autocomplete="new-password"
                    required
                    autofocus
                    passwordrules={passwordRules}
                />
                <InputError message={errors.password} />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirmation</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    autocomplete="new-password"
                    required
                    passwordrules={passwordRules}
                />
            </div>

            <Button type="submit" class="mt-2 h-11 w-full" disabled={processing}>
                {#if processing}<Spinner />{/if}
                Activer mon compte
            </Button>
        </div>
    {/snippet}
</Form>

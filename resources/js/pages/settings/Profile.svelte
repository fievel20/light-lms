<script module lang="ts">
    import { edit } from '@/routes/profile';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Profil',
                href: edit(),
            },
        ],
    };
</script>

<script lang="ts">
    import { Form, page } from '@inertiajs/svelte';
    import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
    import AppHead from '@/components/AppHead.svelte';
    import DeleteUser from '@/components/DeleteUser.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import TextLink from '@/components/TextLink.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { send } from '@/routes/verification';

    const user = $derived(page.props.auth.user);
</script>

<AppHead title="Profil" />

<h1 class="sr-only">Profil</h1>

<div class="flex flex-col space-y-6">
    <Heading
        variant="small"
        title="Profil"
        description="Ton nom et ton adresse email."
    />

    <Form
        {...ProfileController.update.form()}
        class="space-y-6"
        options={{ preserveScroll: true }}
    >
        {#snippet children({ errors, processing })}
            <div class="grid gap-6 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="first_name">Prénom</Label>
                    <Input
                        id="first_name"
                        name="first_name"
                        class="mt-1 block w-full"
                        value={user.first_name}
                        required
                        autocomplete="given-name"
                    />
                    <InputError class="mt-2" message={errors.first_name} />
                </div>

                <div class="grid gap-2">
                    <Label for="last_name">Nom</Label>
                    <Input
                        id="last_name"
                        name="last_name"
                        class="mt-1 block w-full"
                        value={user.last_name}
                        required
                        autocomplete="family-name"
                    />
                    <InputError class="mt-2" message={errors.last_name} />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="email">Adresse email</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    class="mt-1 block w-full"
                    value={user.email}
                    required
                    autocomplete="username"
                    placeholder="Adresse email"
                />
                <InputError class="mt-2" message={errors.email} />
            </div>

            {#if Boolean(page.props.mustVerifyEmail) && !user.email_verified_at}
                <div>
                    <p class="-mt-4 text-sm text-muted-foreground">
                        Ton adresse email n’est pas vérifiée.
                        <TextLink href={send()} as="button">
                            Renvoyer l’email de vérification.
                        </TextLink>
                    </p>

                    {#if page.props.status === 'verification-link-sent'}
                        <div class="mt-2 text-sm font-medium text-green-600">
                            Un nouveau lien de vérification vient de t’être envoyé.
                        </div>
                    {/if}
                </div>
            {/if}

            <div class="flex items-center gap-4">
                <Button
                    type="submit"
                    disabled={processing}
                    data-test="update-profile-button">Enregistrer</Button
                >
            </div>
        {/snippet}
    </Form>
</div>

<DeleteUser />

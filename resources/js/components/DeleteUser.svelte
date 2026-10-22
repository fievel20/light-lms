<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogClose,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
        DialogTrigger,
    } from '@/components/ui/dialog';
    import { Label } from '@/components/ui/label';
</script>

<div class="space-y-6">
    <Heading
        variant="small"
        title="Supprimer le compte"
        description="Supprime ton compte et toutes ses données."
    />
    <div
        class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10"
    >
        <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
            <p class="font-medium">Attention</p>
            <p class="text-sm">
                Cette action est définitive.
            </p>
        </div>
        <Dialog>
            <DialogTrigger>
                <Button variant="destructive" data-test="delete-user-button"
                    >Supprimer le compte</Button
                >
            </DialogTrigger>
            <DialogContent>
                <Form
                    {...ProfileController.destroy.form()}
                    class="space-y-6"
                    options={{ preserveScroll: true }}
                >
                    {#snippet children({ errors, processing })}
                        <div class="space-y-3">
                            <DialogTitle
                                >Supprimer définitivement ton compte ?</DialogTitle
                            >
                            <DialogDescription>
                                Toutes tes données seront supprimées définitivement. Saisis ton mot de passe pour confirmer.
                            </DialogDescription>
                        </div>

                        <div class="grid gap-2">
                            <Label for="password" class="sr-only"
                                >Mot de passe</Label
                            >
                            <PasswordInput
                                id="password"
                                name="password"
                                placeholder="Mot de passe"
                            />
                            <InputError message={errors.password} />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose>
                                <Button variant="secondary">Annuler</Button>
                            </DialogClose>

                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={processing}
                                data-test="confirm-delete-user-button"
                            >
                                Supprimer le compte
                            </Button>
                        </DialogFooter>
                    {/snippet}
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</div>

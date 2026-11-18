<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import RotateCcw from '@lucide/svelte/icons/rotate-ccw';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import UserRound from '@lucide/svelte/icons/user-round';
    import PromotionDemoAccountController from '@/actions/App/Http/Controllers/PromotionDemoAccountController';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';

    let {
        promotionId,
        account,
    }: {
        promotionId: number;
        account: { email: string; password: string } | null;
    } = $props();

    let editing = $state(false);
</script>

<!-- A made-up learner to show the learners' space; emptied at each logout. -->
<section class="grid gap-3 surface p-4" aria-labelledby="demo-account-title">
    <div class="flex items-start gap-3">
        <UserRound class="mt-0.5 size-5 shrink-0 text-primary" />
        <div class="min-w-0 flex-1">
            <h2 id="demo-account-title" class="font-medium">Compte démo</h2>
            <p class="text-sm text-muted-foreground">
                Un faux apprenant de la promotion : il voit les séquences, fait
                les quiz et s’évalue. Tout est remis à zéro à sa déconnexion, et
                il n’apparaît pas dans tes résultats.
            </p>
        </div>
    </div>

    {#if account && !editing}
        <dl class="grid gap-2 rounded-md bg-muted p-3 text-sm sm:grid-cols-2">
            <div class="min-w-0">
                <dt class="eyebrow">Email</dt>
                <dd class="truncate font-mono">{account.email}</dd>
            </div>
            <div class="min-w-0">
                <dt class="eyebrow">Mot de passe</dt>
                <dd class="font-mono">{account.password}</dd>
            </div>
        </dl>
        <div class="flex flex-wrap gap-2">
            <Button
                variant="outline"
                class="h-11 sm:h-9"
                onclick={() => (editing = true)}>Modifier</Button
            >
            <Form
                {...PromotionDemoAccountController.reset.form(promotionId)}
                options={{ preserveScroll: true }}
            >
                {#snippet children({ processing })}
                    <Button
                        type="submit"
                        variant="outline"
                        class="h-11 sm:h-9"
                        disabled={processing}
                    >
                        {#if processing}<Spinner />{:else}<RotateCcw
                                class="size-4"
                            />{/if}
                        Remettre à zéro
                    </Button>
                {/snippet}
            </Form>
            <Form
                {...PromotionDemoAccountController.destroy.form(promotionId)}
                options={{ preserveScroll: true }}
                onBefore={() => confirm('Supprimer le compte démo ?')}
            >
                {#snippet children({ processing })}
                    <Button
                        type="submit"
                        variant="ghost"
                        class="h-11 text-destructive sm:h-9"
                        disabled={processing}
                    >
                        {#if processing}<Spinner />{:else}<Trash2
                                class="size-4"
                            />{/if}
                        Supprimer
                    </Button>
                {/snippet}
            </Form>
        </div>
    {:else}
        <Form
            {...PromotionDemoAccountController.update.form(promotionId)}
            options={{ preserveScroll: true }}
            onSuccess={() => (editing = false)}
            class="grid gap-4"
        >
            {#snippet children({ errors, processing })}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="demo_email">Adresse email (inventée)</Label>
                        <Input
                            id="demo_email"
                            name="demo_email"
                            type="email"
                            inputmode="email"
                            required
                            autocomplete="off"
                            placeholder="demo@formation.test"
                            value={account?.email ?? ''}
                        />
                        <InputError message={errors.demo_email} />
                    </div>
                    <div class="grid gap-2">
                        <Label for="demo_password">Mot de passe (4 caractères ou plus)</Label>
                        <Input
                            id="demo_password"
                            name="demo_password"
                            required
                            minlength={4}
                            autocomplete="off"
                            value={account?.password ?? ''}
                        />
                        <InputError message={errors.demo_password} />
                    </div>
                </div>
                <div class="flex flex-wrap justify-end gap-2">
                    {#if account}
                        <Button
                            type="button"
                            variant="ghost"
                            class="h-11 sm:h-9"
                            onclick={() => (editing = false)}>Annuler</Button
                        >
                    {/if}
                    <Button type="submit" class="h-11 sm:h-9" disabled={processing}>
                        {#if processing}<Spinner />{/if}
                        {account ? 'Enregistrer' : 'Créer le compte démo'}
                    </Button>
                </div>
            {/snippet}
        </Form>
    {/if}
</section>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import ExpressPlayController from '@/actions/App/Http/Controllers/ExpressPlayController';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Spinner } from '@/components/ui/spinner';
</script>

<AppHead title="Quiz express" />

<!-- For those who type the code shown on the screen instead of scanning. -->
<div
    class="mx-auto flex w-full max-w-sm flex-1 flex-col justify-center gap-6 p-4"
>
    <div class="space-y-2">
        <p class="eyebrow text-primary">// Quiz express</p>
        <h1 class="display-title text-3xl">Code du quiz</h1>
        <p class="text-muted-foreground">
            Tape les 6 caractères affichés à l’écran.
        </p>
    </div>
    <Form {...ExpressPlayController.find.form()} class="grid gap-3">
        {#snippet children({ errors, processing })}
            <Input
                name="code"
                required
                maxlength={8}
                autocomplete="off"
                autocapitalize="characters"
                aria-label="Code du quiz"
                class="h-14 text-center font-mono text-2xl tracking-[0.3em] uppercase"
            />
            <InputError message={errors.code} />
            <Button
                type="submit"
                disabled={processing}
                class="h-14 bg-cta font-mono text-base font-semibold text-cta-foreground uppercase hover:bg-cta/90"
            >
                {#if processing}<Spinner />{/if}
                Continuer
            </Button>
        {/snippet}
    </Form>
</div>

<script lang="ts">
    import { Form, page } from '@inertiajs/svelte';
    import Eye from '@lucide/svelte/icons/eye';
    import ObserverController from '@/actions/App/Http/Controllers/ObserverController';
    import { Spinner } from '@/components/ui/spinner';

    const observer = $derived(
        page.props.observer as { learner: string } | null | undefined,
    );
</script>

{#if observer}
    <!-- Always in sight: the trainer must not forget whose space this is. -->
    <div
        class="sticky top-0 z-40 flex flex-wrap items-center gap-x-3 gap-y-1 bg-night px-4 py-2 text-sm text-white"
        role="status"
    >
        <Eye class="size-4 shrink-0 text-cta" />
        <p class="min-w-0 flex-1">
            <span class="font-semibold">Mode observateur</span> : tu vois
            l’espace de <span class="font-semibold">{observer.learner}</span>,
            en lecture seule.
        </p>
        <Form {...ObserverController.destroy.form()}>
            {#snippet children({ processing })}
                <button
                    type="submit"
                    disabled={processing}
                    class="inline-flex h-9 items-center gap-2 rounded-md bg-cta px-3 font-mono text-xs font-semibold text-cta-foreground uppercase"
                >
                    {#if processing}<Spinner />{/if}
                    Quitter
                </button>
            {/snippet}
        </Form>
    </div>
{/if}

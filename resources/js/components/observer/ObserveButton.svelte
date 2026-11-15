<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import Eye from '@lucide/svelte/icons/eye';
    import ObserverController from '@/actions/App/Http/Controllers/ObserverController';
    import { Button } from '@/components/ui/button';
    import { Spinner } from '@/components/ui/spinner';
    import { cn } from '@/lib/utils';

    let {
        learnerId,
        class: className = '',
    }: { learnerId: number; class?: string } = $props();
</script>

<!-- Opens the learner's space as they see it, read only. -->
<Form
    {...ObserverController.store.form(learnerId)}
    class={cn('grid', className)}
>
    {#snippet children({ processing })}
        <Button
            type="submit"
            variant="outline"
            disabled={processing}
            class="h-11 sm:h-9"
        >
            {#if processing}<Spinner />{:else}<Eye class="size-4" />{/if}
            Voir comme l’apprenant
        </Button>
    {/snippet}
</Form>

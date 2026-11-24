<script lang="ts">
    import { Button } from '@/components/ui/button';
    import { Spinner } from '@/components/ui/spinner';

    let {
        count,
        saving,
        onsave,
        label = 'Enregistrer le quiz',
    }: {
        count: number;
        saving: boolean;
        onsave: () => void;
        label?: string;
    } = $props();
</script>

<!-- Always within reach: the quiz is only saved with this button. -->
<div
    class="fixed inset-x-0 bottom-[calc(4.5rem+env(safe-area-inset-bottom))] z-30 border-t border-border bg-background/95 p-3 backdrop-blur md:bottom-0 md:left-(--sidebar-width)"
>
    <div class="mx-auto flex max-w-3xl items-center justify-between gap-3">
        <span class="text-sm text-muted-foreground"
            >{count} question{count > 1 ? 's' : ''}</span
        >
        <Button
            type="button"
            disabled={saving}
            class="h-11 sm:h-9"
            onclick={onsave}
        >
            {#if saving}<Spinner />{/if}
            {label}
        </Button>
    </div>
</div>

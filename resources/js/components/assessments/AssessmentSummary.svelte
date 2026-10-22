<script lang="ts">
    import CircleCheck from '@lucide/svelte/icons/circle-check';
    import { Badge } from '@/components/ui/badge';
    import { valueLabel } from '@/lib/competencies';
    import { formatDate } from '@/lib/dates';
    import type { AssessedCompetency } from '@/types/framework';

    let { competency }: { competency: AssessedCompetency } = $props();
</script>

<dl class="grid gap-2 text-sm sm:grid-cols-2">
    <div class="rounded-lg bg-muted/50 p-3">
        <dt class="text-xs text-muted-foreground">Autoévaluation</dt>
        <dd class="font-medium">
            {competency.self
                ? valueLabel(competency, competency.self.value)
                : 'Pas encore'}
        </dd>
    </div>
    <div class="rounded-lg bg-muted/50 p-3">
        <dt class="flex items-center justify-between text-xs text-muted-foreground">
            Validation formateur
            {#if competency.acquired}
                <Badge class="gap-1"><CircleCheck class="size-3" />Acquise</Badge>
            {/if}
        </dt>
        <dd class="font-medium">
            {competency.trainer
                ? valueLabel(competency, competency.trainer.value)
                : 'Pas encore'}
            {#if competency.trainer}
                <span class="text-xs font-normal text-muted-foreground">
                    · {formatDate(competency.trainer.created_at)}
                </span>
            {/if}
        </dd>
        {#if competency.trainer?.comment}
            <p class="mt-1 text-muted-foreground">« {competency.trainer.comment} »</p>
        {/if}
    </div>
</dl>

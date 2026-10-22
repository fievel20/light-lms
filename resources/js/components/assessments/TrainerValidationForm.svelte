<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import LearnerAssessmentController from '@/actions/App/Http/Controllers/LearnerAssessmentController';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { competencyOptions } from '@/lib/competencies';
    import { nativeFieldClass } from '@/lib/form';
    import type { AssessedCompetency } from '@/types/framework';

    let {
        promotionId,
        learnerId,
        competency,
    }: {
        promotionId: number;
        learnerId: number;
        competency: AssessedCompetency;
    } = $props();
</script>

<Form
    {...LearnerAssessmentController.store.form([
        promotionId,
        learnerId,
        competency.id,
    ])}
    options={{ preserveScroll: true }}
    errorBag={`competency-${competency.id}`}
    resetOnSuccess={['comment']}
    class="grid gap-3"
>
    {#snippet children({ errors, processing })}
        <fieldset>
            <legend class="mb-2 text-sm font-medium">Ma validation</legend>
            <div class="flex flex-wrap gap-2">
                {#each competencyOptions(competency) as option (option.value)}
                    <label
                        class="flex min-h-11 flex-1 cursor-pointer items-center justify-center rounded-lg border px-3 text-sm has-[:checked]:border-primary has-[:checked]:bg-primary has-[:checked]:text-primary-foreground sm:min-h-9 sm:flex-none"
                    >
                        <input
                            type="radio"
                            name="value"
                            value={option.value}
                            checked={competency.trainer?.value === option.value}
                            required
                            class="sr-only"
                        />
                        {option.label}
                    </label>
                {/each}
            </div>
            <InputError message={errors.value} />
        </fieldset>

        <div class="grid gap-2">
            <Label for={`comment-${competency.id}`}>Commentaire (facultatif)</Label>
            <textarea
                id={`comment-${competency.id}`}
                name="comment"
                rows="2"
                maxlength="2000"
                class={nativeFieldClass}
            ></textarea>
            <InputError message={errors.comment} />
        </div>

        <Button type="submit" disabled={processing} class="h-11 sm:h-9 sm:justify-self-end">
            {#if processing}<Spinner />{/if}
            Valider
        </Button>
    {/snippet}
</Form>

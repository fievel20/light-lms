<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import PartyPopper from '@lucide/svelte/icons/party-popper';
    import SkipForward from '@lucide/svelte/icons/skip-forward';
    import X from '@lucide/svelte/icons/x';
    import LearnerAssessmentController from '@/actions/App/Http/Controllers/LearnerAssessmentController';
    import InputError from '@/components/InputError.svelte';
    import LevelPips from '@/components/design/LevelPips.svelte';
    import ProgressBar from '@/components/progress/ProgressBar.svelte';
    import { Button } from '@/components/ui/button';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { competencyOptions, valueLabel } from '@/lib/competencies';
    import { nativeFieldClass } from '@/lib/form';
    import { toUrl } from '@/lib/utils';
    import { progress } from '@/routes/promotions';
    import { show as learnerPage } from '@/routes/promotions/learners';
    import type { AssessedCompetency } from '@/types/framework';

    type NextLearner = { id: number; name: string; to_review: number } | null;

    let {
        promotionId,
        learner,
        competencies,
        nextToReview,
        onclose,
    }: {
        promotionId: number;
        learner: { id: number; first_name: string };
        competencies: AssessedCompetency[];
        nextToReview: NextLearner;
        onclose: () => void;
    } = $props();

    // Snapshot of the queue when the review starts, so validated items stay put.
    // svelte-ignore state_referenced_locally
    const queue = competencies.filter((competency) => competency.to_review).map((c) => c.id);

    let index = $state(0);
    let chosen = $state<number | null>(null);
    let comment = $state('');
    let processing = $state(false);
    let validated = $state(0);

    const current = $derived(
        index < queue.length ? competencies.find((c) => c.id === queue[index]) : undefined,
    );
    const errors = $derived(
        ((page.props.errors as Record<string, Record<string, string>> | undefined)?.quick ??
            {}) as Record<string, string | undefined>,
    );

    // Pre-select the learner's own self-assessment for each new item.
    $effect(() => {
        chosen = current?.self?.value ?? null;
        comment = '';
    });

    function next(): void {
        index += 1;
    }

    function submit(event: SubmitEvent): void {
        event.preventDefault();

        if (!current || chosen === null) {
            return;
        }

        processing = true;
        router.visit(LearnerAssessmentController.store([promotionId, learner.id, current.id]), {
            data: { value: chosen, comment },
            errorBag: 'quick',
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                validated += 1;
                next();
            },
            onFinish: () => (processing = false),
        });
    }
</script>

<section class="surface flex flex-col gap-5 p-5" aria-label="Validation rapide">
    <div class="flex items-center justify-between gap-3">
        <h2 class="font-bold">Validation rapide</h2>
        <Button variant="ghost" size="icon" class="size-9" aria-label="Quitter la validation rapide" onclick={onclose}>
            <X class="size-4" />
        </Button>
    </div>

    {#if current}
        <ProgressBar
            percent={Math.round((index * 100) / queue.length)}
            label={`${index + 1} / ${queue.length} à examiner`}
        />

        <form onsubmit={submit} class="flex flex-col gap-5">
            <div class="flex items-start gap-3">
                <span
                    class="grid size-10 shrink-0 place-items-center rounded-xl bg-primary-soft font-mono text-xs font-bold text-primary"
                    >{current.code}</span
                >
                <div class="min-w-0">
                    <p class="leading-snug font-semibold">{current.name}</p>
                    {#if current.self}
                        <p class="mt-1 text-sm text-muted-foreground">
                            {learner.first_name} s'estime :
                            <span class="font-semibold text-primary"
                                >{valueLabel(current, current.self.value)}</span
                            >
                        </p>
                    {/if}
                </div>
            </div>

            <div class="grid gap-2">
                <p class="text-sm font-medium">Ta validation</p>
                <LevelPips
                    options={competencyOptions(current)}
                    selected={chosen}
                    validated={current.trainer?.value ?? null}
                    threshold={current.type === 'binary' ? 1 : current.validation_threshold}
                    disabled={processing}
                    label={`Niveau validé pour ${current.code}`}
                    onselect={(value) => (chosen = value)}
                />
                <InputError message={errors.value} />
            </div>

            <div class="grid gap-2">
                <Label for="quick-comment">Commentaire (facultatif)</Label>
                <textarea
                    id="quick-comment"
                    rows="2"
                    maxlength="2000"
                    class={nativeFieldClass}
                    bind:value={comment}
                ></textarea>
                <InputError message={errors.comment} />
            </div>

            <div class="flex gap-2">
                <Button type="button" variant="outline" class="h-12 flex-1" disabled={processing} onclick={next}>
                    <SkipForward class="size-4" />
                    Passer
                </Button>
                <Button type="submit" class="h-12 flex-[2]" disabled={processing || chosen === null}>
                    {#if processing}<Spinner />{/if}
                    Valider et suivant
                </Button>
            </div>
        </form>
    {:else}
        <div class="flex flex-col items-center gap-3 py-4 text-center">
            <span class="grid size-14 place-items-center rounded-2xl bg-success-soft text-success">
                <PartyPopper class="size-7" />
            </span>
            <p class="text-lg font-bold">
                {queue.length === 0 ? 'Rien à examiner' : 'Tout est examiné ✨'}
            </p>
            <p class="text-sm text-muted-foreground">
                {validated} validation{validated > 1 ? 's' : ''} enregistrée{validated > 1 ? 's' : ''}
                pour {learner.first_name}.
            </p>

            {#if nextToReview}
                <Button asChild class="mt-2 h-12 w-full">
                    {#snippet children(props)}
                        <Link
                            {...props}
                            href={toUrl(
                                learnerPage([promotionId, nextToReview.id], { query: { mode: 'rapide' } }),
                            )}
                        >
                            Suivant : {nextToReview.name} ({nextToReview.to_review})
                            <ArrowRight class="size-4" />
                        </Link>
                    {/snippet}
                </Button>
            {/if}
            <Button variant="outline" asChild class="h-11 w-full">
                {#snippet children(props)}
                    <Link {...props} href={toUrl(progress(promotionId))}>Retour au suivi</Link>
                {/snippet}
            </Button>
        </div>
    {/if}
</section>

<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import Check from '@lucide/svelte/icons/check';
    import CircleAlert from '@lucide/svelte/icons/circle-alert';
    import LoaderCircle from '@lucide/svelte/icons/loader-circle';
    import MessageSquare from '@lucide/svelte/icons/message-square';
    import MessageSquareText from '@lucide/svelte/icons/message-square-text';
    import LearnerAssessmentController from '@/actions/App/Http/Controllers/LearnerAssessmentController';
    import { competencyOptions, valueLabel } from '@/lib/competencies';
    import { cn } from '@/lib/utils';
    import type { Competency } from '@/types/framework';

    let {
        promotionId,
        learnerId,
        competency,
        title,
        subtitle,
        self,
        trainer,
        comment,
        acquired,
        toReview,
        disabled = false,
    }: {
        promotionId: number;
        learnerId: number;
        competency: Competency;
        title: string;
        subtitle?: string;
        self: number | null;
        trainer: number | null;
        comment: string | null;
        acquired: boolean;
        toReview: boolean;
        disabled?: boolean;
    } = $props();

    const bag = $derived(`row-${learnerId}-${competency.id}`);
    const options = $derived(competencyOptions(competency));

    // Optimistic value: shown immediately, confirmed by the server.
    let chosen = $state<number | null>(null);
    let status = $state<'idle' | 'saving' | 'saved' | 'error'>('idle');
    let commentOpen = $state(false);
    // svelte-ignore state_referenced_locally
    let draft = $state(comment ?? '');

    const current = $derived(chosen ?? trainer);
    const error = $derived(
        (page.props.errors as Record<string, Record<string, string>> | undefined)?.[bag]?.value,
    );

    function save(value: number, withComment: string | null): void {
        chosen = value;
        status = 'saving';

        router.visit(LearnerAssessmentController.store([promotionId, learnerId, competency.id]), {
            data: { value, comment: withComment, quiet: 1 },
            errorBag: bag,
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                status = 'saved';
                chosen = null;
                setTimeout(() => status === 'saved' && (status = 'idle'), 1500);
            },
            onError: () => {
                status = 'error';
                chosen = null;
            },
        });
    }

    function choose(value: number): void {
        if (value !== current) {
            save(value, comment);
        }
    }

    function saveComment(): void {
        const text = draft.trim();

        if (current !== null && text !== (comment ?? '')) {
            save(current, text === '' ? null : text);
        }
    }

    function isAcquiring(value: number): boolean {
        return competency.type === 'binary' ? value === 1 : value >= (competency.validation_threshold ?? Infinity);
    }
</script>

<li class="py-3">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
        <div class="min-w-0 flex-1">
            <p class="flex items-center gap-2 font-medium">
                <span class="truncate">{title}</span>
                {#if toReview}
                    <span class="size-2 shrink-0 rounded-full bg-warning" title="Autoévaluation à examiner"></span>
                {/if}
            </p>
            <p class="truncate text-xs text-muted-foreground">
                {#if subtitle}{subtitle} · {/if}
                {self === null ? 'Pas d\'autoévaluation' : `s'estime : ${valueLabel(competency, self)}`}
            </p>
        </div>

        <div class="flex items-center gap-2">
            <div
                class="inline-flex flex-1 rounded-xl border border-ink/25 bg-card p-0.5 sm:flex-none"
                role="radiogroup"
                aria-label={`Validation pour ${title}`}
            >
                {#each options as option (option.value)}
                    {@const selected = current === option.value}
                    <button
                        type="button"
                        role="radio"
                        aria-checked={selected}
                        title={option.label}
                        {disabled}
                        onclick={() => choose(option.value)}
                        class={cn(
                            'h-10 min-w-10 flex-1 rounded-md px-2 font-mono text-sm font-semibold transition-colors disabled:cursor-not-allowed sm:h-9 sm:flex-none',
                            selected
                                ? isAcquiring(option.value)
                                    ? 'bg-success text-success-foreground'
                                    : 'bg-ink text-background'
                                : 'text-muted-foreground enabled:hover:text-foreground',
                        )}
                    >
                        {competency.type === 'binary' ? (option.value === 1 ? 'Oui' : 'Non') : option.value}
                    </button>
                {/each}
            </div>

            <button
                type="button"
                class={cn(
                    'grid size-10 shrink-0 place-items-center rounded-xl transition-colors hover:bg-muted sm:size-9',
                    comment ? 'text-primary' : 'text-muted-foreground',
                )}
                aria-label={comment ? 'Modifier le commentaire' : 'Ajouter un commentaire'}
                aria-expanded={commentOpen}
                disabled={disabled || current === null}
                onclick={() => (commentOpen = !commentOpen)}
            >
                {#if comment}
                    <MessageSquareText class="size-4" />
                {:else}
                    <MessageSquare class="size-4" />
                {/if}
            </button>

            <span class="grid size-5 shrink-0 place-items-center" aria-live="polite">
                {#if status === 'saving'}
                    <LoaderCircle class="size-4 animate-spin text-muted-foreground" aria-label="Enregistrement" />
                {:else if status === 'saved'}
                    <Check class="size-4 text-success-ink" aria-label="Enregistré" />
                {:else if status === 'error'}
                    <CircleAlert class="size-4 text-destructive" aria-label="Erreur" />
                {:else if acquired}
                    <span class="size-1.5 rounded-full bg-success" title="Acquise"></span>
                {/if}
            </span>
        </div>
    </div>

    {#if error}
        <p class="mt-1 text-xs text-destructive">{error}</p>
    {/if}

    {#if commentOpen}
        <textarea
            class="mt-2 w-full rounded-xl border-0 bg-muted px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            rows="2"
            maxlength="2000"
            placeholder="Commentaire pour l'apprenant (enregistré automatiquement)"
            bind:value={draft}
            onblur={saveComment}
        ></textarea>
    {/if}
</li>

<script module lang="ts">
    import { index } from '@/routes/promotions';

    export const layout = {
        breadcrumbs: [{ title: 'Promotions', href: index() }],
    };
</script>

<script lang="ts">
    import { Form, Link, page, router } from '@inertiajs/svelte';
    import Eye from '@lucide/svelte/icons/eye';
    import EyeOff from '@lucide/svelte/icons/eye-off';
    import Plus from '@lucide/svelte/icons/plus';
    import X from '@lucide/svelte/icons/x';
    import PromotionLearningPathController from '@/actions/App/Http/Controllers/PromotionLearningPathController';
    import SequenceReleaseController from '@/actions/App/Http/Controllers/SequenceReleaseController';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Label } from '@/components/ui/label';
    import { formatDate } from '@/lib/dates';
    import { nativeFieldClass } from '@/lib/form';
    import { cn, toUrl } from '@/lib/utils';
    import * as learningPathRoutes from '@/routes/learning-paths';
    import * as promotionRoutes from '@/routes/promotions';
    import * as sequenceRoutes from '@/routes/sequences';

    type ReleasedSequence = {
        id: number;
        title: string;
        summary: string | null;
        released_at: string | null;
    };

    type AssignedPath = {
        id: number;
        name: string;
        description: string | null;
        sequences: ReleasedSequence[];
    };

    let {
        promotion,
        learningPaths,
        availableLearningPaths,
    }: {
        promotion: { id: number; name: string; archived_at: string | null };
        learningPaths: AssignedPath[];
        availableLearningPaths: { id: number; name: string }[];
    } = $props();

    // Sequences being switched: the switch shows its new state at once.
    let pending: Record<number, boolean> = $state({});

    const archived = $derived(promotion.archived_at !== null);
    const releaseError = $derived(page.props.errors?.visible);

    function isVisible(sequence: ReleasedSequence): boolean {
        return pending[sequence.id] ?? sequence.released_at !== null;
    }

    function toggle(sequence: ReleasedSequence): void {
        const visible = !isVisible(sequence);
        pending[sequence.id] = visible;

        router.visit(
            SequenceReleaseController.update([promotion.id, sequence.id]),
            {
                data: { visible },
                preserveScroll: true,
                preserveState: true,
                onFinish: () => delete pending[sequence.id],
            },
        );
    }

    function openCount(path: AssignedPath): number {
        return path.sequences.filter(isVisible).length;
    }
</script>

<AppHead title={`Parcours · ${promotion.name}`} />

<div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
    <div>
        <Heading
            title="Parcours"
            description={`${promotion.name} : ouvre les séquences une par une, au rythme de la promotion.`}
        />
        {#if archived}
            <p class="-mt-4 text-sm text-muted-foreground">
                Promotion archivée : les séquences ouvertes restent lisibles,
                plus rien ne s'ouvre.
            </p>
        {/if}
        {#if releaseError}
            <p class="text-sm text-destructive">{releaseError}</p>
        {/if}
    </div>

    {#if learningPaths.length === 0}
        <p
            class="rounded-2xl border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            Aucun parcours affecté à cette promotion.
        </p>
    {/if}

    {#each learningPaths as path (path.id)}
        <section class="flex flex-col gap-3 surface p-4">
            <header class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <Link
                        href={toUrl(learningPathRoutes.show(path.id))}
                        class="font-display text-lg font-bold hover:underline"
                        >{path.name}</Link
                    >
                    <p class="eyebrow mt-1">
                        {openCount(path)} / {path.sequences.length} ouverte{openCount(
                            path,
                        ) > 1
                            ? 's'
                            : ''}
                    </p>
                </div>
                <Form
                    {...PromotionLearningPathController.destroy.form([
                        promotion.id,
                        path.id,
                    ])}
                    options={{ preserveScroll: true }}
                    onBefore={() =>
                        confirm(
                            `Retirer « ${path.name} » de ${promotion.name} ? Les séquences ouvertes seront masquées à nouveau.`,
                        )}
                >
                    {#snippet children({ processing })}
                        <Button
                            type="submit"
                            variant="ghost"
                            size="icon"
                            class="size-11 text-muted-foreground hover:text-destructive sm:size-9"
                            disabled={processing}
                            aria-label={`Retirer « ${path.name} » de la promotion`}
                        >
                            <X class="size-4" />
                        </Button>
                    {/snippet}
                </Form>
            </header>

            {#if path.sequences.length === 0}
                <p class="text-sm text-muted-foreground">
                    Ce parcours n'a pas encore de séquence.
                </p>
            {:else}
                <ol class="flex flex-col divide-y divide-border">
                    {#each path.sequences as sequence, i (sequence.id)}
                        {@const visible = isVisible(sequence)}
                        <li class="flex items-center gap-3 py-2">
                            <span
                                class="w-6 shrink-0 text-center figures text-primary"
                                >{i + 1}</span
                            >
                            <div class="min-w-0 flex-1">
                                <Link
                                    href={toUrl(
                                        sequenceRoutes.show(sequence.id),
                                    )}
                                    class="block truncate font-medium hover:underline"
                                    >{sequence.title}</Link
                                >
                                <span class="text-sm text-muted-foreground">
                                    {#if visible && sequence.released_at}
                                        Visible depuis le {formatDate(
                                            sequence.released_at,
                                        )}
                                    {:else if visible}
                                        Visible
                                    {:else}
                                        Masquée
                                    {/if}
                                </span>
                            </div>
                            <button
                                type="button"
                                role="switch"
                                aria-checked={visible}
                                aria-label={`Rendre « ${sequence.title} » ${visible ? 'invisible' : 'visible'} pour les apprenants`}
                                disabled={archived}
                                onclick={() => toggle(sequence)}
                                class={cn(
                                    'inline-flex h-11 shrink-0 items-center gap-2 rounded-md border px-3 text-sm font-medium transition-colors disabled:opacity-50 sm:h-9',
                                    visible
                                        ? 'border-transparent bg-success text-success-foreground'
                                        : 'border-input text-muted-foreground hover:bg-muted',
                                )}
                            >
                                {#if visible}
                                    <Eye class="size-4" /> Visible
                                {:else}
                                    <EyeOff class="size-4" /> Masquée
                                {/if}
                            </button>
                        </li>
                    {/each}
                </ol>
            {/if}
        </section>
    {/each}

    {#if !archived}
        <section class="surface p-4">
            {#if availableLearningPaths.length === 0}
                <p class="text-sm text-muted-foreground">
                    Tous les parcours sont déjà affectés.
                    <Link
                        href={toUrl(learningPathRoutes.create())}
                        class="font-semibold text-primary underline underline-offset-4"
                        >Créer un parcours</Link
                    >
                </p>
            {:else}
                <Form
                    {...PromotionLearningPathController.store.form(
                        promotion.id,
                    )}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    class="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end"
                >
                    {#snippet children({ errors, processing })}
                        <div class="grid gap-2">
                            <Label for="learning_path_id"
                                >Affecter un parcours</Label
                            >
                            <select
                                id="learning_path_id"
                                name="learning_path_id"
                                required
                                class={cn(
                                    nativeFieldClass,
                                    'h-11 sm:h-9 sm:py-1',
                                )}
                            >
                                <option value="">Choisir…</option>
                                {#each availableLearningPaths as path (path.id)}
                                    <option value={path.id}>{path.name}</option>
                                {/each}
                            </select>
                            <InputError message={errors.learning_path_id} />
                        </div>
                        <Button
                            type="submit"
                            disabled={processing}
                            class="h-11 sm:h-9"
                        >
                            <Plus class="size-4" />
                            Affecter
                        </Button>
                    {/snippet}
                </Form>
                <p class="mt-2 text-xs text-muted-foreground">
                    Ses séquences arrivent masquées.
                </p>
            {/if}
        </section>
    {/if}

    <Link
        href={toUrl(promotionRoutes.show(promotion.id))}
        class="mono-link text-muted-foreground">← {promotion.name}</Link
    >
</div>

<script module lang="ts">
    import { index } from '@/routes/learning-paths';

    export const layout = {
        breadcrumbs: [{ title: 'Parcours', href: index() }],
    };
</script>

<script lang="ts">
    import { Form, Link, page, router } from '@inertiajs/svelte';
    import ArrowDown from '@lucide/svelte/icons/arrow-down';
    import ArrowUp from '@lucide/svelte/icons/arrow-up';
    import GripVertical from '@lucide/svelte/icons/grip-vertical';
    import Pencil from '@lucide/svelte/icons/pencil';
    import Plus from '@lucide/svelte/icons/plus';
    import X from '@lucide/svelte/icons/x';
    import LearningPathSequenceController from '@/actions/App/Http/Controllers/LearningPathSequenceController';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Label } from '@/components/ui/label';
    import { nativeFieldClass } from '@/lib/form';
    import { cn, toUrl } from '@/lib/utils';
    import { edit } from '@/routes/learning-paths';
    import * as promotionLearningPaths from '@/routes/promotions/learning-paths';
    import * as sequenceRoutes from '@/routes/sequences';
    import type { LearningPath } from '@/types/learning-path';
    import type { Sequence } from '@/types/sequence';

    let {
        learningPath,
        sequences,
        promotions,
        availableSequences,
    }: {
        learningPath: LearningPath;
        sequences: Sequence[];
        promotions: { id: number; name: string }[];
        availableSequences: { id: number; title: string }[];
    } = $props();

    // Optimistic order: updated at once, replaced by the server's answer.
    let order = $derived(sequences);
    let dragged: number | null = $state(null);

    const orderError = $derived(page.props.errors?.sequences);

    function save(next: Sequence[]): void {
        order = next;
        router.visit(
            LearningPathSequenceController.reorder(learningPath.id),
            {
                data: { sequences: next.map((sequence) => sequence.id) },
                preserveScroll: true,
                preserveState: true,
            },
        );
    }

    function move(from: number, to: number): void {
        if (to < 0 || to >= order.length || from === to) {
            return;
        }

        const next = [...order];
        const [item] = next.splice(from, 1);
        next.splice(to, 0, item);
        save(next);
    }
</script>

<AppHead title={learningPath.name} />

<div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="eyebrow">// Parcours</p>
            <h1 class="mt-1 display-title text-3xl sm:text-4xl">
                {learningPath.name}
            </h1>
            {#if learningPath.description}
                <p class="mt-2 text-muted-foreground">
                    {learningPath.description}
                </p>
            {/if}
            <p class="mt-1 text-sm text-muted-foreground">
                {#if promotions.length === 0}
                    Affecté à aucune promotion.
                {:else}
                    Promotion{promotions.length > 1 ? 's' : ''} :
                    {#each promotions as promotion, i (promotion.id)}
                        <Link
                            href={toUrl(
                                promotionLearningPaths.index(promotion.id),
                            )}
                            class="underline underline-offset-4"
                            >{promotion.name}</Link
                        >{i < promotions.length - 1 ? ', ' : ''}
                    {/each}
                {/if}
            </p>
        </div>

        <Button variant="outline" asChild class="h-11 w-full sm:h-9 sm:w-auto">
            {#snippet children(props)}
                <Link {...props} href={toUrl(edit(learningPath.id))}>
                    <Pencil class="size-4" />
                    Modifier
                </Link>
            {/snippet}
        </Button>
    </header>

    <section class="flex flex-col gap-3">
        <h2 class="eyebrow">
            {order.length} séquence{order.length > 1 ? 's' : ''}
        </h2>

        {#if orderError}
            <p class="text-sm text-destructive">{orderError}</p>
        {/if}

        {#if order.length === 0}
            <p
                class="rounded-2xl border border-dashed p-8 text-center text-sm text-muted-foreground"
            >
                Aucune séquence. Ajoute-en depuis la bibliothèque ci-dessous.
            </p>
        {:else}
            <ol class="flex flex-col gap-2">
                {#each order as sequence, i (sequence.id)}
                    <li
                        class={cn(
                            'flex items-center gap-2 surface p-2',
                            dragged === i && 'opacity-50',
                        )}
                        draggable="true"
                        ondragstart={() => (dragged = i)}
                        ondragend={() => (dragged = null)}
                        ondragover={(event) => event.preventDefault()}
                        ondrop={(event) => {
                            event.preventDefault();
                            if (dragged !== null) {
                                move(dragged, i);
                            }
                            dragged = null;
                        }}
                    >
                        <GripVertical
                            class="hidden size-4 shrink-0 cursor-grab text-muted-foreground md:block"
                            aria-hidden="true"
                        />
                        <span
                            class="w-6 shrink-0 text-center figures text-primary"
                            >{i + 1}</span
                        >
                        <Link
                            href={toUrl(sequenceRoutes.show(sequence.id))}
                            class="min-w-0 flex-1 py-2"
                        >
                            <span class="block truncate font-medium"
                                >{sequence.title}</span
                            >
                            {#if sequence.summary}
                                <span
                                    class="block truncate text-sm text-muted-foreground"
                                    >{sequence.summary}</span
                                >
                            {/if}
                        </Link>
                        <div class="flex shrink-0 items-center">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-11 sm:size-9"
                                disabled={i === 0}
                                aria-label={`Monter « ${sequence.title} »`}
                                onclick={() => move(i, i - 1)}
                            >
                                <ArrowUp class="size-4" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-11 sm:size-9"
                                disabled={i === order.length - 1}
                                aria-label={`Descendre « ${sequence.title} »`}
                                onclick={() => move(i, i + 1)}
                            >
                                <ArrowDown class="size-4" />
                            </Button>
                            <Form
                                {...LearningPathSequenceController.destroy.form(
                                    [learningPath.id, sequence.id],
                                )}
                                options={{ preserveScroll: true }}
                            >
                                {#snippet children({ processing })}
                                    <Button
                                        type="submit"
                                        variant="ghost"
                                        size="icon"
                                        class="size-11 text-muted-foreground hover:text-destructive sm:size-9"
                                        disabled={processing}
                                        aria-label={`Retirer « ${sequence.title} » du parcours`}
                                    >
                                        <X class="size-4" />
                                    </Button>
                                {/snippet}
                            </Form>
                        </div>
                    </li>
                {/each}
            </ol>
        {/if}
    </section>

    <section class="surface p-4">
        {#if availableSequences.length === 0}
            <p class="text-sm text-muted-foreground">
                Toutes les séquences sont déjà dans ce parcours.
                <Link
                    href={toUrl(sequenceRoutes.create())}
                    class="font-semibold text-primary underline underline-offset-4"
                    >Créer une séquence</Link
                >
            </p>
        {:else}
            <Form
                {...LearningPathSequenceController.store.form(learningPath.id)}
                options={{ preserveScroll: true }}
                resetOnSuccess
                class="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end"
            >
                {#snippet children({ errors, processing })}
                    <div class="grid gap-2">
                        <Label for="sequence_id">Ajouter une séquence</Label>
                        <select
                            id="sequence_id"
                            name="sequence_id"
                            required
                            class={cn(nativeFieldClass, 'h-11 sm:h-9 sm:py-1')}
                        >
                            <option value="">Choisir…</option>
                            {#each availableSequences as sequence (sequence.id)}
                                <option value={sequence.id}
                                    >{sequence.title}</option
                                >
                            {/each}
                        </select>
                        <InputError message={errors.sequence_id} />
                    </div>
                    <Button
                        type="submit"
                        disabled={processing}
                        class="h-11 sm:h-9"
                    >
                        <Plus class="size-4" />
                        Ajouter à la fin
                    </Button>
                {/snippet}
            </Form>
        {/if}
    </section>
</div>

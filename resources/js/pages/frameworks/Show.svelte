<script module lang="ts">
    import { index } from '@/routes/frameworks';

    export const layout = {
        breadcrumbs: [{ title: 'Référentiels', href: index() }],
    };
</script>

<script lang="ts">
    import { Form, Link, page } from '@inertiajs/svelte';
    import Pencil from '@lucide/svelte/icons/pencil';
    import Plus from '@lucide/svelte/icons/plus';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import CompetencyController from '@/actions/App/Http/Controllers/CompetencyController';
    import AppHead from '@/components/AppHead.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { cn, toUrl } from '@/lib/utils';
    import * as competencyRoutes from '@/routes/frameworks/competencies';
    import { edit } from '@/routes/frameworks';
    import * as promotionRoutes from '@/routes/promotions';
    import type { Competency, Framework } from '@/types/framework';

    let {
        framework,
        competencies,
        promotions,
    }: {
        framework: Framework;
        competencies: Competency[];
        promotions: { id: number; name: string }[];
    } = $props();

    const competencyError = $derived(page.props.errors?.competency);
</script>

<AppHead title={framework.name} />

<div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-xl font-semibold tracking-tight">{framework.name}</h1>
            {#if framework.description}
                <p class="text-sm text-muted-foreground">
                    {framework.description}
                </p>
            {/if}
            <p class="mt-1 text-sm text-muted-foreground">
                {#if promotions.length === 0}
                    Affecté à aucune promotion.
                {:else}
                    Promotion{promotions.length > 1 ? 's' : ''} :
                    {#each promotions as promotion, i (promotion.id)}
                        <Link
                            href={toUrl(promotionRoutes.show(promotion.id))}
                            class="underline underline-offset-4"
                            >{promotion.name}</Link
                        >{i < promotions.length - 1 ? ', ' : ''}
                    {/each}
                {/if}
            </p>
        </div>

        <div class="flex w-full gap-2 sm:w-auto">
            <Button variant="outline" asChild class="h-11 flex-1 sm:h-9">
                {#snippet children(props)}
                    <Link {...props} href={toUrl(edit(framework.id))}>
                        <Pencil class="size-4" />
                        Modifier
                    </Link>
                {/snippet}
            </Button>
            <Button asChild class="h-11 flex-1 sm:h-9">
                {#snippet children(props)}
                    <Link
                        {...props}
                        href={toUrl(competencyRoutes.create(framework.id))}
                    >
                        <Plus class="size-4" />
                        Compétence
                    </Link>
                {/snippet}
            </Button>
        </div>
    </header>

    {#if competencyError}
        <p class="rounded-xl border border-destructive p-4 text-sm text-destructive">
            {competencyError}
        </p>
    {/if}

    {#if competencies.length === 0}
        <p
            class="rounded-2xl border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            Aucune compétence pour l'instant.
        </p>
    {:else}
        <ol class="grid gap-3">
            {#each competencies as competency (competency.id)}
                <li class="flex flex-col gap-3 surface p-4">
                    <div class="flex items-start gap-3">
                        <Badge variant="outline" class="shrink-0 font-mono"
                            >{competency.code}</Badge
                        >
                        <div class="min-w-0 flex-1">
                            <p class="font-medium">{competency.name}</p>
                            {#if competency.description}
                                <p class="text-sm text-muted-foreground">
                                    {competency.description}
                                </p>
                            {/if}
                        </div>
                    </div>

                    {#if competency.type === 'binary'}
                        <p class="text-sm text-muted-foreground">
                            Acquis / non acquis
                        </p>
                    {:else}
                        <ul
                            class="flex flex-wrap gap-1.5"
                            aria-label="Niveaux de l'échelle"
                        >
                            {#each competency.levels as level (level.value)}
                                <li
                                    class={cn(
                                        'rounded-full border px-2.5 py-0.5 text-xs',
                                        competency.validation_threshold !==
                                            null &&
                                            level.value >=
                                                competency.validation_threshold
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'text-muted-foreground',
                                    )}
                                >
                                    {level.value} · {level.label}
                                </li>
                            {/each}
                        </ul>
                        <p class="text-xs text-muted-foreground">
                            Acquise à partir du niveau {competency.validation_threshold}.
                        </p>
                    {/if}

                    <div class="flex gap-2 sm:justify-end">
                        <Button
                            variant="outline"
                            size="sm"
                            asChild
                            class="h-11 flex-1 sm:h-9 sm:flex-none"
                        >
                            {#snippet children(props)}
                                <Link
                                    {...props}
                                    href={toUrl(
                                        competencyRoutes.edit([
                                            framework.id,
                                            competency.id,
                                        ]),
                                    )}
                                >
                                    <Pencil class="size-4" />
                                    Modifier
                                </Link>
                            {/snippet}
                        </Button>
                        <Form
                            {...CompetencyController.destroy.form([
                                framework.id,
                                competency.id,
                            ])}
                            options={{ preserveScroll: true }}
                            onBefore={() =>
                                confirm(
                                    `Supprimer la compétence ${competency.code} ?`,
                                )}
                            class="flex flex-1 sm:flex-none"
                        >
                            {#snippet children({ processing })}
                                <Button
                                    type="submit"
                                    variant="ghost"
                                    size="sm"
                                    disabled={processing}
                                    class="h-11 w-full sm:h-9"
                                >
                                    <Trash2 class="size-4" />
                                    Supprimer
                                </Button>
                            {/snippet}
                        </Form>
                    </div>
                </li>
            {/each}
        </ol>
    {/if}
</div>

<script module lang="ts">
    import { index } from '@/routes/promotions';

    export const layout = {
        breadcrumbs: [{ title: 'Promotions', href: index() }],
    };
</script>

<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import Archive from '@lucide/svelte/icons/archive';
    import ArchiveRestore from '@lucide/svelte/icons/archive-restore';
    import Pencil from '@lucide/svelte/icons/pencil';
    import Plus from '@lucide/svelte/icons/plus';
    import PromotionController from '@/actions/App/Http/Controllers/PromotionController';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import { Button } from '@/components/ui/button';
    import { formatDate } from '@/lib/dates';
    import { cn, toUrl } from '@/lib/utils';
    import { create, edit, show } from '@/routes/promotions';
    import type { Promotion } from '@/types/promotion';

    let {
        promotions,
        archived,
    }: {
        promotions: Promotion[];
        archived: boolean;
    } = $props();

    const tabs = [
        { label: 'En cours', archived: false },
        { label: 'Archivées', archived: true },
    ];
</script>

<AppHead title="Promotions" />

<div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <Heading
            title="Promotions"
            description="Gérez vos promotions d'apprenants."
        />

        <Button asChild class="w-full sm:w-auto">
            {#snippet children(props)}
                <Link {...props} href={toUrl(create())}>
                    <Plus class="size-4" />
                    Nouvelle promotion
                </Link>
            {/snippet}
        </Button>
    </div>

    <nav
        class="inline-flex w-full rounded-lg bg-muted p-1 sm:w-auto sm:self-start"
        aria-label="Filtrer les promotions"
    >
        {#each tabs as tab (tab.label)}
            <Link
                href={toUrl(index({ query: tab.archived ? { archived: 1 } : {} }))}
                aria-current={tab.archived === archived ? 'page' : undefined}
                class={cn(
                    'flex-1 rounded-md px-4 py-2 text-center text-sm font-medium transition-colors sm:flex-none',
                    tab.archived === archived
                        ? 'bg-background shadow-sm'
                        : 'text-muted-foreground hover:text-foreground',
                )}
            >
                {tab.label}
            </Link>
        {/each}
    </nav>

    {#if promotions.length === 0}
        <p
            class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            {archived
                ? 'Aucune promotion archivée.'
                : 'Aucune promotion en cours. Créez la première !'}
        </p>
    {:else}
        <ul class="grid gap-3">
            {#each promotions as promotion (promotion.id)}
                <li
                    class="flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="min-w-0">
                        <Link
                            href={toUrl(show(promotion.id))}
                            class="block truncate font-medium underline-offset-4 hover:underline"
                        >
                            {promotion.name}
                        </Link>
                        <p class="text-sm text-muted-foreground">
                            Du {formatDate(promotion.starts_on)} au {formatDate(
                                promotion.ends_on,
                            )}
                        </p>
                    </div>

                    <div class="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            asChild
                            class="h-11 flex-1 sm:h-9 sm:flex-none"
                        >
                            {#snippet children(props)}
                                <Link
                                    {...props}
                                    href={toUrl(edit(promotion.id))}
                                >
                                    <Pencil class="size-4" />
                                    Modifier
                                </Link>
                            {/snippet}
                        </Button>

                        <Form
                            {...(archived
                                ? PromotionController.unarchive.form(
                                      promotion.id,
                                  )
                                : PromotionController.archive.form(
                                      promotion.id,
                                  ))}
                            options={{ preserveScroll: true }}
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
                                    {#if archived}
                                        <ArchiveRestore class="size-4" />
                                        Désarchiver
                                    {:else}
                                        <Archive class="size-4" />
                                        Archiver
                                    {/if}
                                </Button>
                            {/snippet}
                        </Form>
                    </div>
                </li>
            {/each}
        </ul>
    {/if}
</div>

<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import Check from '@lucide/svelte/icons/check';
    import ChevronsUpDown from '@lucide/svelte/icons/chevrons-up-down';
    import CurrentPromotionController from '@/actions/App/Http/Controllers/CurrentPromotionController';
    import {
        DropdownMenu,
        DropdownMenuContent,
        DropdownMenuItem,
        DropdownMenuLabel,
        DropdownMenuSeparator,
        DropdownMenuTrigger,
    } from '@/components/ui/dropdown-menu';
    import { cn } from '@/lib/utils';

    type Option = { id: number; name: string };
    type Switcher = { current: Option | null; active: Option[]; archived: Option[] };

    let { compact = false }: { compact?: boolean } = $props();

    const switcher = $derived(page.props.promotionSwitcher as Switcher | null);
    const current = $derived(switcher?.current ?? null);

    function choose(option: Option): void {
        if (option.id !== current?.id) {
            router.visit(CurrentPromotionController.update(), { data: { promotion_id: option.id } });
        }
    }
</script>

{#if switcher && current}
    <DropdownMenu class="w-full min-w-0">
        <DropdownMenuTrigger asChild>
            {#snippet children(props)}
                <button
                    type="button"
                    onclick={props.onclick}
                    aria-expanded={props['aria-expanded']}
                    aria-label="Changer de promotion"
                    class={cn(
                        'flex w-full min-w-0 items-center gap-2 rounded-xl text-left transition-colors hover:bg-muted',
                        compact ? 'px-2 py-1.5' : 'px-3 py-2.5',
                    )}
                >
                    <span class="min-w-0 flex-1">
                        {#if !compact}
                            <span class="block text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                                >Promotion</span
                            >
                        {/if}
                        <span class="block truncate text-sm font-bold">{current.name}</span>
                    </span>
                    <ChevronsUpDown class="size-4 shrink-0 text-muted-foreground" />
                </button>
            {/snippet}
        </DropdownMenuTrigger>
        <DropdownMenuContent class="w-64 rounded-xl p-1" align="start" sideOffset={6}>
            {#if switcher.active.length > 0}
                <DropdownMenuLabel class="px-2 text-xs text-muted-foreground">En cours</DropdownMenuLabel>
                {#each switcher.active as option (option.id)}
                    <DropdownMenuItem asChild>
                        {#snippet children(props)}
                            <button
                                type="button"
                                class={cn(props.class, 'min-h-10 justify-between gap-2 rounded-lg font-medium')}
                                onclick={() => {
                                    props.onClick?.();
                                    choose(option);
                                }}
                            >
                                <span class="truncate">{option.name}</span>
                                {#if option.id === current?.id}
                                    <Check class="size-4 shrink-0 text-primary" />
                                {/if}
                            </button>
                        {/snippet}
                    </DropdownMenuItem>
                {/each}
            {/if}

            {#if switcher.archived.length > 0}
                <DropdownMenuSeparator />
                <DropdownMenuLabel class="px-2 text-xs text-muted-foreground">Archivées</DropdownMenuLabel>
                {#each switcher.archived as option (option.id)}
                    <DropdownMenuItem asChild>
                        {#snippet children(props)}
                            <button
                                type="button"
                                class={cn(props.class, 'min-h-10 justify-between gap-2 rounded-lg text-muted-foreground/70')}
                                onclick={() => {
                                    props.onClick?.();
                                    choose(option);
                                }}
                            >
                                <span class="truncate">{option.name}</span>
                                {#if option.id === current?.id}
                                    <Check class="size-4 shrink-0" />
                                {/if}
                            </button>
                        {/snippet}
                    </DropdownMenuItem>
                {/each}
            {/if}
        </DropdownMenuContent>
    </DropdownMenu>
{/if}

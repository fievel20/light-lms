<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import { currentUrlState } from '@/lib/currentUrl.svelte';
    import { activeHref, mobileNavItems } from '@/lib/navigation';
    import { cn, toUrl } from '@/lib/utils';

    const url = currentUrlState();
    const currentPromotionId = $derived(
        (page.props.promotionSwitcher as { current: { id: number } | null } | null)?.current?.id ?? null,
    );
    const items = $derived(mobileNavItems(page.props.auth.user, currentPromotionId));
    const active = $derived(activeHref(items, url.currentUrl));
</script>

<nav
    class="fixed inset-x-0 bottom-0 z-40 border-t border-border/60 bg-background/90 pb-[env(safe-area-inset-bottom)] backdrop-blur-lg md:hidden"
    aria-label="Navigation principale"
>
    <ul class="mx-auto flex max-w-md">
        {#each items as item (toUrl(item.href))}
            {@const isActive = toUrl(item.href) === active}
            <li class="min-w-0 flex-1">
                <Link
                    href={toUrl(item.href)}
                    aria-current={isActive ? 'page' : undefined}
                    class={cn(
                        'flex h-16 flex-col items-center justify-center gap-1 text-[11px] font-semibold transition-colors',
                        isActive ? 'text-primary' : 'text-muted-foreground',
                    )}
                >
                    <span
                        class={cn(
                            'grid h-7 w-12 place-items-center rounded-full transition-colors',
                            isActive && 'bg-primary-soft',
                        )}
                    >
                        {#if item.icon}
                            <item.icon class="size-5" />
                        {/if}
                    </span>
                    <span class="max-w-full truncate px-1">{item.title}</span>
                </Link>
            </li>
        {/each}
    </ul>
</nav>

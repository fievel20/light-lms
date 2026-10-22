<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import { currentUrlState } from '@/lib/currentUrl.svelte';
    import { mobileNavItems } from '@/lib/navigation';
    import { cn, toUrl } from '@/lib/utils';
    import { dashboard } from '@/routes';

    const url = currentUrlState();
    const items = $derived(mobileNavItems(page.props.auth.user));

    function isActive(href: (typeof items)[number]['href']): boolean {
        // The home tab only matches itself; other tabs match their sub-pages.
        return toUrl(href) === toUrl(dashboard())
            ? url.isCurrentUrl(href, url.currentUrl)
            : url.isCurrentOrParentUrl(href, url.currentUrl);
    }
</script>

<nav
    class="fixed inset-x-0 bottom-0 z-40 border-t border-border/60 bg-background/90 pb-[env(safe-area-inset-bottom)] backdrop-blur-lg md:hidden"
    aria-label="Navigation principale"
>
    <ul class="mx-auto flex max-w-md">
        {#each items as item (toUrl(item.href))}
            {@const active = isActive(item.href)}
            <li class="flex-1">
                <Link
                    href={toUrl(item.href)}
                    aria-current={active ? 'page' : undefined}
                    class={cn(
                        'flex h-16 flex-col items-center justify-center gap-1 text-[11px] font-semibold transition-colors',
                        active ? 'text-primary' : 'text-muted-foreground',
                    )}
                >
                    <span
                        class={cn(
                            'grid h-7 w-12 place-items-center rounded-full transition-colors',
                            active && 'bg-primary-soft',
                        )}
                    >
                        {#if item.icon}
                            <item.icon class="size-5" />
                        {/if}
                    </span>
                    {item.title}
                </Link>
            </li>
        {/each}
    </ul>
</nav>

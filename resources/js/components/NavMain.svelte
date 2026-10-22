<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import {
        SidebarGroup,
        SidebarMenu,
        SidebarMenuButton,
        SidebarMenuItem,
    } from '@/components/ui/sidebar';
    import { currentUrlState } from '@/lib/currentUrl.svelte';
    import { toUrl } from '@/lib/utils';
    import { dashboard } from '@/routes';
    import type { NavItem } from '@/types';

    let {
        items = [],
    }: {
        items: NavItem[];
    } = $props();

    const url = currentUrlState();
</script>

<SidebarGroup class="px-2 py-2">
    <SidebarMenu class="gap-1">
        {#each items as item (toUrl(item.href))}
            <SidebarMenuItem>
                <SidebarMenuButton
                    asChild
                    size="lg"
                    isActive={toUrl(item.href) === toUrl(dashboard())
                        ? url.isCurrentUrl(item.href, url.currentUrl)
                        : url.isCurrentOrParentUrl(item.href, url.currentUrl)}
                    tooltip={item.title}
                    class="h-10 rounded-xl font-medium data-[active=true]:bg-primary-soft data-[active=true]:font-semibold data-[active=true]:text-primary"
                >
                    {#snippet children(props)}
                        <Link
                            {...props}
                            href={toUrl(item.href)}
                            class={props.class}
                        >
                            {#if item.icon}
                                <item.icon class="size-4 shrink-0" />
                            {/if}
                            <span>{item.title}</span>
                        </Link>
                    {/snippet}
                </SidebarMenuButton>
            </SidebarMenuItem>
        {/each}
    </SidebarMenu>
</SidebarGroup>

<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import AppLogo from '@/components/AppLogo.svelte';
    import NavMain from '@/components/NavMain.svelte';
    import NavUser from '@/components/NavUser.svelte';
    import {
        Sidebar,
        SidebarContent,
        SidebarFooter,
        SidebarHeader,
        SidebarMenu,
        SidebarMenuButton,
        SidebarMenuItem,
    } from '@/components/ui/sidebar';
    import PromotionSwitcher from '@/components/navigation/PromotionSwitcher.svelte';
    import { navGroups } from '@/lib/navigation';
    import { toUrl } from '@/lib/utils';
    import { dashboard } from '@/routes';

    let {
        children,
    }: {
        children?: Snippet;
    } = $props();

    const currentPromotionId = $derived(
        (page.props.promotionSwitcher as { current: { id: number } | null } | null)?.current?.id ?? null,
    );
    const groups = $derived(navGroups(page.props.auth.user, currentPromotionId));
</script>

<Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
        <SidebarMenu>
            <SidebarMenuItem>
                <SidebarMenuButton size="lg" asChild>
                    {#snippet children(props)}
                        <Link
                            {...props}
                            href={toUrl(dashboard())}
                            class={props.class}
                        >
                            <AppLogo />
                        </Link>
                    {/snippet}
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
        <div class="group-data-[collapsible=icon]:hidden">
            <PromotionSwitcher />
        </div>
    </SidebarHeader>

    <SidebarContent>
        <NavMain {groups} />
    </SidebarContent>

    <SidebarFooter>
        <NavUser />
    </SidebarFooter>
</Sidebar>
{@render children?.()}

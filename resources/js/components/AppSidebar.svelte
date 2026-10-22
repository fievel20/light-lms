<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import FolderGit2 from '@lucide/svelte/icons/folder-git-2';
    import LayoutGrid from '@lucide/svelte/icons/layout-grid';
    import Users from '@lucide/svelte/icons/users';
    import type { Snippet } from 'svelte';
    import AppLogo from '@/components/AppLogo.svelte';
    import NavFooter from '@/components/NavFooter.svelte';
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
    import { toUrl } from '@/lib/utils';
    import { dashboard } from '@/routes';
    import * as promotions from '@/routes/promotions';
    import type { NavItem } from '@/types';

    let {
        children,
    }: {
        children?: Snippet;
    } = $props();

    const isTrainer = $derived(page.props.auth.user?.role === 'trainer');

    const mainNavItems: NavItem[] = $derived([
        {
            title: 'Tableau de bord',
            href: dashboard(),
            icon: LayoutGrid,
        },
        ...(isTrainer
            ? [
                  {
                      title: 'Promotions',
                      href: promotions.index(),
                      icon: Users,
                  },
              ]
            : []),
    ]);

    const footerNavItems: NavItem[] = [
        {
            title: 'Code source',
            href: 'https://github.com/fievel20/light-lms',
            icon: FolderGit2,
        },
    ];
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
    </SidebarHeader>

    <SidebarContent>
        <NavMain items={mainNavItems} />
    </SidebarContent>

    <SidebarFooter>
        <NavFooter items={footerNavItems} />
        <NavUser />
    </SidebarFooter>
</Sidebar>
{@render children?.()}

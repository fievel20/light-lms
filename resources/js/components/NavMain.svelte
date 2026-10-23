<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import {
        SidebarGroup,
        SidebarGroupLabel,
        SidebarMenu,
        SidebarMenuButton,
        SidebarMenuItem,
    } from '@/components/ui/sidebar';
    import { currentUrlState } from '@/lib/currentUrl.svelte';
    import { activeHref, type NavGroup } from '@/lib/navigation';
    import { toUrl } from '@/lib/utils';

    let {
        groups = [],
    }: {
        groups: NavGroup[];
    } = $props();

    const url = currentUrlState();
    const active = $derived(
        activeHref(
            groups.flatMap((group) => group.items),
            url.currentUrl,
        ),
    );
</script>

{#each groups as group, index (group.label ?? index)}
    <SidebarGroup class="px-2 py-2">
        {#if group.label}
            <SidebarGroupLabel class="eyebrow">// {group.label}</SidebarGroupLabel>
        {/if}
        <SidebarMenu class="gap-1">
            {#each group.items as item (toUrl(item.href))}
                <SidebarMenuItem>
                    <SidebarMenuButton
                        asChild
                        size="lg"
                        isActive={toUrl(item.href) === active}
                        tooltip={item.title}
                        class="h-10 rounded-xl font-medium data-[active=true]:bg-primary-soft data-[active=true]:font-semibold data-[active=true]:text-primary"
                    >
                        {#snippet children(props)}
                            <Link {...props} href={toUrl(item.href)} class={props.class}>
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
{/each}

<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import AppLogo from '@/components/AppLogo.svelte';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
    import PromotionSwitcher from '@/components/navigation/PromotionSwitcher.svelte';
    import Breadcrumbs from '@/components/Breadcrumbs.svelte';
    import { Avatar, AvatarFallback } from '@/components/ui/avatar';
    import { SidebarTrigger } from '@/components/ui/sidebar';
    import { getInitials } from '@/lib/initials';
    import { toUrl } from '@/lib/utils';
    import { dashboard } from '@/routes';
    import { edit as profile } from '@/routes/profile';
    import type { BreadcrumbItem } from '@/types';

    let {
        breadcrumbs = [],
    }: {
        breadcrumbs?: BreadcrumbItem[];
    } = $props();

    const user = $derived(page.props.auth.user);
</script>

<!-- Phones: logo and avatar only, navigation lives in the bottom tab bar. -->
<header
    class="sticky top-0 z-30 flex h-14 items-center justify-between bg-background/85 px-4 backdrop-blur-lg md:hidden"
>
    {#if page.props.promotionSwitcher}
        <div class="flex min-w-0 flex-1 items-center gap-2 pr-3">
            <Link
                href={toUrl(dashboard())}
                class="grid size-8 shrink-0 place-items-center rounded-xl bg-primary text-primary-foreground"
                aria-label="Accueil"
            >
                <AppLogoIcon class="size-5" />
            </Link>
            <div class="min-w-0 flex-1">
                <PromotionSwitcher compact />
            </div>
        </div>
    {:else}
        <Link href={toUrl(dashboard())} class="flex items-center gap-1">
            <AppLogo />
        </Link>
    {/if}
    {#if user}
        <Link href={toUrl(profile())} aria-label="Mon profil">
            <Avatar class="size-9">
                <AvatarFallback class="bg-primary-soft text-sm font-bold text-primary">
                    {getInitials(user.name)}
                </AvatarFallback>
            </Avatar>
        </Link>
    {/if}
</header>

<!-- Tablets and desktops: sidebar toggle and breadcrumbs. -->
<header
    class="hidden h-14 shrink-0 items-center gap-2 px-6 transition-[width,height] ease-linear md:flex md:px-4"
>
    <SidebarTrigger class="-ml-1" />
    {#if breadcrumbs && breadcrumbs.length > 0}
        <Breadcrumbs {breadcrumbs} />
    {/if}
</header>

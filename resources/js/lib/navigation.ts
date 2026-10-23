import Award from '@lucide/svelte/icons/award';
import Library from '@lucide/svelte/icons/library';
import LayoutGrid from '@lucide/svelte/icons/layout-grid';
import Target from '@lucide/svelte/icons/target';
import UserRound from '@lucide/svelte/icons/user-round';
import Users from '@lucide/svelte/icons/users';
import { dashboard } from '@/routes';
import * as frameworks from '@/routes/frameworks';
import * as myBadges from '@/routes/my-badges';
import * as myCompetencies from '@/routes/my-competencies';
import * as promotions from '@/routes/promotions';
import { edit as profile } from '@/routes/profile';
import type { NavItem, User } from '@/types';

/**
 * Main navigation, shared by the desktop sidebar and the mobile tab bar.
 */
export function mainNavItems(user: User | null | undefined): NavItem[] {
    const home: NavItem = {
        title: 'Accueil',
        href: dashboard(),
        icon: LayoutGrid,
    };

    if (user?.role === 'trainer') {
        return [
            home,
            { title: 'Promotions', href: promotions.index(), icon: Users },
            { title: 'Référentiels', href: frameworks.index(), icon: Library },
        ];
    }

    return [
        home,
        { title: 'Compétences', href: myCompetencies.index(), icon: Target },
        { title: 'Badges', href: myBadges.index(), icon: Award },
    ];
}

/**
 * Tab bar items on phones: main navigation plus the profile.
 */
export function mobileNavItems(user: User | null | undefined): NavItem[] {
    return [
        ...mainNavItems(user),
        { title: 'Profil', href: profile(), icon: UserRound },
    ];
}

import Award from '@lucide/svelte/icons/award';
import ChartColumn from '@lucide/svelte/icons/chart-column';
import LayoutGrid from '@lucide/svelte/icons/layout-grid';
import Library from '@lucide/svelte/icons/library';
import Target from '@lucide/svelte/icons/target';
import UserRound from '@lucide/svelte/icons/user-round';
import Users from '@lucide/svelte/icons/users';
import UsersRound from '@lucide/svelte/icons/users-round';
import { toUrl } from '@/lib/utils';
import { dashboard } from '@/routes';
import * as frameworks from '@/routes/frameworks';
import * as myBadges from '@/routes/my-badges';
import * as myCompetencies from '@/routes/my-competencies';
import { edit as profile } from '@/routes/profile';
import * as promotions from '@/routes/promotions';
import type { NavItem, User } from '@/types';

export type NavGroup = { label?: string; items: NavItem[] };

const home = (): NavItem => ({
    title: 'Accueil',
    href: dashboard(),
    icon: LayoutGrid,
});

/**
 * Sidebar navigation. For the trainer, the first group is about the
 * promotion chosen in the menu.
 */
export function navGroups(
    user: User | null | undefined,
    currentPromotionId: number | null,
): NavGroup[] {
    if (user?.role !== 'trainer') {
        return [
            {
                items: [
                    home(),
                    {
                        title: 'Compétences',
                        href: myCompetencies.index(),
                        icon: Target,
                    },
                    { title: 'Badges', href: myBadges.index(), icon: Award },
                ],
            },
        ];
    }

    return [
        {
            label: 'Promotion',
            items: [
                home(),
                ...(currentPromotionId
                    ? [
                          {
                              title: 'Suivi',
                              href: promotions.progress(currentPromotionId),
                              icon: ChartColumn,
                          },
                          {
                              title: 'Apprenants',
                              href: promotions.show(currentPromotionId),
                              icon: UsersRound,
                          },
                      ]
                    : []),
            ],
        },
        {
            label: 'Administration',
            items: [
                { title: 'Promotions', href: promotions.index(), icon: Users },
                {
                    title: 'Référentiels',
                    href: frameworks.index(),
                    icon: Library,
                },
            ],
        },
    ];
}

/**
 * Tab bar items on phones. Learners also get their profile here; the trainer
 * reaches it through the avatar of the header.
 */
export function mobileNavItems(
    user: User | null | undefined,
    currentPromotionId: number | null,
): NavItem[] {
    const items = navGroups(user, currentPromotionId).flatMap(
        (group) => group.items,
    );

    return user?.role === 'trainer'
        ? items
        : [...items, { title: 'Profil', href: profile(), icon: UserRound }];
}

/**
 * The item matching the current URL best: exact match for the home page,
 * otherwise the longest matching prefix.
 */
export function activeHref(
    items: NavItem[],
    currentUrl: string,
): string | null {
    let best: string | null = null;

    for (const item of items) {
        const href = toUrl(item.href);

        if (typeof href !== 'string') {
            continue;
        }

        const matches =
            href === toUrl(dashboard())
                ? currentUrl === href
                : currentUrl === href || currentUrl.startsWith(`${href}/`);

        if (matches && (best === null || href.length > best.length)) {
            best = href;
        }
    }

    return best;
}

import BookOpen from '@lucide/svelte/icons/book-open';
import ChartColumn from '@lucide/svelte/icons/chart-column';
import Eye from '@lucide/svelte/icons/eye';
import LayoutGrid from '@lucide/svelte/icons/layout-grid';
import Route from '@lucide/svelte/icons/route';
import Shapes from '@lucide/svelte/icons/shapes';
import Library from '@lucide/svelte/icons/library';
import Target from '@lucide/svelte/icons/target';
import UserRound from '@lucide/svelte/icons/user-round';
import Users from '@lucide/svelte/icons/users';
import UsersRound from '@lucide/svelte/icons/users-round';
import { toUrl } from '@/lib/utils';
import { dashboard } from '@/routes';
import * as frameworks from '@/routes/frameworks';
import * as icons from '@/routes/icons';
import * as learningPaths from '@/routes/learning-paths';
import * as myCompetencies from '@/routes/my-competencies';
import * as myLearningPaths from '@/routes/my-learning-paths';
import { edit as profile } from '@/routes/profile';
import * as promotions from '@/routes/promotions';
import * as promotionCompetencies from '@/routes/promotions/competencies';
import * as promotionLearningPaths from '@/routes/promotions/learning-paths';
import * as promotionLearners from '@/routes/promotions/learners';
import * as sequences from '@/routes/sequences';
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
                    {
                        title: 'Parcours',
                        href: myLearningPaths.index(),
                        icon: Route,
                    },
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
                              title: 'Par compétence',
                              href: promotionCompetencies.index(
                                  currentPromotionId,
                              ),
                              icon: Target,
                          },
                          {
                              title: 'Par apprenant',
                              href: promotionLearners.index(currentPromotionId),
                              icon: UsersRound,
                          },
                          {
                              title: 'Suivi',
                              href: promotions.progress(currentPromotionId),
                              icon: ChartColumn,
                          },
                          {
                              title: 'Séquences ouvertes',
                              href: promotionLearningPaths.index(
                                  currentPromotionId,
                              ),
                              icon: Eye,
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
                {
                    title: 'Séquences',
                    href: sequences.index(),
                    icon: BookOpen,
                },
                {
                    title: 'Parcours',
                    href: learningPaths.index(),
                    icon: Route,
                },
                { title: 'Icônes', href: icons.index(), icon: Shapes },
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

    if (user?.role !== 'trainer') {
        return [
            ...items,
            { title: 'Profil', href: profile(), icon: UserRound },
        ];
    }

    // Five tabs at most on a phone: short titles, administration grouped.
    const short: Record<string, string> = {
        'Par compétence': 'Compétences',
        'Par apprenant': 'Apprenants',
        Promotions: 'Gestion',
    };

    return items
        .filter(
            (item) =>
                ![
                    'Séquences ouvertes',
                    'Référentiels',
                    'Séquences',
                    'Parcours',
                    'Icônes',
                ].includes(item.title),
        )
        .map((item) => ({ ...item, title: short[item.title] ?? item.title }));
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

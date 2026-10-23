import Eye from '@lucide/svelte/icons/eye';
import Footprints from '@lucide/svelte/icons/footprints';
import Gem from '@lucide/svelte/icons/gem';
import GraduationCap from '@lucide/svelte/icons/graduation-cap';
import ListChecks from '@lucide/svelte/icons/list-checks';
import Mountain from '@lucide/svelte/icons/mountain';
import Rocket from '@lucide/svelte/icons/rocket';
import Trophy from '@lucide/svelte/icons/trophy';
import type { Component } from 'svelte';

type BadgeStyle = {
    icon: Component<{ class?: string }>;
    /** Tailwind gradient classes for the earned medal. */
    gradient: string;
};

const styles: Record<string, BadgeStyle> = {
    'premier-pas': { icon: Footprints, gradient: 'from-sky-400 to-blue-600' },
    'premiere-reussite': {
        icon: Trophy,
        gradient: 'from-amber-300 to-orange-500',
    },
    assidu: { icon: ListChecks, gradient: 'from-violet-400 to-indigo-600' },
    lucide: { icon: Eye, gradient: 'from-teal-300 to-emerald-600' },
    expert: { icon: Gem, gradient: 'from-fuchsia-400 to-purple-700' },
    'mi-parcours': { icon: Mountain, gradient: 'from-lime-300 to-green-600' },
    'sprint-final': { icon: Rocket, gradient: 'from-rose-400 to-red-600' },
    diplomable: {
        icon: GraduationCap,
        gradient: 'from-yellow-300 to-amber-600',
    },
};

export function badgeStyle(key: string): BadgeStyle {
    return (
        styles[key] ?? {
            icon: Trophy,
            gradient: 'from-violet-400 to-indigo-600',
        }
    );
}

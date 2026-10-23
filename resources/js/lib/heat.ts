import type { Competency } from '@/types/framework';

export type HeatCell = {
    self: number | null;
    trainer: number | null;
    acquired: boolean;
    gap: boolean;
    to_review: boolean;
};

/**
 * Colour of a learner × competency cell in the heat map:
 * green when acquired, amber getting stronger as the validated level nears
 * the threshold, light violet when only self-assessed, empty otherwise.
 */
export function heatClass(competency: Competency, cell: HeatCell): string {
    if (cell.acquired) {
        return 'bg-success text-success-foreground';
    }

    if (cell.trainer !== null) {
        const ratio = progressTowardsThreshold(competency, cell.trainer);

        if (ratio >= 0.67) {
            return 'bg-warning/80 text-warning-foreground';
        }

        return ratio >= 0.34
            ? 'bg-warning/50 text-warning-foreground'
            : 'bg-warning/25 text-warning-foreground dark:text-warning';
    }

    if (cell.self !== null) {
        return 'bg-primary-soft text-primary';
    }

    return 'border border-dashed border-border';
}

function progressTowardsThreshold(
    competency: Competency,
    value: number,
): number {
    if (competency.type === 'binary') {
        return value >= 1 ? 1 : 0;
    }

    const values = competency.levels.map((level) => level.value);
    const min = Math.min(...values);
    const threshold = competency.validation_threshold ?? Math.max(...values);

    return threshold === min
        ? 0
        : Math.min(1, Math.max(0, (value - min) / (threshold - min)));
}

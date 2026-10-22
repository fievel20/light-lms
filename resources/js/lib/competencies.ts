import type { Competency, ScaleLevel } from '@/types/framework';

/**
 * The values a competency can take, with their labels.
 */
export function competencyOptions(competency: Competency): ScaleLevel[] {
    return competency.type === 'binary'
        ? [
              { value: 0, label: 'Non acquis' },
              { value: 1, label: 'Acquis' },
          ]
        : competency.levels;
}

export function valueLabel(competency: Competency, value: number): string {
    return (
        competencyOptions(competency).find((option) => option.value === value)
            ?.label ?? String(value)
    );
}

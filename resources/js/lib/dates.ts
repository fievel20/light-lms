const dateFormatter = new Intl.DateTimeFormat('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    timeZone: 'UTC',
});

const relativeFormatter = new Intl.RelativeTimeFormat('fr-FR', {
    numeric: 'auto',
});

/**
 * Formate une date ISO (AAAA-MM-JJ) en français, ex. « 1 sept. 2026 ».
 */
export function formatDate(isoDate: string): string {
    return dateFormatter.format(new Date(`${isoDate.slice(0, 10)}T00:00:00Z`));
}

/**
 * Formate un instant ISO de façon relative, ex. « il y a 2 heures », « hier ».
 */
export function formatRelative(
    isoDateTime: string,
    now: Date = new Date(),
): string {
    const seconds = Math.round(
        (new Date(isoDateTime).getTime() - now.getTime()) / 1000,
    );
    const units: [Intl.RelativeTimeFormatUnit, number][] = [
        ['year', 31_536_000],
        ['month', 2_592_000],
        ['week', 604_800],
        ['day', 86_400],
        ['hour', 3_600],
        ['minute', 60],
    ];

    for (const [unit, size] of units) {
        if (Math.abs(seconds) >= size) {
            return relativeFormatter.format(Math.round(seconds / size), unit);
        }
    }

    return 'à l’instant';
}

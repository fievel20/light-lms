const dateFormatter = new Intl.DateTimeFormat('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    timeZone: 'UTC',
});

/**
 * Formate une date ISO (AAAA-MM-JJ) en français, ex. « 1 sept. 2026 ».
 */
export function formatDate(isoDate: string): string {
    return dateFormatter.format(new Date(`${isoDate.slice(0, 10)}T00:00:00Z`));
}

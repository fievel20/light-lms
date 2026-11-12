// Short, unambiguous French words: easy to read out and to type.
const words = [
    'Ciel',
    'Pomme',
    'Velo',
    'Lune',
    'Tigre',
    'Neige',
    'Plage',
    'Robot',
    'Fusee',
    'Moulin',
    'Rivage',
    'Cactus',
    'Banane',
    'Jardin',
    'Orange',
    'Piano',
    'Soleil',
    'Tortue',
    'Violet',
    'Zebre',
];
const symbols = '!?#*+=';

function pick<T>(items: ArrayLike<T>): T {
    const [random] = crypto.getRandomValues(new Uint32Array(1));

    return items[random % items.length];
}

/**
 * A temporary password that meets the production rules (12+ characters,
 * upper and lower case, digits, a symbol), e.g. "Pomme-Tigre-4827!".
 */
export function readablePassword(): string {
    const digits = Array.from({ length: 4 }, () => pick('0123456789')).join('');

    return `${pick(words)}-${pick(words)}-${digits}${pick(symbols)}`;
}

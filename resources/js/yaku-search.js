// Recherche des yaku au fil de la frappe (portée de la référence) :
// noms, alias (romanisations, anglais, français), glosses, et une faute de frappe tolérée.

export const norm = (text) =>
    text
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, ' ')
        .trim();

export const compact = (text) => text.replace(/ /g, '');

// Ce que l'on tape souvent et qui n'est pas un yaku.
const NOT_YAKU = [
    [/^(aka|ura)?(dora|doras)$|^(aka|ura)$/, 'Les dora ne sont pas des yaku : ils se comptent à l’étape 3.'],
    [/^nagashi/, 'Nagashi mangan se gagne sur les défausses, pas avec une main : il n’apparaît pas ici.'],
    [/^(fu|han|mangan|haneman|baiman)$/, 'Les han, les fu et les limites viennent aux étapes suivantes.'],
];

function levenshtein(a, b) {
    const d = Array.from({ length: a.length + 1 }, (_, i) => [i]);
    for (let j = 1; j <= b.length; j++) d[0][j] = j;
    for (let i = 1; i <= a.length; i++) {
        for (let j = 1; j <= b.length; j++) {
            d[i][j] = Math.min(d[i - 1][j] + 1, d[i][j - 1] + 1, d[i - 1][j - 1] + (a[i - 1] === b[j - 1] ? 0 : 1));
        }
    }
    return d[a.length][b.length];
}

/**
 * Prépare les entrées : { id, label, name, gloss, description, aliases, yakuman, han }.
 */
export function prepare(entries) {
    return entries.map((entry, order) => {
        const names = [norm(entry.label), norm(entry.name), ...entry.aliases.map(norm)];
        const gloss = norm(entry.gloss);
        return {
            ...entry,
            order,
            names,
            compacts: names.map(compact),
            words: names.flatMap((name) => name.split(' ')),
            gloss,
            glossWords: gloss.split(' '),
        };
    });
}

// Rang d'une entrée pour la saisie (plus petit = meilleur), ou Infinity.
function rank(entry, query, queryCompact) {
    if (entry.compacts[0].startsWith(queryCompact) || entry.compacts[1].startsWith(queryCompact)) return 0;
    if (entry.compacts.some((c) => c.startsWith(queryCompact)) || entry.words.some((w) => w.startsWith(query))) return 1;
    if (compact(entry.gloss).startsWith(queryCompact) || entry.glossWords.some((w) => w.startsWith(query))) return 2;
    if (queryCompact.length >= 3 && (entry.compacts.some((c) => c.includes(queryCompact)) || compact(entry.gloss).includes(queryCompact))) return 3;
    // Une faute de frappe : « richi » → riichi.
    if (queryCompact.length >= 4 && entry.compacts.some((c) => [-1, 0, 1].some((k) => levenshtein(queryCompact, c.slice(0, queryCompact.length + k)) <= 1))) return 4;
    return Infinity;
}

export function suggest(entries, text, exclude = []) {
    const query = norm(text);
    const queryCompact = compact(query);
    if (!queryCompact) return [];
    return entries
        .filter((entry) => !exclude.includes(entry.id))
        .map((entry) => [entry, rank(entry, query, queryCompact)])
        .filter(([, r]) => r < Infinity)
        .sort((a, b) => a[1] - b[1] || a[0].order - b[0].order)
        .map(([entry]) => entry);
}

export function notYakuHint(text) {
    const q = compact(norm(text));
    const found = q && NOT_YAKU.find(([pattern]) => pattern.test(q));
    return found ? found[1] : null;
}

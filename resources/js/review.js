// La liste « À revoir » de l'accueil : la main redessinée en petit, et ce qu'en dit sa graine.
//
// Les tuiles arrivent en abrégé (App\Domain\Mahjong\Generation\GeneratedHand::tableCodes) :
// « 12 », « 4r » pour un cinq rouge, « s20 » pour une tuile couchée, « x » pour une tuile retournée.
// Le dessin reprend celui du composant Blade x-tile.

const CODE = /^(s?)(\d{1,2})(r?)$/;

/**
 * Une tuile, en HTML. Un code inconnu (entrée abîmée dans le stockage du navigateur) ne donne rien.
 */
export function tileHtml(code, sprite, extraClass = '') {
    const extra = extraClass ? ` ${extraClass}` : '';

    if (code === 'x') {
        return `<span class="tile tile--down${extra}"><svg viewBox="0 0 34 46" focusable="false"><use href="${sprite}#tile-down"/></svg></span>`;
    }

    const match = CODE.exec(String(code));
    if (!match || Number(match[2]) > 33) return '';

    const face = `${sprite}#f-${match[2]}${match[3]}`;

    if (match[1]) {
        return `<span class="tile tile--side${extra}"><svg viewBox="0 0 40 40" focusable="false"><use href="${sprite}#tile-side"/><g transform="translate(0 32) rotate(-90)"><use href="${face}" width="30" height="40"/></g></svg></span>`;
    }

    return `<span class="tile${extra}"><svg viewBox="0 0 34 46" focusable="false"><use href="${sprite}#tile-up"/><use href="${face}" x="2" width="30" height="40"/></svg></span>`;
}

/**
 * La main comme sur la table : les tuiles cachées, la tuile gagnante (soulignée d'or), puis les appels.
 */
export function handHtml(tiles, sprite) {
    if (!tiles || !Array.isArray(tiles.hand)) return '';

    const row = (codes, extraClass = '') => codes.map((code) => tileHtml(code, sprite, extraClass)).join('');
    const groups = [
        `<span class="tiles gap-px">${row(tiles.hand)}</span>`,
        `<span class="tiles">${row([tiles.win], 'rounded-[3px] outline-2 outline-offset-1 outline-gilt')}</span>`,
        ...(Array.isArray(tiles.melds) ? tiles.melds : []).map((meld) => `<span class="tiles">${row(Array.isArray(meld) ? meld : [])}</span>`),
    ];

    return groups.join('');
}

const SEED = /^(?:([a-z]+)-)?(?:([a-z]+)-)?[a-z0-9]{8}$/;
const DAILY = /^defi-(\d{4}-\d{2}-\d{2})$/;

/**
 * Ce que dit la graine, comme App\Domain\Mahjong\Generation\Seed : « Défi du 12 novembre », « Expert · Chinitsu »…
 *
 * labels : { themes: {valeur: nom}, levels: {valeur: nom}, free: 'Main libre', daily: 'Défi du :day' }
 */
export function seedCaption(seed, labels, locale = 'fr') {
    const daily = DAILY.exec(seed);
    if (daily) {
        const day = new Date(`${daily[1]}T12:00:00Z`).toLocaleDateString(locale, { day: 'numeric', month: 'long', timeZone: 'UTC' });
        return labels.daily.replace(':day', day);
    }

    const words = (SEED.exec(seed) ?? []).slice(1).filter(Boolean);
    const parts = [];

    if (words.length && words[0] !== 'normal' && labels.levels[words[0]]) {
        parts.push(labels.levels[words.shift()]);
    }

    parts.push(words.length === 1 && labels.themes[words[0]] ? labels.themes[words[0]] : labels.free);

    return parts.join(' · ');
}

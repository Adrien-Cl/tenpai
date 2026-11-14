import { handHtml, seedCaption, tileHtml } from '../../resources/js/review';

const SPRITE = '/tuiles.svg?v=1';

describe('tileHtml', () => {
    it('dessine une tuile debout, avec son cinq rouge', () => {
        expect(tileHtml('4r', SPRITE)).toContain(`href="${SPRITE}#f-4r"`);
        expect(tileHtml('12', SPRITE)).toContain('#tile-up');
    });

    it('couche la tuile prise à un adversaire, retourne celles du kan fermé', () => {
        expect(tileHtml('s20', SPRITE)).toContain('tile--side');
        expect(tileHtml('s20', SPRITE)).toContain('#f-20"');
        expect(tileHtml('x', SPRITE)).toContain('#tile-down');
    });

    it('ignore un code inconnu', () => {
        expect(tileHtml('<img src=x>', SPRITE)).toBe('');
        expect(tileHtml('34', SPRITE)).toBe('');
        expect(tileHtml(null, SPRITE)).toBe('');
    });
});

describe('handHtml', () => {
    it('pose la main, la tuile gagnante puis les appels', () => {
        const html = handHtml({ hand: ['0', '1'], win: '2', melds: [['s27', '27', '27']] }, SPRITE);

        expect(html.match(/class="tiles/g)).toHaveLength(3);
        expect(html).toContain('outline-gilt');
        expect(html.indexOf('#f-2"')).toBeLessThan(html.indexOf('#f-27"'));
    });

    it('ne dessine rien sans tuiles (anciennes entrées)', () => {
        expect(handHtml(null, SPRITE)).toBe('');
        expect(handHtml({}, SPRITE)).toBe('');
    });
});

describe('seedCaption', () => {
    const labels = {
        themes: { libre: 'Main libre', chinitsu: 'Chinitsu' },
        levels: { debutant: 'Débutant', normal: 'Normal', expert: 'Expert' },
        free: 'Main libre',
        daily: 'Défi du :day',
    };

    it('nomme le défi par sa date', () => {
        expect(seedCaption('defi-2026-11-12', labels, 'fr')).toBe('Défi du 12 novembre');
    });

    it('donne le niveau et le thème de la graine', () => {
        expect(seedCaption('chinitsu-ab12cd34', labels)).toBe('Chinitsu');
        expect(seedCaption('expert-chinitsu-ab12cd34', labels)).toBe('Expert · Chinitsu');
        expect(seedCaption('debutant-ab12cd34', labels)).toBe('Débutant · Main libre');
    });

    it('tient une graine libre ou choisie à la main pour une main libre', () => {
        expect(seedCaption('ab12cd34', labels)).toBe('Main libre');
        expect(seedCaption('tenpai', labels)).toBe('Main libre');
        expect(seedCaption('kokushi-ab12cd34', labels)).toBe('Main libre');
    });
});

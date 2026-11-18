import { RECIPES, shouldHover, soundForResult } from '../../resources/js/sound';

describe('soundForResult', () => {
    it('fait entendre la réponse affichée avant tout le reste', () => {
        expect(soundForResult({ ok: false, revealed: true })).toBe('reveal');
        expect(soundForResult({ ok: true, revealed: true, perfect: true })).toBe('reveal');
    });

    it('distingue une main sans faute d’une étape juste', () => {
        expect(soundForResult({ ok: true, revealed: false, perfect: true })).toBe('perfect');
        expect(soundForResult({ ok: true, revealed: false, perfect: false })).toBe('ok');
    });

    it('signale une réponse à revoir, même sans détail', () => {
        expect(soundForResult({ ok: false, revealed: false })).toBe('ko');
        expect(soundForResult()).toBe('ko');
    });
});

describe('shouldHover', () => {
    const tile = {};
    const other = {};

    it('ne sonne pas deux fois sur la même tuile', () => {
        expect(shouldHover({ tile, time: 0 }, tile, 1000)).toBe(false);
    });

    it('attend un instant entre deux tuiles survolées', () => {
        expect(shouldHover({ tile, time: 1000 }, other, 1020)).toBe(false);
        expect(shouldHover({ tile, time: 1000 }, other, 1050)).toBe(true);
    });

    it('sonne au premier survol', () => {
        expect(shouldHover({ tile: null, time: 0 }, tile, 10)).toBe(false);
        expect(shouldHover({ tile: null, time: 0 }, tile, 100)).toBe(true);
    });
});

describe('RECIPES', () => {
    it('a une recette pour chaque son joué par l’interface', () => {
        ['tap', 'hover', 'tile', 'deal', 'ok', 'ko', 'reveal', 'perfect'].forEach((name) => {
            expect(RECIPES[name]?.length).toBeGreaterThan(0);
        });
    });

    it('garde des sons brefs et doux', () => {
        Object.entries(RECIPES).forEach(([name, notes]) => {
            const end = Math.max(...notes.map((note) => (note.at ?? 0) + note.length));
            expect(end, name).toBeLessThanOrEqual(1);
            notes.forEach((note) => {
                expect(note.gain, name).toBeGreaterThan(0);
                expect(note.gain, name).toBeLessThanOrEqual(0.5);
                expect(['sine', 'triangle', 'noise'], name).toContain(note.wave);
                if (note.wave !== 'noise') expect(note.freq, name).toBeGreaterThan(0);
            });
        });
    });
});

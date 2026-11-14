import { applyChoice, choiceCookie, choiceFor, readChoice } from '../../resources/js/backdrop';

describe('choiceFor', () => {
    it('ne garde rien quand le fond voulu est celui du système', () => {
        expect(choiceFor(true, true)).toBeNull();
        expect(choiceFor(false, false)).toBeNull();
    });

    it('garde le fond qui diffère du système', () => {
        expect(choiceFor(true, false)).toBe('dark');
        expect(choiceFor(false, true)).toBe('light');
    });
});

describe('readChoice', () => {
    it('lit le fond parmi les autres cookies', () => {
        expect(readChoice('regles=abc; fond=dark; autre=1')).toBe('dark');
        expect(readChoice('fond=light')).toBe('light');
    });

    it('ignore un fond absent ou inconnu', () => {
        expect(readChoice('')).toBeNull();
        expect(readChoice('fond=sepia')).toBeNull();
        expect(readChoice('arrierefond=dark')).toBeNull();
    });
});

describe('choiceCookie', () => {
    it('garde le choix un an, ou l’efface', () => {
        expect(choiceCookie('dark')).toBe('fond=dark; max-age=31536000; path=/; samesite=lax');
        expect(choiceCookie(null, true)).toBe('fond=; max-age=0; path=/; samesite=lax; secure');
    });
});

describe('applyChoice', () => {
    it('pose ou retire data-theme', () => {
        const root = { dataset: {} };

        applyChoice('light', root);
        expect(root.dataset.theme).toBe('light');

        applyChoice(null, root);
        expect(root.dataset).not.toHaveProperty('theme');
    });
});

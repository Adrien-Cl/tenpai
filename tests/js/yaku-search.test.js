import { compact, norm, notYakuHint, prepare, suggest } from '../../resources/js/yaku-search';
import entries from './fixtures/yaku-entries.json';

const prepared = prepare(entries);
const ids = (text, exclude = []) => suggest(prepared, text, exclude).map((entry) => entry.id);

describe('norm', () => {
    it('retire les accents, la casse et la ponctuation', () => {
        expect(norm('  Rinshan Kaihō !')).toBe('rinshan kaiho');
        expect(norm('Yakuhai : vent du siège')).toBe('yakuhai vent du siege');
        expect(compact(norm('San ankou'))).toBe('sanankou');
    });
});

describe('prepare', () => {
    it('garde les accents des textes affichés', () => {
        const riichi = prepared.find((entry) => entry.id === 'riichi');

        expect(riichi.gloss).toBe('déclaration');
        expect(riichi.glossKey).toBe('declaration');
    });
});

describe('suggest', () => {
    it('ne propose rien pour une saisie vide', () => {
        expect(ids('')).toEqual([]);
        expect(ids('  - ')).toEqual([]);
    });

    it('met en tête le yaku dont le nom commence par la saisie', () => {
        expect(ids('ri')[0]).toBe('riichi');
        expect(ids('tan')[0]).toBe('tanyao');
        expect(ids('chii')[0]).toBe('chiitoi');
    });

    it('trouve un yaku par ses autres noms', () => {
        expect(ids('kuitan')).toContain('tanyao');
        expect(ids('seven pairs')[0]).toBe('chiitoi');
        expect(ids('ikkitsuukan')[0]).toBe('ittsu');
        expect(ids('full flush')[0]).toBe('chinitsu');
    });

    it('trouve un yaku par sa traduction', () => {
        expect(ids('tout simples')[0]).toBe('tanyao');
        expect(ids('sept paires')).toContain('chiitoi');
        expect(ids('dragon rouge')[0]).toBe('chun');
    });

    it('ignore les espaces dans les noms composés', () => {
        expect(ids('sanankou')[0]).toBe('sanankou');
        expect(ids('san ankou')[0]).toBe('sanankou');
    });

    it('tolère une faute de frappe dès quatre lettres', () => {
        expect(ids('richi')).toContain('riichi');
        expect(ids('tamyao')).toContain('tanyao');
        expect(ids('ric')).not.toContain('riichi');
        // Deux lettres inversées font deux fautes : trop loin.
        expect(ids('tanayo')).not.toContain('tanyao');
    });

    it('propose les cinq yakuhai pour « yakuhai »', () => {
        expect(ids('yakuhai')).toEqual(expect.arrayContaining(['haku', 'hatsu', 'chun', 'jikaze', 'bakaze']));
    });

    it('écarte les yaku déjà annoncés', () => {
        expect(ids('ri', ['riichi'])).not.toContain('riichi');
    });

    it('trouve aussi les yakuman', () => {
        expect(ids('kokushi')[0]).toBe('kokushi');
        expect(ids('nine gates')[0]).toBe('chuuren');
    });

    it('ne propose rien pour un mot sans rapport', () => {
        expect(ids('zzzz')).toEqual([]);
    });
});

describe('notYakuHint', () => {
    it('rappelle que les dora ne sont pas des yaku', () => {
        expect(notYakuHint('dora')).toContain('étape 3');
        expect(notYakuHint('Aka')).toContain('étape 3');
        expect(notYakuHint('ura-dora')).toContain('étape 3');
    });

    it('signale les limites et nagashi mangan', () => {
        expect(notYakuHint('mangan')).toContain('étapes suivantes');
        expect(notYakuHint('nagashi')).toContain('défausses');
    });

    it('ne dit rien pour un vrai yaku', () => {
        expect(notYakuHint('riichi')).toBeNull();
        expect(notYakuHint('')).toBeNull();
    });
});

import { prepare, suggest, notYakuHint } from './yaku-search';
import { pageLocale, t } from './text';

const STEP_KEYS = ['yaku', 'han', 'dora', 'fu', 'points'];
const emptyProgress = () => ({ hands: 0, perfect: 0, streak: 0, best: 0, steps: {}, seen: [], missedYaku: {} });
const REVIEW_SIZE = 20;

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    /**
     * Progrès, gardés dans ce navigateur seulement ($persist → localStorage).
     */
    Alpine.store('progress', {
        data: Alpine.$persist(emptyProgress()).as('tenpai-progres'),
        daily: Alpine.$persist({}).as('tenpai-defis'),
        drills: Alpine.$persist({}).as('tenpai-exercices'),
        review: Alpine.$persist([]).as('tenpai-a-revoir'),

        get hasPlayed() {
            return this.data.hands > 0 || Object.keys(this.drills).length > 0;
        },

        /**
         * Les mains à revoir, la plus récente d'abord.
         */
        get toReview() {
            return [...this.review].reverse();
        },

        /**
         * Les yaku le plus souvent oubliés : [id, nombre], du plus oublié au moins oublié.
         */
        mostMissed(limit = 5) {
            return Object.entries(this.data.missedYaku ?? {})
                .sort((a, b) => b[1] - a[1])
                .slice(0, limit);
        },

        drillResult(drill) {
            return this.drills[drill] ?? null;
        },

        rate(key) {
            const [ok, total] = this.data.steps[key] ?? [0, 0];
            return total ? Math.round((100 * ok) / total) : null;
        },

        dailyResult(date) {
            return this.daily[date] ?? null;
        },

        record({ seed, results, daily, drill = null, first = 0, missed = [] }) {
            const data = this.data;
            const key = drill ? `${drill}:${seed}` : seed;

            // Une main à revoir, refaite sans faute, quitte la liste (même déjà comptée).
            if (!drill && STEP_KEYS.every((_, i) => results[i])) {
                this.review = this.review.filter((entry) => entry.seed !== seed);
            }

            if (data.seen.includes(key)) return;

            data.seen = [...data.seen, key].slice(-60);

            // Exercice court : compté à part, sans toucher aux statistiques des mains complètes.
            if (drill) {
                const played = STEP_KEYS.slice(first).map((_, i) => !!results[first + i]);
                const previous = this.drills[drill] ?? { hands: 0, perfect: 0 };
                this.drills = {
                    ...this.drills,
                    [drill]: { hands: previous.hands + 1, perfect: previous.perfect + (played.every(Boolean) ? 1 : 0) },
                };
                return;
            }

            data.hands++;

            const flags = STEP_KEYS.map((_, i) => !!results[i]);
            const perfect = flags.every(Boolean);

            if (perfect) {
                data.perfect++;
                data.streak++;
                data.best = Math.max(data.best, data.streak);
            } else {
                data.streak = 0;
                const entry = { seed, score: flags.filter(Boolean).length };
                this.review = [...this.review.filter((e) => e.seed !== seed), entry].slice(-REVIEW_SIZE);
            }

            if (missed.length) {
                const counts = { ...(data.missedYaku ?? {}) };
                missed.forEach((id) => (counts[id] = (counts[id] ?? 0) + 1));
                data.missedYaku = counts;
            }

            STEP_KEYS.forEach((key, i) => {
                const [ok, total] = data.steps[key] ?? [0, 0];
                data.steps = { ...data.steps, [key]: [ok + (flags[i] ? 1 : 0), total + 1] };
            });

            if (daily && !this.daily[daily]) {
                this.daily = { ...this.daily, [daily]: { score: flags.filter(Boolean).length, perfect, steps: flags } };
            }
        },

        /**
         * Le résultat d'un défi, à coller dans une conversation. Les anciens résultats n'ont pas le détail des étapes.
         */
        shareText(date) {
            const result = this.daily[date];
            if (!result) return null;

            const day = new Date(`${date}T12:00:00`).toLocaleDateString(pageLocale(), { day: 'numeric', month: 'long' });
            const row = Array.isArray(result.steps) ? `${result.steps.map((ok) => (ok ? '🟩' : '🟥')).join('')} ` : '';

            return `${t('Tenpai · défi du :day', { day })}\n${row}${result.score}/5${result.perfect ? t(', sans faute') : ''}`;
        },

        /**
         * Partage (mobile) ou copie (ordinateur) le résultat du défi. Renvoie vrai si le texte est parti.
         */
        async shareDaily(date, url) {
            const text = this.shareText(date);
            if (!text) return false;

            if (navigator.share && matchMedia('(pointer: coarse)').matches) {
                try {
                    await navigator.share({ title: 'Tenpai', text, url });
                    return true;
                } catch (e) {
                    return false;
                }
            }

            try {
                await navigator.clipboard.writeText(`${text}\n${url}`);
                return true;
            } catch (e) {
                return false;
            }
        },

        reset() {
            this.data = emptyProgress();
            this.drills = {};
            this.review = [];
        },
    });

    /**
     * Calendrier des défis : les cinq dernières semaines, du lundi au dimanche, et la série en cours.
     * Les dates sont calculées en UTC à partir du jour donné par le serveur (heure de Paris).
     */
    Alpine.data('dailyCalendar', ({ today, url }) => {
        const shift = (date, days) => {
            const d = new Date(`${date}T00:00:00Z`);
            d.setUTCDate(d.getUTCDate() + days);
            return d.toISOString().slice(0, 10);
        };
        const weekday = (new Date(`${today}T00:00:00Z`).getUTCDay() + 6) % 7;
        const start = shift(today, -weekday - 28);

        return {
            weekdays: t('L M M J V S D').split(' '),

            get days() {
                const daily = Alpine.store('progress').daily;
                return Array.from({ length: 35 }, (_, i) => {
                    const date = shift(start, i);
                    const result = daily[date] ?? null;
                    return {
                        date,
                        day: Number(date.slice(8)),
                        future: date > today,
                        today: date === today,
                        result,
                        url: url.replace('DATE', date),
                        label: new Date(`${date}T12:00:00Z`).toLocaleDateString(pageLocale(), { weekday: 'long', day: 'numeric', month: 'long', timeZone: 'UTC' })
                            + (result ? ` : ${result.score}/5${result.perfect ? t(', sans faute') : ''}` : t(' : pas joué')),
                    };
                });
            },

            // Jours d'affilée jusqu'à aujourd'hui ; un défi pas encore fait aujourd'hui ne casse pas la série.
            get streak() {
                const daily = Alpine.store('progress').daily;
                let date = daily[today] ? today : shift(today, -1);
                let count = 0;
                while (daily[date]) {
                    count++;
                    date = shift(date, -1);
                }
                return count;
            },
        };
    });

    /**
     * Barème : trois onglets (Han, Fu, Points). Sur la page, l'onglet suit l'adresse (#fu…).
     */
    Alpine.data('scoringSheet', ({ tab, useHash }) => ({
        tab,
        tabs: ['han', 'fu', 'points'],

        init() {
            if (useHash) {
                const fromHash = location.hash.slice(1);
                if (this.tabs.includes(fromHash)) this.tab = fromHash;
                this.$watch('tab', (value) => history.replaceState(null, '', `#${value}`));
            }
        },

        next(delta) {
            const index = (this.tabs.indexOf(this.tab) + delta + this.tabs.length) % this.tabs.length;
            this.tab = this.tabs[index];
            this.$nextTick(() => document.getElementById(`tab-${this.tab}`)?.focus());
        },
    }));

    /**
     * Calculateur de points : les réponses viennent du moteur (JSON préparé côté serveur).
     */
    Alpine.data('pointsCalculator', ({ source }) => ({
        table: {},
        dealer: false,
        tsumo: false,
        han: 3,
        fu: 30,

        init() {
            try {
                this.table = JSON.parse(document.getElementById(source)?.textContent ?? '{}');
            } catch (e) {
                this.table = {};
            }
        },

        get result() {
            const fu = this.han >= 5 ? 0 : this.fu;
            return this.table[`${this.dealer ? 1 : 0}${this.tsumo ? 1 : 0}-${this.han}-${fu}`] ?? { possible: false };
        },

        get caption() {
            if (!this.tsumo) return this.dealer ? t('Ron du donneur') : t('Ron d’un non-donneur');
            return this.dealer ? t('Tsumo du donneur : chaque adversaire paie') : t('Tsumo : chaque non-donneur / le donneur');
        },

        get impossible() {
            if (this.fu === 20) return this.tsumo ? t('20 fu en tsumo, c’est un pinfu : 2 han au moins (pinfu et tsumo).') : t('20 fu n’existe pas en ron : un pinfu en ron vaut 30 fu.');
            return this.tsumo ? t('25 fu, c’est un chiitoitsu : en tsumo, 3 han au moins.') : t('25 fu, c’est un chiitoitsu : 2 han au moins.');
        },
    }));

    /**
     * Barème des yaku : recherche (mêmes noms et alias que l'étape 1).
     */
    Alpine.data('yakuSheet', ({ entries }) => ({
        entries: prepare(entries),
        query: '',
        open: false,

        get matches() {
            return this.query.trim() ? suggest(this.entries, this.query).map((entry) => entry.id) : null;
        },

        get hint() {
            if (!this.query.trim()) return '';
            const note = notYakuHint(this.query);
            if (note) return note;
            const count = this.matches.length;
            if (!count) return t('Aucun yaku ne correspond à « :query ».', { query: this.query.trim() });
            return count > 1 ? t(':count yaku trouvés.', { count }) : t(':count yaku trouvé.', { count });
        },

        visible(id) {
            return this.matches === null || this.matches.includes(id);
        },

        anyVisible(ids) {
            return ids.some((id) => this.visible(id));
        },
    }));

    /**
     * Annonce des yaku : saisie avec suggestions, ou liste à parcourir (pratique au doigt).
     */
    Alpine.data('yakuPicker', ({ entries, locked }) => ({
        entries: prepare(entries),
        picked: [],
        query: '',
        options: [],
        active: -1,
        browsing: false,
        hint: '',
        locked,

        init() {
            this.$watch('query', (value) => {
                // La saisie vidée par add() ne doit pas effacer l'annonce « Riichi ajouté. ».
                if (!value && this.confirming) {
                    this.confirming = false;
                    return;
                }
                this.refresh();
            });
        },

        confirming: false,

        entry(id) {
            return this.entries.find((entry) => entry.id === id);
        },

        get groups() {
            const groups = {};
            this.entries
                .filter((entry) => !entry.yakuman)
                .forEach((entry) => {
                    (groups[entry.han] ??= []).push(entry);
                });
            return Object.entries(groups).sort((a, b) => a[0] - b[0]);
        },

        refresh(all = false) {
            this.options = all ? this.entries.filter((e) => !this.picked.includes(e.id)) : suggest(this.entries, this.query, this.picked);
            this.active = this.options.length ? 0 : -1;
            const note = notYakuHint(this.query);
            const already = this.query.trim() && !this.options.length && suggest(this.entries, this.query).length;
            this.hint = note
                ? note
                : already
                  ? t('Ce yaku est déjà dans votre liste.')
                  : this.query.trim() && !this.options.length
                    ? t('Aucun yaku ne correspond à « :query ».', { query: this.query.trim() })
                    : '';
        },

        toggle(id) {
            if (this.locked) return;
            if (this.picked.includes(id)) this.remove(id);
            else this.add(id, false);
        },

        add(id, refocus = true) {
            if (this.locked || this.picked.includes(id)) return;
            this.picked = [...this.picked, id];
            this.confirming = this.query !== '';
            this.query = '';
            this.options = [];
            this.active = -1;
            this.hint = t(':yaku ajouté.', { yaku: this.entry(id).label });
            if (refocus) this.$nextTick(() => this.$refs.input?.focus());
        },

        remove(id) {
            if (this.locked) return;
            this.picked = this.picked.filter((x) => x !== id);
            this.hint = t(':yaku retiré.', { yaku: this.entry(id).label });
        },

        close() {
            this.options = [];
            this.active = -1;
        },

        move(delta) {
            if (!this.options.length) {
                this.refresh(!this.query.trim());
                return;
            }
            this.active = (this.active + delta + this.options.length) % this.options.length;
            this.$nextTick(() => document.getElementById(`yopt-${this.options[this.active]?.id}`)?.scrollIntoView({ block: 'nearest' }));
        },

        // Entrée : ajoute la suggestion active ; sur un champ vide, laisse le formulaire se valider.
        enter(event) {
            if (event.isComposing) return;
            if (this.options.length && this.active >= 0) {
                event.preventDefault();
                this.add(this.options[this.active].id);
            } else if (this.query.trim()) {
                event.preventDefault();
            }
        },

        backspace() {
            if (!this.query && this.picked.length) this.remove(this.picked[this.picked.length - 1]);
        },
    }));
});

// Service worker : l'application s'installe, et le barème reste lisible hors ligne.
if ('serviceWorker' in navigator && window.isSecureContext) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
}

// Raccourcis clavier : B ouvre le barème, N passe à la main suivante (quand la main est finie).
document.addEventListener('keydown', (event) => {
    if (event.ctrlKey || event.metaKey || event.altKey) return;
    if (/^(INPUT|SELECT|TEXTAREA)$/.test(document.activeElement?.tagName ?? '')) return;
    if (document.querySelector('dialog[open]') && event.key.toLowerCase() !== 'b') return;

    const key = event.key.toLowerCase();
    if (key === 'b') document.querySelector('[data-shortcut="b"]')?.click();
    if (key === 'n') document.querySelector('[data-shortcut="n"]')?.click();
});

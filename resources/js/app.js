import { prepare, suggest, notYakuHint } from './yaku-search';

const STEP_KEYS = ['yaku', 'han', 'dora', 'fu', 'points'];
const emptyProgress = () => ({ hands: 0, perfect: 0, streak: 0, best: 0, steps: {}, seen: [] });

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    /**
     * Progrès, gardés dans ce navigateur seulement ($persist → localStorage).
     */
    Alpine.store('progress', {
        data: Alpine.$persist(emptyProgress()).as('tenpai-progres'),
        daily: Alpine.$persist({}).as('tenpai-defis'),

        get hasPlayed() {
            return this.data.hands > 0;
        },

        rate(key) {
            const [ok, total] = this.data.steps[key] ?? [0, 0];
            return total ? Math.round((100 * ok) / total) : null;
        },

        dailyResult(date) {
            return this.daily[date] ?? null;
        },

        record({ seed, results, daily }) {
            const data = this.data;
            if (data.seen.includes(seed)) return;

            data.seen = [...data.seen, seed].slice(-60);
            data.hands++;

            const flags = STEP_KEYS.map((_, i) => !!results[i]);
            const perfect = flags.every(Boolean);

            if (perfect) {
                data.perfect++;
                data.streak++;
                data.best = Math.max(data.best, data.streak);
            } else {
                data.streak = 0;
            }

            STEP_KEYS.forEach((key, i) => {
                const [ok, total] = data.steps[key] ?? [0, 0];
                data.steps = { ...data.steps, [key]: [ok + (flags[i] ? 1 : 0), total + 1] };
            });

            if (daily && !this.daily[daily]) {
                this.daily = { ...this.daily, [daily]: { score: flags.filter(Boolean).length, perfect } };
            }
        },

        reset() {
            this.data = emptyProgress();
        },
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
            if (!this.tsumo) return this.dealer ? 'Ron du donneur' : 'Ron d’un non-donneur';
            return this.dealer ? 'Tsumo du donneur : chaque adversaire paie' : 'Tsumo : chaque non-donneur / le donneur';
        },

        get impossible() {
            if (this.fu === 20) return this.tsumo ? '20 fu en tsumo, c’est un pinfu : 2 han au moins (pinfu et tsumo).' : '20 fu n’existe pas en ron : un pinfu en ron vaut 30 fu.';
            return this.tsumo ? '25 fu, c’est un chiitoitsu : en tsumo, 3 han au moins.' : '25 fu, c’est un chiitoitsu : 2 han au moins.';
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
            return count ? `${count} yaku trouvé${count > 1 ? 's' : ''}.` : `Aucun yaku ne correspond à « ${this.query.trim()} ».`;
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
            this.$watch('query', () => this.refresh());
        },

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
                  ? 'Ce yaku est déjà dans votre liste.'
                  : this.query.trim() && !this.options.length
                    ? `Aucun yaku ne correspond à « ${this.query.trim()} ».`
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
            this.query = '';
            this.options = [];
            this.active = -1;
            this.hint = `${this.entry(id).label} ajouté.`;
            if (refocus) this.$nextTick(() => this.$refs.input?.focus());
        },

        remove(id) {
            if (this.locked) return;
            this.picked = this.picked.filter((x) => x !== id);
            this.hint = `${this.entry(id).label} retiré.`;
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

// Raccourcis clavier : B ouvre le barème, N passe à la main suivante (quand la main est finie).
document.addEventListener('keydown', (event) => {
    if (event.ctrlKey || event.metaKey || event.altKey) return;
    if (/^(INPUT|SELECT|TEXTAREA)$/.test(document.activeElement?.tagName ?? '')) return;
    if (document.querySelector('dialog[open]') && event.key.toLowerCase() !== 'b') return;

    const key = event.key.toLowerCase();
    if (key === 'b') document.querySelector('[data-shortcut="b"]')?.click();
    if (key === 'n') document.querySelector('[data-shortcut="n"]')?.click();
});

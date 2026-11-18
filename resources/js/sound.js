/**
 * Sons de l'interface, synthétisés dans le navigateur (Web Audio) : aucun fichier à charger, rien à garder hors ligne.
 *
 * Une recette est une liste de notes : { wave, freq, to, at, length, gain, attack, filter }.
 * wave : 'sine', 'triangle' ou 'noise' · freq → to : glissement de hauteur (Hz) · at, length, attack : secondes
 * filter : { type, freq, q } (le bruit d'une tuile passe par un filtre passe-bande).
 */

const clack = (at, freq) => [
    { wave: 'noise', at, length: 0.045, gain: 0.32, filter: { type: 'bandpass', freq, q: 1.4 } },
    { wave: 'sine', freq: freq * 0.36, to: freq * 0.27, at, length: 0.06, gain: 0.1 },
];

// Un coup sur du bois : une note ronde qui s'éteint vite, avec un léger claquement à l'attaque.
const knock = (at, freq) => [
    { wave: 'sine', freq, to: freq * 0.82, at, length: 0.08, gain: 0.13, attack: 0.002 },
    { wave: 'triangle', freq: freq * 2.7, at, length: 0.02, gain: 0.025, attack: 0.001 },
];

export const RECIPES = {
    // Clic de bouton : un tic sec.
    tap: [{ wave: 'triangle', freq: 1400, to: 900, length: 0.03, gain: 0.07 }],
    // Survol d'une tuile : à peine audible.
    hover: [{ wave: 'sine', freq: 2300, to: 2100, length: 0.018, gain: 0.022 }],
    // Une tuile posée sur la table.
    tile: clack(0, 2600),
    // Une main tirée : les tuiles glissent sur le feutre, puis trois petits coups de bois qui montent.
    deal: [
        { wave: 'noise', length: 0.24, gain: 0.1, attack: 0.09, filter: { type: 'lowpass', freq: 900, q: 0.7 } },
        ...knock(0.13, 523),
        ...knock(0.2, 659),
        ...knock(0.27, 784),
    ],
    // Juste : deux notes qui montent (sol, do).
    ok: [
        { wave: 'sine', freq: 784, length: 0.22, gain: 0.16, attack: 0.008 },
        { wave: 'sine', freq: 1046.5, at: 0.09, length: 0.34, gain: 0.16, attack: 0.008 },
        { wave: 'triangle', freq: 2093, at: 0.09, length: 0.18, gain: 0.025 },
    ],
    // À revoir : une note grave qui retombe, sans dureté.
    ko: [
        { wave: 'triangle', freq: 233, to: 175, length: 0.28, gain: 0.15, attack: 0.012 },
        { wave: 'sine', freq: 117, to: 88, length: 0.28, gain: 0.08, attack: 0.012 },
    ],
    // Réponse affichée : une note neutre.
    reveal: [{ wave: 'sine', freq: 587, length: 0.26, gain: 0.11, attack: 0.01 }],
    // Main sans faute : un arpège pentatonique.
    perfect: [
        { wave: 'sine', freq: 784, length: 0.3, gain: 0.14, attack: 0.008 },
        { wave: 'sine', freq: 880, at: 0.08, length: 0.3, gain: 0.14, attack: 0.008 },
        { wave: 'sine', freq: 1046.5, at: 0.16, length: 0.34, gain: 0.14, attack: 0.008 },
        { wave: 'sine', freq: 1318.5, at: 0.24, length: 0.55, gain: 0.15, attack: 0.008 },
        { wave: 'triangle', freq: 2637, at: 0.24, length: 0.3, gain: 0.02 },
    ],
};

/**
 * Le son d'une correction : réponse affichée, main sans faute, juste ou à revoir.
 */
export function soundForResult({ ok = false, revealed = false, perfect = false } = {}) {
    if (revealed) return 'reveal';
    if (ok) return perfect ? 'perfect' : 'ok';
    return 'ko';
}

/**
 * Un survol mérite un son s'il arrive sur une autre tuile, et pas trop vite après le précédent.
 */
export function shouldHover(last, tile, now, gap = 45) {
    return tile !== last.tile && now - last.time >= gap;
}

/**
 * Le lecteur. Le contexte audio n'est créé qu'au premier geste (unlock) : avant, les navigateurs le refusent,
 * et une page chargée à froid reste muette.
 */
export function createPlayer(AudioContextClass = globalThis.AudioContext ?? globalThis.webkitAudioContext) {
    let context = null;
    let master = null;
    let noise = null;

    const noiseBuffer = () => {
        if (!noise) {
            noise = context.createBuffer(1, Math.ceil(context.sampleRate * 0.2), context.sampleRate);
            const data = noise.getChannelData(0);
            for (let i = 0; i < data.length; i++) data[i] = Math.random() * 2 - 1;
        }
        return noise;
    };

    const note = (start, { wave, freq, to, at = 0, length, gain, attack = 0.003, filter }, detune) => {
        const t = start + at;
        const end = t + length;
        const envelope = context.createGain();
        envelope.gain.setValueAtTime(0.0001, t);
        envelope.gain.linearRampToValueAtTime(gain, t + attack);
        envelope.gain.exponentialRampToValueAtTime(0.0001, end);

        let source;
        if (wave === 'noise') {
            source = context.createBufferSource();
            source.buffer = noiseBuffer();
        } else {
            source = context.createOscillator();
            source.type = wave;
            source.frequency.setValueAtTime(freq * detune, t);
            if (to) source.frequency.exponentialRampToValueAtTime(to * detune, end);
        }

        let output = source;
        if (filter) {
            const node = context.createBiquadFilter();
            node.type = filter.type;
            node.frequency.value = filter.freq * detune;
            node.Q.value = filter.q ?? 1;
            source.connect(node);
            output = node;
        }

        output.connect(envelope).connect(master);
        source.start(t);
        source.stop(end + 0.02);
    };

    return {
        unlock() {
            if (!AudioContextClass) return;
            if (!context) {
                context = new AudioContextClass();
                master = context.createGain();
                master.gain.value = 0.7;
                master.connect(context.destination);
            }
            if (context.state === 'suspended') context.resume().catch(() => {});
        },

        play(name) {
            const recipe = RECIPES[name];
            if (!recipe || !context || context.state === 'closed' || document.hidden) return;

            // Les tuiles ne sonnent jamais tout à fait pareil.
            const detune = name === 'tile' || name === 'deal' || name === 'hover' ? 0.96 + Math.random() * 0.08 : 1;
            const start = context.currentTime + 0.005;
            recipe.forEach((part) => note(start, part, detune));
        },
    };
}

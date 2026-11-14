// Les textes de l'interface écrits en JavaScript.
//
// Le français est la langue source : chaque phrase sert de clé. Le layout injecte les traductions de la page
// (window.tenpaiText, depuis App\Support\ScriptText) ; sans elles, la phrase française est rendue telle quelle.
// Les valeurs s'écrivent « :nom », comme dans les fichiers de langue de Laravel.

export function t(text, replace = {}) {
    let line = globalThis.tenpaiText?.[text] ?? text;

    // Les noms les plus longs d'abord : « :fu » ne doit pas mordre sur « :full ».
    for (const [key, value] of Object.entries(replace).sort((a, b) => b[0].length - a[0].length)) {
        line = line.replaceAll(`:${key}`, String(value));
    }

    return line;
}

// La langue de la page, pour les dates (« fr », « en »).
export function pageLocale() {
    return globalThis.document?.documentElement.lang || 'fr';
}

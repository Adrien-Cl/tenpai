// Le fond de la page : clair (le jour) ou foncé (la nuit).
//
// Sans choix, la page suit le système. Le choix est gardé dans le cookie « fond », lu par le serveur qui le rend
// en data-theme sur <html> : chaque page arrive déjà dans le bon fond. Revenir au fond du système efface le choix.

export const BACKDROP_COOKIE = 'fond';

const ONE_YEAR = 60 * 60 * 24 * 365;

// Le choix à garder pour obtenir ce fond : rien s'il est déjà celui du système.
export function choiceFor(dark, systemDark) {
    if (dark === systemDark) return null;

    return dark ? 'dark' : 'light';
}

// Le choix lu dans les cookies de la page, ou null.
export function readChoice(cookies) {
    const match = cookies.match(new RegExp(`(?:^|;\\s*)${BACKDROP_COOKIE}=(light|dark)(?:;|$)`));

    return match ? match[1] : null;
}

// Le cookie qui garde le choix, ou qui l'efface.
export function choiceCookie(choice, secure = false) {
    const attributes = `; path=/; samesite=lax${secure ? '; secure' : ''}`;

    return choice ? `${BACKDROP_COOKIE}=${choice}; max-age=${ONE_YEAR}${attributes}` : `${BACKDROP_COOKIE}=; max-age=0${attributes}`;
}

// Pose le choix sur <html>, comme le fait le serveur.
export function applyChoice(choice, root = document.documentElement) {
    if (choice) {
        root.dataset.theme = choice;
    } else {
        delete root.dataset.theme;
    }
}

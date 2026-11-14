// Service worker de Tenpai.
//
// L'entraînement a besoin du serveur (la main et les corrections y sont calculées) : il ne marche pas hors ligne.
// Ce qui marche hors ligne : l'accueil et le barème déjà visités, et une page qui explique la coupure.
//
// - Pages : réseau d'abord ; sans réseau, la copie gardée (accueil, barème) ou la page hors ligne.
// - Fichiers compilés (/build), script Livewire, tuiles et icônes : cache d'abord, leur adresse change à chaque version.
// - Requêtes Livewire (POST) : jamais touchées.

const VERSION = 'tenpai-v2';
const PAGES = `${VERSION}-pages`;
const ASSETS = `${VERSION}-assets`;
const OFFLINE = '/hors-ligne';
const OFFLINE_EN = '/en/offline';
const KEPT_PAGES = ['/', '/bareme', OFFLINE, '/en', '/en/scoring', OFFLINE_EN];

// La page hors ligne dans la langue de l'adresse demandée.
const offlinePage = (path) => (path === '/en' || path.startsWith('/en/') ? OFFLINE_EN : OFFLINE);

// Les fichiers compilés cités par une page ou une feuille de style, et le script de Livewire (qui apporte Alpine).
const ASSET_PATTERN = /\/build\/assets\/[^"'()\s]+|\/livewire[a-z0-9-]*\/livewire(\.min)?\.js\?id=[a-z0-9]+/g;
const CACHE_FIRST = /^\/(build|icons|livewire[a-z0-9-]*)\/|^\/(tuiles|favicon)\.svg$/;

async function precache() {
    const pages = await caches.open(PAGES);
    const assets = await caches.open(ASSETS);
    const found = new Set();

    for (const path of KEPT_PAGES) {
        const response = await fetch(path, { credentials: 'same-origin' });
        if (!response.ok) continue;
        await pages.put(path, response.clone());
        (await response.text()).match(ASSET_PATTERN)?.forEach((url) => found.add(url));
    }

    // Les polices sont citées par la feuille de style, pas par la page.
    for (const url of [...found].filter((url) => url.endsWith('.css'))) {
        const css = await (await fetch(url)).text();
        css.match(ASSET_PATTERN)?.forEach((asset) => found.add(asset));
    }

    await assets.addAll([...found, '/favicon.svg', '/manifest.webmanifest']);
}

self.addEventListener('install', (event) => {
    event.waitUntil(precache().then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) => Promise.all(keys.filter((key) => !key.startsWith(VERSION)).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

async function cacheFirst(request) {
    const hit = await caches.match(request);
    if (hit) return hit;

    const response = await fetch(request);
    if (response.ok) (await caches.open(ASSETS)).put(request, response.clone());
    return response;
}

async function networkFirst(request) {
    const path = new URL(request.url).pathname;

    try {
        const response = await fetch(request);
        if (response.ok && KEPT_PAGES.includes(path)) (await caches.open(PAGES)).put(path, response.clone());
        return response;
    } catch (error) {
        return (await caches.match(path)) ?? (await caches.match(offlinePage(path)));
    }
}

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== location.origin) return;

    if (request.mode === 'navigate' || url.pathname === '/manifest.webmanifest') {
        event.respondWith(networkFirst(request));
    } else if (CACHE_FIRST.test(url.pathname)) {
        event.respondWith(cacheFirst(request));
    }
});

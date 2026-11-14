// Génère les images de l'aperçu des mains partagées (Open Graph), avec les vrais styles du site.
//
//   node resources/og/generate.mjs http://localhost:8000
//
// Produit, dans resources/og :
// - tiles/{n}.png, {n}r.png (cinq rouges), side-{n}.png, side-{n}r.png (tuiles couchées), back.png : une tuile
//   sur fond transparent, avec son ombre, dans une marge de PAD pixels (voir App\View\Tiles\HandPreview) ;
// - base.png, base-defi.png, base-en.png, base-defi-en.png : le fond 1200 × 630, sans les tuiles.
// Et dans public : og.png et og-en.png, l'image générale du site (avec la main de l'accueil).
//
// À relancer après un changement du dessin des tuiles ou de la charte.

import { mkdir } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { chromium } from 'playwright';

const SITE = (process.argv[2] ?? 'http://localhost:8000').replace(/\/$/, '');
const OUT = fileURLToPath(new URL('.', import.meta.url));
const TILE = 72;
const PAD = 12;
const FIVES = [4, 13, 22];

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1400, height: 900 } });

await page.goto(`${SITE}/?saison=automne`);
await page.waitForLoadState('networkidle');

const sprite = await page.evaluate(() => document.querySelector('.tile use')?.getAttribute('href')?.split('#')[0]);
if (!sprite) throw new Error('Sprite des tuiles introuvable sur la page d’accueil.');

// La main de l'accueil, pour l'image générale du site : tuile gagnante posée, sans animation.
const heroRack = await page.evaluate(() => {
    const rack = document.querySelector('.hero-rack').cloneNode(true);
    rack.querySelectorAll('.hero-slot__ghost').forEach((node) => node.remove());
    rack.querySelectorAll('*').forEach((node) => {
        node.style.animation = 'none';
        node.style.transition = 'none';
    });
    rack.querySelectorAll('.hero-win, .hero-slot, .hero-slot *').forEach((node) => {
        node.style.transform = 'none';
        node.style.translate = 'none';
        node.style.rotate = 'none';
        node.style.opacity = '1';
    });
    const slot = rack.querySelector('.hero-slot');
    slot?.style.setProperty('margin-left', '18px');
    slot?.style.setProperty('position', 'relative');
    rack.querySelector('.hero-win')?.style.setProperty('position', 'static');
    rack.style.setProperty('--tile', '3.9rem');
    return rack.outerHTML;
});

// Une page vide, aux styles du site, sur fond transparent.
await page.evaluate(() => {
    document.body.innerHTML = '<main id="board" style="display:flex;flex-wrap:wrap;gap:8px;padding:8px"></main>';
    const style = document.createElement('style');
    style.textContent = 'html, body { background: transparent !important; } body::before, body::after { display: none !important; }';
    document.head.appendChild(style);
});

const up = (face) => `<svg viewBox="0 0 34 46"><use href="${sprite}#tile-up"/><use href="${sprite}#${face}" x="2" width="30" height="40"/></svg>`;
const side = (face) =>
    `<svg viewBox="0 0 40 40"><use href="${sprite}#tile-side"/><g transform="translate(0 32) rotate(-90)"><use href="${sprite}#${face}" width="30" height="40"/></g></svg>`;
const down = () => `<svg viewBox="0 0 34 46"><use href="${sprite}#tile-down"/></svg>`;

const tiles = [];
for (let n = 0; n < 34; n++) {
    tiles.push([`${n}`, 'tile', up(`f-${n}`)], [`side-${n}`, 'tile tile--side', side(`f-${n}`)]);
}
for (const n of FIVES) {
    tiles.push([`${n}r`, 'tile', up(`f-${n}r`)], [`side-${n}r`, 'tile tile--side', side(`f-${n}r`)]);
}
tiles.push(['back', 'tile', down()]);

await page.evaluate(
    ({ tiles, TILE, PAD }) => {
        const board = document.getElementById('board');
        for (const [name, classes, svg] of tiles) {
            board.insertAdjacentHTML(
                'beforeend',
                `<div class="tiles" data-name="${name}" style="--tile:${TILE}px;padding:${PAD}px"><span class="${classes}">${svg}</span></div>`,
            );
        }
    },
    { tiles, TILE, PAD },
);
await page.waitForTimeout(800);

await mkdir(`${OUT}tiles`, { recursive: true });
for (const [name] of tiles) {
    await page.locator(`[data-name="${name}"]`).screenshot({ path: `${OUT}tiles/${name}.png`, omitBackground: true });
}

// Les fonds : la charte en haut, le tapis en bas (vide, ou avec la main de l'accueil).
const card = (title, subtitle, rack = '') => `
  <div id="card" style="width:1200px;height:630px;overflow:hidden;background:var(--paper);display:flex;flex-direction:column;padding:52px 72px 0;box-sizing:border-box">
    <div style="display:flex;align-items:center;gap:20px">
      <img src="/favicon.svg" style="width:76px;height:76px">
      <span class="display" style="font-size:44px;color:var(--ink)">Tenpai</span>
    </div>
    <div class="display" style="margin-top:46px;font-size:64px;line-height:1.05;color:var(--ink)">${title}</div>
    <div style="margin-top:14px;font-size:28px;color:var(--ink-muted)">${subtitle}</div>
    <div class="felt" style="margin:auto -72px 0;height:212px;border-radius:0;display:flex;align-items:center;justify-content:center">${rack}</div>
  </div>`;

const images = [
    [`${OUT}base.png`, 'Saurez-vous compter cette main ?', 'Yaku, han, dora, fu et points : à vous de la compter.'],
    [`${OUT}base-defi.png`, 'Le défi du jour : combien vaut-elle ?', 'Yaku, han, dora, fu et points : à vous de la compter.'],
    [`${OUT}base-en.png`, 'Can you score this hand?', 'Yaku, han, dora, fu and points: over to you.'],
    [`${OUT}base-defi-en.png`, 'Daily challenge: what is it worth?', 'Yaku, han, dora, fu and points: over to you.'],
    [`${OUT}../../public/og.png`, 'Comptez une main de riichi, sans calculette.', 'Yaku, han, dora, fu et points : cinq étapes corrigées et expliquées.', heroRack],
    [`${OUT}../../public/og-en.png`, 'Score a riichi hand, no calculator.', 'Yaku, han, dora, fu and points: five steps, checked and explained.', heroRack],
];

for (const [path, title, subtitle, rack] of images) {
    await page.evaluate((html) => (document.body.innerHTML = html), card(title, subtitle, rack));
    await page.waitForTimeout(500);
    await page.locator('#card').screenshot({ path });
}

await browser.close();
console.log(`Images générées dans ${OUT}`);

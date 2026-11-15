import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { local } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

/*
| Les polices sont dans resources/fonts : le sous-ensemble latin (accents français et œ compris), en woff2 seulement.
| Bunny et Google ne conviennent pas ici : Bunny livre chaque graisse en woff2 puis en woff, dans deux @font-face
| distincts, et le navigateur retient le dernier (le woff, plus lourd) en laissant le woff2 préchargé inutilisé ;
| Google découpe ces polices japonaises en une centaine de tranches, toutes préchargées.
*/
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                // Titres et grands chiffres : une gothique japonaise très grasse, façon enseigne de salle.
                local('Dela Gothic One', {
                    variants: [{ src: 'resources/fonts/dela-gothic-one-400.woff2', weight: 400 }],
                }),
                // Texte courant.
                local('Zen Kaku Gothic New', {
                    variants: [
                        { src: 'resources/fonts/zen-kaku-gothic-new-400.woff2', weight: 400 },
                        { src: 'resources/fonts/zen-kaku-gothic-new-500.woff2', weight: 500 },
                        { src: 'resources/fonts/zen-kaku-gothic-new-700.woff2', weight: 700 },
                    ],
                    preload: [{ weight: 400 }, { weight: 700 }],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});

import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                // Titres et grands chiffres : une gothique japonaise très grasse, façon enseigne de salle.
                bunny('Dela Gothic One', {
                    weights: [400],
                    subsets: ['latin', 'latin-ext'],
                }),
                // Texte courant.
                bunny('Zen Kaku Gothic New', {
                    weights: [400, 500, 700],
                    subsets: ['latin', 'latin-ext'],
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

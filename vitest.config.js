import { defineConfig } from 'vitest/config';

// Tests du JavaScript de l'interface. Configuration à part : pas besoin des plugins Laravel ni des polices.
//
// describe, it et expect sont des globales : sous Windows, un terminal ouvert sur « c:\ » (et non « C:\ »)
// ferait charger le module vitest deux fois, et un import { describe } pointerait sur la mauvaise copie.
export default defineConfig({
    test: {
        include: ['tests/js/**/*.test.js'],
        environment: 'node',
        globals: true,
    },
});

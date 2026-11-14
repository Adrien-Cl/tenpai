<?php

it('affiche les pages principales sans erreur JavaScript', function () {
    visit(['/', '/bareme', '/comptage/tenpai', '/exercice/fu/tenpai', '/exercice/lectures/tenpai', '/attentes/tenpai', '/hors-ligne'])->assertNoSmoke();
});

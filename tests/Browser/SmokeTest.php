<?php

it('affiche les pages principales sans erreur JavaScript', function () {
    visit(['/', '/bareme', '/entrainement/tenpai', '/exercice/fu/tenpai', '/hors-ligne'])->assertNoSmoke();
});
